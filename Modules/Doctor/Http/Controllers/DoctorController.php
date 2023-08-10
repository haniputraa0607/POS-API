<?php

namespace Modules\Doctor\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Lib\MyHelper;
use Illuminate\Http\JsonResponse;
use Modules\Outlet\Http\Controllers\OutletController;
use Modules\User\Entities\User;
use Modules\Doctor\Entities\DoctorShift;
use Modules\Doctor\Entities\DoctorSchedule;
use Modules\Doctor\Entities\DoctorScheduleDate;
use Modules\Order\Entities\OrderConsultation;
use Modules\Order\Entities\Order;
use Modules\Order\Entities\OrderProduct;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Setting;
use Modules\Customer\Entities\Customer;
use DateTime;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductOutletStock;
use Modules\Product\Http\Controllers\ProductController;
use App\Jobs\GenerateQueueOrder;

class DoctorController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function home(Request $request):JsonResponse
    {

        $doctor = $request->user();
        $outlet = $doctor->outlet;

        $status_outlet = true;
        $status_doctor = true;
        $order = [];
        $schedule = $outlet->outlet_schedule->where('day', date('l'))->first();
        if(!$schedule){
            $status_outlet = false;
        }elseif($schedule['is_closed'] == 1){
            $status_outlet = false;
        }elseif(($schedule['open'] && date('H:i') < date('H:i', strtotime($schedule['open']))) || ($schedule['close'] && date('H:i') > date('H:i', strtotime($schedule['close'])))){
            $status_outlet = false;
        }

        $schedule_doc = DoctorScheduleDate::whereHas('doctor_schedule', function($sch) use($doctor){
            $sch->where('user_id', $doctor['id']);
        })->whereDate('date', date('Y-m-d'))->first();
        if(!$schedule_doc){
            $status_doctor = false;
        }

        $shift = DoctorShift::where('user_id', $doctor['id'])->where('day', date('l'))->whereTime('start', '<=', date('H:i'))->whereTime('end', '>=', date('H:i'))->first();
        if(!$shift){
            $status_doctor = false;
        }

        $timezone = $outlet->district->province['timezone'] ?? 7;
        $on_progress = false;
        $queue = null;
        $id_order = null;

        if($status_outlet && $status_doctor && $shift){
            $order = OrderConsultation::whereHas('order', function($order) use($outlet){
                $order->where('outlet_id', $outlet['id'])
                ->where('is_submited', 1)
                ->where('send_to_transaction', 0);
            })
            ->whereDate('schedule_date', date('Y-m-d'))
            ->where('doctor_id', $doctor['id'])
            ->where('doctor_shift_id', $shift['id'])
            ->orderBy('queue', 'asc')
            ->get()->toArray();

            $ready = false;
            foreach($order ?? [] as $ord){
                if($ord['status'] == 'On Progress'){
                    $on_progress = true;
                    $queue = $ord['queue_code'];
                    $id_order = $ord['order_id'];

                }
                if($ord['status'] == 'Ready'){
                    $ready = true;
                }

            }

            if($order && !$on_progress && !$ready){
                OrderConsultation::where('id', $order[0]['id'])->update(['status' => 'Ready']);
            }
        }

        $data = [
            'status_outlet' => $status_outlet,
            'status_doctor' => $status_doctor,
            'clock' => $on_progress ? 15 * 60 : null,
            'status_queue' => count($order) > 0 ? ($on_progress ? $queue : count($order).' QUEUE') : 'VACANT',
            'id_order' => $on_progress ? $id_order : null,
            'is_outline' => $on_progress ? false : true,
            'is_vacant' => count($order) > 0 ? false : true,
            'doctor' => [
                'id' => $doctor['id'],
                'name' => $doctor['name'],
            ],
        ];

        return $this->ok('', $data);

    }

    public function listService(Request $request):JsonResponse
    {

        $doctor = $request->user();
        $outlet = $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $outlet_service = json_decode($outlet['activities'], true) ?? [];
        $data['service'] = [];
        $default_icon = config('default_icon') ?? [];

        foreach($outlet_service ?? [] as $key => $serv){

            $data['service'][] = [
                'icon' => $serv == 'consultation' ? ($default_icon['overview']['icon_inactive'] ?? null) : ($default_icon[$serv]['icon_inactive'] ?? null),
                'icon_active' => $serv == 'consultation' ? ($default_icon['overview']['icon_active'] ?? null) : ($default_icon[$serv]['icon_active'] ?? null),
                'title' => $serv == 'consultation' ? 'Overview' : ($serv == 'prescription' ? 'Prescription' : ($serv == 'product' ? 'Product' : ($serv == 'treatment' ? 'Treatment' : ''))),
                'key' => $serv == 'consultation' ? 1 : ($serv == 'prescription' ? 2 : ($serv == 'product' ? 3 : ($serv == 'treatment' ? 4 : 0)))
            ];

        }

        return $this->ok('', $data);

    }

    public function splash(Request $request):JsonResponse
    {
        $splash = Setting::where('key', '=', 'splash_doctor_apps')->first();
        $duration = Setting::where('key', '=', 'splash_doctor_apps_duration')->pluck('value')->first();

        if(!empty($splash)){
            $splash = env('STORAGE_URL_API').$splash['value'];
        } else {
            $splash = null;
        }
        $ext=explode('.', $splash);
        $result = [
            'splash_screen_url' => $splash."?update=".time(),
            'splash_screen_duration' => $duration??5,
            'splash_screen_ext' => '.'.end($ext)
        ];
        return $this->ok('', $result);
    }

    public function nextQueue(Request $request):mixed
    {
        $doctor = $request->user();
        $outlet = $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $shift = DoctorShift::where('user_id', $doctor['id'])->where('day', date('l'))->whereTime('start', '<=', date('H:i'))->whereTime('end', '>=', date('H:i'))->first();
        if(!$shift){
            return $this->error('Shift not found');
        }

        $order_consultation = OrderConsultation::with(['order'])->whereHas('order', function($order) use($outlet){
            $order->where('outlet_id', $outlet['id'])
            ->where('is_submited', 1)
            ->where('send_to_transaction', 0);
        })
        ->whereDate('schedule_date', date('Y-m-d'))
        ->where('doctor_id', $doctor['id'])
        ->where('doctor_shift_id', $shift['id'])
        ->where('status', 'Ready')
        ->orderBy('queue', 'asc')
        ->first();

        if(!$order_consultation){
            $order_consultation = OrderConsultation::with(['order'])->whereHas('order', function($order) use($outlet){
                $order->where('outlet_id', $outlet['id'])
                ->where('is_submited', 1)
                ->where('send_to_transaction', 0);
            })
            ->whereDate('schedule_date', date('Y-m-d'))
            ->where('doctor_id', $doctor['id'])
            ->where('doctor_shift_id', $shift['id'])
            ->where('status', 'On Progress')
            ->orderBy('queue', 'asc')
            ->first();
        }

        if($order_consultation){
            $order_consultation_after = OrderConsultation::where('doctor_id', $order_consultation['doctor_id'])->where('doctor_shift_id', $order_consultation['doctor_shift_id'])->whereDate('schedule_date', date('Y-m-d', strtotime($order_consultation['schedule_date'])))->where('status', '<>', 'Finished')->update(['status' => 'Pending']);
            return $this->getDataOrder([
                'order_id' => $order_consultation['order_id'],
                'order_consultation' => $order_consultation
            ],'');
        }

        return $this->error('Order not found');

    }

    public function getDataOrder($data, $message):mixed
    {
        $id_order = $data['order_id'];
        $id_order_consultation = $data['order_consultation']['id'];

        $return = [
            'summary' => [
                [
                    'label' => 'Subtotal',
                    'value' => 0
                ],
                [
                    'label' => 'Tax',
                    'value' => 0
                ],
                [
                    'label' => 'Payable Ammount',
                    'value' => 0
                ],
            ],
        ];

        DB::beginTransaction();
        $order = Order::with(['order_products.product', 'order_consultations.consultation.patient_diagnostic.diagnostic', 'order_consultations.consultation.patient_grievance.grievance', 'order_consultations.shift', 'order_consultations.doctor'])->where('id', $id_order)
        ->where('send_to_transaction', 0)
        ->latest()
        ->first();

        if($order){

            $user = Customer::where('id', $order['patient_id'])->first();
            if(!$user){
                return $this->error('Customer not found');
            }

            $ord_prod = [];
            $ord_treat = [];
            $ord_consul = [];
            foreach($order['order_products'] ?? [] as $key => $ord_pro){

                if($ord_pro['type'] == 'Product'){
                    $ord_prod[] = [
                        'order_product_id' => $ord_pro['id'],
                        'product_id'       => $ord_pro['product']['id'],
                        'product_name'     => $ord_pro['product']['product_name'],
                        'qty'              => $ord_pro['qty'],
                        'price_total'      => $ord_pro['order_product_grandtotal'],
                    ];
                }elseif($ord_pro['type'] == 'Treatment'){
                    $ord_treat[] = [
                        'order_product_id' => $ord_pro['id'],
                        'product_id'       => $ord_pro['product']['id'],
                        'product_name'     => $ord_pro['product']['product_name'],
                        'schedule_date'    => date('d F Y', strtotime($ord_pro['schedule_date'])),
                        'price_total'      => $ord_pro['order_product_grandtotal'],
                        'queue'            => $ord_pro['queue_code'],
                    ];
                }
            }

            foreach($order['order_consultations'] ?? [] as $key => $ord_con){

                $consul = [];
                $is_submit = 0;
                if($ord_con['consultation'] && $ord_con['consultation']['session_end'] == 1){
                    $consul['queue_number']  = $ord_con['queue_code'];
                    $consul['schedule_date'] = date('d F Y', strtotime($ord_con['schedule_date']));
                    $consul['grievance'] = [];
                    $consul['diagnostic'] = [];

                    foreach($ord_con['consultation']['patient_grievance'] ?? [] as $grievance){
                        $consul['grievance'][] = $grievance['grievance']['grievance_name'];
                    }
                    foreach($ord_con['consultation']['patient_diagnostic'] ?? [] as $diagnostic){
                        $consul['diagnostic'][] = $diagnostic['diagnostic']['diagnostic_name'];
                    }
                    $is_submit = 1;

                }
                $ord_consul[] = [
                    'order_consultation_id' => $ord_con['id'],
                    'doctor_id'             => $ord_con['doctor']['id'],
                    'doctor_name'           => $ord_con['doctor']['name'],
                    'schedule_date'         => date('d F Y', strtotime($ord_con['schedule_date'])),
                    'time'                  => date('H:i', strtotime($ord_con['shift']['start'])).'-'.date('H:i', strtotime($ord_con['shift']['end'])),
                    'price_total'           => $ord_con['order_consultation_grandtotal'],
                    'queue'                 => $ord_con['queue_code'],
                    'is_submit'             => $is_submit,
                    'consultation'          => $consul,
                ];
            }

            $bithdayDate = new DateTime($user['birth_date']);
            $now = new DateTime();
            $interval = $now->diff($bithdayDate)->y;

            $return = [
                'user'                => [
                    'id'    => $user['id'],
                    'name'  => $user['name'],
                    'age'   => $interval.' years',
                    'phone' => substr_replace($user['phone'], str_repeat('x', (strlen($user['phone']) - 7)), 4, (strlen($user['phone']) - 7)),
                ],
                'order_id'            => $order['id'],
                'order_code'          => $order['order_code'],
                'order_products'      => $ord_prod,
                'order_treatments'    => $ord_treat,
                'order_consultations' => $ord_consul,
                'order_precriptions'  => [],
                'summary'             => [
                    [
                        'label' => 'Subtotal',
                        'value' => $order['order_subtotal']
                    ],
                    [
                        'label' => 'Tax',
                        'value' => (float)$order['order_tax']
                    ],
                    [
                        'label' => 'Payable Ammount',
                        'value' => $order['order_grandtotal']
                    ],
                ],
            ];
        }

        $order_consultation = OrderConsultation::where('id', $id_order_consultation)->first();
        $update = $order_consultation->update(['status' => 'On Progress']);
        if(!$update){
            DB::rollBack();
            return $this->error('Failed to get data order');
        }
        $order_consultation_after = OrderConsultation::where('id', '<>', $order_consultation['id'])->where('doctor_id', $order_consultation['doctor_id'])->where('doctor_shift_id', $order_consultation['doctor_shift_id'])->whereDate('schedule_date', date('Y-m-d', strtotime($order_consultation['schedule_date'])))->where('status', 'Pending')->orderBy('queue', 'asc')->get()->toArray();
        $check_after = false;
        if($order_consultation_after){
            foreach($order_consultation_after ?? [] as $key => $after){
                if($after['queue'] > $order_consultation['queue']){
                    $queue_after = $after['id'];
                    $check_after = true;
                    break;
                }
            }
            if($check_after){
                $update_after = OrderConsultation::where('id', $queue_after)->update(['status' => 'Ready']);
            }else{
                $update_after = OrderConsultation::where('id', $order_consultation_after[0]['id'])->update(['status' => 'Ready']);
            }
            if(!$update_after){
                DB::rollBack();
                return $this->error('Failed to get data order');
            }
        }

        DB::commit();

        return $this->ok($message, $return);

    }

    public function getDoctor(Request $request):mixed
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

        $date = false;
        if($post['search']['filter'] == 'date'){
            $date = date('Y-m-d', strtotime($post['search']['value']));
        }

        $doctors = User::where('outlet_id', $outlet['id']);
        if($date){
            $doctor = $doctors->with(['doctor_schedules.schedule_dates' => function($query) use($date){
                if($date){
                    $query->where('doctor_schedule_dates.date', $date);
                }
            }])
            ->whereHas('doctor_schedules.schedule_dates',function($query) use($date){
                if($date){
                    $query->where('schedule_month', date('m', strtotime($date)));
                    $query->where('schedule_year', date('Y', strtotime($date)));
                    $query->where('doctor_schedule_dates.date', $date);
                }
            });
        }

        if($post['search']['filter'] == 'name'){
            $doctors = $doctors->where('name', 'like', '%'.$post['search']['value'].'%');
        }

        $doctors = $doctors->doctor()->get()->toArray();

        $list_doctors = [];
        foreach($doctors ?? [] as $key => $doc){

            $doc_shift = [];
            if($date){
                $day = date('l', strtotime($date));
                $shifts = DoctorShift::with(['order_consultations' => function($order_consultation) use($date){
                    $order_consultation->whereDate('schedule_date', $date);
                }])->where('user_id', $doc['id'])->where('day',$day)->get()->toArray();
                foreach($shifts ?? [] as $key_2 =>$shift){
                    $doc_shift[] = [
                        'id_doctor_shift' => $shift['id'],
                        'time'            => date('H:i',strtotime($shift['start'])).' - '.date('H:i',strtotime($shift['end'])),
                        'price'           => $shift['price'],
                        'quote'           => $shift['quota'] - count($shift['order_consultations'])
                    ];
                }

                if(isset($doc['doctor_schedules']) && !empty($doc_shift)){
                    $list_doctors[] = [
                        'id_doctor' => $doc['id'],
                        'name'      => $doc['name'],
                        'image_url' => isset($doc['image_url']) ? env('STORAGE_URL_API').$doc['image_url'] : env('STORAGE_URL_DEFAULT_IMAGE').'default_image/default_doctor.png',
                        'shifts'    => $doc_shift
                    ];
                }
            }else{
                $list_doctors[] = [
                    'id_doctor' => $doc['id'],
                    'name'      => $doc['name'],
                    'image_url' => isset($doc['image_url']) ? env('STORAGE_URL_API').$doc['image_url'] : env('STORAGE_URL_DEFAULT_IMAGE').'default_image/default_doctor.png',
                    'shifts'    => $doc_shift
                ];
            }

        }
        $return = [
            'available'    => count($list_doctors),
            'list_doctors' => $list_doctors,
        ];
        return $this->ok('', $return);

    }

    public function getDoctorDate(Request $request):mixed
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $date_now = date('Y-m-d');
        $dates = MyHelper::getListDate(date('d'),date('m'),date('Y'));

        $doctors = User::with(['doctor_schedules' => function($query) use($date_now, $dates){
            $query->where('schedule_month', date('m', strtotime($date_now)));
            $query->where('schedule_year', date('Y', strtotime($date_now)));
            $query->with(['schedule_dates' => function($query2) use($date_now, $dates){
                $query2->where('date', '>=' ,$dates['start']);
                $query2->where('date', '<=' ,$dates['end']);
            }]);
        }])
        ->whereHas('doctor_schedules.schedule_dates',function($query) use($date_now, $dates){
            $query->where('schedule_month', date('m', strtotime($date_now)));
            $query->where('schedule_year', date('Y', strtotime($date_now)));
            $query->where('date', '>=' ,$dates);
            $query->where('date', '<=' ,$dates);
        })->where('outlet_id', $outlet['id'])
        ->where('id', $post['id'])->doctor()->first();

        $list_doctors = [];
        $list_dates = [];
        foreach($doctors['doctor_schedules'][0]['schedule_dates'] ?? [] as $key => $schedule_dates){

            $doc_shift = [];
            $day = date('l', strtotime($schedule_dates['date']));
            $shifts = DoctorShift::with(['order_consultations' => function($order_consultation) use($schedule_dates){
                $order_consultation->whereDate('schedule_date', $schedule_dates['date']);
            }])->where('user_id', $doctors['id'])->where('day',$day)->get()->toArray();

            foreach($shifts ?? [] as $key_2 =>$shift){
                $doc_shift[] = [
                    'id_doctor_shift' => $shift['id'],
                    'time'            => date('H:i',strtotime($shift['start'])).' - '.date('H:i',strtotime($shift['end'])),
                    'quote'           => $shift['quota'] - count($shift['order_consultations'])
                ];
            }
            if(isset($doctors['doctor_schedules']) && !empty($doc_shift)){
                $list_doctors[] = [
                    'id_doctor' => $doctors['id'],
                    'name'      => $doctors['name'],
                    'date'      => date('d F Y', strtotime($schedule_dates['date'])),
                    'image_url' => isset($doctors['image_url']) ? env('STORAGE_URL_API').$doctors['image_url'] : null,
                    'shifts'    => $doc_shift
                ];
                $list_dates[] = date('Y-m-d', strtotime($schedule_dates['date']));
            }

        }
        $return = [
            'available'    => count($list_doctors),
            'list_doctors' => $list_doctors,
            'list_dates'   => $list_dates,
        ];
        return $this->ok('', $return);

    }

    public function getOrder(Request $request):mixed
    {
        $post = $request->json()->all();
        $doctor = $request->user();
        $outlet =  $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(isset($post['id_order'])){

            $order_consultation = OrderConsultation::whereHas('order', function($order) use($outlet){
                $order->where('outlet_id', $outlet['id'])
                ->where('is_submited', 1)
                ->where('send_to_transaction', 0);
            })
            ->where('order_id', $post['id_order'])
            ->whereDate('schedule_date', date('Y-m-d'))
            ->where('status', 'On Progress')
            ->orderBy('queue', 'asc')
            ->first();

            if($order_consultation){
                return $this->getDataOrder([
                    'order_id' => $order_consultation['order_id'],
                    'order_consultation' => $order_consultation
                ],'');
            }else{
                return $this->error('Order not found');
            }
        }

        return $this->ok('', $return);

    }

    public function addOrder(Request $request):mixed
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(isset($post['id_order'])){

            $order = Order::with(['order_consultations'])->where('id', $post['id_order'])
            ->where('send_to_transaction', 0)
            ->latest()
            ->first();

            DB::beginTransaction();
            if(!$order){
                return $this->error('Order not found');
            }

            if(($post['type']??false) == 'product' || ($post['type']??false) == 'treatment'){
                $product = Product::with(['global_price','outlet_price' => function($outlet_price) use ($outlet){$outlet_price->where('outlet_id',$outlet['id']);}])
                ->where('id', $post['order']['id'])->first();

                if(!$product){
                    DB::rollBack();
                    return $this->error('Product not found');
                }

                $price = $product['outlet_price'][0]['price'] ?? $product['global_price']['price'];

                if(($post['type']??false) == 'product'){
                    $order_product = OrderProduct::where('order_id', $order['id'])->where('product_id', $product['id'])->first();
                    if($order_product){
                        $order_product->update([
                            'qty'                      => $order_product['qty'] + $post['order']['qty'],
                            'order_product_price'      => $price,
                            'order_product_subtotal'   => $order_product['order_product_subtotal'] + ($post['order']['qty']*$price),
                            'order_product_grandtotal' => $order_product['order_product_grandtotal'] + ($post['order']['qty']*$price),
                        ]);
                    }else{
                        $order_product = OrderProduct::create([
                            'order_id'                 => $order['id'],
                            'product_id'               => $product['id'],
                            'type'                     => $product['type'],
                            'qty'                      => $post['order']['qty'],
                            'order_product_price'      => $price,
                            'order_product_subtotal'   => $post['order']['qty']*$price,
                            'order_product_grandtotal' => $post['order']['qty']*$price,
                        ]);
                    }

                    $price_to_order = ($post['order']['qty']*$price);
                    if(!$order_product){
                        DB::rollBack();
                        return $this->error('Product not found');
                    }

                    $stock = ProductOutletStock::where('product_id', $product['id'])->where('outlet_id', $outlet['id'])->first();
                    if($stock){
                        $old_stock = clone $stock;
                        $stock->update([
                            'stock' =>  $stock['stock']-$post['order']['qty']
                        ]);

                        if(!$stock){
                            DB::rollBack();
                            return $this->error('Failed to update stock');
                        }

                        (new ProductController)->addLogProductStockLog($old_stock['id'], -$post['order']['qty'], $old_stock['stock'], $stock['stock'], 'Booking Order', null);
                    }

                    $send = [
                        'order_id'          => $order['id'],
                        'order_product_id'  => $order_product['id'],
                        'outlet_id'         => $outlet['id'],
                        'type'              => 'product',
                    ];
                }else{
                    $order_product = OrderProduct::where('order_id', $order['id'])->where('product_id', $product['id'])->whereDate('schedule_date',$post['order']['date'])->first();
                    if($order_product){
                        return $this->getDataOrder(['id_customer' => $post['id_customer']], 'Treatment already exist in order');
                    }else{

                        if(($post['order']['continue']??false) == 1){
                            $customerPatient = TreatmentPatient::where('patient_id', $post['id_customer'])
                            ->where('treatment_id', $product['id'])
                            ->where('status', '<>', 'Finished')
                            ->first();

                        }else{
                            $customerPatient = TreatmentPatient::create([
                                'treatment_id' => $product['id'],
                                'patient_id' => $post['id_customer'],
                                'step' => 1,
                                'progress' => 0,
                                'status' => 'On Progress',
                                'start_date' => date('Y-m-d H:i:s'),
                                'timeframe' => 1,
                                'timeframe_type' => 'Day',
                                'expired_date' => date('Y-m-d H:i:s', strtotime('+1 days')),
                            ]);
                        }

                        if(!$customerPatient){
                            return $this->error('Invalid Error');
                        }

                        $customerPatientStep = TreatmentPatientStep::create([
                            'treatment_patient_id' => $customerPatient['id'],
                            'step'                 => $customerPatient['progress'] + 1,
                            'date'                 => date('Y-m-d H:i:s'),
                        ]);

                        if(!$customerPatientStep){
                            return $this->error('Invalid Error');
                        }

                        $create_order_product = OrderProduct::create([
                            'order_id'                 => $order['id'],
                            'product_id'               => $product['id'],
                            'type'                     => $product['type'],
                            'schedule_date'            => $post['order']['date'],
                            'treatment_patient_id'     => $customerPatient['id'] ?? null,
                            'qty'                      => 1,
                            'order_product_price'      => $price,
                            'order_product_subtotal'   => $price,
                            'order_product_grandtotal' => $price,
                        ]);

                        if(!$create_order_product){
                            DB::rollBack();
                            return $this->error('Treatment not found');
                        }

                        $price_to_order = $price;
                        $send = [
                            'order_id'          => $order['id'],
                            'order_product_id'  => $create_order_product['id'],
                            'outlet_id'         => $outlet['id'],
                            'type'              => 'treatment',
                            'schedule_date'     => date('Y-m-d', strtotime($create_order_product['schedule_date']))
                        ];
                    }
                }

                $order->update([
                    'order_subtotal'   => $order['order_subtotal'] + $price_to_order,
                    'order_gross'      => $order['order_gross'] + $price_to_order,
                    'order_grandtotal' => $order['order_grandtotal'] + $price_to_order,
                ]);

                DB::commit();

                $generate = GenerateQueueOrder::dispatch($send)->onConnection('generatequeueorder');
                return $this->getDataOrder([
                    'order_id' => $order['id'],
                    'order_consultation' => $order['order_consultations'][0]
                ],'Succes to add new order');

            }elseif(($post['type']??false) == 'consultation'){

                $doctor = User::with(['doctor_shifts' => function($query) use($post){
                    $query->where('id', $post['order']['id_shift']);
                }])
                ->whereHas('doctor_shifts',function($query) use($post){
                    $query->where('id', $post['order']['id_shift']);
                })
                ->whereHas('doctor_schedules.schedule_dates',function($query) use($post){
                    $query->where('schedule_month', date('m', strtotime($post['order']['date'])));
                    $query->where('schedule_year', date('Y', strtotime($post['order']['date'])));
                    $query->where('doctor_schedule_dates.date', date('Y-m-d', strtotime($post['order']['date'])));
                })
                ->where('id', $post['order']['id'])->first();

                if(!$doctor){
                    DB::rollBack();
                    return $this->error('Doctor not found');
                }

                $order_consultation = OrderConsultation::where('order_id', $order['id'])->first();
                if($order_consultation){
                    return $this->getDataOrder(['id_customer' => $post['id_customer']], 'Consultation already exist in order');
                }else{

                    $price = $doctor['doctor_shifts'][0]['price'] ?? $doctor['consultation_price'] ?? $outlet['consultation_price'];
                    $create_order_consultation = OrderConsultation::create([
                        'order_id'                 => $order['id'],
                        'doctor_id'                => $doctor['id'],
                        'schedule_date'            => $post['order']['date'],
                        'doctor_shift_id'          => $post['order']['id_shift'],
                        'order_consultation_price'      => $price,
                        'order_consultation_subtotal'   => $price,
                        'order_consultation_grandtotal' => $price,
                    ]);

                    if(!$create_order_consultation){
                        DB::rollBack();
                        return $this->error('Treatment not found');
                    }

                    $send = [
                        'order_id'          => $order['id'],
                        'order_product_id'  => $create_order_consultation['id'],
                        'outlet_id'         => $outlet['id'],
                        'type'              => 'consultation',
                        'schedule_date'     => date('Y-m-d', strtotime($create_order_consultation['schedule_date'])),
                        'doctor_id'         => $doctor['id']
                    ];

                    $order->update([
                        'order_subtotal'   => $order['order_subtotal'] + $price,
                        'order_gross'      => $order['order_gross'] + $price,
                        'order_grandtotal' => $order['order_grandtotal'] + $price,
                    ]);

                    DB::commit();

                    $generate = GenerateQueueOrder::dispatch($send)->onConnection('generatequeueorder');
                    return $this->getDataOrder(['id_customer' => $post['id_customer']], 'Succes to add new order');

                }

            }else{
                return $this->error('Type is invalid');
            }

        }else{
            return $this->error('Customer not found');
        }

    }

    public function editOrder(Request $request):mixed
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(!isset($post['id_order'])){
            return $this->error('Order not found');
        }

        if(($post['qty']??false) == 0){

            return $this->deleteOrderData([
                'outlet' => $outlet,
                'type' => $post['type'] ?? null,
                'post' => [
                    'id_order' => $post['id_order'],
                    'id' => $post['id']
                ]
            ]);

        }elseif(($post['qty']??false) >= 1){

            if(($post['type']??false) == 'product'){

                DB::beginTransaction();
                $order_product = OrderProduct::with(['order.order_consultations'])->whereHas('order', function($order) use($post){
                    $order->where('id', $post['id_order']);
                    $order->where('send_to_transaction', 0);
                })->whereHas('product')
                ->where('id', $post['id'])->first();

                if(!$order_product){
                    DB::rollBack();
                    return $this->error('Order not found');
                }

                if($post['qty']>$order_product['qty']){

                    $old_order_product = clone $order_product;
                    $order_product->update([
                        'qty'                      => $post['qty'],
                        'order_product_subtotal'   => ($post['qty']*$order_product['order_product_price']),
                        'order_product_grandtotal' => ($post['qty']*$order_product['order_product_price']),
                    ]);

                    $order = Order::where('id', $order_product['order_id'])->update([
                        'order_subtotal'   => $order_product['order']['order_subtotal'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_subtotal']),
                        'order_gross'      => $order_product['order']['order_gross'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_subtotal']),
                        'order_grandtotal' => $order_product['order']['order_grandtotal'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_grandtotal']),
                    ]);

                }elseif($post['qty']<$order_product['qty']){

                    $old_order_product = clone $order_product;
                    $order_product->update([
                        'qty'                      => $post['qty'],
                        'order_product_subtotal'   => ($post['qty']*$order_product['order_product_price']),
                        'order_product_grandtotal' => ($post['qty']*$order_product['order_product_price']),
                    ]);

                    $order = Order::where('id', $order_product['order_id'])->update([
                        'order_subtotal'   => $order_product['order']['order_subtotal'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_subtotal']),
                        'order_gross'      => $order_product['order']['order_gross'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_subtotal']),
                        'order_grandtotal' => $order_product['order']['order_grandtotal'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_grandtotal']),
                    ]);

                }else{
                    return $this->getDataOrder([
                        'order_id' => $order_product['order_id'],
                        'order_consultation' => $order_product['order']['order_consultations'][0]
                    ],'Succes to add new order');
                }

                if(!$order){
                    DB::rollBack();
                    return $this->error('Order not found');
                }

                $stock = ProductOutletStock::where('product_id', $order_product['product_id'])->where('outlet_id', $outlet['id'])->first();
                if($stock){
                    $old_stock = clone $stock;
                    if($post['qty']>$old_order_product['qty']){
                        $qty = $post['qty'] - $old_order_product['qty'];
                        $qty_log = -$qty;
                        $stock->update([
                            'stock' =>  $stock['stock']-$qty
                        ]);
                    }elseif($post['qty']<$old_order_product['qty']){
                        $qty = $old_order_product['qty'] - $post['qty'];
                        $qty_log = $qty;
                        $stock->update([
                            'stock' =>  $stock['stock']+$qty
                        ]);
                    }

                    if(!$stock){
                        DB::rollBack();
                        return $this->error('Failed to update stock');
                    }
                    (new ProductController)->addLogProductStockLog($old_stock['id'], $qty_log, $old_stock['stock'], $stock['stock'], 'Update Booking Order', null);
                }

                DB::commit();
                return $this->getDataOrder([
                    'order_id' => $order_product['order_id'],
                    'order_consultation' => $order_product['order']['order_consultations'][0]
                ],'Succes to add new order');

            }else{
                return $this->error('Type is invalid');
            }

        }else{
            return $this->error('Qty can be null');
        }

    }

    public function deleteOrder(Request $request):mixed
    {

        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(!isset($post['id_order'])){
            return $this->error('Order not found');
        }

        return $this->deleteOrderData([
            'outlet' => $outlet,
            'type' => $post['type'] ?? null,
            'post' => [
                'id_order' => $post['id_order'],
                'id' => $post['id']
            ]
        ]);

    }

    public function deleteOrderData($data):mixed
    {
        $outlet =  $data['outlet'];
        $type =  $data['type'];
        $post =  $data['post'];


        if(($type??false) == 'product' || ($type??false) == 'treatment'){

            DB::beginTransaction();
            $order_product = OrderProduct::with(['order.order_consultations'])->whereHas('order', function($order) use($post){
                $order->where('order_id', $post['id_order']);
                $order->where('send_to_transaction', 0);
            })->whereHas('product')
            ->where('id', $post['id'])->first();

            if(!$order_product){
                DB::rollBack();
                return $this->error('Order not found');
            }

            $order = Order::where('id', $order_product['order_id'])->update([
                'order_subtotal'   => $order_product['order']['order_subtotal'] - $order_product['order_product_subtotal'],
                'order_gross'      => $order_product['order']['order_gross'] - $order_product['order_product_subtotal'],
                'order_grandtotal' => $order_product['order']['order_grandtotal'] - $order_product['order_product_grandtotal'],
            ]);

            if(!$order){
                DB::rollBack();
                return $this->error('Order not found');
            }

            if(($type??false) == 'product'){
                $stock = ProductOutletStock::where('product_id', $order_product['product_id'])->where('outlet_id', $outlet['id'])->first();
                if($stock){
                    $old_stock = clone $stock;
                    $stock->update([
                        'stock' =>  $stock['stock']+$order_product['qty']
                    ]);

                    if(!$stock){
                        DB::rollBack();
                        return $this->error('Failed to update stock');
                    }
                    (new ProductController)->addLogProductStockLog($old_stock['id'], $order_product['qty'], $old_stock['stock'], $stock['stock'], 'Cancel Booking Order', null);
                }
            }

            $delete_order_product = $order_product->delete();

            if($delete_order_product && ($type??false) == 'treatment' && $order_product['treatment_patient_id']){
                $delete_step =  TreatmentPatientStep::where('treatment_patient_id', $order_product['treatment_patient_id'])->where('status', 'Pending')->delete();
                if($delete_step){
                    $treatment_patient = TreatmentPatient::with(['steps'])->where('id', $order_product['treatment_patient_id'])->first();
                    if($treatment_patient){
                        if(count($treatment_patient['steps']) <= 0){
                            $delete_treatment_patient = $treatment_patient->delete();
                            if(!$delete_treatment_patient){
                                DB::rollBack();
                                return $this->error('Failed to delete treatment patient');
                            }
                        }
                    }else{
                        DB::rollBack();
                        return $this->error('Failed to get treatment patient');
                    }
                }else{
                    DB::rollBack();
                    return $this->error('Failed to delete step');
                }
            }

            DB::commit();
            return $this->getDataOrder([
                'order_id' => $order_product['order']['id'],
                'order_consultation' => $order_product['order']['order_consultations'][0]
            ],'Succes to add new order');

        }elseif(($type??false) == 'consultation'){

            DB::beginTransaction();
            $order_consultation = OrderConsultation::with(['order'])->whereHas('order', function($order) use($post){
                $order->where('patient_id', $post['id_customer']);
                $order->where('send_to_transaction', 0);
            })->whereHas('doctor')
            ->where('id', $post['id'])->first();

            if(!$order_consultation){
                DB::rollBack();
                return $this->error('Order not found');
            }

            $order = Order::where('id', $order_consultation['order_id'])->update([
                'order_subtotal'   => $order_consultation['order']['order_subtotal'] - $order_consultation['order_consultation_subtotal'],
                'order_gross'      => $order_consultation['order']['order_gross'] - $order_consultation['order_consultation_subtotal'],
                'order_grandtotal' => $order_consultation['order']['order_grandtotal'] - $order_consultation['order_consultation_grandtotal'],
            ]);

            if(!$order){
                DB::rollBack();
                return $this->error('Order not found');
            }

            $order_consultation->delete();

            DB::commit();
            return $this->getDataOrder(['id_customer' => $post['id_customer']], 'Succes to delete order');

        }else{
            return $this->error('Type is invalid');
        }
    }
}
