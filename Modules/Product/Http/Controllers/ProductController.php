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

class ProductController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
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

    public function list(Request $request):mixed
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
        ->select('id','product_name')
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
                'id' => $value['id'],
                'product_name' => $value['product_name'],
                "image_url" => null,
                'price' => $price,
                'stock' => $value['outlet_stock'][0]['stock'] ?? 0
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
            'description'            => $desc ?? null,
        ]);

    }
}
