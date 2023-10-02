<?php

namespace Modules\Banner\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Banner\Entities\Banner;

class BannerController extends Controller
{

    public function index()
    {
        $data = Banner::with(['product:id,product_name,description,image,product_category_id', 'product.product_category:id,product_category_name'])->get();
        foreach ($data as $item) {
            if($item->product->image && is_string($item->product->image)){
                $item->product->image = json_decode($item->product->image);
            }
        }
        return $this->ok('Banner List', $data);
    }

    public function show($id): JsonResponse
    {
        $data = Banner::with(['product:id,product_name,description,image,product_category_id', 'product.product_category:id,product_category_name'])->findOrFail($id);
        if($data->product->image){
            $data->product->image = json_decode($data->product->image);
        }
        return $this->ok('Banner Show', $data);
    }

}
