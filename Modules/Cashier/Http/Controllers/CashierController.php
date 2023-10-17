<?php

namespace Modules\Cashier\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Lib\MyHelper;
use Illuminate\Http\JsonResponse;
use Modules\User\Entities\User;
use Modules\Customer\Entities\Customer;
use DateTime;
use Modules\EmployeeSchedule\Entities\EmployeeSchedule;
use Modules\EmployeeSchedule\Entities\EmployeeScheduleDate;
use Modules\Cashier\Entities\EmployeeAttendance;
use Modules\Outlet\Entities\OutletSchedule;
use Modules\Order\Entities\Order;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function histories(Request $request):mixed
    {
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        $schedules = EmployeeScheduleDate::with([
            'employee_schedule.user',
            'attendance.device',
            'shift'
        ])->whereHas('employee_schedule', function($schedule) use($outlet){
            $schedule->where('outlet_id', $outlet['id'])
            ->where('schedule_month', date('m'))
            ->where('schedule_year', date('Y'));
        })->whereHas('attendance')->whereDate('date', '<=',date('Y-m-d'))->get()->toArray();

        $data[] = [
            'date' => date('d F Y'),
            'is_today' => 1,
            'list' => []
        ];
        foreach($schedules ?? [] as $schedule){

            $detail = [];
            foreach($schedule['attendance'] as $attendance){
                $detail[] = [
                    'date' => $attendance['date'] == date('Y-m-d') ? 'Today - '.date('d F Y') : date('d F Y', strtotime($attendance['date'])),
                    'type' => $attendance['type'] == 'Log in' ? 'Entry' : 'Log out',
                    'time' => date('H:i', strtotime($attendance['attendance_time']))
                ];
            }
            if($schedule['shift'] && $schedule['shift']['start_break'] && $schedule['shift']['end_break']){
                $detail[] = [
                    'date' => date('Y-m-d', strtotime($schedule['date'])) == date('Y-m-d') ? 'Today - '.date('d F Y') : date('d F Y', strtotime($schedule['date'])),
                    'type' => 'Recess',
                    'time' => date('H:i', strtotime($schedule['shift']['start_break'])).' - '.date('H:i', strtotime($schedule['shift']['end_break']))
                ];
            }

            $value = [
                'station' => $attendance['device']['name'],
                'date' => date('Y-m-d', strtotime($schedule['date'])),
                'name' => $schedule['employee_schedule']['user']['name'],
                'shift' => $schedule['shift']['shift'],
                'start' => date('H:i', strtotime($schedule['shift']['shift_time_start'])),
                'end' => date('H:i', strtotime($schedule['shift']['shift_time_end'])),
                'text_shift' => date('H:i', strtotime($schedule['shift']['shift_time_start'])).' - '.date('H:i', strtotime($schedule['shift']['shift_time_end'])),
                'header' => [
                    'name' => $schedule['employee_schedule']['user']['name'],
                    'shift' => $schedule['shift']['shift'],
                    'station' => $attendance['device']['name']
                ],
                'detail' => $detail
            ];

            $check = array_search(date('d F Y', strtotime($schedule['date'])), array_column($data??[], 'date'));
            if($check !== false){
                array_push($data[$check]['list'], $value);
            }else{
                $data[] = [
                    'date' => date('d F Y', strtotime($value['date'])),
                    'is_today' => 0,
                    'list' => [
                        $value
                    ]
                ];
            }
        }

        return $this->ok('Success to get histories', $data);
    }

    public function listCashier(Request $request): mixed
    {
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        $dates = MyHelper::reverseGetListDate(date('d'),date('m'),date('Y'));

        $make_new = false;
        $check_json = file_exists(storage_path() . "/json/list_cashier.json");
        if($check_json){
            $config = json_decode(file_get_contents(storage_path() . "/json/list_cashier.json"), true);
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

            $all = User::with(['employee_schedules' => function($employee_schedules){
                $employee_schedules->where('schedule_month', date('m'))
                ->where('schedule_year', date('Y'))
                ->with(['employee_schedule_dates' => function($employee_schedule_dates){
                    $employee_schedule_dates->with(['shift','attendance.device'])->whereDate('date', '<=',date('Y-m-d'))->orderBy('date', 'desc');
                }]);
            }])->where('outlet_id', $outlet['id'])
            ->cashier()->isActive()->get()->toArray();

            $config[$outlet['id']] = [
                'updated_at' => date('Y-m-d H:i'),
                'data'       => $all
            ];
            file_put_contents(storage_path('/json/list_cashier.json'), json_encode($config));

        }
        $config = $config[$outlet['id']] ?? [];

        $cashiers = $config['data'] ?? [];

        $result = [];
        foreach($cashiers ?? [] as $val){

            $header = [
                'name' => $val['name'],
                'shift' => '-',
                'station' => '-',
            ];
            $list = [];
            foreach($dates['list'] ?? [] as $date)
            {
                $list_val = [
                    'date' => $date,
                    'date_text' => date('Y-m-d') == date('Y-m-d', strtotime($date)) ? date('d F Y', strtotime($date)).' (Today)' : date('d F Y', strtotime($date)),
                    'shift' => 'Day Off'
                ];
                if(isset($val['employee_schedules'][0])){
                    $check = array_search(date('Y-m-d', strtotime($date)), array_column($val['employee_schedules'][0]['employee_schedule_dates']??[], 'date'));
                    if($check !== false){
                        $val_date = $val['employee_schedules'][0]['employee_schedule_dates'][$check];
                        $list_val['shift'] = $val_date['shift']['shift'];
                        if(date('Y-m-d') == date('Y-m-d', strtotime($val_date['date']))){
                            $header['shift'] = $val_date['shift']['shift'];
                            if(isset($val_date['attendance']) && count($val_date['attendance']) > 0){
                                $header['station'] = $val_date['attendance'][0]['device']['name'];
                            }
                        }
                    }
                }
                $list[] = $list_val;

            }
            $result[] = [
                'id' => $val['id'],
                'name' => $val['name'],
                'header' => $header,
                'list' => $list,
            ];
        }

        return $this->ok('Success to get cashiers', $result);

    }

    public function scheduleAll(Request $request): mixed
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        if(!isset($post['date'])){
            return $this->error('Date cant be null');
        }

        $date = date('Y-m-d', strtotime($post['date']));
        if(date('m', strtotime($date)) == date('m')){
            $today_month = true;
        }

        $year = date('Y',strtotime($date));
        $month = date('m',strtotime($date));
        $today = date('d',strtotime($date));

        $dates = MyHelper::getListDate(1,$month,$year);
        $data = [];
        foreach($dates['list']  ?? [] as $date){

            $sche = OutletSchedule::with(['outlet_shifts.employee' => function($employee_sch) use($date){
                $employee_sch->whereDate('employee_schedule_dates.date', $date)->with(['employee_schedule.user']);
            }])->where('day', date('l', strtotime($date)))->where('outlet_id', $outlet['id'])
            ->first();
            if($sche){
                $result = [
                    'id' => $sche['id'],
                    'outlet_id' => $sche['outlet_id'],
                    'shifts' => array_map(function($outlet_shift){
                        $return_outlet_shift['shift_name'] = $outlet_shift['shift'];
                        $return_outlet_shift['start'] = date('H:i', strtotime($outlet_shift['shift_time_start']));
                        $return_outlet_shift['end'] = date('H:i', strtotime($outlet_shift['shift_time_end']));
                        $return_outlet_shift['text_shift'] = date('H:i', strtotime($outlet_shift['shift_time_start'])).' - '.date('H:i', strtotime($outlet_shift['shift_time_end']));
                        $return_outlet_shift['cashiers'] = array_map(function($employees){
                            $return_employee['id'] = $employees['employee_schedule']['user']['id'];
                            $return_employee['name'] = $employees['employee_schedule']['user']['name'];
                            return $return_employee;
                        },$outlet_shift['employee'] ?? []);
                        return $return_outlet_shift;
                    }, $sche['outlet_shifts']->toArray() ?? [])
                ];
            }
            $data[] = [
                'date' => $date,
                'date_text' => date('d F Y', strtotime($date)),
                'data' => $result ?? null
            ];
        }

        return $this->ok('Success to get all schedule', $data);
    }

    public function mySchedule(Request $request): mixed
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        if(!isset($post['date'])){
            return $this->error('Date cant be null');
        }

        $date = date('Y-m-d', strtotime($post['date']));
        if(date('m', strtotime($date)) == date('m')){
            $today_month = true;
        }

        $year = date('Y',strtotime($date));
        $month = date('m',strtotime($date));
        $today = date('d',strtotime($date));

        $dates = MyHelper::getListDate(1,$month,$year);
        $data = [];

        $sch = EmployeeScheduleDate::with(['shift','attendance.device'])
        ->whereHas('employee_schedule', function($employee_schedule) use($cashier){
            $employee_schedule->where('schedule_month', date('m'))
            ->where('schedule_year', date('Y'))
            ->where('user_id', $cashier['id']);
        })->whereDate('date', '<=',date('Y-m-d'))
        ->orderBy('date', 'desc')
        ->get()->toArray();

        $list = [];
        foreach($dates['list']  ?? [] as $date){

            $list_val = [
                'date' => $date,
                'date_text' => date('Y-m-d') == date('Y-m-d', strtotime($date)) ? date('d F Y', strtotime($date)).' (Today)' : date('d F Y', strtotime($date)),
                'detail' => [
                    [
                        'label' => 'Day Off',
                        'value' => '-',
                    ],
                    [
                        'label' => 'Entry',
                        'value' => '-',
                    ],
                    [
                        'label' => 'Log Out',
                        'value' => '-',
                    ]
                ]
            ];

            $check = array_search(date('Y-m-d', strtotime($date)), array_column($sch??[], 'date'));
            if($check !== false){
                $val_date = $sch[$check];
                $value_shift = $val_date['shift']['shift'];
                $time_shift = date('H:i', strtotime($val_date['shift']['shift_time_start'])).' - '.date('H:i', strtotime($val_date['shift']['shift_time_end']));

                $value_entry = '-';
                $value_logout = '-';
                foreach($val_date['attendance'] ?? [] as $attendance){
                    if($attendance['type'] == 'Log in'){
                        $value_entry = date('H:i',strtotime($attendance['attendance_time']));
                    }

                    if($attendance['type'] == 'Log out'){
                        $value_logout = date('H:i',strtotime($attendance['attendance_time']));
                    }
                }
                $list_val['detail'] = [
                    [
                        'label' => $value_shift,
                        'value' => $time_shift,
                    ],
                    [
                        'label' => 'Entry',
                        'value' => $value_entry,
                    ],
                    [
                        'label' => 'Log Out',
                        'value' => $value_logout,
                    ]
                    ];
            }


            $list[] = $list_val;
        }

        return $this->ok('Success to get cashiers', $list);
    }

    public function record(Request $request): mixed
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        if(!isset($post['date'])){
            return $this->error('Date cant be null');
        }

        $orders = Order::with([
            'patient',
            'order_products.product',
            'order_prescriptions.prescription.category',
            'order_consultations.consultation.patient_diagnostic.diagnostic',
            'order_consultations.consultation.patient_grievance.grievance',
            'order_consultations.shift',
            'order_consultations.doctor',
            'child.order_consultations.consultation.patient_grievance.grievance',
            'child.order_consultations.shift',
            'child.order_consultations.doctor',
            'order_products.treatment_patient',
            'order_products.step',
            'transaction'
        ])->whereHas('transaction', function($trx) use($post){
            $trx->whereDate('transaction_date', date('Y-m-d', strtotime($post['date'])));
        })
        ->where('outlet_id', $outlet['id'])
        ->where('send_to_transaction', 1)
        ->get()->toArray();

        $data = [
            'total' => count($orders??[]),
            'list' => []
        ];
        foreach($orders ?? [] as $order){

            $bithdayDate = new DateTime($order['patient']['birth_date']);
            $now = new DateTime();
            $interval = $now->diff($bithdayDate)->y;

            $ord_prod = [];
            $ord_treat = [];
            $ord_consul = [];
            $ord_prescrip = [];

            $card_ord_prod = [];
            $card_ord_treat = [];
            $card_ord_consul = [];
            $card_ord_prescrip = [];

            foreach($order['order_products'] ?? [] as $order_product){

                if($order_product['type'] == 'Product'){

                    $ord_prod[] = [
                        'order_product_id' => $order_product['id'],
                        'product_id'       => $order_product['product']['id'],
                        'product_name'     => $order_product['product']['product_name'],
                        'qty'              => $order_product['qty'],
                        'price_total'      => $order_product['order_product_grandtotal'],
                    ];

                    $card_ord_prod[] = [
                        'product_id'       => $order_product['product']['id'],
                        'product_name'     => $order_product['product']['product_name'],
                        'qty'              => $order_product['qty'],
                    ];

                }elseif($order_product['type'] == 'Treatment'){

                    $progress = null;
                    if($order_product['treatment_patient'] && isset($order_product['treatment_patient']['doctor_id']) && isset($order_product['step'])){
                        $progress = $order_product['step']['step'].'/'.$order_product['treatment_patient']['step'];
                    }

                    $ord_treat[] = [
                        'order_product_id' => $order_product['id'],
                        'product_id'       => $order_product['product']['id'],
                        'product_name'     => $order_product['product']['product_name'],
                        'schedule_date'    => date('d F Y', strtotime($order_product['schedule_date'])),
                        'schedule'         => date('Y-m-d', strtotime($order_product['schedule_date'])),
                        'price_total'      => $order_product['order_product_grandtotal'],
                        'progress'         => $progress
                    ];

                    $card_ord_treat[] = [
                        'product_id'       => $order_product['product']['id'],
                        'product_name'     => $order_product['product']['product_name'],
                        'schedule_date'    => date('d F Y', strtotime($order_product['schedule_date'])),
                        'progress'         => $progress
                    ];

                }
            }

            foreach($order['order_prescriptions'] ?? [] as $key => $ord_pres){

                $ord_prescrip[] = [
                    'order_prescription_id' => $ord_pres['id'],
                    'prescription_id'       => $ord_pres['prescription']['id'],
                    'prescription_name'     => $ord_pres['prescription']['prescription_name'],
                    'type'                  => $ord_pres['prescription']['category']['category_name'] ?? null,
                    'unit'                  => $ord_pres['prescription']['unit'],
                    'qty'                   => $ord_pres['qty'],
                    'price_total'           => $ord_pres['order_prescription_grandtotal'],
                ];

                $card_ord_prescrip[] = [
                    'prescription_id'       => $ord_pres['prescription']['id'],
                    'prescription_name'     => $ord_pres['prescription']['prescription_name'],
                    'type'                  => $ord_pres['prescription']['category']['category_name'] ?? null,
                    'unit'                  => $ord_pres['prescription']['unit'],
                    'qty'                   => $ord_pres['qty'],
                ];

                $total_prescription = $total_prescription + 1;
                $queue_prescription = $ord_pres['queue_code'];

            }

            foreach($order['order_consultations'] ?? [] as $order_consultation){

                $consul = [];
                if($order_consultation['consultation']){
                    $consul['grievance'] = [];
                    $consul['diagnostic'] = [];
                    if($order_consultation['consultation']['session_end'] == 1 || $order_consultation['consultation']['is_edit'] == 1){
                        foreach($order_consultation['consultation']['patient_grievance'] ?? [] as $grievance){
                            $consul['grievance'][] = $grievance['grievance']['grievance_name'];
                        }
                        foreach($order_consultation['consultation']['patient_diagnostic'] ?? [] as $diagnostic){
                            $consul['diagnostic'][] = $diagnostic['diagnostic']['diagnostic_name'];
                        }
                    }
                }

                $ord_consul[] = [
                    'order_consultation_id'    => $order_consultation['id'],
                    'doctor_id'                => $order_consultation['doctor']['id'],
                    'doctor_name'              => $order_consultation['doctor']['name'],
                    'schedule_date'            => date('d F Y', strtotime($order_consultation['schedule_date'])),
                    'time'                     => date('H:i', strtotime($order_consultation['shift']['start'])).'-'.date('H:i', strtotime($order_consultation['shift']['end'])),
                    'price_total'              => $order_consultation['order_consultation_grandtotal'],
                    'queue'                    => $order_consultation['queue_code'],
                    'consultation'             => $consul,
                    'treatment_recommendation' => $ord_con['consultation']['treatment_recomendation'] ?? null,
                ];

                $card_ord_consul[] = [
                    'doctor_id'                => $order_consultation['doctor']['id'],
                    'doctor_name'              => $order_consultation['doctor']['name'],
                    'schedule_date'            => date('d F Y', strtotime($order_consultation['schedule_date'])),
                    'time'                     => date('H:i', strtotime($order_consultation['shift']['start'])).'-'.date('H:i', strtotime($order_consultation['shift']['end'])),
                ];

            }

            $detail = [
                'user' => [
                    'id'    => $order['patient']['id'],
                    'name' => $order['patient']['name'],
                    'gender'  => $order['patient']['gender'],
                    'email' => substr_replace($order['patient']['email'], str_repeat('x', (strlen($order['patient']['email']) - 6)), 3, (strlen($order['patient']['email']) - 6)),
                    'phone' => substr_replace($order['patient']['phone'], str_repeat('x', (strlen($order['patient']['phone']) - 7)), 4, (strlen($order['patient']['phone']) - 7)),
                    'age'   => $interval.' years',
                ],
                'order_product' => $ord_prod,
                'order_treatment' => $ord_treat,
                'order_consultation' => $ord_consul,
                'order_prescription' => $ord_prescrip,
            ];

            $card = [
                'name' => $order['patient']['name'],
                'time' => date('H:i',strtotime($order['transaction']['transaction_date'])),
                'order_product' => $card_ord_prod,
                'order_treatment' => $card_ord_treat,
                'order_consultation' => $card_ord_consul,
                'order_prescription' => $card_ord_prescrip,
                'detail' => $detail
            ];


            $data['list'][] = $card;

        }

        return $this->ok('Success to get cashiers', $data);

    }

    public function getProfile(Request $request): mixed
    {
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        $user = User::with(['employee_schedules' => function($employee_schedules){
            $employee_schedules->where('schedule_month', date('m'))
            ->where('schedule_year', date('Y'))
            ->with(['employee_schedule_dates' => function($employee_schedule_dates){
                $employee_schedule_dates->with(['shift','attendance.device'])->whereDate('date', '=',date('Y-m-d'))->orderBy('date', 'desc');
            }]);
        }])->where('outlet_id', $outlet['id'])
        ->cashier()->isActive()->first();

        $data = [
            'header' => [
                'name' => $cashier['name'],
                'image_url' => isset($cashier['image_url']) ? env('STORAGE_URL_API').$cashier['image_url'] : env('STORAGE_URL_DEFAULT_IMAGE').'default_image/default_doctor.png'
            ],
            'body' => [
                'cashier_id' => $cashier['id_number'] ?? null,
                'start_join' => date('d F Y', strtotime($cashier['created_at'])),
                'station' => $cashier['employee_schedules'][0]['employee_schedule_dates'][0]['attendance'][0]['device']['name'] ?? null,
                'shift' => isset($cashier['employee_schedules'][0]['employee_schedule_dates'][0]['shift']['shift']) ?  $cashier['employee_schedules'][0]['employee_schedule_dates'][0]['shift']['shift'].' ('.date('H:i', strtotime($cashier['employee_schedules'][0]['employee_schedule_dates'][0]['shift']['shift_time_start'])).' - '.date('H:i', strtotime($cashier['employee_schedules'][0]['employee_schedule_dates'][0]['shift']['shift_time_end'])).')' : null,
            ],
            'footer' => [
                'name' => $cashier['name'],
                'birth_date' => date('d F Y', strtotime($cashier['birthdate'])),
                'gender' => $cashier['gender'] ?? null,
                'phone' => $cashier['phone'] ?? null,
                'email' => $cashier['email'] ?? null,
                'address' => $cashier['address'] ?? null,
            ]
        ];

        return $this->ok('Success to get cashiers', $data);
    }

    public function updateProfile(Request $request): mixed
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        if($post['type']??false && $post['value']??false){

            if($post['type'] == 'phone' || $post['type'] == 'email'){
                $check = User::where($post['type'], $post['value'])->where('id', '<>', $cashier['id'])->get()->toArray();
                if($check){
                    return $this->error('Already exist');
                }
            }

            DB::beginTransaction();
            $update = User::where('id', $cashier['id'])->update([
                $post['type'] => $post['value']
            ]);
            if(!$update){
                DB::rollBack();
                return $this->error('Failed update data');
            }

            DB::commit();
            return $this->ok('Success update data', []);
        }else{
            return $this->error('Error');
        }
    }
}
