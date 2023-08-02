<?php

namespace Modules\Product\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Product\Entities\Product;
use Modules\Customer\Entities\Customer;
use Modules\Customer\Entities\TreatmentPatient;

class TreatmentController extends Controller
{

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function list(Request $request):mixed
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(!isset($post['search']) || count($post['search']) < 1){
            return $this->error('Filter cant be null');
        }

        $products = Product::with(['global_price','outlet_price' => function($outlet_price) use ($outlet){
            $outlet_price->where('outlet_id',$outlet['id']);
        }])->whereHas('outlet_treatment', function($outlet_treatment) use ($outlet, $post){

            $outlet_treatment->where('outlet_id',$outlet['id']);

        });

        $filter_name = array_search('name', array_column($post['search'], 'filter'));
        if($filter_name !== false){
            $products = $products->where('product_name', 'like', '%'.$post['search'][$filter_name]['value'].'%');
        }

        $products = $products->treatment()->get()->toArray();
        if(!$products){
            return $this->error('Something Error');
        }

        $products = array_map(function($value){

            if(isset($value['outlet_price'][0]['price']) ?? false){
                $price = $value['outlet_price'][0]['price'];
            }else{
                $price = $value['global_price']['price'];
            }
            $data = [
                'id' => $value['id'],
                'treatment_name' => $value['product_name'],
                'price' => $price,
                'can_continue' => false,
                'record_history' => [],
                'date' => date('d F Y'),
            ];
            return $data;
        },$products);

        $filter_date = array_search('date', array_column($post['search'], 'filter'));
        if($filter_date !== false){
            $dates = $post['search'][$filter_date]['value'];
            $new_product = [];
            foreach($dates ?? [] as $key => $date){
                $outlet_schedule = $outlet->outlet_schedule->where('day', date('l', strtotime($date)))->first();
                if($outlet_schedule){
                    if($outlet_schedule['all_products'] == 0){
                        $custom = json_decode($outlet_schedule['custom_products'], true) ?? [];
                        foreach($products ?? [] as $prod){
                            if(in_array($prod['id'],$custom)){
                                $new_product[] = [
                                    'id' => $prod['id'],
                                    'treatment_name' => $prod['treatment_name'],
                                    'price' => $prod['price'],
                                    'can_continue' => $prod['can_continue'],
                                    'record_history' => $prod['record_history'],
                                    'date' => date('d F Y', strtotime($date)),
                                ];
                            }
                        }
                    }else{
                        foreach($products ?? [] as $prod){
                            $new_product[] = [
                                'id' => $prod['id'],
                                'treatment_name' => $prod['treatment_name'],
                                'price' => $prod['price'],
                                'can_continue' => $prod['can_continue'],
                                'record_history' => $prod['record_history'],
                                'date' => date('d F Y', strtotime($date)),
                            ];
                        }
                    }
                }
            }
            $products = $new_product;
        }

        $return = [
            'available' => count($products),
            'treatment' => $products,
        ];

        if(isset($post['id_customer'])){
            $customerPatient = TreatmentPatient::where('patient_id', $post['id_customer'])
            ->where('status', '<>', 'Finished')
            ->get()->toArray();

            $return['treatment'] = array_map(function($value) use($customerPatient){

                foreach($customerPatient ?? [] as $cp){
                    if($value['id'] == $cp['treatment_id']){
                        $value['can_continue'] = true;
                        $value['record_history'] = [
                            'from' => $cp['progress'].'/'.$cp['step'],
                            'to' => ($cp['progress']+1).'/'.$cp['step'],
                        ];
                    }
                }

                return $value;
            },$return['treatment'] ?? []);

        }
        return $this->ok('success', $return);
    }

    public function customerHistory(Request $request):JsonResponse
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(isset($post['id_customer'])){

            $histories = TreatmentPatient::with(['treatment','doctor','steps' => function($steps){ $steps->orderBy('step', 'desc');}])->where('patient_id', $post['id_customer'])->get()->toArray();

            $return = [];
            foreach($histories ?? [] as $key => $history){

                $steps = [];
                foreach($history['steps'] as $key2 => $step){
                    $steps[] = [
                        'index' => $step['step'] == 1 ? '1st Treatment' : ($step['step'] == 2 ? '2nd Treatment' : ($step['step'] == 3 ? '3rd Treatment' : ($step['step'] >= 4 ? $step['step'].'th Treatment' : ''))),
                        'date' => date('Y-m-d, H:i',strtotime($step['date'])).' WIB',
                        'queue' => 'Queue Number '.'T0',
                    ];
                }

                $return[] = [
                    'treatment_name' => $history['treatment']['product_name'],
                    'doctor_name' => 'By '.$history['doctor']['name'],
                    'start_treatment' => date('Y-m-d', strtotime($history['start_date'])),
                    'expired_treatment' => date('Y-m-d', strtotime($history['expired_date'])),
                    'suggestion' => $history['suggestion'],
                    'progress' => $history['progress'] == $history['step'] ? 'Finished' : $history['progress'].'/'. $history['step'].' Continue Treatment',
                    'step' => $steps,
                ];
            }
        }else{
            return $this->error('ID customer cant be null');

        }
        return $this->ok('success', $return);
    }
}
