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
        $data = Banner::with(['product:id,product_name,description,product_category_id', 'product.product_category:id,product_category_name'])->get();
        return $this->ok('Banner List', $data);
    }

    public function show($id): JsonResponse
    {
        $data = Banner::with(['product:id,product_name,description,product_category_id', 'product.product_category:id,product_category_name'])->findOrFail($id);
        return $this->ok('Banner Show', $data);
    }

}
