<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Product\Entities\Product;

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

        if($post['type'] == 'Product' && (!isset($post['product_categoriy_id']) || $post['product_categoriy_id'] == '')){
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

        $product = Product::where('product_categoriy_id', $post['id'])->select('id','product_name')->product()->get()->toArray();
        if(!$product){
            return $this->error('Something Error');
        }
        return $this->ok('success', $product);
    }
}
