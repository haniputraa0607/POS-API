<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductCategory;
use Modules\Outlet\Http\Controllers\OutletController;

class LandingPageController extends Controller
{

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function list(Request $request):JsonResponse
    {
        $post = $request->json()->all();
        if(empty($post['category'])){
            return $this->error('Category is empty');
        }
        if(empty($post['order_by'])){
            return $this->error('Order By is empty');
        }
        $orderBy = [
            'ASC',
            'DESC'
        ];
        if(!in_array($post['order_by'], $orderBy, TRUE)){
            return $this->error('Order By cannot be other than asc and desc');
        }
        $products = Product::with(['global_price', 'product_category'])->orderBy('product_name', $post['order_by'])->paginate(10);
        if($post['category'] != 'all'){
            $dataCategory = ProductCategory::find($post['category']);
            if(empty($dataCategory)){
                return $this->error('Category not found');
            }
            $products->where('product_category_id', $post['category']);
        }
        return $this->ok('success', $products);
    }

    public function detail(Request $request):JsonResponse
    {
        $post = $request->json()->all();
        if(empty($post['id'])){
            return $this->error('ID Product is empty');
        }
        $products = Product::with(['global_price', 'product_category'])->where(['id' => $post['id']])->get();
        if(empty($products)){
            return $this->error('Product Not Found');
        }
        return $this->ok('success', $products);
    }
}

