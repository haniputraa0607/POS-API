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

    public function list(Request $request, $type):JsonResponse
    {
        $post = $request->json()->all();
        $category = $post['product_category_id'] ? $post['product_category_id'] : 'all';
        $sortBy = $post['order_by'] ? $post['order_by'] : 'asc';
        $products = Product::with(['global_price', 'product_category'])
        ->where('type', $type)
        ->when($category, function ($query) use ($category) {
            if($category != 'all'){
                return $query->where('product_category_id', $category);
            }
        })
        ->when($sortBy, function ($query, $sortBy) {
            return $query->orderBy('product_name', $sortBy);
        })
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

    public function table_list(Request $request):mixed
    {
        $post = $request->json()->all();
        $category = empty($post['product_category_id']) ? 'all' : $post['product_category_id'];
        $type = empty($post['type']) ? 'Product' : $post['type'];
        $product = Product::with(['global_price', 'product_category'])
        ->when($category, function ($query) use ($category) {
            if($category != 'all'){
                return $query->where('product_category_id', $category);
            }
        })
        ->when($type, function ($query) use ($type) {
            return $query->where('type', $type);
        })
        ->when($request->search, fn ($query, $search) => $query->where('product_name', 'like', '%' . $search . '%'))
        ->paginate(10);
        if(!$product){
            return $this->error('Something Error');
        }

        return $this->ok('success', $product);
    }

    public function datatable_list(Request $request)
    {
        $where[] = ['id', '',  '', 'NOTNULL'];
        $column_order   = ['id', 'product_code', 'product_name', 'type'];
        $column_search  = ['product_code', 'product_name', 'type'];
        $order = ['id' => 'DESC'];
        $list = DataTablesModel::getDatatable($request->post(), 'products', $column_order, $column_search, $order, $where);
        // die;
        $data = array();
        $no = $request->post('start');
        foreach ($list as $key) {
            $no++;
            $row = array();
            $row[] = $no;
            $row[] = $key->product_code;
            $row[] = $key->product_name;
            $row[] = $key->type;
            $row[] = '
                <a data-id="'.$key->id.'" data-name="'.$key->product_name.'" class="btn btn-sm blue" onclick="main.detail(this)"><i class="fa fa-search"></i></a>
                <a class="btn btn-sm red sweetalert-delete btn-primary" data-id="'.$key->id.'" data-name="'.$key->product_name.'" onclick="main.delete(this)"><i class="fa fa-trash-o"></i></a>            
            ';
            $data[] = $row;
        }
        $output = array(
            "draw" => $request->draw,
            "recordsTotal" => DataTablesModel::countAll($request->post(), 'products', $where),
            "recordsFiltered" => DataTablesModel::countFiltered($request->post(), 'products', $column_order, $column_search, $order, $where),
            "data" => $data,
        );
        return response()->json($output);
    }

}

