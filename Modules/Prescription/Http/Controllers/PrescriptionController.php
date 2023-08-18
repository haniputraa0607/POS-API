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
use Modules\Prescription\Entities\PrescriptionCustomCategory;

class PrescriptionController extends Controller
{

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->product_path = "img/product/";
    }

    public function list(Request $request):JsonResponse
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
            }
        ])->whereHas('prescription_outlets', function($prescription_outlets) use ($outlet){
            $prescription_outlets->where('outlet_id',$outlet['id']);
            $prescription_outlets->where('stock', '>', 0);
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
                'type'              => $value['type'],
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

        $prescriptionCategories = PrescriptionCustomCategory::select('id','category_name')->get()->toArray();
        if(!$prescriptionCategories){
            return $this->error('Category not found');
        }
        return $this->ok('success', $prescriptionCategories);
    }

}
