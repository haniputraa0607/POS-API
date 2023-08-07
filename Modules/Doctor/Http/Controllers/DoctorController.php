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

    public function nextQueue(Request $request):JsonResponse
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

    public function getDataOrder($data, $message):JsonResponse
    {
        $id_order = $data['order_id'];
        $id_order_consultation = $data['order_consultation']['id'];

        $return = [];
        DB::beginTransaction();
        $order = Order::with(['order_products.product', 'order_consultations.consultation.patient_diagnostic.diagnostic', 'order_consultations.consultation.patient_grievance.grievance', 'order_consultations.shift', 'order_consultations.doctor'])->where('id', $id_order)
        ->where('send_to_transaction', 0)
        ->latest()
        ->first();

        if($order){
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

            $return = [
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

    public function getDoctor(Request $request):JsonResponse
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

        $date = date('Y-m-d');
        if($post['search']['filter'] == 'date'){
            $date = date('Y-m-d', strtotime($post['search']['value']));
        }

        $doctors = User::with(['doctor_schedules.schedule_dates' => function($query) use($date){
            $query->where('doctor_schedule_dates.date', $date);
        }])
        ->whereHas('doctor_schedules.schedule_dates',function($query) use($date){
            $query->where('schedule_month', date('m', strtotime($date)));
            $query->where('schedule_year', date('Y', strtotime($date)));
            $query->where('doctor_schedule_dates.date', $date);
        })
        ->where('outlet_id', $outlet['id']);

        if($post['search']['filter'] == 'name'){
            $doctors = $doctors->where('name', 'like', '%'.$post['search']['value'].'%');
        }

        $doctors = $doctors->doctor()->get()->toArray();

        $list_doctors = [];
        foreach($doctors ?? [] as $key => $doc){

            $day = date('l', strtotime($date));
            $shifts = DoctorShift::with(['order_consultations' => function($order_consultation) use($date){
                $order_consultation->whereDate('schedule_date', $date);
            }])->where('user_id', $doc['id'])->where('day',$day)->get()->toArray();

            $doc_shift = [];
            foreach($shifts ?? [] as $key_2 =>$shift){
                $doc_shift[] = [
                    'id_doctor_shift' => $shift['id'],
                    'time'            => date('H:i',strtotime($shift['start'])).' - '.date('H:i',strtotime($shift['end'])),
                    'quote'           => $shift['quota'] - count($shift['order_consultations'])
                ];
            }

            if(isset($doc['doctor_schedules']) && !empty($doc_shift)){
                $list_doctors[] = [
                    'id_doctor' => $doc['id'],
                    'name'      => $doc['name'],
                    'date'      => date('j F Y',strtotime($date)),
                    'image_url' => isset($doc['image_url']) ? env('STORAGE_URL_API').$doc['image_url'] : null,
                    'shifts'    => $doc_shift
                ];
            }
        }
        $return = [
            'available' => count($list_doctors),
            'list_doctors' => $list_doctors,
        ];
        return $this->ok('', $return);

    }
}
