<?php

namespace Modules\Prescription\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Lib\MyHelper;
use Modules\Prescription\Entities\Prescription;
use Modules\Prescription\Entities\PrescriptionOutlet;
use Modules\Prescription\Entities\PrescriptionOutletLog;
use Modules\Prescription\Entities\PrescriptionCategory;
use Modules\Order\Entities\Order;
use Modules\Prescription\Entities\CategoryContainer;
use Modules\Prescription\Entities\Container;

class PrescriptionController extends Controller
{

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->product_path = "img/product/";
    }

    public function list(Request $request):mixed
    {
        $post = $request->json()->all();
        $doctor = $request->user();
        $outlet =  $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $prescriptions = Prescription::with([
            'prescription_outlets' => function($prescription_outlets) use ($outlet){
                $prescription_outlets->where('outlet_id',$outlet['id']);
            }, 'category'
        ])->whereHas('prescription_outlets', function($prescription_outlets) use ($outlet){
            $prescription_outlets->where('outlet_id',$outlet['id']);
        });

        if(isset($post['search']) && !empty($post['search'])){
            $prescriptions = $prescriptions->where('prescription_name', 'like', '%'.$post['search'].'%');
        }

        $prescriptions = $prescriptions->original()->get()->toArray();

        $prescriptions = array_map(function($value){

            if(isset($value['prescription_outlets'][0]['price']) ?? false){
                $price = $value['prescription_outlets'][0]['price'] ?? 0;
            }else{
                $price = $value['price'] ?? 0;
            }
            $data = [
                'id'                => $value['id'],
                'prescription_name' => $value['prescription_name'],
                'type'              => $value['category']['category_name'] ?? '',
                'unit'              => $value['unit'],
                'price'             => $price,
                'stock'             => $value['prescription_outlets'][0]['stock'] ?? 0
            ];
            return $data;
        },$prescriptions ?? []);

        return $this->ok('success', $prescriptions);
    }

    public function addLogPrescriptionStockLog($id, $qty, $stock_before, $stock_after, $source, $desc){

        $stock_log = PrescriptionOutletLog::create([
            'prescription_outlet_id'  => $id,
            'qty'                     => $qty,
            'stock_before'            => $stock_before,
            'stock_after'             => $stock_after,
            'source'                  => $source,
            'description'             => $desc ?? null,
        ]);
    }

    public function categoriesCustom(Request $request):JsonResponse
    {
        $doctor = $request->user();
        $outlet =  $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $prescriptionCategories = PrescriptionCategory::select('id','category_name')->get()->toArray();
        if(!$prescriptionCategories){
            return $this->error('Category not found');
        }
        return $this->ok('success', $prescriptionCategories);
    }

    public function createCustom(Request $request):mixed
    {
        $request->validate([
            'id_order' => 'required',
            'custom_name' => 'required',
            'id_category' => 'required',
        ]);

        $post = $request->json()->all();
        $doctor = $request->user();
        $outlet =  $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $prescriptionCategories = PrescriptionCategory::where('id', $post['id_category'])->first();
        if(!$prescriptionCategories){
            return $this->error('Category not found');
        }

        $order = Order::where('id', $post['id_order'])->first();
        if(!$order){
            return $this->error('Order not found');
        }

        $last_code = Prescription::latest('prescription_code')->first()['prescription_code']??'';
        $last_code = $last_code == '' ? 0 : explode('-',$last_code)[1];
        $last_code = (int)$last_code + 1;
        $pres_code = 'PRE-'.sprintf("%02d", $last_code);
        $create = Prescription::create([
            'prescription_code' => $pres_code,
            'prescription_name' => $post['custom_name'],
            'prescription_category_id' => $prescriptionCategories['id'],
            'is_custom' => 1,
            'patient_id' => $order['patient_id'],
        ]);

        if(!$create){
            return $this->error('Create prescription failed');
        }

        return $this->ok('success', $create);

    }

    public function listContainer(Request $request): mixed
    {
        $request->validate([
            'id_custom' => 'required',
        ]);

        $post = $request->json()->all();
        $doctor = $request->user();
        $outlet =  $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $prescription = Prescription::where('id', $post['id_custom'])->where('is_custom', 1)->first();
        if(!$prescription){
            return $this->error('Prescription not found');
        }

        $containers = Container::whereHas('categories', function($categories) use($prescription){
            $categories->where('prescription_category_id', $prescription['prescription_category_id']);
        })->get()->toArray();

        $containers = array_map(function($value){
            $value = [
                'id'   => $value['id'],
                'name' => $value['container_name'].' '.$value['type'].' - '.$value['unit'],
            ];
            return $value;
        }, $containers ?? []);

        return $this->ok('success', $containers);


    }

}
