<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Product\Entities\Product;
use Modules\Outlet\Http\Controllers\OutletController;

class ProductController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function create(Request $request):JsonResponse
    {
        $post = $request->json()->all();

        if(!$post || !isset($post['type']) || $post['type'] == ''){
            return $this->error('Type cant be null');
        }

        if($post['type'] == 'Product' && (!isset($post['product_category_id']) || $post['product_category_id'] == '')){
            return $this->error('Product Category name cant be null');
        }

        if(!isset($post['product_name']) || $post['product_name'] == ''){
            return $this->error('Product name cant be null');
        }

        $store = Product::create($post);
        if(!$store){
            return $this->error('Something Error');
        }
        return $this->ok('success', $store);
    }

    public function list(Request $request):mixed
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $product = Product::with(['global_price','outlet_price' => function($outlet_price) use ($outlet){$outlet_price->where('outlet_id',$outlet['id']);}, 'outlet_stock' => function($outlet_stock) use ($outlet){$outlet_stock->where('outlet_id',$outlet['id']);}])->where('product_category_id', $post['id'])->select('id','product_name')->product()->get()->toArray();
        if(!$product){
            return $this->error('Something Error');
        }

        $product = array_map(function($value){

            if(isset($value['outlet_price'][0]['price']) ?? false){
                $price = $value['outlet_price'][0]['price'];
            }else{
                $price = $value['global_price']['price'];
            }
            $data = [
                'id' => $value['id'],
                'product_name' => $value['product_name'],
                'price' => $price,
                'stock' => $value['outlet_stock'][0]['stock'] ?? 0
            ];
            return $data;
        },$product);

        return $this->ok('success', $product);
    }
}
