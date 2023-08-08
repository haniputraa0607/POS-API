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

    public function list(Request $request):JsonResponse
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $product = Product::with(['global_price','outlet_price' => function($outlet_price) use ($outlet){
            $outlet_price->where('outlet_id',$outlet['id']);
        }, 'outlet_stock' => function($outlet_stock) use ($outlet){
            $outlet_stock->where('outlet_id',$outlet['id']);
        }])->where('product_category_id', $post['id'])
        ->select('id','product_name', 'image')
        ->product()
        ->get()->toArray();
        if(!$product){
            return $this->error('Something Error');
        }

        $product = array_map(function($value){

            if(isset($value['outlet_price'][0]['price']) ?? false){
                $price = $value['outlet_price'][0]['price'] ?? null;
            }else{
                $price = $value['global_price']['price'] ?? null;
            }
            $data = [
                'id'           => $value['id'],
                'product_name' => $value['product_name'],
                'image_url'    => isset($value['image']) ? env('STORAGE_URL_API').$value['image'] : null,
                'price'        => $price,
                'stock'        => $value['outlet_stock'][0]['stock'] ?? 0
            ];
            return $data;
        },$product);

        return $this->ok('success', $product);
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
}
