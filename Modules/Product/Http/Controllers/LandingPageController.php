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
        $category = $post['product_category_id'];
        $sortBy = $post['order_by'];
        $products = Product::with(['global_price', 'product_category'])
        ->when($category, function ($query) use ($category) {
            if($category != 'all'){
                return $query->where('product_category_id', $category);
            }
        })
        ->when($sortBy, function ($query, $sortBy) {
            return $query->orderBy('product_name', $sortBy);
        })
        ->firstOrFail()
        ->paginate(10);
        return $this->ok('success', $products);
    }

    public function detail(Request $request):JsonResponse
    {
        $post = $request->json()->all();
        $id = $post['id'];
        $products = Product::with(['global_price', 'product_category'])->where('id', $id)->firstOrFail();
        return $this->ok("success", $products);
    }
}

