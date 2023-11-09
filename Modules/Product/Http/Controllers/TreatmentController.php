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
use Illuminate\Support\Facades\DB;
use Modules\User\Entities\User;
use Modules\Doctor\Entities\Nurse;
use Modules\Doctor\Entities\Beautician;

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

        if(!isset($post['search']) || !isset($post['search']['filter'])){
            return $this->error('Filter cant be null');
        }

        $custom = [];
        $all_products = 1;
        $today_month = false;

        if($post['search']['filter'] == 'date'){

            $date = date('Y-m-d', strtotime($post['search']['value']));
            if(date('m', strtotime($date)) == date('m')){
                $today_month = true;
            }
        }elseif($post['search']['filter'] == 'name'){
            return $this->listAll($outlet);
        }

        $year = date('Y',strtotime($date));
        $month = date('m',strtotime($date));
        $today = $month > date('m') || $year > date('Y') ? 1 : date('d');

        $dates = MyHelper::getListDate($today,$month,$year);

        $get_treatments = [];
        $make_new = false;
        $check_json = file_exists(storage_path() . "/json/get_treatment.json");
        if($check_json){
            $config = json_decode(file_get_contents(storage_path() . "/json/get_treatment.json"), true);
            if(isset($config[$outlet['id']])){
                if(($date && !$today_month) || (date('Y-m-d H:i', strtotime($config[$outlet['id']]['updated_at']. ' +6 hours')) <= date('Y-m-d H:i'))){
                    $make_new = true;
                }elseif($month != $config[$outlet['id']]['month'] || $year != $config[$outlet['id']]['year']){
                    $make_new = true;
                }
            }else{
                $make_new = true;
            }
        }else{
            $make_new = true;
        }

        if($make_new){

            $products = Product::with(['global_price','outlet_price' => function($outlet_price) use ($outlet){
                $outlet_price->where('outlet_id',$outlet['id']);
            }])->whereHas('outlet_treatment', function($outlet_treatment) use ($outlet, $all_products, $custom){

                $outlet_treatment->where('outlet_id',$outlet['id']);
            });

            $products = $products->treatment()->get()->toArray();

            $config[$outlet['id']] = [
                'updated_at' => date('Y-m-d H:i'),
                'year'       => $year,
                'month'      => $month,
                'data'       => $products
            ];
            file_put_contents(storage_path('/json/get_treatment.json'), json_encode($config));

        }

        $config = $config[$outlet['id']] ?? [];

        $get_treatments = $config['data'] ?? [];

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
                'record_history' => [],
                'date_start' => null
            ];
            return $data;
        },$get_treatments ?? []);

        $list_date_return['data'] = [];

        foreach($dates['list'] ?? [] as $date_list){
            $customerPatient = [];
            if(isset($post['id_customer'])){
                $customerPatient = TreatmentPatient::with(['steps' => function($steps){
                    $steps->latest('step');
                }])
                ->where('patient_id', $post['id_customer'])
                ->where('status', '<>', 'Finished')
                ->get()->toArray() ?? [];
            }elseif(isset($post['id_order'])){
                $order = Order::where('id', $post['id_order'])->first();
                if(!$order){
                    return $this->error('Order not found');
                }

                $customerPatient = TreatmentPatient::with(['steps' => function($steps){
                    $steps->latest('step');
                }])
                ->where('patient_id', $order['patient_id'])
                ->where('status', '<>', 'Finished')
                ->get()->toArray()?? [];
            }

            $treatment_list_date = [];
            foreach($products ?? [] as $prod){
                $outlet_schedule = $outlet->outlet_schedule->where('day', date('l', strtotime($date_list)))->where('is_closed', 0)->first();
                if(!$outlet_schedule){
                    continue;
                }

                if($outlet_schedule['all_products'] == 0){
                    $custom = json_decode($outlet_schedule['custom_products'], true) ?? [];
                    if(!in_array($prod['id'], $custom)){
                        continue;
                    }
                }

                foreach($customerPatient ?? [] as $cp){
                    if($prod['id'] == $cp['treatment_id'] && (date('Y-m-d',strtotime($cp['expired_date'])) >= date('Y-m-d',strtotime($date_list)))){
                        if($cp['steps'][0]['step'] < $cp['step']){
                            $prod['can_continue'] = true;
                            $prod['record_history'] = [
                                'start' => $cp['steps'][0]['step'],
                                'total' => $cp['step'],
                                'from' => $cp['steps'][0]['step'].'/'.$cp['step'],
                                'to' => ($cp['steps'][0]['step']+1).'/'.$cp['step'],
                                'expired_date' => date('Y-m-d',strtotime($cp['expired_date']))
                            ];
                            $prod['date_start'] = date('Y-m-d',strtotime($cp['start_date']));
                        }
                        if(isset($post['id_order'])){
                            $prod['can_new'] = false;
                        }
                    }
                }
                $prod['total_history'] = count($customerPatient??[]);

                $avail_doctor = User::whereHas('doctor_schedules', function($doctor_schedules) use($date_list){
                    $doctor_schedules->where('schedule_month', date('m', strtotime($date_list)))
                    ->where('schedule_year', date('Y', strtotime($date_list)))
                    ->whereHas('schedule_dates', function($schedule_dates) use($date_list){
                        $schedule_dates->whereDate('date', date('Y-m-d', strtotime($date_list)));
                    });
                })->where('outlet_id', $outlet['id'])
                ->doctor()->select('id', 'name')->get()->toArray();

                $avail_nurse = Nurse::whereHas('nurse_schedules', function($nurse_schedules) use($date_list){
                    $nurse_schedules->where('schedule_month', date('m', strtotime($date_list)))
                    ->where('schedule_year', date('Y', strtotime($date_list)))
                    ->whereHas('schedule_dates', function($schedule_dates) use($date_list){
                        $schedule_dates->whereDate('date', date('Y-m-d', strtotime($date_list)));
                    });
                })->where('outlet_id', $outlet['id'])
                ->select('id', 'name')->get()->toArray();

                $avail_beautician = Beautician::whereHas('beautician_schedules', function($beautician_schedules) use($date_list){
                    $beautician_schedules->where('schedule_month', date('m', strtotime($date_list)))
                    ->where('schedule_year', date('Y', strtotime($date_list)))
                    ->whereHas('schedule_dates', function($schedule_dates) use($date_list){
                        $schedule_dates->whereDate('date', date('Y-m-d', strtotime($date_list)));
                    });
                })->where('outlet_id', $outlet['id'])
                ->select('id', 'name')->get()->toArray();

                $treatment_list_date[] =[
                    'id' => $prod['id'],
                    'treatment_name' => $prod['treatment_name'],
                    'list_doctors' => $avail_doctor ?? [],
                    'list_nurses' => $avail_nurse ?? [],
                    'list_beauticians' => $avail_beautician ?? [],
                    'price' => $prod['price'],
                    'can_continue' => $prod['can_continue'],
                    'can_new' => $prod['can_new'],
                    'date_text' => date('d F Y', strtotime($date_list)),
                    'date' => date('Y-m-d', strtotime($date_list)),
                    'total_history' => $prod['total_history'],
                    'record_history' => $prod['record_history'],
                    'date_start' => $prod['date_start'],
                ];
            }
            $list_date_return['data'][] = [
                'date'      => $date_list,
                'available' => count($treatment_list_date),
                'treatment' => $treatment_list_date
            ];

        }

        return $this->ok('success', $list_date_return);
    }

    public function listAll($outlet):JsonResponse
    {

        $custom = [];
        $all_products = 1;

        $get_treatments = [];
        $make_new = false;
        $check_json = file_exists(storage_path() . "/json/get_treatment_all.json");
        if($check_json){
            $config = json_decode(file_get_contents(storage_path() . "/json/get_treatment_all.json"), true);
            if(isset($config[$outlet['id']])){
                if(date('Y-m-d H:i', strtotime($config[$outlet['id']]['updated_at']. ' +6 hours')) <= date('Y-m-d H:i')){
                    $make_new = true;
                }
            }else{
                $make_new = true;
            }
        }else{
            $make_new = true;
        }

        if($make_new){

            $products = Product::with(['global_price','outlet_price' => function($outlet_price) use ($outlet){
                $outlet_price->where('outlet_id',$outlet['id']);
            }])->whereHas('outlet_treatment', function($outlet_treatment) use ($outlet, $all_products, $custom){

                $outlet_treatment->where('outlet_id',$outlet['id']);
                if(count($custom) > 0 && $all_products == 0){
                    $outlet_treatment->whereIn('treatment_id',$custom);
                }
            });

            $products = $products->treatment()->get()->toArray();

            $config[$outlet['id']] = [
                'updated_at' => date('Y-m-d H:i'),
                'data'       => $products
            ];
            file_put_contents(storage_path('/json/get_treatment_all.json'), json_encode($config));

        }

        $config = $config[$outlet['id']] ?? [];

        $get_treatments = $config['data'] ?? [];

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
                'can_new' => true,
                'total_history' => 0,
                'record_history' => []
            ];

            return $data;
        },$get_treatments ?? []);

        $return = [
            'available' => count($products),
            'recent_history' => [],
            'treatment' => $products,
        ];

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
            'record_history' => [],
            'date_start' => null
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
                'record_history' => [],
                'date_start' => null
            ];
            $list_dates[] = $date;
        }

        $return = [
            'available' => count($new),
            'treatment' => $new,
            'list_dates' => $list_dates,
        ];

        if(isset($post['id_customer'])){
            $customerPatient = TreatmentPatient::with(['steps' => function($steps){
                $steps->latest('step');
            }])
            ->where('patient_id', $post['id_customer'])
            ->where('status', '<>', 'Finished')
            ->get()->toArray();

            $return['treatment'] = array_map(function($value) use($customerPatient){

                foreach($customerPatient ?? [] as $cp){
                    if($value['id'] == $cp['treatment_id'] && (date('Y-m-d',strtotime($cp['expired_date'])) >= date('Y-m-d',strtotime($value['date'])))){
                        if($cp['steps'][0]['step'] < $cp['step']){
                            $value['can_continue'] = true;
                            $value['record_history'] = [
                                'start' => $cp['steps'][0]['step'],
                                'total' => $cp['step'],
                                'from' => $cp['steps'][0]['step'].'/'.$cp['step'],
                                'to' => ($cp['steps'][0]['step']+1).'/'.$cp['step'],
                                'expired_date' => date('Y-m-d',strtotime($cp['expired_date']))
                            ];
                            $value['date_start'] = date('Y-m-d',strtotime($cp['start_date']));

                        }
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

            $customerPatient = TreatmentPatient::with(['steps' => function($steps){
                $steps->latest('step');
            }])
            ->where('patient_id', $order['patient_id'])
            ->where('status', '<>', 'Finished')
            ->get()->toArray();

            $return['treatment'] = array_map(function($value) use($customerPatient){

                foreach($customerPatient ?? [] as $cp){
                    if($value['id'] == $cp['treatment_id'] && (date('Y-m-d',strtotime($cp['expired_date'])) >= date('Y-m-d',strtotime($value['date'])))){
                        if($cp['steps'][0]['step'] < $cp['step']){
                            $value['can_continue'] = true;
                            $value['record_history'] = [
                                'start' => $cp['steps'][0]['step'],
                                'total' => $cp['step'],
                                'from' => $cp['steps'][0]['step'].'/'.$cp['step'],
                                'to' => ($cp['steps'][0]['step']+1).'/'.$cp['step'],
                                'expired_date' => date('Y-m-d',strtotime($cp['expired_date']))
                            ];
                            $value['date_start'] = date('Y-m-d',strtotime($cp['start_date']));
                        }
                        $value['can_new'] = false;
                    }
                }
                $value['total_history'] = count($customerPatient);

                return $value;
            },$return['treatment'] ?? []);
        }
        return $this->ok('success', $return);
    }

    public function customerHistory(Request $request):mixed
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
            'treatment',
            'doctor',
            'steps' => function($steps){
                $steps->orderBy('step', 'desc');
            },
            'steps.order_product'
        ])->whereHas('doctor')
        ->whereNotNull('doctor_id')
        ->where('patient_id', $customer_id)
        ->get()->toArray();

        $config[$customer_id] = [
            'updated_at' => date('Y-m-d H:i'),
            'data'       => $histories
        ];

        $return = [];
        foreach($histories ?? [] as $key => $history){

            $steps = [];
            foreach($history['steps'] ?? [] as $key2 => $step){
                $steps[] = [
                    'index' => $step['step'] == 1 ? '1st Treatment' : ($step['step'] == 2 ? '2nd Treatment' : ($step['step'] == 3 ? '3rd Treatment' : ($step['step'] >= 4 ? $step['step'].'th Treatment' : ''))),
                    'date' => date('y-m-d, H:i',strtotime($step['date'])).' WIB',
                    'date_text' => date('d F Y, H:i',strtotime($step['date'])).' WIB',
                    'queue' => isset($step['order_product']['queue_code']) ? 'Queue Number '.$step['order_product']['queue_code'] : '-',
                ];
            }

            $continue = true;
            if($history['status'] == 'Finished'){
                $continue = false;
            }
            if(date('Y-m-d', strtotime($history['expired_date'])) < date('Y-m-d')){
                $continue = false;
            }
            if(count($history['steps']) > 0 && $history['steps'][0]['step'] >= $history['step']){
                $continue = false;
            }

            $return[] = [
                'id_treatment' => $history['treatment']['id'],
                'treatment_name' => $history['treatment']['product_name'],
                'doctor_name' => 'By '.$history['doctor']['name'],
                'start_treatment' => date('Y-m-d', strtotime($history['start_date'])),
                'start_treatment_text' => date('d F Y', strtotime($history['start_date'])),
                'expired_treatment' => date('Y-m-d', strtotime($history['expired_date'])),
                'expired_treatment_text' => date('d F Y', strtotime($history['expired_date'])),
                'suggestion' => $history['suggestion'],
                'progress' => $history['status'] == 'Finished' ? 'Finished' : ($history['steps'][0]['step'] ?? 0).'/'. $history['step'].' Continue Treatment',
                'can_continue' => $continue,
                'step' => $steps,
            ];
        }
        return $this->ok('success', $return);
    }

    public function cronCheckTreatment()
    {
        $log = MyHelper::logCron('Check Treatment Patient');
        try {

            $treatment_patients = TreatmentPatient::with(['steps' => function($step){$step->where('status', 'Pending');}])
            ->whereHas('doctor')
            ->whereHas('patient')
            ->whereHas('treatment')
            ->where('status', '<>', 'Finished')
            ->get();

            DB::beginTransaction();

            foreach($treatment_patients ?? [] as $key => $treatment_patient){
                if(date('Y-m-d', strtotime($treatment_patient['expired_date'])) < date('Y-m-d')){

                    foreach($treatment_patient['steps'] ?? [] as $key_2 => $step){
                        $delete_step = $step->delete();

                        if(!$delete_step){
                            DB::rollBack();
                            $log->fail('Failed deleted step');
                        }
                    }

                    $update = $treatment_patient->update([
                        'status' => 'Finished'
                    ]);

                    if(!$update){
                        DB::rollBack();
                        $log->fail('Failed update treatment patient');
                    }
                }
            }

            DB::commit();
            $log->success();
        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        }
    }
}
