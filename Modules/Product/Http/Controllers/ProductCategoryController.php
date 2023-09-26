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

    public function list(Request $request):mixed
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $return = [];
        $make_new = false;
        $check_json = file_exists(storage_path() . "/json/product_category.json");
        if($check_json){
            $config = json_decode(file_get_contents(storage_path() . "/json/product_category.json"), true);
            if(date('Y-m-d H:i', strtotime($config['updated_at']. ' +6 hours')) <= date('Y-m-d H:i')){
                $make_new = true;
            }
        }else{
            $make_new = true;
        }

        if($make_new){
            $productCategories = ProductCategory::select('id','product_category_name')->get()->toArray();
            if($productCategories){
                $config = [
                    'updated_at' => date('Y-m-d H:i'),
                    'data'       => $productCategories
                ];
                file_put_contents(storage_path('/json/product_category.json'), json_encode($config));

            }
        }

        $return = $config['data'] ?? [];

        return $this->ok('success', $return);
    }

    public function webHookCreate(Create $request)
    {
        $product = ProductCategory::create($request->all());
        return $this->ok("succes", $product);
    }

    public function webHookUpdate(Update $request)
    {
        $product = ProductCategory::where(['equal_id' => $request->equal_id])->update($request->all());
        return $this->ok("succes", $product);
    }

    public function webHookDelete(Request $request)
    {
        ProductCategory::where(['equal_id' => $request->id_item_category])->delete();
        return $this->ok("success","");
    }
}

