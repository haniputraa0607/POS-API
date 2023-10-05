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
use Modules\Order\Entities\OrderPrescription;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Setting;
use Modules\Customer\Entities\Customer;
use DateTime;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductOutletStock;
use Modules\Product\Http\Controllers\ProductController;
use Modules\Prescription\Http\Controllers\PrescriptionController;
use App\Jobs\GenerateQueueOrder;
use Modules\Customer\Entities\TreatmentPatient;
use Modules\Customer\Entities\TreatmentPatientStep;
use Modules\Prescription\Entities\Prescription;
use Modules\Prescription\Entities\PrescriptionOutlet;
use Modules\Prescription\Entities\PrescriptionOutletLog;
use Modules\Prescription\Entities\ContainerStock;
use Modules\Prescription\Entities\SubstanceStock;
use Modules\PatientGrievance\Entities\PatientGrievance;
use Modules\PatientDiagnostic\Entities\PatientDiagnostic;
use Modules\Consultation\Entities\Consultation;

class DoctorController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function home(Request $request):mixed
    {

        $doctor = $request->user();
        $outlet = $doctor->outlet;

        $status_outlet = true;
        $order = [];

        $make_new = false;
        $check_json = file_exists(storage_path() . "/json/outlet_status.json");
        $schedule = $outlet->outlet_schedule->where('day', date('l'))->first();

        if($check_json){
            $config = json_decode(file_get_contents(storage_path() . "/json/outlet_status.json"), true);
            if(isset($config[$outlet['id']]['schedule'][date('l')])){
                if(date('Y-m-d H:i', strtotime($config[$outlet['id']]['schedule'][date('l')]['updated_at']. ' +6 hours')) <= date('Y-m-d H:i')){
                    $make_new = true;
                }
            }else{
                $make_new = true;
            }
        }else{
            $make_new = true;
        }

        if($make_new){
            $schedule = $outlet->outlet_schedule->where('day', date('l'))->first();
            if(!$schedule){
                $config[$outlet['id']]['schedule'][date('l')] = [
                    'updated_at' => date('Y-m-d H:i'),
                    'status' => false
                ];
            }else{
                $config[$outlet['id']]['schedule'][date('l')] = [
                    'updated_at' => date('Y-m-d H:i'),
                    'status' => $schedule['is_closed'] == 1 ? false : true,
                    'open'   => $schedule['open'],
                    'close'  => $schedule['close'],
                ];
            }

            file_put_contents(storage_path('/json/outlet_status.json'), json_encode($config));
        }
        $config = $config[$outlet['id']]['schedule'][date('l')] ?? [];

        $status_outlet = $config['status'] ?? false;

        if($status_outlet && (($schedule['open'] && date('H:i') < date('H:i', strtotime($schedule['open']))) || ($schedule['close'] && date('H:i') > date('H:i', strtotime($schedule['close']))))){
            $status_outlet = false;
        }

        $make_new = false;
        $check_json = file_exists(storage_path() . "/json/doctor_schedule.json");
        $schedule = $outlet->outlet_schedule->where('day', date('l'))->first();
        $config = [];
        if($check_json){
            $config = json_decode(file_get_contents(storage_path() . "/json/doctor_schedule.json"), true);
            if(isset($config[$doctor['id']])){
                if(date('Y-m-d H:i', strtotime($config[$doctor['id']]['updated_at']. ' +6 hours')) <= date('Y-m-d H:i')){
                    $make_new = true;
                }
            }else{
                $make_new = true;
            }
        }else{
            $make_new = true;
        }

        if($make_new){
            $schedule_doc = DoctorScheduleDate::whereHas('doctor_schedule', function($sch) use($doctor){
                $sch->where('user_id', $doctor['id']);
            })->whereDate('date', date('Y-m-d'))->first();
            if(!$schedule_doc){
                $config[$doctor['id']] = [
                    'updated_at' => date('Y-m-d H:i'),
                    'data' => [
                        'status' => false
                    ],
                ];
            }else{
                $shift = DoctorShift::whereHas('users', function($users) use($doctor){
                    $users->where('user_id', $doctor['id']);
                })->where('day', date('l'))
                ->get()->toArray();
                $config[$doctor['id']] = [
                    'updated_at' => date('Y-m-d H:i'),
                    'data' => [
                        'status' => true,
                        'shift' => $shift ?? []
                    ],
                ];
            }

            file_put_contents(storage_path('/json/doctor_schedule.json'), json_encode($config));

        }

        $config = $config[$doctor['id']] ?? [];

        $schedule_doc = $config['data'] ?? [];

        $status_doctor = true;
        $shift = false;

        if(!$schedule_doc['status']){
            $status_doctor = false;
        }else{
            foreach($schedule_doc['shift'] ?? [] as $sh){
                if(date('H:i') >= date('H:i', strtotime($sh['start'])) && date('H:i') <= date('H:i', strtotime($sh['end']))){
                    $shift = $sh;
                }
            }
            if(!$shift){
                $status_doctor = false;
            }
        }

        $on_progress = false;
        $queue = null;
        $id_order = null;

        if($status_outlet && $status_doctor && $shift){

            $order_skip = OrderConsultation::whereHas('order', function($order) use($outlet){
                $order->where('outlet_id', $outlet['id'])
                ->where('is_submited', 1)
                ->where('is_submited_doctor', 0)
                ->where('send_to_transaction', 0);
            })
            ->whereDate('schedule_date', date('Y-m-d'))
            ->where('doctor_id', $doctor['id'])
            ->whereHas('shift', function($shift_skip) use($shift){
                $shift_skip->whereTime('end', '<=', date('H:i:s', strtotime($shift['start'])));
            })
            ->orderBy('queue', 'asc')
            ->update(['doctor_shift_id' => $shift['id']]);

            $order = OrderConsultation::whereHas('order', function($order) use($outlet){
                $order->where('outlet_id', $outlet['id'])
                ->where('is_submited', 1)
                ->where('is_submited_doctor', 0)
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
        $data = [];
        $make_new = false;
        $check_json = file_exists(storage_path() . "/json/splash.json");
        if($check_json){
            $config = json_decode(file_get_contents(storage_path() . "/json/splash.json"), true);
            if(isset($config['doctor'])){
                if(date('Y-m-d H:i', strtotime($config['doctor']['updated_at']. ' +6 hours')) <= date('Y-m-d H:i')){
                    $make_new = true;
                }
            }else{
                $make_new = true;
            }
        }else{
            $make_new = true;
        }

        if($make_new){

            $splash = Setting::where('key', '=', 'splash_doctor_apps')->first();
            $duration = Setting::where('key', '=', 'splash_doctor_apps_duration')->pluck('value')->first();

            if(!empty($splash)){
                $splash = env('STORAGE_URL_API').$splash['value'];
            } else {
                $splash = null;
            }

            $ext=explode('.', $splash);
            $config['doctor'] = [
                'updated_at' => date('Y-m-d H:i'),
                'data' => [
                    'splash_screen_url' => isset($splash) ? $splash."?update=".time() : null,
                    'splash_screen_duration' => (int)($duration??5),
                    'splash_screen_ext' => '.'.end($ext)
                ]
            ];

            file_put_contents(storage_path('/json/splash.json'), json_encode($config));

        }

        $config = $config['pos'] ?? [];

        $data = $config['data'] ?? [];

        return $this->ok('', $data);
    }

    public function nextQueue(Request $request):JsonResponse
    {
        $doctor = $request->user();
        $outlet = $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $make_new = false;
        $check_json = file_exists(storage_path() . "/json/doctor_schedule.json");
        $schedule = $outlet->outlet_schedule->where('day', date('l'))->first();

        if($check_json){
            $config = json_decode(file_get_contents(storage_path() . "/json/doctor_schedule.json"), true);
            if(isset($config[$doctor['id']])){
                if(date('Y-m-d H:i', strtotime($config[$doctor['id']]['updated_at']. ' +6 hours')) <= date('Y-m-d H:i')){
                    $make_new = true;
                }
            }else{
                $make_new = true;
            }
        }else{
            $make_new = true;
        }

        if($make_new){
            $schedule_doc = DoctorScheduleDate::whereHas('doctor_schedule', function($sch) use($doctor){
                $sch->where('user_id', $doctor['id']);
            })->whereDate('date', date('Y-m-d'))->first();
            if(!$schedule_doc){
                $config[$doctor['id']] = [
                    'updated_at' => date('Y-m-d H:i'),
                    'data' => [
                        'status' => false
                    ],
                ];
            }else{
                $shift = DoctorShift::whereHas('users', function($users) use($doctor){
                    $users->where('user_id', $doctor['id']);
                })->where('day', date('l'))
                ->get()->toArray();
                $config[$doctor['id']] = [
                    'updated_at' => date('Y-m-d H:i'),
                    'data' => [
                        'status' => true,
                        'shift' => $shift ?? []
                    ],
                ];
            }

            file_put_contents(storage_path('/json/doctor_schedule.json'), json_encode($config));

        }

        $config = $config[$doctor['id']] ?? [];

        $schedule_doc = $config['data'] ?? [];

        $shift = false;

        if(!$schedule_doc['status']){
            $shift = false;
        }else{
            foreach($schedule_doc['shift'] ?? [] as $sh){
                if(date('H:i') >= date('H:i', strtotime($sh['start'])) && date('H:i') <= date('H:i', strtotime($sh['end']))){
                    $shift = $sh;
                }
            }
            if(!$shift){
                $status_doctor = false;
            }
        }

        if(!$shift){
            return $this->error('Shift not found');
        }

        $order_consultation = OrderConsultation::with(['order'])->whereHas('order', function($order) use($outlet){
            $order->where('outlet_id', $outlet['id'])
            ->where('is_submited', 1)
            ->where('is_submited_doctor', 0)
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
                ->where('is_submited_doctor', 0)
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
            return $this->getDataOrder(true, [
                'order_id' => $order_consultation['order_id'],
                'outlet_id' => $outlet['id'],
                'order_consultation' => $order_consultation
            ],'');
        }

        return $this->error('Order not found');

    }

    public function getDataOrder($status = true, $data, $message):JsonResponse
    {
        $id_order = $data['order_id'];
        $id_order_consultation = $data['order_consultation']['id'];
        $id_outlet = $data['outlet_id'];

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
        $order = Order::with([
            'order_products.product.global_price',
            'order_products.product.outlet_price' => function($outlet_price) use($id_outlet){
                $outlet_price->where('product_outlet_prices.outlet_id',$id_outlet);
            },
            'order_products.product.outlet_stock' => function($outlet_stock) use($id_outlet){
                $outlet_stock->where('product_outlet_stocks.outlet_id',$id_outlet);
            },
            'order_prescriptions.prescription.category',
            'order_consultations.consultation.patient_diagnostic.diagnostic',
            'order_consultations.consultation.patient_grievance.grievance',
            'order_consultations.shift',
            'order_consultations.doctor',
            'order_products.treatment_patient',
            'order_products.step' => function($step) {
                $step->where('status', 'Pending');
            }
        ])->where('id', $id_order)
        ->where('send_to_transaction', 0)
        ->where('is_submited', 1)
        ->where('is_submited_doctor', 0)
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
            $ord_prescriptions = [];
            foreach($order['order_products'] ?? [] as $key => $ord_pro){

                if($ord_pro['type'] == 'Product'){
                    if(isset($ord_pro['product']['outlet_price'][0]['price']) ?? false){
                        $price = $ord_pro['product']['outlet_price'][0]['price'] ?? null;
                    }else{
                        $price = $ord_pro['product']['global_price']['price'] ?? null;
                    }
                    $image_url = json_decode($ord_pro['product']['image'] ?? '' , true) ?? [];

                    $ord_prod[] = [
                        'order_product_id' => $ord_pro['id'],
                        'product_id'       => $ord_pro['product']['id'],
                        'product_name'     => $ord_pro['product']['product_name'],
                        // 'image_url'        => isset($ord_pro['product']['image']) ? env('STORAGE_URL_API').$ord_pro['product']['image'] : env('STORAGE_URL_DEFAULT_IMAGE').'default_image/default_product.png',
                        'image_url'        => isset($ord_pro['product']['image']) ? ($image_url[0] ?? null) : env('STORAGE_URL_DEFAULT_IMAGE').'default_image/default_product.png',
                        'qty'              => $ord_pro['qty'],
                        'current_qty'      => $ord_pro['qty'],
                        'stock'            => ($ord_pro['product']['outlet_stock'][0]['stock'] ?? 0) + $ord_pro['qty'],
                        'price'            => $price,
                        'price_total'      => $ord_pro['order_product_grandtotal'],
                    ];
                }elseif($ord_pro['type'] == 'Treatment'){

                    $progress = null;
                    if($ord_pro['treatment_patient'] && isset($ord_pro['treatment_patient']['doctor_id']) && isset($ord_pro['step'])){
                        $progress = $ord_pro['step']['step'].'/'.$ord_pro['treatment_patient']['step'];
                    }

                    $ord_treat[] = [
                        'order_product_id' => $ord_pro['id'],
                        'product_id'       => $ord_pro['product']['id'],
                        'product_name'     => $ord_pro['product']['product_name'],
                        'schedule_date'    => date('d F Y', strtotime($ord_pro['schedule_date'])),
                        'schedule'         => date('Y-m-d', strtotime($ord_pro['schedule_date'])),
                        'price_total'      => $ord_pro['order_product_grandtotal'],
                        'queue'            => $ord_pro['queue_code'],
                        'progress'         => $progress
                    ];
                }
            }

            foreach($order['order_consultations'] ?? [] as $key => $ord_con){

                $consul = [];
                $is_submit = 0;
                if($ord_con['consultation']){
                    $consul['queue_number']  = $ord_con['queue_code'];
                    $consul['schedule_date'] = date('d F Y', strtotime($ord_con['schedule_date']));
                    $consul['grievance'] = [];
                    $consul['diagnostic'] = [];
                    if($ord_con['consultation']['session_end'] == 1 || $ord_con['consultation']['is_edit'] == 1){
                        foreach($ord_con['consultation']['patient_grievance'] ?? [] as $grievance){
                            $consul['grievance'][] = $grievance['grievance']['grievance_name'];
                        }
                        foreach($ord_con['consultation']['patient_diagnostic'] ?? [] as $diagnostic){
                            $consul['diagnostic'][] = $diagnostic['diagnostic']['diagnostic_name'];
                        }
                        $is_submit = $ord_con['consultation']['session_end'];
                    }else{
                        foreach($ord_con['consultation']['patient_grievance'] ?? [] as $grievance){
                            if($grievance['from_pos'] == 1){
                                $consul['grievance'][] = $grievance['grievance']['grievance_name'];
                            }
                        }
                    }


                }
                $ord_consul[] = [
                    'order_consultation_id'    => $ord_con['id'],
                    'doctor_id'                => $ord_con['doctor']['id'],
                    'doctor_name'              => $ord_con['doctor']['name'],
                    'schedule_date'            => date('d F Y', strtotime($ord_con['schedule_date'])),
                    'time'                     => date('H:i', strtotime($ord_con['shift']['start'])).'-'.date('H:i', strtotime($ord_con['shift']['end'])),
                    'price_total'              => $ord_con['order_consultation_grandtotal'],
                    'queue'                    => $ord_con['queue_code'],
                    'is_submit'                => $is_submit,
                    'consultation'             => $consul,
                    'treatment_recommendation' => $ord_con['consultation']['treatment_recomendation'] ?? null
                ];
            }

            foreach($order['order_prescriptions'] ?? [] as $key => $ord_pres){

                $ord_prescriptions[] = [
                    'order_prescription_id' => $ord_pres['id'],
                    'prescription_id'       => $ord_pres['prescription']['id'],
                    'prescription_name'     => $ord_pres['prescription']['prescription_name'],
                    'type'                  => $ord_pres['prescription']['category']['category_name'] ?? null,
                    'unit'                  => $ord_pres['prescription']['unit'],
                    'qty'                   => $ord_pres['qty'],
                    'current_qty'           => $ord_pres['qty'],
                    'price_total'           => $ord_pres['order_prescription_grandtotal'],
                ];
            }

            $bithdayDate = new DateTime($user['birth_date']);
            $now = new DateTime();
            $interval = $now->diff($bithdayDate)->y;

            $return = [
                'user'                => [
                    'id'    => $user['id'],
                    'name'  => $user['name'],
                    'gender'  => $user['gender'],
                    'birth_date_text' => date('d F Y', strtotime($user['birth_date'])),
                    'age'   => $interval.' years',
                    'email' => substr_replace($user['email'], str_repeat('x', (strlen($user['email']) - 6)), 3, (strlen($user['email']) - 6)),
                    'phone' => substr_replace($user['phone'], str_repeat('x', (strlen($user['phone']) - 7)), 4, (strlen($user['phone']) - 7)),
                    'time' => 120
                ],
                'order_id'            => $order['id'],
                'order_code'          => $order['order_code'],
                'order_consultations' => $ord_consul,
                'order_products'      => $ord_prod,
                'order_treatments'    => $ord_treat,
                'order_precriptions'  => $ord_prescriptions,
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

        if($status){
            return $this->ok($message, $return);
        }else{
            return $this->error($message, $return);
        }

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
        $today = false;
        if($post['search']['filter'] == 'date'){
            $date = date('Y-m-d', strtotime($post['search']['value']));
            if($date == date('Y-m-d')){
                $today = true;
            }
        }elseif($post['search']['filter'] == 'name'){
            return $this->getDoctorAll($outlet);
        }

        $get_doctors = [];
        $make_new = false;
        $check_json = file_exists(storage_path() . "/json/get_doctor.json");
        if($check_json){
            $config = json_decode(file_get_contents(storage_path() . "/json/get_doctor.json"), true);
            if(isset($config[$outlet['id']])){
                if(($date && !$today) || (date('Y-m-d H:i', strtotime($config[$outlet['id']]['updated_at']. ' +6 hours')) <= date('Y-m-d H:i'))){
                    $make_new = true;
                }
            }else{
                $make_new = true;
            }
        }else{
            $make_new = true;
        }

        if($make_new){
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
                if($post['search']['value'] == ''){
                    $doctors = $doctors->where('name', '');
                }else{
                    $doctors = $doctors->where('name', 'like', '%'.$post['search']['value'].'%');
                }
            }

            $doctors = $doctors->doctor()->get()->toArray();
            $config[$outlet['id']] = [
                'updated_at' => date('Y-m-d H:i'),
                'data'       => $doctors
            ];
            file_put_contents(storage_path('/json/get_doctor.json'), json_encode($config));

        }
        $config = $config[$outlet['id']] ?? [];

        $get_doctors = $config['data'] ?? [];

        $list_doctors = [];
        foreach($get_doctors ?? [] as $key => $doc){

            $doc_shift = [];
            if($date){
                $day = date('l', strtotime($date));
                $shifts = DoctorShift::with([
                    'order_consultations' => function($order_consultation) use($date){
                    $order_consultation->whereDate('schedule_date', $date);
                }])->whereHas('users', function($users) use($doc){
                    $users->where('user_id', $doc['id']);
                })
                ->where('day',$day)->get()->toArray();
                foreach($shifts ?? [] as $key_2 =>$shift){
                    $doc_shift[] = [
                        'id_doctor_shift' => $shift['id'],
                        'time'            => date('H:i',strtotime($shift['start'])).' - '.date('H:i',strtotime($shift['end'])),
                        'price'           => $shift['price'],
                        'quote'           => count($shift['order_consultations'])
                    ];
                }

                if(isset($doc['doctor_schedules']) && !empty($doc_shift)){
                    $list_doctors[] = [
                        'id_doctor' => $doc['id'],
                        'name'      => $doc['name'],
                        'date_text' => date('d F Y', strtotime($date)),
                        'date'      => date('Y-m-d', strtotime($date)),
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

    public function getDoctorAll($outlet):mixed
    {

        $get_doctors = [];
        $make_new = false;
        $check_json = file_exists(storage_path() . "/json/get_doctor_all.json");
        if($check_json){
            $config = json_decode(file_get_contents(storage_path() . "/json/get_doctor_all.json"), true);
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
            $doctors = User::where('outlet_id', $outlet['id']);

            $doctors = $doctors->doctor()->get()->toArray();
            $config[$outlet['id']] = [
                'updated_at' => date('Y-m-d H:i'),
                'data'       => $doctors
            ];
            file_put_contents(storage_path('/json/get_doctor_all.json'), json_encode($config));

        }
        $config = $config[$outlet['id']] ?? [];

        $get_doctors = $config['data'] ?? [];

        $list_doctors = [];
        foreach($get_doctors ?? [] as $key => $doc){

            $doc_shift = [];
            $list_doctors[] = [
                'id_doctor' => $doc['id'],
                'name'      => $doc['name'],
                'image_url' => isset($doc['image_url']) ? env('STORAGE_URL_API').$doc['image_url'] : env('STORAGE_URL_DEFAULT_IMAGE').'default_image/default_doctor.png',
                'shifts'    => $doc_shift
            ];

        }
        $return = [
            'available'      => count($list_doctors),
            'recent_history' => [],
            'list_doctors'   => $list_doctors,
        ];
        return $this->ok('', $return);

    }

    public function getDoctorDate(Request $request):mixed
    {
        $post = $request->json()->all();
        $doctor = $request->user();
        $outlet =  $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $date_now = date('Y-m-d');
        $dates = MyHelper::getListDate(date('d'),date('m'),date('Y'));

        $get_doctors = [];
        $make_new = false;
        $check_json = file_exists(storage_path() . "/json/get_doctor_date.json");
        if($check_json){
            $config = json_decode(file_get_contents(storage_path() . "/json/get_doctor_date.json"), true);
            if(isset($config[$outlet['id']][$post['id']])){
                if(date('Y-m-d H:i', strtotime($config[$outlet['id']][$post['id']]['updated_at']. ' +6 hours')) <= date('Y-m-d H:i')){
                    $make_new = true;
                }
            }else{
                $make_new = true;
            }
        }else{
            $make_new = true;
        }

        if($make_new){

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

            $config[$outlet['id']][$post['id']] = [
                'updated_at' => date('Y-m-d H:i'),
                'data'       => $doctors ?? []
            ];

            file_put_contents(storage_path('/json/get_doctor_date.json'), json_encode($config));

        }
        $config = $config[$outlet['id']][$post['id']] ?? [];

        $get_doctors = $config['data'] ?? [];

        $list_doctors = [];
        $list_dates = [];
        foreach($get_doctors['doctor_schedules'][0]['schedule_dates'] ?? [] as $key => $schedule_dates){

            $doc_shift = [];
            $day = date('l', strtotime($schedule_dates['date']));
            $shifts = DoctorShift::with([
                'order_consultations' => function($order_consultation) use($schedule_dates){
                $order_consultation->whereDate('schedule_date', $schedule_dates['date']);
            }])->whereHas('users', function($users) use($get_doctors){
                $users->where('user_id', $get_doctors['id']);
            })
            ->where('day',$day)->get()->toArray();

            foreach($shifts ?? [] as $key_2 =>$shift){
                $doc_shift[] = [
                    'id_doctor_shift' => $shift['id'],
                    'time'            => date('H:i',strtotime($shift['start'])).' - '.date('H:i',strtotime($shift['end'])),
                    'quote'           => count($shift['order_consultations'])
                ];
            }
            if(isset($get_doctors['doctor_schedules']) && !empty($doc_shift)){
                $list_doctors[] = [
                    'id_doctor' => $get_doctors['id'],
                    'name'      => $get_doctors['name'],
                    'date_text' => date('d F Y', strtotime($schedule_dates['date'])),
                    'date'      => date('Y-m-d', strtotime($schedule_dates['date'])),
                    'image_url' => isset($get_doctors['image_url']) ? env('STORAGE_URL_API').$get_doctors['image_url'] : env('STORAGE_URL_DEFAULT_IMAGE').'default_image/default_doctor.png',
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

    public function getOrder(Request $request):JsonResponse
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
                ->where('is_submited_doctor', 0)
                ->where('send_to_transaction', 0);
            })
            ->where('order_id', $post['id_order'])
            ->whereDate('schedule_date', date('Y-m-d'))
            ->orderBy('queue', 'asc')
            ->first();

            if($order_consultation){
                return $this->getDataOrder(true, [
                    'order_id' => $order_consultation['order_id'],
                    'outlet_id' => $outlet['id'],
                    'order_consultation' => $order_consultation
                ],'');
            }else{
                return $this->error('Order not found');
            }
        }

        return $this->ok('', $return);

    }

    public function addOrder(Request $request):JsonResponse
    {
        $post = $request->json()->all();
        $doctor = $request->user();
        $outlet =  $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(isset($post['id_order'])){

            $order = Order::with(['order_consultations'])->where('id', $post['id_order'])
            ->where('send_to_transaction', 0)
            ->where('is_submited', 1)
            ->where('is_submited_doctor', 0)
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

                        if(($post['order']['qty']??false) == 0){

                            return $this->deleteOrderData([
                                'outlet' => $outlet,
                                'type' => $post['type'] ?? null,
                                'post' => [
                                    'id_order' => $post['id_order'],
                                    'id' => $order_product['id']
                                ]
                            ]);

                        }elseif(($post['order']['qty']??false) >= 1){

                            if($post['order']['qty']>$order_product['qty']){

                                $old_order_product = clone $order_product;
                                $order_product->update([
                                    'qty'                      => $post['order']['qty'],
                                    'order_product_subtotal'   => ($post['order']['qty']*$order_product['order_product_price']),
                                    'order_product_grandtotal' => ($post['order']['qty']*$order_product['order_product_price']),
                                ]);

                                $update_order = $order->update([
                                    'order_subtotal'   => $order_product['order']['order_subtotal'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_subtotal']),
                                    'order_gross'      => $order_product['order']['order_gross'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_subtotal']),
                                    'order_grandtotal' => $order_product['order']['order_grandtotal'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_grandtotal']),
                                ]);

                            }elseif($post['order']['qty']<$order_product['qty']){

                                $old_order_product = clone $order_product;
                                $order_product->update([
                                    'qty'                      => $post['order']['qty'],
                                    'order_product_subtotal'   => ($post['order']['qty']*$order_product['order_product_price']),
                                    'order_product_grandtotal' => ($post['order']['qty']*$order_product['order_product_price']),
                                ]);

                                $update_order = $order->update([
                                    'order_subtotal'   => $order_product['order']['order_subtotal'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_subtotal']),
                                    'order_gross'      => $order_product['order']['order_gross'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_subtotal']),
                                    'order_grandtotal' => $order_product['order']['order_grandtotal'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_grandtotal']),
                                ]);

                            }else{
                                return $this->getDataOrder(true, [
                                    'order_id' => $order['id'],
                                    'outlet_id' => $outlet['id'],
                                    'order_consultation' => $order['order_consultations'][0]
                                ],'Success to add new order');
                            }

                            if(!$update_order){
                                DB::rollBack();
                                return $this->error('Order not found');
                            }

                            $stock = ProductOutletStock::where('product_id', $order_product['product_id'])->where('outlet_id', $outlet['id'])->first();

                            if($stock){
                                $old_stock = clone $stock;
                                if($post['order']['qty']>$old_order_product['qty']){
                                    $qty = $post['order']['qty'] - $old_order_product['qty'];
                                    $qty_log = -$qty;
                                    $stock->update([
                                        'stock' =>  $stock['stock']-$qty
                                    ]);
                                }elseif($post['order']['qty']<$old_order_product['qty']){
                                    $qty = $old_order_product['qty'] - $post['order']['qty'];
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
                            return $this->getDataOrder(true, [
                                'order_id' => $order['id'],
                                'outlet_id' => $outlet['id'],
                                'order_consultation' => $order['order_consultations'][0]
                            ],'Success to add new order');
                        }

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
                        return $this->getDataOrder(false, [
                            'order_id' => $order['id'],
                            'outlet_id' => $outlet['id'],
                            'order_consultation' => $order['order_consultations'][0]
                        ],'Treatment already exist in order');
                    }else{

                        if(($post['order']['continue']??false) == 1){
                            $customerPatient = TreatmentPatient::where('patient_id', $order['patient_id'])
                            ->where('treatment_id', $product['id'])
                            ->where('status', '<>', 'Finished')
                            ->whereDate('expired_date', '>=', date('y-m-d', strtotime($post['order']['date'])))
                            ->first();

                        }else{
                            if(!isset($post['order']['record'])){
                                return $this->getDataOrder(false, [
                                    'order_id' => $order['id'],
                                    'outlet_id' => $outlet['id'],
                                    'order_consultation' => $order['order_consultations'][0]
                                ],'Record not found');
                            }
                            $expired = '+'.$post['order']['record']['time_frame'].' '.strtolower($post['order']['record']['type']).'s';
                            $customerPatient = TreatmentPatient::create([
                                'treatment_id' => $product['id'],
                                'patient_id' => $order['patient_id'],
                                'doctor_id' => $doctor['id'],
                                'step' => $post['order']['record']['qty'],
                                'progress' => 0,
                                'status' => 'On Progress',
                                'start_date' => date('Y-m-d H:i:s'),
                                'timeframe' => $post['order']['record']['time_frame'],
                                'timeframe_type' => $post['order']['record']['type'],
                                'expired_date' => date('Y-m-d H:i:s', strtotime($expired)),
                                'suggestion' => $post['order']['record']['notes'],
                            ]);
                        }

                        if(!$customerPatient){
                            return $this->error('Invalid Error');
                        }

                        $existcustomerPatientStep = TreatmentPatientStep::where('treatment_patient_id', $customerPatient['id'])->max('step') ?? 0;
                        if(($existcustomerPatientStep+1) > $customerPatient['step']){
                            return $this->error('Step cannot exceed those specified');
                        }

                        $customerPatientStep = TreatmentPatientStep::create([
                            'treatment_patient_id' => $customerPatient['id'],
                            'step'                 => $existcustomerPatientStep + 1,
                            'date'                 => $post['order']['date'],
                        ]);

                        if(!$customerPatientStep){
                            return $this->error('Invalid Error');
                        }

                        $create_order_product = OrderProduct::create([
                            'order_id'                  => $order['id'],
                            'product_id'                => $product['id'],
                            'type'                      => $product['type'],
                            'schedule_date'             => $post['order']['date'],
                            'treatment_patient_id'      => $customerPatient['id'] ?? null,
                            'treatment_patient_step_id' => $customerPatientStep['id'] ?? null,
                            'qty'                       => 1,
                            'order_product_price'       => $price,
                            'order_product_subtotal'    => $price,
                            'order_product_grandtotal'  => $price,
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
                return $this->getDataOrder(true, [
                    'order_id' => $order['id'],
                    'outlet_id' => $outlet['id'],
                    'order_consultation' => $order['order_consultations'][0]
                ],'Success to add new order');

            }elseif(($post['type']??false) == 'prescription'){
                $prescription = Prescription::with(['prescription_outlets' => function($outlet_price) use ($outlet){$outlet_price->where('outlet_id',$outlet['id']);}])
                ->whereHas('prescription_outlets', function($prescription_outlets) use ($outlet, $post){
                    $prescription_outlets->where('outlet_id',$outlet['id']);
                    $prescription_outlets->where('stock', '>=', $post['order']['qty']);
                })
                ->where('id', $post['order']['id'])->original()->first();

                if(!$prescription){
                    DB::rollBack();
                    return $this->error('Prescription not found');
                }

                $price = $prescription['prescription_outlets'][0]['price'] ?? $prescription['price'] ?? 0;

                $order_prescription = OrderPrescription::where('order_id', $order['id'])->where('prescription_id', $prescription['id'])->first();
                if($order_prescription){

                    if(($post['order']['qty']??false) == 0){

                        return $this->deleteOrderData([
                            'outlet' => $outlet,
                            'type' => $post['type'] ?? null,
                            'post' => [
                                'id_order' => $post['id_order'],
                                'id' => $order_prescription['id']
                            ]
                        ]);

                    }elseif(($post['order']['qty']??false) >= 1){

                        if($post['order']['qty']>$order_prescription['qty']){

                            $old_order_prescription = clone $order_prescription;
                            $order_prescription->update([
                                'qty'                      => $post['order']['qty'],
                                'order_prescription_subtotal'   => ($post['order']['qty']*$order_prescription['order_prescription_price']),
                                'order_prescription_grandtotal' => ($post['order']['qty']*$order_prescription['order_prescription_price']),
                            ]);

                            $update_order = $order->update([
                                'order_subtotal'   => $order_prescription['order']['order_subtotal'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_subtotal']),
                                'order_gross'      => $order_prescription['order']['order_gross'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_subtotal']),
                                'order_grandtotal' => $order_prescription['order']['order_grandtotal'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_grandtotal']),
                            ]);

                        }elseif($post['order']['qty']<$order_prescription['qty']){

                            $old_order_prescription = clone $order_prescription;
                            $order_prescription->update([
                                'qty'                      => $post['order']['qty'],
                                'order_prescription_subtotal'   => ($post['order']['qty']*$order_prescription['order_prescription_price']),
                                'order_prescription_grandtotal' => ($post['order']['qty']*$order_prescription['order_prescription_price']),
                            ]);

                            $update_order = $order->update([
                                'order_subtotal'   => $order_prescription['order']['order_subtotal'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_subtotal']),
                                'order_gross'      => $order_prescription['order']['order_gross'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_subtotal']),
                                'order_grandtotal' => $order_prescription['order']['order_grandtotal'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_grandtotal']),
                            ]);

                        }else{
                            return $this->getDataOrder(true, [
                                'order_id' => $order['id'],
                                'outlet_id' => $outlet['id'],
                                'order_consultation' => $order['order_consultations'][0]
                            ],'Success to add new order');
                        }

                        if(!$update_order){
                            DB::rollBack();
                            return $this->error('Order not found');
                        }

                        $stock = PrescriptionOutlet::where('prescription_id', $prescription['id'])->where('outlet_id', $outlet['id'])->first();

                        if($stock){
                            $old_stock = clone $stock;
                            if($post['order']['qty']>$old_order_prescription['qty']){
                                $qty = $post['order']['qty'] - $old_order_prescription['qty'];
                                $qty_log = -$qty;
                                $stock->update([
                                    'stock' =>  $stock['stock']-$qty
                                ]);
                            }elseif($post['order']['qty']<$old_order_prescription['qty']){
                                $qty = $old_order_prescription['qty'] - $post['order']['qty'];
                                $qty_log = $qty;
                                $stock->update([
                                    'stock' =>  $stock['stock']+$qty
                                ]);
                            }

                            if(!$stock){
                                DB::rollBack();
                                return $this->error('Failed to update stock');
                            }
                            (new PrescriptionController)->addLogPrescriptionStockLog($old_stock['id'], $qty_log, $old_stock['stock'], $stock['stock'], 'Update Booking Order', null);
                        }

                        DB::commit();
                        return $this->getDataOrder(true, [
                            'order_id' => $order['id'],
                            'outlet_id' => $outlet['id'],
                            'order_consultation' => $order['order_consultations'][0]
                        ],'Success to add new order');
                    }

                }else{
                    $order_prescription = OrderPrescription::create([
                        'order_id'                      => $order['id'],
                        'prescription_id'               => $prescription['id'],
                        'qty'                           => $post['order']['qty'],
                        'order_prescription_price'      => $price,
                        'order_prescription_subtotal'   => $post['order']['qty']*$price,
                        'order_prescription_grandtotal' => $post['order']['qty']*$price,
                    ]);

                }
                if(!$order_prescription){
                    DB::rollBack();
                    return $this->error('Product not found');
                }
                $price_to_order = ($post['order']['qty']*$price);
                $stock = PrescriptionOutlet::where('prescription_id', $prescription['id'])->where('outlet_id', $outlet['id'])->first();

                if($stock){
                    $old_stock = clone $stock;
                    $stock->update([
                        'stock' =>  $stock['stock']-$post['order']['qty']
                    ]);

                    if(!$stock){
                        DB::rollBack();
                        return $this->error('Failed to update stock');
                    }

                    (new PrescriptionController)->addLogPrescriptionStockLog($old_stock['id'], -$post['order']['qty'], $old_stock['stock'], $stock['stock'], 'Booking Order', null);
                }

                $order->update([
                    'order_subtotal'   => $order['order_subtotal'] + $price_to_order,
                    'order_gross'      => $order['order_gross'] + $price_to_order,
                    'order_grandtotal' => $order['order_grandtotal'] + $price_to_order,
                ]);

                DB::commit();
                return $this->getDataOrder(true, [
                    'order_id' => $order['id'],
                    'outlet_id' => $outlet['id'],
                    'order_consultation' => $order['order_consultations'][0]
                ],'Success to add new order');

            }elseif(($post['type']??false) == 'prescription_custom'){
                $prescription = Prescription::with([
                    'prescription_container.container.outlet_price' => function($container) use ($outlet){
                        $container->where('outlet_id', $outlet['id']);
                    },
                    'prescription_substances.substance.outlet_price' => function($substance) use ($outlet){
                        $substance->where('outlet_id', $outlet['id']);
                    },
                    'category'
                ])->where('id', $post['order']['id'])
                ->where('is_active', 1)
                ->whereHas('prescription_container')
                ->whereHas('prescription_substances')
                ->whereHas('category')
                ->custom()
                ->orderBy('created_at', 'desc')
                ->first();

                if(!$prescription){
                    DB::rollBack();
                    return $this->error('Prescription not found');
                }

                $price = 0;
                if($prescription['prescription_container'] ?? false){
                    $price += ($prescription['prescription_container']['container']['outlet_price'][0]['price'] ?? $prescription['prescription_container']['container']['price']) ?? 0;
                }

                foreach($prescription['prescription_substances'] ?? [] as $key_sub => $sub){
                    $price += (($sub['substance']['outlet_price'][0]['price'] ?? $sub['substance']['price']) ?? 0) * $sub['qty'];
                }

                $order_prescription = OrderPrescription::where('order_id', $order['id'])->where('prescription_id', $prescription['id'])->first();
                if($order_prescription){
                    if(($post['order']['qty']??false) == 0){

                        return $this->deleteOrderData([
                            'outlet' => $outlet,
                            'type' => $post['type'] ?? null,
                            'post' => [
                                'id_order' => $post['id_order'],
                                'id' => $order_prescription['id']
                            ]
                        ]);

                    }elseif(($post['order']['qty']??false) >= 1){

                        if($post['order']['qty']>$order_prescription['qty']){

                            $old_order_prescription = clone $order_prescription;
                            $order_prescription->update([
                                'qty'                      => $post['order']['qty'],
                                'order_prescription_subtotal'   => ($post['order']['qty']*$order_prescription['order_prescription_price']),
                                'order_prescription_grandtotal' => ($post['order']['qty']*$order_prescription['order_prescription_price']),
                            ]);

                            $update_order = $order->update([
                                'order_subtotal'   => $order_prescription['order']['order_subtotal'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_subtotal']),
                                'order_gross'      => $order_prescription['order']['order_gross'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_subtotal']),
                                'order_grandtotal' => $order_prescription['order']['order_grandtotal'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_grandtotal']),
                            ]);

                        }elseif($post['order']['qty']<$order_prescription['qty']){

                            $old_order_prescription = clone $order_prescription;
                            $order_prescription->update([
                                'qty'                      => $post['order']['qty'],
                                'order_prescription_subtotal'   => ($post['order']['qty']*$order_prescription['order_prescription_price']),
                                'order_prescription_grandtotal' => ($post['order']['qty']*$order_prescription['order_prescription_price']),
                            ]);

                            $update_order = $order->update([
                                'order_subtotal'   => $order_prescription['order']['order_subtotal'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_subtotal']),
                                'order_gross'      => $order_prescription['order']['order_gross'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_subtotal']),
                                'order_grandtotal' => $order_prescription['order']['order_grandtotal'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_grandtotal']),
                            ]);

                        }else{
                            return $this->getDataOrder(true, [
                                'order_id' => $order['id'],
                                'outlet_id' => $outlet['id'],
                                'order_consultation' => $order['order_consultations'][0]
                            ],'Success to add new order');
                        }

                        if(!$update_order){
                            DB::rollBack();
                            return $this->error('Order not found');
                        }

                        if($prescription['prescription_container'] ?? false){
                            $stock = ContainerStock::where('container_id', $prescription['prescription_container']['container']['id'])->where('outlet_id', $outlet['id'])->first();

                            if($stock){
                                $old_stock = clone $stock;
                                if($post['order']['qty']>$old_order_prescription['qty']){
                                    $qty = $post['order']['qty'] - $old_order_prescription['qty'];
                                    $qty_log = -$qty;
                                    $stock->update([
                                        'qty' =>  $stock['qty']-$qty
                                    ]);
                                }elseif($post['order']['qty']<$old_order_prescription['qty']){
                                    $qty = $old_order_prescription['qty'] - $post['order']['qty'];
                                    $qty_log = $qty;
                                    $stock->update([
                                        'qty' =>  $stock['qty']+$qty
                                    ]);
                                }

                                if(!$stock){
                                    DB::rollBack();
                                    return $this->error('Failed to update stock');
                                }

                                (new PrescriptionController)->addLogContainerStockLog($old_stock['id'], $qty_log, $old_stock['qty'], $stock['qty'], 'Update Booking Order', null);
                            }
                        }

                        foreach($prescription['prescription_substances'] ?? [] as $key_sub => $sub){

                            $stock = SubstanceStock::where('substance_id', $sub['substance']['id'])->where('outlet_id', $outlet['id'])->first();

                            if($stock){
                                $old_stock = clone $stock;
                                if($post['order']['qty']>$old_order_prescription['qty']){
                                    $qty = ($post['order']['qty'] - $old_order_prescription['qty'])*$sub['qty'];
                                    $qty_log = -$qty;
                                    $stock->update([
                                        'qty' =>  $stock['qty']-$qty
                                    ]);
                                }elseif($post['order']['qty']<$old_order_prescription['qty']){
                                    $qty = ($old_order_prescription['qty'] - $post['order']['qty'])*$sub['qty'];
                                    $qty_log = $qty;
                                    $stock->update([
                                        'qty' =>  $stock['qty']+$qty
                                    ]);
                                }

                                if(!$stock){
                                    DB::rollBack();
                                    return $this->error('Failed to update stock');
                                }

                                (new PrescriptionController)->addLogSubstanceStockLog($old_stock['id'], $qty_log, $old_stock['qty'], $stock['qty'], 'Update Booking Order', null);
                            }
                        }

                        DB::commit();
                        return $this->getDataOrder(true, [
                            'order_id' => $order['id'],
                            'outlet_id' => $outlet['id'],
                            'order_consultation' => $order['order_consultations'][0]
                        ],'Success to add new order');

                    }
                }else{
                    $order_prescription = OrderPrescription::create([
                        'order_id'                      => $order['id'],
                        'prescription_id'               => $prescription['id'],
                        'qty'                           => $post['order']['qty'],
                        'order_prescription_price'      => $price,
                        'order_prescription_subtotal'   => $post['order']['qty']*$price,
                        'order_prescription_grandtotal' => $post['order']['qty']*$price,
                    ]);

                }

                if(!$order_prescription){
                    DB::rollBack();
                    return $this->error('Product not found');
                }

                $price_to_order = ($post['order']['qty']*$price);
                if($prescription['prescription_container'] ?? false){
                    $stock = ContainerStock::where('container_id', $prescription['prescription_container']['container']['id'])->where('outlet_id', $outlet['id'])->first();

                    if($stock){
                        $old_stock = clone $stock;
                        $stock->update([
                            'qty' =>  $stock['qty']-$post['order']['qty']
                        ]);

                        if(!$stock){
                            DB::rollBack();
                            return $this->error('Failed to update stock');
                        }

                        (new PrescriptionController)->addLogContainerStockLog($old_stock['id'], -$post['order']['qty'], $old_stock['qty'], $stock['qty'], 'Booking Order', null);
                    }
                }

                foreach($prescription['prescription_substances'] ?? [] as $key_sub => $sub){

                    $stock = SubstanceStock::where('substance_id', $sub['substance']['id'])->where('outlet_id', $outlet['id'])->first();

                    if($stock){
                        $old_stock = clone $stock;
                        $stock->update([
                            'qty' =>  $stock['qty']-($post['order']['qty']*$sub['qty'])
                        ]);

                        if(!$stock){
                            DB::rollBack();
                            return $this->error('Failed to update stock');
                        }

                        (new PrescriptionController)->addLogSubstanceStockLog($old_stock['id'], -($post['order']['qty']*$sub['qty']), $old_stock['qty'], $stock['qty'], 'Booking Order', null);
                    }
                }

                $order->update([
                    'order_subtotal'   => $order['order_subtotal'] + $price_to_order,
                    'order_gross'      => $order['order_gross'] + $price_to_order,
                    'order_grandtotal' => $order['order_grandtotal'] + $price_to_order,
                ]);
                DB::commit();
                return $this->getDataOrder(true, [
                    'order_id' => $order['id'],
                    'outlet_id' => $outlet['id'],
                    'order_consultation' => $order['order_consultations'][0]
                ],'Success to add new order');

            }else{
                return $this->error('Type is invalid');
            }

        }else{
            return $this->error('Customer not found');
        }

    }

    public function deleteOrder(Request $request):JsonResponse
    {

        $post = $request->json()->all();
        $doctor = $request->user();
        $outlet =  $doctor->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(!isset($post['id_order'])){
            return $this->error('Order not found');
        }

        DB::beginTransaction();

        return $this->deleteOrderData([
            'outlet' => $outlet,
            'type' => $post['type'] ?? null,
            'post' => [
                'id_order' => $post['id_order'],
                'id' => $post['id']
            ]
        ]);

    }

    public function deleteOrderData($data, &$delete_errors):mixed
    {
        $outlet =  $data['outlet'];
        $type =  $data['type'];
        $post =  $data['post'];

        if(($type??false) == 'product' || ($type??false) == 'treatment'){

            $order_product = OrderProduct::with(['order.order_consultations'])->whereHas('order', function($order) use($post){
                $order->where('order_id', $post['id_order']);
                $order->where('send_to_transaction', 0);
                $order->where('is_submited', 1);
                $order->where('is_submited_doctor', 0);
            })->whereHas('product')
            ->where('id', $post['id'])->first();

            if(!$order_product){
                $delete_errors = 'Order not found';
                return false;
            }

            $order = Order::where('id', $order_product['order_id'])->update([
                'order_subtotal'   => $order_product['order']['order_subtotal'] - $order_product['order_product_subtotal'],
                'order_gross'      => $order_product['order']['order_gross'] - $order_product['order_product_subtotal'],
                'order_grandtotal' => $order_product['order']['order_grandtotal'] - $order_product['order_product_grandtotal'],
            ]);

            if(!$order){
                $delete_errors = 'Order not found';
                return false;
            }

            if(($type??false) == 'product'){
                $stock = ProductOutletStock::where('product_id', $order_product['product_id'])->where('outlet_id', $outlet['id'])->first();
                if($stock){
                    $old_stock = clone $stock;
                    $stock->update([
                        'stock' =>  $stock['stock']+$order_product['qty']
                    ]);

                    if(!$stock){
                        $delete_errors = 'Failed to update stock';
                        return false;
                    }
                    (new ProductController)->addLogProductStockLog($old_stock['id'], $order_product['qty'], $old_stock['stock'], $stock['stock'], 'Cancel Booking Order', null);
                }
            }

            $delete_order_product = $order_product->delete();

            if($delete_order_product && ($type??false) == 'treatment' && $order_product['treatment_patient_id']){
                $step =  TreatmentPatientStep::where('id', $order_product['treatment_patient_step_id'])->where('treatment_patient_id', $order_product['treatment_patient_id'])->where('status', 'Pending')->first();
                if($step){
                    OrderProduct::where('id', $order_product['id'])->update(['treatment_patient_step_id' => null]);
                    $delete_step = $step->delete();
                    if($delete_step){
                        $treatment_patient = TreatmentPatient::with(['steps'])->where('id', $order_product['treatment_patient_id'])->first();
                        if($treatment_patient){
                            if(count($treatment_patient['steps']) <= 0){
                                OrderProduct::where('id', $order_product['id'])->update(['treatment_patient_id' => null]);
                                $delete_treatment_patient = $treatment_patient->delete();
                                if(!$delete_treatment_patient){
                                    $delete_errors = 'Failed to delete treatment patient';
                                    return false;
                                }
                            }else{
                                $anotherSteps = TreatmentPatientStep::where('treatment_patient_id', $treatment_patient['id'])->where('status', 'Pending')->orderBy('step', 'asc')->get();
                                $start_from = ($treatment_patient['progress'] ?? 0) + 1;
                                foreach($anotherSteps ?? [] as $another){
                                    $another->update([
                                        'step' => $start_from,
                                    ]);
                                    $start_from++;
                                }
                            }
                        }else{
                            $delete_errors = 'Failed to get treatment patient';
                            return false;
                        }
                    }else{
                        $delete_errors = 'Failed to delete step';
                        return false;
                    }
                }else{
                    $delete_errors = 'Failed to get treatment patient step';
                    return false;
                }
            }

            return true;

        }elseif(($type??false) == 'prescription'){

            $order_prescription = OrderPrescription::with(['order.order_consultations'])->whereHas('order', function($order) use($post){
                $order->where('order_id', $post['id_order']);
                $order->where('send_to_transaction', 0);
                $order->where('is_submited', 1);
                $order->where('is_submited_doctor', 0);
            })->whereHas('prescription')
            ->where('id', $post['id'])->first();

            if(!$order_prescription){
                DB::rollBack();
                return $this->error('Order not found');
            }

            $order = Order::where('id', $order_prescription['order_id'])->update([
                'order_subtotal'   => $order_prescription['order']['order_subtotal'] - $order_prescription['order_prescription_subtotal'],
                'order_gross'      => $order_prescription['order']['order_gross'] - $order_prescription['order_prescription_subtotal'],
                'order_grandtotal' => $order_prescription['order']['order_grandtotal'] - $order_prescription['order_prescription_grandtotal'],
            ]);

            if(!$order){
                DB::rollBack();
                return $this->error('Order not found');
            }

            $stock = PrescriptionOutlet::where('prescription_id', $order_prescription['prescription_id'])->where('outlet_id', $outlet['id'])->first();

            if($stock){
                $old_stock = clone $stock;
                $stock->update([
                    'stock' =>  $stock['stock']+$order_prescription['qty']
                ]);

                if(!$stock){
                    DB::rollBack();
                    return $this->error('Failed to update stock');
                }

                (new PrescriptionController)->addLogPrescriptionStockLog($old_stock['id'], $order_prescription['qty'], $old_stock['stock'], $stock['stock'], 'Cancel Booking Order', null);

            }

            $delete_order_prescription = $order_prescription->delete();
            DB::commit();
            return $this->getDataOrder(true, [
                'order_id' => $order_prescription['order']['id'],
                'outlet_id' => $outlet['id'],
                'order_consultation' => $order_prescription['order']['order_consultations'][0]
            ],'Success to delete order');

        }elseif (($type??false) == 'prescription_custom') {

            $order_prescription = OrderPrescription::with([
                    'order.order_consultations',
                    'prescription.prescription_container',
                    'prescription.prescription_substances'
                ])->whereHas('order', function($order) use($post){
                $order->where('order_id', $post['id_order']);
                $order->where('send_to_transaction', 0);
                $order->where('is_submited', 1);
                $order->where('is_submited_doctor', 0);
            })->whereHas('prescription')
            ->where('id', $post['id'])->first();

            if(!$order_prescription){
                DB::rollBack();
                return $this->error('Order not found');
            }

            $order = Order::where('id', $order_prescription['order_id'])->update([
                'order_subtotal'   => $order_prescription['order']['order_subtotal'] - $order_prescription['order_prescription_subtotal'],
                'order_gross'      => $order_prescription['order']['order_gross'] - $order_prescription['order_prescription_subtotal'],
                'order_grandtotal' => $order_prescription['order']['order_grandtotal'] - $order_prescription['order_prescription_grandtotal'],
            ]);

            if(!$order){
                DB::rollBack();
                return $this->error('Order not found');
            }

            if($order_prescription['prescription']['prescription_container'] ?? false){
                $stock = ContainerStock::where('container_id', $order_prescription['prescription']['prescription_container']['container']['id'])->where('outlet_id', $outlet['id'])->first();

                if($stock){
                    $old_stock = clone $stock;
                    $stock->update([
                        'qty' =>  $stock['qty']+$order_prescription['qty']
                    ]);

                    if(!$stock){
                        DB::rollBack();
                        return $this->error('Failed to update stock');
                    }

                    (new PrescriptionController)->addLogContainerStockLog($old_stock['id'], $order_prescription['qty'], $old_stock['qty'], $stock['qty'], 'Cancel Booking Order', null);

                }
            }

            foreach($order_prescription['prescription']['prescription_substances'] ?? [] as $key_sub => $sub){
                $stock = SubstanceStock::where('substance_id', $sub['substance']['id'])->where('outlet_id', $outlet['id'])->first();

                if($stock){
                    $old_stock = clone $stock;
                    $stock->update([
                        'qty' =>  $stock['qty']+($order_prescription['qty']*$sub['qty'])
                    ]);

                    if(!$stock){
                        DB::rollBack();
                        return $this->error('Failed to update stock');
                    }

                    (new PrescriptionController)->addLogSubstanceStockLog($old_stock['id'], ($order_prescription['qty']*$sub['qty']), $old_stock['qty'], $stock['qty'], 'Cancel Booking Order', null);

                }
            }

            $delete_order_prescription = $order_prescription->delete();
            DB::commit();
            return $this->getDataOrder(true, [
                'order_id' => $order_prescription['order']['id'],
                'outlet_id' => $outlet['id'],
                'order_consultation' => $order_prescription['order']['order_consultations'][0]
            ],'Success to delete order');

        }else{
            return $this->error('Type is invalid');
        }
    }

    public function submitOrder(Request $request):mixed
    {
        $request->validate([
            'id_order' => 'required',
        ]);

        $doctor = $request->user();
        $outlet = $doctor->outlet;
        $post = $request->json()->all();


        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $is_error = false;
        $errors = [];
        $order = Order::with([
            'order_consultations.consultation',
        ])->where('id', $post['id_order'])
        ->where('outlet_id', $outlet['id'])
        ->where('send_to_transaction', 0)
        ->where('is_submited', 1)
        ->where('is_submited_doctor', 0)
        ->whereHas('order_consultations')
        ->latest()
        ->first();

        if(!$order){
            DB::rollBack();
            return $this->error('Order not found');
        }

        DB::beginTransaction();

        if($post['order_consultations']??false){
            $order_consultations = $order['order_consultations'][0];
            $consultation = $order_consultations['consultation'] ?? null;
            if(!$consultation){
                $consultation = Consultation::create([
                    'order_consultation_id' => $order_consultations['id'],
                ]);
            }

            $patient_grievance = [];
            foreach($post['order_consultations']['grievance'] ?? [] as $post_grievance){
                $patient_grievance[] = [
                    'consultation_id' => $consultation['id'],
                    'grievance_id'    => $post_grievance['id'],
                    'notes'           => $post_grievance['notes'],
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ];
            }
            if($patient_grievance){
                $insert_patient_grievance = PatientGrievance::insert($patient_grievance);
                if(!$insert_patient_grievance){
                    DB::rollBack();
                    return $this->error('Grievance error');
                }
            }

            $patient_diagnostics = [];
            foreach($post['order_consultations']['diagnostic'] ?? [] as $post_diagnostic){
                $patient_diagnostics[] = [
                    'consultation_id' => $consultation['id'],
                    'diagnostic_id'   => $post_diagnostic['id'],
                    'notes'           => $post_diagnostic['notes'],
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ];
            }
            if($patient_diagnostics){
                $insert_patient_diagnostics = PatientDiagnostic::insert($patient_diagnostics);
                if(!$insert_patient_diagnostics){
                    DB::rollBack();
                    return $this->error('Diagnostic error');
                }
            }

            $update_consultation = Consultation::where('id', $consultation['id'])->update([
                'session_end' => 1,
                'treatment_recomendation' => $post['order_consultations']['treatment_recommendation'] ?? null,
            ]);

        }else{
            DB::rollBack();
            return $this->error('Order Consultation not found');
        }

        $add_prod = [];
        foreach($post['order_products'] ?? [] as $post_order_product){

            $product = Product::with([
                'global_price','outlet_price' => function($outlet_price) use ($outlet){
                    $outlet_price->where('outlet_id',$outlet['id']);
                }, 'outlet_stock' => function($outlet_stock) use ($outlet){
                    $outlet_stock->where('outlet_id',$outlet['id']);
                }
            ])->where('id', $post_order_product['id'])->first();

            if(!$product){
                $is_error = true;
                $errors[] = 'Product not found';
                continue;
            }

            $price = ($product['outlet_price'][0]['price'] ?? $product['global_price']['price']) ?? 0;
            $stock = $product['outlet_stock'][0]['stock'] ?? 0;

            if($post_order_product['qty'] > $stock){
                $is_error = true;
                $errors[] = $product['product_name']. ' out of stock';
                continue;
            }

            $order_product = OrderProduct::where('order_id', $order['id'])->where('product_id', $product['id'])->first();
            if($order_product){

                if($post_order_product['qty']>$order_product['qty']){

                    $old_order_product = clone $order_product;
                    $order_product->update([
                        'qty'                      => $post_order_product['qty'],
                        'order_product_subtotal'   => ($post_order_product['qty']*$order_product['order_product_price']),
                        'order_product_grandtotal' => ($post_order_product['qty']*$order_product['order_product_price']),
                    ]);

                    $update_order = $order->update([
                        'order_subtotal'   => $order_product['order']['order_subtotal'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_subtotal']),
                        'order_gross'      => $order_product['order']['order_gross'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_subtotal']),
                        'order_grandtotal' => $order_product['order']['order_grandtotal'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_grandtotal']),
                    ]);

                }elseif($post_order_product['qty']<$order_product['qty']){

                    $old_order_product = clone $order_product;
                    $order_product->update([
                        'qty'                      => $post_order_product['qty'],
                        'order_product_subtotal'   => ($post_order_product['qty']*$order_product['order_product_price']),
                        'order_product_grandtotal' => ($post_order_product['qty']*$order_product['order_product_price']),
                    ]);

                    $update_order = $order->update([
                        'order_subtotal'   => $order_product['order']['order_subtotal'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_subtotal']),
                        'order_gross'      => $order_product['order']['order_gross'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_subtotal']),
                        'order_grandtotal' => $order_product['order']['order_grandtotal'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_grandtotal']),
                    ]);

                }elseif($post_order_product['qty']==$order_product['qty']){
                    $add_prod[] = $product['id'];
                    continue;
                }

                if(!$update_order){
                    $is_error = true;
                    $errors[] = 'Failed to order '.$product['product_name'];
                    continue;
                }

                $stock = ProductOutletStock::where('product_id', $product['id'])->where('outlet_id', $outlet['id'])->first();
                if($stock){
                    $old_stock = clone $stock;
                    if($post_order_product['qty']>$old_order_product['qty']){
                        $qty = $post_order_product['qty'] - $old_order_product['qty'];
                        $qty_log = -$qty;
                        $stock->update([
                            'stock' =>  $stock['stock']-$qty
                        ]);
                    }elseif($post_order_product['qty']<$old_order_product['qty']){
                        $qty = $old_order_product['qty'] - $post_order_product['qty'];
                        $qty_log = $qty;
                        $stock->update([
                            'stock' =>  $stock['stock']+$qty
                        ]);
                    }

                    if(!$stock){
                        $is_error = true;
                        $errors[] = 'Failed to update stock '.$product['product_name'];
                        continue;
                    }
                    (new ProductController)->addLogProductStockLog($old_stock['id'], $qty_log, $old_stock['stock'], $stock['stock'], 'Update Booking Order', null);
                }
                $add_prod[] = $product['id'];

            }else{
                $store_order_product = OrderProduct::create([
                    'order_id'                 => $order['id'],
                    'product_id'               => $product['id'],
                    'type'                     => 'Product',
                    'qty'                      => $post_order_product['qty'],
                    'order_product_price'      => $price,
                    'order_product_subtotal'   => $post_order_product['qty']*$price,
                    'order_product_grandtotal' => $post_order_product['qty']*$price,
                ]);

                $price_to_order = ($post_order_product['qty']*$price);
                if(!$store_order_product){
                    $is_error = true;
                    $errors[] = 'Failed to order '.$product['product_name'];
                    continue;
                }

                $stock = ProductOutletStock::where('product_id', $product['id'])->where('outlet_id', $outlet['id'])->first();
                if($stock){
                    $old_stock = clone $stock;
                    $stock->update([
                        'stock' =>  $stock['stock']-$post_order_product['qty']
                    ]);

                    if(!$stock){
                        $is_error = true;
                        $errors[] = 'Failed to update stock '.$product['product_name'];
                        continue;
                    }

                    (new ProductController)->addLogProductStockLog($old_stock['id'], -$post_order_product['qty'], $old_stock['stock'], $stock['stock'], 'Booking Order', null);
                }

                $order->update([
                    'order_subtotal'   => $order['order_subtotal'] + $price_to_order,
                    'order_gross'      => $order['order_gross'] + $price_to_order,
                    'order_grandtotal' => $order['order_grandtotal'] + $price_to_order,
                ]);
                $add_prod[] = $product['id'];
            }
        }

        if($add_prod && !$is_error){
            $other_order_products = OrderProduct::where('order_id', $order['id'])->whereNotIn('product_id', $add_prod)->where('type', 'Product')->get()->toArray();

            foreach($other_order_products ?? [] as $other_order_product){
                $delete = $this->deleteOrderData([
                    'outlet' => $outlet,
                    'type' => 'product',
                    'post' => [
                        'id_order' => $order['id'],
                        'id' => $other_order_product['id']
                    ]
                ], $delete_errors);
                if(!$delete){
                    $is_error = true;
                    $errors[] = $delete_errors;
                    continue;
                }
            }
        }

        $add_treat = [];
        foreach($post['order_treatments'] ?? [] as $post_order_treatment){
            $treatment = Product::with([
                'global_price','outlet_price' => function($outlet_price) use ($outlet){
                    $outlet_price->where('outlet_id',$outlet['id']);
                }
            ])->where('id', $post_order_treatment['id'])->first();

            if(!$treatment){
                $is_error = true;
                $errors[] = 'Treatment not found';
                continue;
            }

            $price = $treatment['outlet_price'][0]['price'] ?? $treatment['global_price']['price'];

            $get_order_treatment = OrderProduct::where('order_id', $order['id'])->where('product_id', $treatment['id'])->whereDate('schedule_date',$post_order_treatment['date'])->where('type', 'Treatment')->first();
            if($get_order_treatment){
                if(isset($post_order_treatment['continue'])){
                    $delete = $this->deleteOrderData([
                        'outlet' => $outlet,
                        'type' => 'treatment',
                        'post' => [
                            'id_order' => $order['id'],
                            'id' => $get_order_treatment['id']
                        ]
                    ], $delete_errors);
                    if(!$delete){
                        $is_error = true;
                        $errors[] = $delete_errors;
                        continue;
                    }


                }else{
                    $add_treat[] = $treatment['id'];
                    continue;
                }
            }

            if(($post_order_treatment['continue']??false) == 1){
                $customerPatient = TreatmentPatient::where('patient_id', $order['patient_id'])
                ->where('treatment_id', $treatment['id'])
                ->where('status', '<>', 'Finished')
                ->whereDate('expired_date', '>=', date('y-m-d', strtotime($post_order_treatment['date'])))
                ->first();

            }else{
                if(!isset($post_order_treatment['record'])){
                    $is_error = true;
                    $errors[] = 'Record not found';
                    continue;
                }
                $expired = '+'.$post_order_treatment['record']['time_frame'].' '.strtolower($post_order_treatment['record']['type']).'s';
                $customerPatient = TreatmentPatient::create([
                    'treatment_id' => $product['id'],
                    'patient_id' => $order['patient_id'],
                    'doctor_id' => $doctor['id'],
                    'step' => $post_order_treatment['record']['qty'],
                    'progress' => 0,
                    'status' => 'On Progress',
                    'start_date' => date('Y-m-d H:i:s'),
                    'timeframe' => $post_order_treatment['record']['time_frame'],
                    'timeframe_type' => $post_order_treatment['record']['type'],
                    'expired_date' => date('Y-m-d H:i:s', strtotime($expired)),
                    'suggestion' => $post_order_treatment['record']['notes'],
                ]);
            }

            if(!$customerPatient){
                $is_error = true;
                $errors[] = 'Invalid error';
                continue;
            }

            $existcustomerPatientStep = TreatmentPatientStep::where('treatment_patient_id', $customerPatient['id'])->max('step') ?? 0;
            if(($existcustomerPatientStep+1) > $customerPatient['step']){
                $is_error = true;
                $errors[] = 'Step cannot exceed those specified';
                continue;
            }

            $customerPatientStep = TreatmentPatientStep::create([
                'treatment_patient_id' => $customerPatient['id'],
                'step'                 => $existcustomerPatientStep + 1,
                'date'                 => $post_order_treatment['date'],
            ]);

            if(!$customerPatientStep){
                $is_error = true;
                $errors[] = 'Invalid error';
                continue;
            }

            $create_order_product = OrderProduct::create([
                'order_id'                  => $order['id'],
                'product_id'                => $product['id'],
                'type'                      => $product['type'],
                'schedule_date'             => $post_order_treatment['date'],
                'treatment_patient_id'      => $customerPatient['id'] ?? null,
                'treatment_patient_step_id' => $customerPatientStep['id'] ?? null,
                'qty'                       => 1,
                'order_product_price'       => $price,
                'order_product_subtotal'    => $price,
                'order_product_grandtotal'  => $price,
            ]);
            $price_to_order = $price;

            if(!$create_order_product){
                $is_error = true;
                $errors[] = 'Treatment not found';
                continue;
            }

            $order->update([
                'order_subtotal'   => $order['order_subtotal'] + $price_to_order,
                'order_gross'      => $order['order_gross'] + $price_to_order,
                'order_grandtotal' => $order['order_grandtotal'] + $price_to_order,
            ]);

            $add_treat[] = $treatment['id'];

        }

        if($add_treat && !$is_error){
            $other_order_products = OrderProduct::where('order_id', $order['id'])->whereNotIn('product_id', $add_treat)->where('type', 'Treatment')->get()->toArray();

            foreach($other_order_products ?? [] as $other_order_product){
                $delete = $this->deleteOrderData([
                    'outlet' => $outlet,
                    'type' => 'treatment',
                    'post' => [
                        'id_order' => $order['id'],
                        'id' => $other_order_product['id']
                    ]
                ], $delete_errors);
                if(!$delete){
                    $is_error = true;
                    $errors[] = $delete_errors;
                    continue;
                }
            }
        }

        foreach($post['order_prescriptions'] ?? [] as $post_order_prescription){
            $prescription = Prescription::with([
                'prescription_outlets' => function($outlet_price) use ($outlet){
                    $outlet_price->where('outlet_id',$outlet['id']);
                },
                'prescription_container.container.stocks' => function($container) use ($outlet){
                    $container->where('outlet_id', $outlet['id']);
                },
                'prescription_substances.substance.stocks' => function($substance) use ($outlet){
                    $substance->where('outlet_id', $outlet['id']);
                },
                'category'
            ])->where('id', $post_order_prescription['id'])
            ->where('is_active', 1)->first();

            if(!$prescription){
                $is_error = true;
                $errors[] = 'Prescription not found';
                continue;
            }

            if($prescription['is_custom'] == 0){

                $price = ($prescription['prescription_outlets'][0]['price'] ?? $prescription['price']) ?? 0;
                $stock = $prescription['prescription_outlets'][0]['stock'] ?? 0;

                if($post_order_prescription['qty'] > $stock){
                    $is_error = true;
                    $errors[] = $prescription['prescription_name']. ' out of stock';
                    continue;
                }

                $create_order_prescription = OrderPrescription::create([
                    'order_id'                      => $order['id'],
                    'prescription_id'               => $prescription['id'],
                    'qty'                           => $post_order_prescription['qty'],
                    'order_prescription_price'      => $price,
                    'order_prescription_subtotal'   => $post_order_prescription['qty']*$price,
                    'order_prescription_grandtotal' => $post_order_prescription['qty']*$price,
                ]);

                if(!$create_order_prescription){
                    $is_error = true;
                    $errors[] = 'Failed to order prescription';
                    continue;
                }

                $price_to_order = ($post_order_prescription['qty']*$price);
                $stock = PrescriptionOutlet::where('prescription_id', $prescription['id'])->where('outlet_id', $outlet['id'])->first();

                if($stock){
                    $old_stock = clone $stock;
                    $stock->update([
                        'stock' =>  $stock['stock']-$post_order_prescription['qty']
                    ]);

                    if(!$stock){
                        $is_error = true;
                        $errors[] = 'Failed to update stock';
                        continue;
                    }

                    (new PrescriptionController)->addLogPrescriptionStockLog($old_stock['id'], -$post_order_prescription['qty'], $old_stock['stock'], $stock['stock'], 'Booking Order', null);
                }

                $order->update([
                    'order_subtotal'   => $order['order_subtotal'] + $price_to_order,
                    'order_gross'      => $order['order_gross'] + $price_to_order,
                    'order_grandtotal' => $order['order_grandtotal'] + $price_to_order,
                ]);

            }else{
                $price = $prescription['price'];
                $create_order_prescription = OrderPrescription::create([
                    'order_id'                      => $order['id'],
                    'prescription_id'               => $prescription['id'],
                    'qty'                           => $post_order_prescription['qty'],
                    'order_prescription_price'      => $price,
                    'order_prescription_subtotal'   => $post_order_prescription['qty']*$price,
                    'order_prescription_grandtotal' => $post_order_prescription['qty']*$price,
                ]);

                if(!$create_order_prescription){
                    $is_error = true;
                    $errors[] = 'Failed to order prescription';
                    continue;
                }

                $price_to_order = ($post_order_prescription['qty']*$price);
                if($prescription['prescription_container'] ?? false){
                    $stock = ContainerStock::where('container_id', $prescription['prescription_container']['container']['id'])->where('outlet_id', $outlet['id'])->first();

                    if($stock){
                        if($post_order_prescription['qty'] > $stock['qty']){
                            $is_error = true;
                            $errors[] = $prescription['prescription_name']. ' out of stock';
                            continue;
                        }

                        $old_stock = clone $stock;
                        $stock->update([
                            'qty' =>  $stock['qty']-$post_order_prescription['qty']
                        ]);

                        if(!$stock){
                            $is_error = true;
                            $errors[] = 'Failed to update stock';
                            continue;
                        }

                        (new PrescriptionController)->addLogContainerStockLog($old_stock['id'], -$post_order_prescription['qty'], $old_stock['qty'], $stock['qty'], 'Booking Order', null);
                    }
                }

                foreach($prescription['prescription_substances'] ?? [] as $key_sub => $sub){

                    $stock = SubstanceStock::where('substance_id', $sub['substance']['id'])->where('outlet_id', $outlet['id'])->first();

                    if($stock){
                        if(($post_order_prescription['qty']*$sub['qty']) > $stock['qty']){
                            $is_error = true;
                            $errors[] = $prescription['prescription_name']. ' out of stock';
                            continue;
                        }

                        $old_stock = clone $stock;
                        $stock->update([
                            'qty' =>  $stock['qty']-($post_order_prescription['qty']*$sub['qty'])
                        ]);

                        if(!$stock){
                            DB::rollBack();
                            return $this->error('Failed to update stock');
                        }

                        (new PrescriptionController)->addLogSubstanceStockLog($old_stock['id'], -($post_order_prescription['qty']*$sub['qty']), $old_stock['qty'], $stock['qty'], 'Booking Order', null);
                    }
                }

                $order->update([
                    'order_subtotal'   => $order['order_subtotal'] + $price_to_order,
                    'order_gross'      => $order['order_gross'] + $price_to_order,
                    'order_grandtotal' => $order['order_grandtotal'] + $price_to_order,
                ]);
            }
        }

        if($is_error){
            DB::rollBack();
            return $this->error($errors);
        }else{
            $update = $order->update([
                'is_submited_doctor' => 1,
            ]);

            if(!$update){
                DB::rollBack();
                return $this->error('Failed to submit order');
            }

            $update_consul = OrderConsultation::where('id', $order['order_consultations'][0]['id'])->update(['status' => 'Finished']);

            DB::commit();

            return $this->ok('Success to submit order', []);
        }

    }
}
