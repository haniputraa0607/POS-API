<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Outlet\Http\Controllers\OutletController;
use Modules\Product\Entities\ProductOutletStockLog;
use Modules\Product\Http\Requests\ProductRequest;
use App\Lib\MyHelper;
use Illuminate\Support\Facades\DB;
use Modules\Product\Entities\ProductCategory;
use Modules\Order\Entities\Order;

class ProductController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->product_path = "img/product/";
    }

    public function index(Request $request): JsonResponse
    {
        $product = $request->length ?  Product::paginate($request->length ?? 10) : Product::get();
        return $this->ok("success get data all users", $product);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $product = Product::with('global_price')->find($id);
        return $this->ok("success", $product);
    }

    public function store(ProductRequest $request):JsonResponse
    {
        $product = Product::create($request->all());
        $globalPrice = [
            'price' => $request->price,
        ];
        $product->global_price()->create($globalPrice);
        return $this->ok("succes", $product);
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->all());
        $globalPrice = [
            'price' => $request->price,
        ];
        $product->global_price()->update($globalPrice);
        return $this->ok("succes", $product);
    }

    public function uploadImage(Request $request):JsonResponse
    {
        $post = $request->all();

        $product = Product::where('id', $post['id_product'])->first();
        DB::beginTransaction();
        try{
            $encode = base64_encode(fread(fopen($post['image'], "r"), filesize($post['image'])));
        }catch(\Exception $e) {
            DB::rollback();
            return $this->error('Error');
        }
        $originalName = $post['image']->getClientOriginalName();
        if($originalName == ''){
            $ext = 'png';
        }else{
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        }
        $name_image = str_replace(' ', '_',strtolower($product['product_name']??''));
        $upload = MyHelper::uploadFile($encode, $this->product_path, $ext, $name_image);
        if (isset($upload['status']) && $upload['status'] == "success") {
            $upload = $product->update(['image' => $upload['path']]);
        }else {
            DB::rollback();
            return response()->json([
                'status'=>'fail',
                'messages'=>['Gagal menyimpan file']
            ]);
        }
        DB::commit();
        return $this->ok('success', $upload);

    }

    public function list(Request $request):mixed
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $products = [];
        $make_new = false;
        $check_json = file_exists(storage_path() . "/json/get_product.json");
        if($check_json){
            $config = json_decode(file_get_contents(storage_path() . "/json/get_product.json"), true);
            if(isset($config[$outlet['id']])){
                if(date('Y-m-d H:i', strtotime($config[$outlet['id']]['updated_at']. ' +6 hours')) <= date('Y-m-d H:i')){
                    $make_new = true;
                }
            }else{
                $make_new = true;
            }
        }else{
            $make_new = true;
        }

        if($make_new){
            $product = Product::with([
                'global_price',
                'outlet_price' => function($outlet_price) use ($outlet){
                    $outlet_price->where('outlet_id',$outlet['id']);
                }, 'outlet_stock' => function($outlet_stock) use ($outlet){
                    $outlet_stock->where('outlet_id',$outlet['id']);
                }
            ])->whereHas('outlet_stock', function($outlet_stock) use ($outlet){
                $outlet_stock->where('outlet_id',$outlet['id']);
            });

            if(isset($post['search']) && !empty($post['search'])){
                $product = $product->where('product_name', 'like', '%'.$post['search'].'%');
            }

            $product = $product->select('id','product_name', 'image', 'product_category_id')
            ->product()->get()->toArray();

            $config[$outlet['id']] = [
                'updated_at' => date('Y-m-d H:i'),
                'data'       => $product
            ];
            file_put_contents(storage_path('/json/get_product.json'), json_encode($config));

        }
        $config = $config[$outlet['id']] ?? [];

        $products = $config['data'] ?? [];

        $order_products = [];
        if(isset($post['id_customer'])){
            $order = Order::with([
                'order_products'
            ])->where('patient_id', $post['id_customer'])
            ->where('outlet_id', $outlet['id'])
            ->where('send_to_transaction', 0)->latest()->first();

            foreach($order['order_products'] ?? [] as $ord_pro){
                $order_products[$ord_pro['product_id']] = $ord_pro['qty'];
            }
        }elseif(isset($post['id_order'])){
            $order = Order::with([
                'order_products'
            ])->where('id', $post['id_order'])
            ->where('outlet_id', $outlet['id'])
            ->where('send_to_transaction', 0)->latest()->first();

            foreach($order['order_products'] ?? [] as $ord_pro){
                $order_products[$ord_pro['product_id']] = $ord_pro['qty'];
            }
        }

        $data_pro = [];
        foreach($products ?? [] as $value){

            if(isset($value['outlet_price'][0]['price']) ?? false){
                $price = $value['outlet_price'][0]['price'] ?? null;
            }else{
                $price = $value['global_price']['price'] ?? null;
            }

            $stock = ($value['outlet_stock'][0]['stock'] ?? 0) + ($order_products[$value['id']] ?? 0);
            if($stock > 0){
                $image_url = json_decode($value['image'] , true) ?? [];
                $data_pro[] = [
                    'id'           => $value['id'],
                    'product_name' => $value['product_name'],
                    // 'image_url'    => isset($value['image']) ? env('STORAGE_URL_API').$value['image'] : env('STORAGE_URL_DEFAULT_IMAGE').'default_image/default_product.png',
                    'image_url'    => $image_url[0] ?? env('STORAGE_URL_DEFAULT_IMAGE').'default_image/default_product.png',
                    'price'        => $price,
                    'stock'        => $stock,
                    'id_category'  => $value['product_category_id']
                ];
            }
        }
        $products = $data_pro;

        return $this->ok('success', $products);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $global_price = ProductGlobalPrice::where(['product_id' => $id])->delete();
        $product = Product::where(['id' => $id])->delete();
        return $this->ok("succes", $product);
    }

    public function addLogProductStockLog($id, $qty, $stock_before, $stock_after, $source, $desc){

        $stock_log = ProductOutletStockLog::create([
            'product_outlet_stock_id' => $id,
            'qty'                     => $qty,
            'stock_before'            => $stock_before,
            'stock_after'             => $stock_after,
            'source'                  => $source,
            'description'             => $desc ?? null,
        ]);
    }

    public function webHookCreate(Request $request)
    {
        $post = $request->json()->all();
        switch($post['item_type']){
            case "1":
                $type = "Product";
                break;
            case "3":
                $type = "Package";
                break;
            case "4":
                $type = "Treatment";
                break;
            default:
                $type = "Product";
        }
        $payload = [
            'equal_id' => $post['id_item'],
            'equal_name' => $post['item_name'],
            'product_code' => $post['item_code'],
            'product_name' => $post['item_name'],
            'type' => $type,
            'description' => $post['description'],
            'image' => json_encode($post['item_photos']),
            'product_groups' => json_encode($post['item_groups']),
            'is_active' => 1,
            'need_recipe_status' => 1,
        ];
        if($post['id_item_category']){
            $payload['equal_id_category'] = $post['id_item_category'];
            $category_db = ProductCategory::where(['equal_id' => $post['id_item_category']])->first();
            if($category_db)
                $payload['product_category_id'] = $category_db->id;
        }
        $product = Product::create($payload);
        return $this->ok("succes", $product);
    }

    public function webHookUpdate(Request $request)
    {

        $post = $request->json()->all();
        switch($post['item_type']){
            case "1":
                $type = "Product";
                break;
            case "3":
                $type = "Package";
                break;
            case "4":
                $type = "Treatment";
                break;
            default:
                $type = "Product";
        }
        $payload = [
            'equal_id' => $post['id_item'],
            'equal_name' => $post['item_name'],
            'product_code' => $post['item_code'],
            'product_name' => $post['item_name'],
            'type' => $type,
            'description' => $post['description'],
            'image' => json_encode($post['item_photos']),
            'product_groups' => json_encode($post['item_groups']),
            'is_active' => 1,
            'need_recipe_status' => 1,
        ];
        if($post['id_item_category']){
            $payload['equal_id_category'] = $post['id_item_category'];
            $category_db = ProductCategory::where(['equal_id' => $post['id_item_category']])->first();
            if($category_db)
                $payload['product_category_id'] = $category_db->id;
        }
        $product = Product::where(['equal_id' => $post['id_item']])->update($payload);
        return $this->ok("succes", $payload);
    }

    public function webHookDelete(Request $request)
    {
        $post = $request->json()->all();
        $product = Product::where(['equal_id' => $post['id_item']])->delete();
        return $this->ok("success","");
    }
}
