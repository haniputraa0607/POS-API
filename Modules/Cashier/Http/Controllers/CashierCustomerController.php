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
use Modules\Order\Entities\OrderConsultation;
use Illuminate\Support\Facades\DB;
use Modules\Product\Entities\Product;

class CashierCustomerController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function draft(Request $request): mixed
    {
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        $orders = Order::with([
            'patient',
            'order_products.product',
            'order_products.doctor',
            'order_products.nurse',
            'order_products.beautician',
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
        ])
        ->where(function($where){
            $where->where('is_submited', 0)->where('is_submited_doctor', 0)->whereNull('parent_id');
        })
        ->whereDate('order_date', date('Y-m-d'))
        ->where('outlet_id', $outlet['id'])
        ->where('status', 'Pending')
        ->where('send_to_transaction', 0)
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
            $summary = [
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
            ];

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
                        'queue'            => $order_product['queue_code'] ?? 'TBD',
                        'progress'         => $progress,
                        'doctor_name'      => $order_product['doctor']['name'] ?? null,
                        'nurse_name'       => $order_product['nurse']['name'] ?? null,
                        'beautician_name'  => $order_product['beautician']['name'] ?? null,
                    ];

                    $card_ord_treat[] = [
                        'product_id'       => $order_product['product']['id'],
                        'product_name'     => $order_product['product']['product_name'],
                        'schedule_date'    => date('d F Y', strtotime($order_product['schedule_date'])),
                        'progress'         => $progress
                    ];

                }
            }

            if($order['child']['order_consultations']??false){
                foreach($order['child']['order_consultations'] ?? [] as $order_consultation){

                    $consul = [];
                    if($order_consultation['consultation']){
                        $consul['grievance'] = [];
                        $consul['diagnostic'] = [];
                        foreach($order_consultation['consultation']['patient_grievance'] ?? [] as $grievance){
                            $consul['grievance'][] = [
                                'id'             => $grievance['grievance']['id'],
                                'grievance_name' => $grievance['grievance']['grievance_name'],
                                'notes'          => $grievance['notes'] ?? null,
                            ];
                        }
                    }

                    $ord_consul[] = [
                        'order_consultation_id'    => $order_consultation['id'],
                        'doctor_id'                => $order_consultation['doctor']['id'],
                        'doctor_name'              => $order_consultation['doctor']['name'],
                        'schedule_date'            => date('d F Y', strtotime($order_consultation['schedule_date'])),
                        'time'                     => date('H:i', strtotime($order_consultation['shift']['start'])).'-'.date('H:i', strtotime($order_consultation['shift']['end'])),
                        'price_total'              => $order_consultation['order_consultation_grandtotal'],
                        'queue'                    => $order_consultation['queue_code'] ?? 'TBD',
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
            }elseif($order['order_consultations']??false){
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
                        'queue'                    => $order_consultation['queue_code'] ?? 'TBD',
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
                'summary' => $summary
            ];

            $card = [
                'name' => $order['patient']['name'],
                'time' => date('H:i',strtotime($order['order_date'])),
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

    public function treatment(Request $request): mixed
    {
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        $products = Product::with([
            'outlet_treatment' => function ($outlet_treatment) use ($outlet){
                $outlet_treatment->where('outlet_id',$outlet['id'])->with(['treatment_room']);
            },
            'orders' => function ($orders) {
                $orders->with([
                    'order.patient',
                    'treatment_patient',
                    'step',
                    'doctor',
                    'nurse',
                    'beautician',
                ])->whereNotNull('treatment_patient_id')
                ->whereNotNull('treatment_patient_step_id')
                ->whereHas('order',function($order){
                    $order->where('send_to_transaction', 1)
                    ->where('status', 'Finished');
                })->whereHas('step',function($step){
                    $step->where('status', 'Finished');
                })->orderBy('schedule_date', 'desc')->orderBy('created_at', 'desc');
            }
        ])->whereHas('outlet_treatment', function($outlet_treatment) use ($outlet){
            $outlet_treatment->where('outlet_id',$outlet['id']);
        })->whereHas('orders', function($orders){
            $orders->whereNotNull('treatment_patient_id')
            ->whereNotNull('treatment_patient_step_id')
            ->whereHas('order',function($order){
                $order->where('send_to_transaction', 1)
                ->where('status', 'Finished');
            })->whereHas('step',function($step){
                $step->where('status', 'Finished');
            });
        })->treatment()->get()->toArray();

        $data = [];
        foreach($products ?? [] as $treatment){

            foreach($treatment['orders'] ?? [] as $order_treatment){

                $progress = null;
                if($order_treatment['treatment_patient'] && isset($order_treatment['treatment_patient']['doctor_id']) && isset($order_treatment['step'])){
                    $progress = $order_treatment['step']['step'].'/'.$order_treatment['treatment_patient']['step'];
                }

                $bithdayDate = new DateTime($order_treatment['order']['patient']['birth_date']);
                $now = new DateTime();
                $interval = $now->diff($bithdayDate)->y;

                $list = [
                    'id_order_treatment' => $order_treatment['id'],
                    'id_treatment' => $treatment['id'],
                    'date' => date('Y-m-d', strtotime($order_treatment['schedule_date'])),
                    'date_text' => date('d F Y', strtotime($order_treatment['schedule_date'])),
                    'name' => $treatment['product_name'],
                    'price_total' => $order_treatment['order_product_grandtotal'],
                    'queue' => $order_treatment['queue_code'],
                    'patient_name' => $order_treatment['order']['patient']['name'],
                    'gender'  => $order_treatment['order']['patient']['gender'],
                    'phone' => substr_replace($order_treatment['order']['patient']['phone'], str_repeat('x', (strlen($order_treatment['order']['patient']['phone']) - 7)), 4, (strlen($order_treatment['order']['patient']['phone']) - 7)),
                    'age'   => $interval.' years',
                    'step' => $progress,
                    'doctor_name'      => $order_treatment['doctor']['name'] ?? null,
                    'nurse_name'       => $order_treatment['nurse']['name'] ?? null,
                    'beautician_name'  => $order_treatment['beautician']['name'] ?? null,
                ];

                $check = array_search(date('d F Y', strtotime($list['date'])), array_column($data??[], 'date'));
                if($check !== false){
                    $check2 = array_search($list['id_treatment'], array_column($data[$check]['list_treatment']??[], 'id_treatment'));
                    if($check2 !== false){
                        $data[$check]['list_treatment'][$check2]['total'] += 1;
                        array_push($data[$check]['list_treatment'][$check2]['list_order'], $list);
                    }else{
                        $data[$check]['list_treatment'][] = [
                            'id_treatment' => $treatment['id'],
                            'name' => $treatment['product_name'],
                            'treatment_room' => $treatment['outlet_treatment'][0]['treatment_room']['name'] ?? null,
                            'total' => 1,
                            'list_order' => [
                                $list
                            ]
                        ];
                    }
                }else{
                    $list_treatment = [];
                    $list_treatment[] = [
                        'id_treatment' => $treatment['id'],
                        'name' => $treatment['product_name'],
                        'treatment_room' => $treatment['outlet_treatment'][0]['treatment_room']['name'] ?? null,
                        'total' => 1,
                        'list_order' => [
                            $list
                        ]
                    ];
                    $data[] = [
                        'date' => date('d F Y', strtotime($list['date'])),
                        'is_today' => date('Y-m-d', strtotime($list['date'])) == date('Y-m-d') ? 1 : 0,
                        'list_treatment' => $list_treatment
                    ];
                }

            }

        }

        return $this->ok('Success to get cashiers', $data);

    }

    public function consultation(Request $request): mixed
    {

        $cashier = $request->user();
        $outlet = $cashier->outlet;

        $order_consultations = OrderConsultation::with([
            'doctor.doctor_room',
            'shift',
            'order.patient',
            'consultation.patient_diagnostic.diagnostic',
            'consultation.patient_grievance.grievance',
        ])->whereHas('order', function($order){
            $order->where(function($whereOrder){
                $whereOrder->where(function($whereOrder1){
                    $whereOrder1->whereNull('parent_id')
                    ->where('status', '<>', 'Cancelled');
                })
                ->orWhereHas('parent', function($parent) {
                    $parent->where('status', 'Finished');
                });
            });
        })->orderBy('schedule_date', 'desc')
        ->orderBy(function($orderBy){
            return $orderBy->from('doctor_shifts')
            ->whereRaw('`doctor_shifts`.id = `order_consultations`.doctor_shift_id')
            ->select('start');
        }, 'desc')
        ->orderByRaw("FIELD(status, \"On Progress\", \"Ready\", \"Pending\", \"Finished\")")
        ->orderBy('id', 'desc')
        ->get()->toArray();

        $data = [];
        $data_today = [];
        foreach($order_consultations ?? [] as $order_consultation){

            $grievances = [];
            $diagnostics = [];

            foreach($order_consultation['patient_grievance'] ?? [] as $patient_grievance){
                $grievances[] = [
                    'id_grievance' => $patient_grievance['grievance']['id'],
                    'grievance_name' => $patient_grievance['grievance']['grievance_name'],
                ];
            }

            foreach($order_consultation['patient_diagnostic'] ?? [] as $patient_diagnostic){
                $diagnostics[] = [
                    'id_diagnostic' => $patient_diagnostic['diagnostic']['id'],
                    'diagnostic_name' => $patient_diagnostic['diagnostic']['diagnostic_name'],
                ];
            }

            $shift_consul = date('H:i', strtotime($order_consultation['shift']['start'])).'-'.date('H:i', strtotime($order_consultation['shift']['end']));

            $bithdayDate = new DateTime($order_consultation['order']['patient']['birth_date']);
            $now = new DateTime();
            $interval = $now->diff($bithdayDate)->y;

            $list = [
                'id_order_consultation' => $order_consultation['id'],
                'id_doctor' => $order_consultation['doctor_id'],
                'title' => 'Consultation',
                'date' => date('Y-m-d', strtotime($order_consultation['schedule_date'])),
                'date_text' => date('d F Y', strtotime($order_consultation['schedule_date'])),
                'name' => $order_consultation['doctor']['name'],
                'status' => $order_consultation['status'],
                'queue' => $order_consultation['queue_code'],
                'patient_name' => $order_consultation['order']['patient']['name'],
                'gender'  => $order_consultation['order']['patient']['gender'],
                'phone' => substr_replace($order_consultation['order']['patient']['phone'], str_repeat('x', (strlen($order_consultation['order']['patient']['phone']) - 7)), 4, (strlen($order_consultation['order']['patient']['phone']) - 7)),
                'age'   => $interval.' years',
                'price_total' => $order_consultation['order_consultation_grandtotal'],
                'id_shift' => $order_consultation['doctor_shift_id'],
                'start' => date('H:i', strtotime($order_consultation['shift']['start'])).'-'.date('H:i', strtotime($order_consultation['shift']['end'])),
                'detail' => [
                    'grievances' => $grievances,
                    'diagnostics' => $diagnostics,
                    'date' => date('Y-m-d', strtotime($order_consultation['schedule_date'])),
                    'queue' => $order_consultation['queue_code'],
                ]
            ];

            if(date('Y-m-d', strtotime($list['date'])) == date('Y-m-d')){
                $status_consul = $order_consultation['status'] == 'Finished' ? 'Finished' : 'Pending';
                $list['doctor_room'] = $order_consultation['doctor']['doctor_room']['name'] ?? null;

                $startTime = new DateTime($order_consultation['start_time']);
                $now = new DateTime();
                $list['time'] = $order_consultation['status'] == 'On Progress' ? date('i:s', strtotime($now->diff($startTime)->h.':'.$now->diff($startTime)->i.':'.$now->diff($startTime)->s)) : ($order_consultation['status'] == 'Finished' ? date('H:i', strtotime($order_consultation['start_time'])).'-'.date('H:i', strtotime($order_consultation['finish_time'])) : null);

                $check = array_search($shift_consul.' '.$status_consul, array_column($data_today??[], 'key'));
                if($check !== false){
                    array_push($data_today[$check]['list_consultation'], $list);
                }else{
                    $data_today[] = [
                        'key' => $shift_consul.' '.$status_consul,
                        'shift' => $shift_consul,
                        'is_today' => 1,
                        'status' => $status_consul,
                        'list_consultation' => [
                            $list
                        ]
                    ];
                }

            }else{
                $list['start_end'] = date('H:i', strtotime($order_consultation['start_time'])).'-'.date('H:i', strtotime($order_consultation['finish_time']));

                $check = array_search(date('d F Y', strtotime($list['date'])), array_column($data??[], 'date'));
                if($check !== false){
                    $check2 = array_search($list['id_doctor'], array_column($data[$check]['list_consultation']??[], 'id_doctor'));
                    if($check2 !== false){
                        $data[$check]['list_consultation'][$check2]['total'] += 1;
                        $check3 = array_search($shift_consul, array_column($data[$check]['list_consultation'][$check2]['list_shift']??[], 'shift'));

                        if($check3 !== false){
                            array_push($data[$check]['list_consultation'][$check2]['list_shift'][$check3]['list_order'], $list);

                        }else{
                            $data[$check]['list_consultation'][$check2]['list_shift'][] = [
                                'shift' => $shift_consul,
                                'list_order' => [
                                    $list
                                ]
                            ];
                        }
                    }else{
                        $list_shift = [];
                        $list_shift[] = [
                            'shift' => $shift_consul,
                            'list_order' => [
                                $list
                            ]
                        ];

                        $data[$check]['list_consultation'][] = [
                            'id_doctor' => $order_consultation['doctor_id'],
                            'name' => $order_consultation['doctor']['name'],
                            'doctor_room' => $order_consultation['doctor']['doctor_room']['name'] ?? null,
                            'total' => 1,
                            'list_shift' => $list_shift
                        ];

                    }

                }else{
                    $list_shift = [];
                    $list_shift[] = [
                        'shift' => $shift_consul,
                        'list_order' => [
                            $list
                        ]
                    ];

                    $list_consultation = [];
                    $list_consultation[] = [
                        'id_doctor' => $order_consultation['doctor_id'],
                        'name' => $order_consultation['doctor']['name'],
                        'doctor_room' => $order_consultation['doctor']['doctor_room']['name'] ?? null,
                        'total' => 1,
                        'list_shift' => $list_shift

                    ];
                    $data[] = [
                        'date' => date('d F Y', strtotime($list['date'])),
                        'is_today' => 0,
                        'list_consultation' => $list_consultation
                    ];
                }
            }

        }

        $data_today = array_map(function($value){
            unset($value['key']);
            return $value;
        }, $data_today ?? []);
        $response =  array_merge($data_today,$data);
        return $this->ok('Success to get cashiers', $response);

    }
}
