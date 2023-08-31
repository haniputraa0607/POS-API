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
use Modules\Order\Entities\Order;
use App\Lib\MyHelper;

class TreatmentController extends Controller
{

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function list(Request $request):JsonResponse
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(!isset($post['search']) || !isset($post['search']['filter'])){
            return $this->error('Filter cant be null');
        }

        $custom = [];
        $all_products = 1;

        if($post['search']['filter'] == 'date'){
            $date = date('Y-m-d', strtotime($post['search']['value']));
            $outlet_schedule = $outlet->outlet_schedule->where('day', date('l', strtotime($date)))->first();
            $all_products = $outlet_schedule['all_products'];
            $custom = json_decode($outlet_schedule['custom_products'], true) ?? [];
        }

        $products = Product::with(['global_price','outlet_price' => function($outlet_price) use ($outlet){
            $outlet_price->where('outlet_id',$outlet['id']);
        }])->whereHas('outlet_treatment', function($outlet_treatment) use ($outlet, $all_products, $custom){

            $outlet_treatment->where('outlet_id',$outlet['id']);
            if(count($custom) > 0 && $all_products == 0){
                $outlet_treatment->whereIn('treatment_id',$custom);
            }
        });

        if($post['search']['filter'] == 'name'){
            if($post['search']['value'] == ''){
                $products = $products->where('product_name', '');
            }else{
                $products = $products->where('product_name', 'like', '%'.$post['search']['value'].'%');
            }
        }

        $products = $products->treatment()->get()->toArray();
        if(!$products){
            return $this->error('Treatment is empty');
        }

        $products = array_map(function($value) use($post){

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
                'can_new' => true,
                'total_history' => 0,
                'record_history' => []
            ];
            if($post['search']['filter'] == 'date'){
                $data['date'] = date('Y-m-d', strtotime($post['search']['value']));
                $data['date_text'] = date('d F Y', strtotime($data['date']));
            }
            return $data;
        },$products ?? []);

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

                $value['total_history'] = count($customerPatient);

                return $value;
            },$return['treatment'] ?? []);

        }elseif(isset($post['id_order'])){
            $order = Order::where('id', $post['id_order'])->first();
            if(!$order){
                return $this->error('Order not found');
            }

            $customerPatient = TreatmentPatient::where('patient_id', $order['patient_id'])
            ->where('status', '<>', 'Finished')
            ->get()->toArray();

            $return['treatment'] = array_map(function($value) use($customerPatient){

                foreach($customerPatient ?? [] as $cp){
                    if($value['id'] == $cp['treatment_id']){
                        $value['can_continue'] = true;
                        $value['can_new'] = false;
                        $value['record_history'] = [
                            'from' => $cp['progress'].'/'.$cp['step'],
                            'to' => ($cp['progress']+1).'/'.$cp['step'],
                        ];
                    }
                }
                $value['total_history'] = count($customerPatient);

                return $value;
            },$return['treatment'] ?? []);
        }

        return $this->ok('success', $return);
    }

    public function listDate(Request $request):JsonResponse
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $date_now = date('Y-m-d');
        $dates = MyHelper::getListDate(date('d'),date('m'),date('Y'));

        $products = Product::with(['global_price','outlet_price' => function($outlet_price) use ($outlet){
            $outlet_price->where('outlet_id',$outlet['id']);
        }])->whereHas('outlet_treatment', function($outlet_treatment) use ($outlet){

            $outlet_treatment->where('outlet_id',$outlet['id']);
        })
        ->where('id', $post['id'])->treatment()->first();

        if(!$products){
            return $this->error('Treatment is empty');
        }

        $price = 0;
        if(isset($products['outlet_price'][0]['price']) ?? false){
            $price = $products['outlet_price'][0]['price'];
        }else{
            $price = $products['global_price']['price'];
        }

        $data = [
            'id' => $products['id'],
            'treatment_name' => $products['product_name'],
            'price' => $price,
            'can_continue' => false,
            'can_new' => true,
            'record_history' => []
        ];

        $list_dates = [];
        $new = [];
        foreach($dates['list'] ?? [] as $date){
            $outlet_schedule = $outlet->outlet_schedule->where('day', date('l', strtotime($date)))->where('is_closed', 0)->first();
            if(!$outlet_schedule){
                continue;
            }

            if($outlet_schedule['all_products'] == 0){
                $custom = json_decode($outlet_schedule['custom_products'], true) ?? [];
                if(!in_array($data['id'], $custom)){
                    continue;
                }
            }

            $new[] = [
                'id' => $data['id'],
                'treatment_name' => $data['treatment_name'],
                'price' => $data['price'],
                'can_continue' => $data['can_continue'],
                'can_new' => $data['can_new'],
                'total_history' => 0,
                'date_text' => date('d F Y', strtotime($date)),
                'date' => date('Y-m-d', strtotime($date)),
                'record_history' => []
            ];
            $list_dates[] = $date;
        }

        $return = [
            'available' => count($new),
            'treatment' => $new,
            'list_dates' => $list_dates,
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
                $value['total_history'] = count($customerPatient);

                return $value;
            },$return['treatment'] ?? []);

        }elseif(isset($post['id_order'])){
            $order = Order::where('id', $post['id_order'])->first();
            if(!$order){
                return $this->error('Order not found');
            }

            $customerPatient = TreatmentPatient::where('patient_id', $order['patient_id'])
            ->where('status', '<>', 'Finished')
            ->get()->toArray();

            $return['treatment'] = array_map(function($value) use($customerPatient){

                foreach($customerPatient ?? [] as $cp){
                    if($value['id'] == $cp['treatment_id']){
                        $value['can_continue'] = true;
                        $value['can_new'] = false;
                        $value['record_history'] = [
                            'from' => $cp['progress'].'/'.$cp['step'],
                            'to' => ($cp['progress']+1).'/'.$cp['step'],
                        ];
                    }
                }
                $value['total_history'] = count($customerPatient);

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
            $customer_id = $post['id_customer'];

        }elseif(isset($post['id_order'])){
            $order = Order::where('id', $post['id_order'])->first();
            if(!$order){
                return $this->error('Order not found');
            }
            $customer_id = $order['patient_id'];
        }else{
            return $this->error('ID customer cant be null');

        }

        $histories = TreatmentPatient::with([
            'treatment','doctor','steps' => function($steps){ $steps->orderBy('step', 'desc');}
        ])->whereHas('doctor')
        ->whereNotNull('doctor_id')
        ->where('patient_id', $customer_id)
        ->get()->toArray();

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
        return $this->ok('success', $return);
    }
}
