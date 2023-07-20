<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Outlet\Http\Controllers\OutletController;
use Illuminate\Http\JsonResponse;

class POSController extends Controller
{

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function home(Request $request):mixed
    {
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        if($outlet){
            $data = [
                'status_outlet' => true,
                'queue' => [
                    'product' => 'P06',
                    'treatment' => 'T11',
                    'consultation' => 'C08'
                ]
                ];

            return $this->ok('', $data);
        }else{
            return $this->error('Outlet not found');
        }

    }

    public function listService(Request $request):JsonResponse
    {
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $outlet_service = json_decode($outlet['activities'], true) ?? [];
        $data = [
            'service' => [
                'product' => in_array('product',$outlet_service) ?? false,
                'treatment' => in_array('treatment',$outlet_service) ?? false,
                'consultation' => in_array('consultation',$outlet_service) ?? false
            ]
            ];

        return $this->ok('', $data);

    }
}
