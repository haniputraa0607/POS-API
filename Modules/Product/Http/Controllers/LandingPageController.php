<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductTrending;
use Modules\Product\Entities\ProductCategory;
use Modules\Product\Entities\DatatablesModel;
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
        $category = empty($post['product_category_id']) ? 'all' : $post['product_category_id'];
        $paginate = empty($post['pagination_total_row']) ? 8 : $post['pagination_total_row'];
        $sort_by = empty($post['sort_by']) ? 1 : $post['sort_by'];
        $productsQuery = Product::with(['global_price', 'product_category', 'product_package.product'])
            ->whereIn('type', ['Product', 'Package'])
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
        if(!empty($post['order_by'])){
            $productsQuery->orderBy('product_name', $post['order_by']);
        }
        $products = $productsQuery->paginate($paginate, ['*'], 'page', $post['page']);
        foreach ($products as $product) {
            $product->image = json_decode($product->image);
            foreach ($product->product_package as $package) {
                if (is_string($package->product->image) && json_decode($package->product->image) !== null) {
                    $package->product->image = json_decode($package->product->image);
                }
            }
        }
        return $this->ok('success', $products);
    }

    public function treatment(Request $request):JsonResponse
    {
        $post = $request->json()->all();
        $sortBy = empty($post['order_by']) ? 'asc' : $post['order_by'];
        $paginate = empty($post['pagination_total_row']) ? 8 : $post['pagination_total_row'];
        $products = Product::with(['global_price', 'product_category'])
        ->where('type', 'Treatment')
        ->when($sortBy, function ($query, $sortBy) {
            return $query->orderBy('product_name', $sortBy);
        })->paginate($paginate, ['*'], 'page', $post['page']);
        foreach ($products as $product) {
            $product->image = json_decode($product->image);
        }
        return $this->ok('success', $products);
    }

    public function detail(Request $request, $id): JsonResponse
    {
        $products = Product::with(['global_price', 'product_category', 'product_package.product'])->where('id', $id)->firstOrFail();

        $category_id = $products->product_category->id;
        $other_products = Product::with(['global_price', 'product_category', 'product_package.product'])
            ->where('product_category_id', $category_id)
            ->where('id', '!=', $id)
            ->inRandomOrder()
            ->limit(4)
            ->get();

        if ($other_products->count() < 4) {
            $additional_products = Product::with(['global_price', 'product_category', 'product_package.product'])
                ->where('product_category_id', $category_id)
                ->whereNotIn('id', $other_products->pluck('id')->toArray())
                ->inRandomOrder()
                ->limit(4 - $other_products->count())
                ->get();

            $other_products = $other_products->concat($additional_products);
        }

        $products->image = json_decode($products->image);
        foreach ($other_products as $otherProduct) {
            $otherProduct->image = json_decode($otherProduct->image);
            foreach ($otherProduct->product_package as $package) {
                if (is_string($package->product->image) && json_decode($package->product->image) !== null) {
                    $package->product->image = json_decode($package->product->image);
                }
            }
        }

        return $this->ok("success", [
            'product' => $products,
            'other_products' => $other_products,
        ]);
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

    public function product_trending()
    {
        $trendingProducts = ProductTrending::with('products')->get();
        foreach ($trendingProducts as $trendingProduct) {
            $trendingProduct->products->image = json_decode($trendingProduct->products->image);
        }
        return $this->ok("success", $trendingProducts);
    }


}

