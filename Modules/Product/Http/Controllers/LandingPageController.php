<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductCategory;
use Modules\Product\Entities\DatatablesModel;
use Modules\Outlet\Http\Controllers\OutletController;

class LandingPageController extends Controller
{

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function list(Request $request,):JsonResponse
    {
        $post = $request->json()->all();
        $category = $post['product_category_id'] ? $post['product_category_id'] : 'all';
        $paginate = $post['perpage'] ? $post['perpage'] : 8;
        $sort_by = $post['sort_by'] ? $post['sort_by'] : 1;
        $productsQuery = Product::with(['global_price', 'product_category'])
            ->where('type', 'Product')
            ->when($category, function ($query) use ($category) {
                if ($category != 'all') {
                    return $query->where('product_category_id', $category);
                }
            });
        switch ($sort_by) {
            case 1:
                $productsQuery->orderBy('product_name', 'asc');
                break;
            case 2:
                $productsQuery->orderBy('product_name', 'desc');
                break;
            case 3:
                $productsQuery->withCount('orders')->orderByDesc('orders_count');
                break;
            case 4: 
                $productsQuery->orderBy('created_at', 'desc');
                break;
            default:
                $productsQuery->orderBy('product_name', 'asc');
        }
        if($post['order_by']){
            $productsQuery->orderBy('product_name', $post['order_by']);
        }
        $products = $productsQuery->paginate($paginate);
        foreach ($products as $product) {
            $product->image = 'https://api-daviena.belum.live/'.$product->image;
        }
        return $this->ok('success', $products);
    }

    public function treatment(Request $request):JsonResponse
    {
        $post = $request->json()->all();
        $sortBy = $post['order_by'] ? $post['order_by'] : 'asc';
        $products = Product::with(['global_price', 'product_category'])
        ->where('type', 'Treatment')
        ->when($sortBy, function ($query, $sortBy) {
            return $query->orderBy('product_name', $sortBy);
        })
        ->paginate(10);
        foreach ($products as $product) {
            $product->image = 'https://api-daviena.belum.live/'.$product->image;
        }
        return $this->ok('success', $products);
    }

    public function detail(Request $request):JsonResponse
    {
        $post = $request->json()->all();
        $id = $post['id'];
        $products = Product::with(['global_price', 'product_category'])->where('id', $id)->firstOrFail();
        return $this->ok("success", $products);
    }

    public function product_category(Request $request):JsonResponse
    {
        $post = $request->json()->all();
        $productCategories = ProductCategory::select('id','product_category_name')->get()->toArray();
        if(!$productCategories){
            return $this->error('Something Error');
        }
        return $this->ok('success', $productCategories);
    }


}

