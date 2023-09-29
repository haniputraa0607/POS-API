<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Product\Entities\ProductCategory;
use Modules\Outlet\Http\Controllers\OutletController;
use Modules\Product\Http\Requests\Webhook\ProductCategory\Create;
use Modules\Product\Http\Requests\Webhook\ProductCategory\Update;
use Modules\Product\Http\Requests\ProductCategoryWebHookCreateRequest;
use Modules\Product\Http\Requests\ProductCategoryWebHookCreateBulkRequest;
use Modules\Product\Http\Requests\ProductCategoryWebHookUpdateRequest;

class ProductCategoryController extends Controller
{

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function create(Request $request):JsonResponse
    {
        $post = $request->json()->all();

        if(!$post || !isset($post['product_category_name']) || $post['product_category_name'] == ''){
            return $this->error('Product Category name cant be null');
        }

        $store = ProductCategory::create($post);
        if(!$store){
            return $this->error('Something Error');
        }
        return $this->ok('success', $store);
    }

    public function list(Request $request):JsonResponse
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $productCategories = ProductCategory::select('id','product_category_name')->get()->toArray();
        if(!$productCategories){
            return $this->error('Something Error');
        }
        return $this->ok('success', $productCategories);
    }

    public function webHookCreate(ProductCategoryWebHookCreateRequest $request)
    {
        $product = ProductCategory::create($request->all());
        return $this->ok("succes", $product);
    }

    public function webHookUpdate(ProductCategoryWebHookUpdateRequest $request)
    {
        $product = ProductCategory::where(['equal_id' => $request->equal_id])->update($request->all());
        return $this->ok("succes", $product);
    }

    public function webHookDelete(Request $request)
    {
        ProductCategory::where(['equal_id' => $request->id_item_category])->delete();
        return $this->ok("success","");
    }

    public function webHookCreateBulk(ProductCategoryWebHookCreateBulkRequest $request)
    {
        $data = $request->validated();
        $createdCategories = [];
        foreach ($data as $categoryData) {
            $category = ProductCategory::create([
                'equal_id' => $categoryData['equal_id'],
                'equal_code' => $categoryData['equal_code'],
                'product_category_name' => $categoryData['product_category_name'],
                'equal_name' => $categoryData['product_category_name'],
                'equal_parent_id' => $categoryData['equal_parent_id'],
                'product_category_photo' => $categoryData['product_category_photo'],
                'is_active' => 1
            ]);

            $createdCategories[] = $category;
        }

        return $this->ok("success", $createdCategories);
    }

}

