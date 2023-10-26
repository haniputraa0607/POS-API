<?php

namespace Modules\POS\Http\Controllers;

namespace Modules\POS\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Outlet\Http\Controllers\OutletController;
use Illuminate\Http\JsonResponse;
use Modules\Order\Entities\Order;
use Modules\Order\Entities\OrderProduct;
use Modules\Order\Entities\OrderConsultation;
use Modules\Order\Entities\OrderPrescription;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductOutletStock;
use Modules\Product\Http\Controllers\ProductController;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Entities\TreatmentPatient;
use Modules\Customer\Entities\TreatmentPatientStep;
use App\Jobs\GenerateQueueOrder;
use Modules\User\Entities\User;
use Modules\Doctor\Entities\DoctorShift;
use App\Http\Models\Setting;
use Modules\Consultation\Entities\Consultation;
use Modules\Grievance\Entities\Grievance;
use Modules\PatientGrievance\Entities\PatientGrievance;
use Modules\PatientDiagnostic\Entities\PatientDiagnostic;
use App\Lib\MyHelper;
use Modules\Prescription\Entities\Prescription;
use Modules\Prescription\Entities\PrescriptionOutlet;
use Modules\Prescription\Entities\PrescriptionOutletLog;
use Modules\Prescription\Entities\ContainerStock;
use Modules\Prescription\Entities\SubstanceStock;
use Modules\Prescription\Http\Controllers\PrescriptionController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use DateTime;

class OrderListController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function list(Request $request):mixed
    {
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $make_new = false;
        $check_json = file_exists(storage_path() . "/json/outlet_status.json");
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

        $outlet_shift = $config[$outlet['id']]['schedule'][date('l')] ?? [];

        $post = $request->json()->all();

        $request->validate([
            'type' => 'required',
        ]);

        if($post['type'] == 'ongoing'){

            $orders = Order::with([
                'patient',
                'order_products',
                'order_prescriptions',
                'order_consultations',
                'child.order_consultations'
            ])
            ->where(function($where){
                $where->where(function($where2){
                    $where2->where('is_submited', 1)->where('is_submited_doctor', 1);
                });
                $where->orWhere(function($where2){
                    $where2->where('is_submited', 0)->where('is_submited_doctor', 0);
                });
            })
            ->where(function($where){
                $where->where(function($where2){
                    $where2->whereNotNull('parent_id')->where('is_submited', 1)->where('is_submited_doctor', 1);
                });
                $where->orWhereNull('parent_id');
            })
            ->where('status', 'Pending')
            ->where('send_to_transaction', 0)
            ->where('outlet_id', $outlet['id'])
            ->orderBy('order_date', 'desc')
            ->get()->toArray();


        }elseif($post['type'] == 'history'){

            $orders = Order::with([
                'patient',
                'order_products',
                'order_prescriptions',
                'order_consultations',
                'child.order_consultations'
            ])
            ->where(function($where){
                $where->where(function($where2){
                    $where2->where('is_submited', 1)->where('is_submited_doctor', 1);
                });
                $where->orWhere(function($where2){
                    $where2->where('is_submited', 0)->where('is_submited_doctor', 0);
                });
            })
            ->where('status', 'Finished')
            ->where('send_to_transaction', 1)
            ->where('outlet_id', $outlet['id'])
            ->orderBy('order_date', 'desc')
            ->get()->toArray();

        }elseif($post['type'] == 'ticket'){

            $orders = Order::with([
                'patient',
                'order_products.product',
                'order_consultations.doctor',
                'order_consultations.shift',
                'child.order_consultations.doctor',
                'child.order_consultations.shift'
            ])
            ->where(function($where){
                $where->where(function($where2){
                    $where2->where('is_submited', 1)->where('is_submited_doctor', 1);
                });
                $where->orWhere(function($where2){
                    $where2->where('is_submited', 0)->where('is_submited_doctor', 0);
                });
            })
            ->where(function($where2){
                $where2->where(function($where4){
                    $where4->whereHas('order_consultations')->orWhereHas('child.order_consultations');
                })
                ->orWhereHas('order_products', function($where3){
                    $where3->where('type', 'Treatment');
                });
            })
            ->where('status', 'Finished')
            ->where('send_to_transaction', 1)
            ->where('outlet_id', $outlet['id'])
            ->orderBy('order_date', 'desc')
            ->get()->toArray();

        }elseif($post['type'] == 'cancel'){

            $orders = Order::with([
                'patient',
                'order_products',
                'order_prescriptions',
                'order_consultations',
                'child.order_consultations'
            ])
            ->where(function($where){
                $where->where(function($where2){
                    $where2->where('is_submited', 1)->where('is_submited_doctor', 1);
                });
                $where->orWhere(function($where2){
                    $where2->where('is_submited', 0)->where('is_submited_doctor', 0);
                });
            })
            ->where('status', 'Cancelled')
            ->where('send_to_transaction', 0)
            ->where('outlet_id', $outlet['id'])
            ->orderBy('order_date', 'desc')
            ->get()->toArray();

        }else{
            return $this->error('Invalid Type');
        }

        $data[] = [
            'date' => date('d F Y'),
            'is_today' => 1,
            'list' => []
        ];
        if($post['type'] != 'ticket'){
            foreach($orders ?? [] as $order){

                $order_name = [];
                $order_queue = [];
                $types = [];

                $from_doctor = 0;
                $product = 0;
                $treatment = 0;
                foreach($order['order_products'] ?? [] as $order_product){

                    if($order_product['type'] == 'Product'){

                        $product += 1;
                    }elseif($order_product['type'] == 'Treatment'){

                        $treatment += 1;
                        $order_queue[] = $order_product['queue_code'];
                    }
                }

                if($product > 0){
                    $order_name[] = 'Prod-'.$product;
                    $types[] = 'Product';
                }

                if($order['is_submited_doctor'] == 0 && !$order['parent_id']){
                    if($order['child']['order_consultations']??false){
                        $order_name[] = 'Consul';
                        $types[] = 'Consultation';
                        if($order['child']['order_consultations'][0]['queue_code']){
                            $order_queue[] = $order['child']['order_consultations'][0]['queue_code'];
                        }

                    }elseif($order['order_consultations']??false){
                        $order_name[] = 'Consul';
                        $types[] = 'Consultation';
                        if($order['order_consultations'][0]['queue_code']){
                            $order_queue[] = $order['order_consultations'][0]['queue_code'];
                        }
                    }
                }

                if($treatment > 0){
                    $order_name[] = 'Treatment-'.$treatment;
                    $types[] = 'Treatment';
                }

                if($order['is_submited_doctor'] == 1){
                    $from_doctor = 1;
                }else{
                    $from_doctor = 0;
                }

                $prescription = 0;
                $queue_prescript = null;
                foreach($order['order_prescriptions'] ?? [] as $order_prescription){

                    $prescription += 1;
                    $queue_prescript = $order_prescription['queue_code'];
                }

                if($prescription > 0){
                    $order_name[] = 'Prescrip-'.$prescription;
                    $types[] = 'Prescription';
                    $order_queue[] = $queue_prescript;
                }

                if($post['type'] == 'cancel'){
                    $date = $order['cancel_date'];
                }else{
                    $date = $order['order_date'];
                }
                $order_date = new DateTime($date);
                $now = new DateTime();

                $interval_i = $now->diff($order_date)->i;
                $interval_h = $now->diff($order_date)->h;
                $interval_d = $now->diff($order_date)->d;
                $interval_m = $now->diff($order_date)->m;
                $interval_y = $now->diff($order_date)->y;

                if($interval_d == 0 && $interval_m == 0 && $interval_y == 0){
                    if($interval_h > 0){
                        $last_created = $interval_h.' Hours';
                    }else{
                        $last_created = $interval_i.' Minutes';
                    }
                }elseif($interval_d >= 1 && $interval_m == 0 && $interval_y == 0){
                    $last_created = $interval_d.' Days';
                }elseif($interval_m >= 1 && $interval_y == 0){
                    $last_created = $interval_m.' Months';
                }elseif($interval_y >= 1){
                    $last_created = $interval_y.' Years';
                }

                $new_list = [
                    'id_order' => $order['id'],
                    'from_doctor' => $from_doctor,
                    'id_customer' => $order['patient']['id'],
                    'order_name' => $order['patient']['name'].' - '.implode(', ', $order_name),
                    'times' => $last_created,
                    'ticket' => implode(' / ', $order_queue),
                    'type' => implode(', ', $types)
                ];

                if($post['type'] == 'ongoing'){
                    unset($new_list['ticket']);
                }

                $check = array_search(date('d F Y', strtotime($date)), array_column($data??[], 'date'));
                if($check !== false){
                    array_push($data[$check]['list'], $new_list);
                }else{
                    $data[] = [
                        'date' => date('d F Y', strtotime($date)),
                        'is_today' => 0,
                        'list' => [
                            $new_list
                        ]
                    ];
                }
            }
        }else{

            foreach($orders ?? [] as $order){

                foreach($order['order_products'] ?? [] as $order_product){

                    if($order_product['type'] == 'Treatment'){

                        $ticket = [
                            'id_order' => $order['id'],
                            'id_customer' => $order['patient']['id'],
                            'id_order_product' => $order_product['id'],
                            'ticket' => $order_product['queue_code'],
                            'name' => $order['patient']['name'],
                            'date' => $order_product['schedule_date'],
                            'time' => date('H:i', strtotime($outlet_shift['open'])).'-'.date('H:i', strtotime($outlet_shift['close'])),
                            'type' => 'treatment',
                            'detail' => $order_product['product']['product_name']
                        ];
                        $check = array_search(date('d F Y', strtotime($ticket['date'])), array_column($data??[], 'date'));
                        if($check !== false){
                            array_push($data[$check]['list'], $ticket);
                        }else{
                            $data[] = [
                                'date' => date('d F Y', strtotime($ticket['date'])),
                                'is_today' => 0,
                                'list' => [
                                    $ticket
                                ]
                            ];
                        }
                    }
                }

                if($order['is_submited_doctor'] == 0 && !$order['parent_id']){

                    if($order['child']['order_consultations']??false){
                        foreach($order['child']['order_consultations'] ?? [] as $order_consultation){

                            $ticket = [
                                'id_order' => $order['id'],
                                'id_customer' => $order['patient']['id'],
                                'id_order_consultation' => $order_consultation['id'],
                                'ticket' => $order_consultation['queue_code'],
                                'name' => $order['patient']['name'],
                                'date' => $order_consultation['schedule_date'],
                                'time' => date('H:i', strtotime($order_consultation['shift']['start'])).'-'.date('H:i', strtotime($order_consultation['shift']['end'])),
                                'type' => 'consultation',
                                'detail' => $order_consultation['doctor']['name']
                            ];
                            $check = array_search(date('d F Y', strtotime($ticket['date'])), array_column($data??[], 'date'));
                            if($check !== false){
                                array_push($data[$check]['list'], $ticket);
                            }else{
                                $data[] = [
                                    'date' => date('d F Y', strtotime($ticket['date'])),
                                    'is_today' => 0,
                                    'list' => [
                                        $ticket
                                    ]
                                ];
                            }
                        }

                    }elseif($order['order_consultations']??false){
                        foreach($order['order_consultations'] ?? [] as $order_consultation){

                            $ticket = [
                                'id_order' => $order['id'],
                                'id_customer' => $order['patient']['id'],
                                'id_order_consultation' => $order_consultation['id'],
                                'ticket' => $order_consultation['queue_code'],
                                'name' => $order['patient']['name'],
                                'date' => $order_consultation['schedule_date'],
                                'time' => date('H:i', strtotime($order_consultation['shift']['start'])).'-'.date('H:i', strtotime($order_consultation['shift']['end'])),
                                'type' => 'consultation',
                                'detail' => $order_consultation['doctor']['name']
                            ];
                            $check = array_search(date('d F Y', strtotime($ticket['date'])), array_column($data??[], 'date'));
                            if($check !== false){
                                array_push($data[$check]['list'], $ticket);
                            }else{
                                $data[] = [
                                    'date' => date('d F Y', strtotime($ticket['date'])),
                                    'is_today' => 0,
                                    'list' => [
                                        $ticket
                                    ]
                                ];
                            }
                        }
                    }
                }

            }

        }
        return $this->ok('Success to get order', $data);
    }

    public function detail(Request $request):mixed
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $make_new = false;
        $check_json = file_exists(storage_path() . "/json/outlet_status.json");
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

        $outlet_shift = $config[$outlet['id']]['schedule'][date('l')] ?? [];

        if(isset($post['id_order'])){

            $order = Order::with([
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
                'order_products.step' => function($step) {
                    $step->where('status', 'Pending');
                }
            ])->where('id', $post['id_order'])
            ->where('outlet_id', $outlet['id'])
            ->where('send_to_transaction', 0)
            ->where(function($where){
                $where->where(function($where2){
                    $where2->where('is_submited', 1)->where('is_submited_doctor', 1);
                });
                $where->orWhere(function($where2){
                    $where2->where('is_submited', 0)->where('is_submited_doctor', 0);
                });
            })
            ->latest()
            ->first();

            if(!$order){
                return $this->error('Order not found');
            }

            $item_list = [];
            $ticket = [];
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
            $ord_prod = [];
            $ord_treat = [];
            $ord_consul = [];
            $ord_prescriptions = [];

            foreach($order['order_products'] ?? [] as $order_product){

                if($order_product['type'] == 'Product'){

                    $ord_prod[] = [
                        'order_product_id' => $order_product['id'],
                        'product_id'       => $order_product['product']['id'],
                        'product_name'     => $order_product['product']['product_name'],
                        'qty'              => $order_product['qty'],
                        'price_total'      => $order_product['order_product_grandtotal'],
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

                    $ticket[] = [
                        'type' => 'treatment',
                        'type_text' => 'TREATMENT',
                        'product_name'     => $order_product['product']['product_name'],
                        'schedule_date'    => date('d F Y', strtotime($order_product['schedule_date'])),
                        'time'  => date('H:i', strtotime($outlet_shift['open'])).' - '.date('H:i', strtotime($outlet_shift['close'])),
                        'queue'              => $order_product['queue_code'] ?? 'TBD',
                    ];
                }
            }

            $total_prescription = 0;
            $queue_prescription = null;
            foreach($order['order_prescriptions'] ?? [] as $key => $ord_pres){

                $ord_prescriptions[] = [
                    'order_prescription_id' => $ord_pres['id'],
                    'prescription_id'       => $ord_pres['prescription']['id'],
                    'prescription_name'     => $ord_pres['prescription']['prescription_name'],
                    'type'                  => $ord_pres['prescription']['category']['category_name'] ?? null,
                    'unit'                  => $ord_pres['prescription']['unit'],
                    'qty'                   => $ord_pres['qty'],
                    'price_total'           => $ord_pres['order_prescription_grandtotal'],
                ];
                $total_prescription = $total_prescription + 1;
                $queue_prescription = $ord_pres['queue_code'] ?? 'TBD';

            }
            if($total_prescription > 0){
                $ticket[] = [
                    'type' => 'prescription',
                    'type_text' => 'PRESCRIPTION',
                    'schedule_date' => date('d F Y', strtotime($order['order_date'])),
                    'qty' => $total_prescription.' item prescription',
                    'customer' => $order['patient']['name'],
                    'queue' => $queue_prescription
                ];

            }

            if($order['child']['order_consultations']??false){
                foreach($order['child']['order_consultations'] ?? [] as $order_consultation){
                    $consul = [];
                    if($order_consultation['consultation']){
                        $consul['queue_number']  = $order_consultation['queue_code'] ?? 'TBD';
                        $consul['schedule_date'] = date('d F Y', strtotime($order_consultation['schedule_date']));
                        $consul['grievance'] = [];
                        $consul['diagnostic'] = [];
                        foreach($order_consultation['consultation']['patient_grievance'] ?? [] as $grievance){
                            if($grievance['from_pos'] == 1){
                                $consul['grievance'][] = $grievance['grievance']['grievance_name'];
                            }
                        }
                    }

                    $ord_consul[] = [
                        'order_consultation_id'    => $order_consultation['id'],
                        'title'                    => 'Consultation',
                        'doctor_id'                => $order_consultation['doctor']['id'],
                        'doctor_name'              => $order_consultation['doctor']['name'],
                        'schedule_date'            => date('d F Y', strtotime($order_consultation['schedule_date'])),
                        'time'                     => date('H:i', strtotime($order_consultation['shift']['start'])).'-'.date('H:i', strtotime($order_consultation['shift']['end'])),
                        'price_total'              => $order_consultation['order_consultation_grandtotal'],
                        'queue'                    => $order_consultation['queue_code'],
                        'is_submit'                => 0,
                        'consultation'             => $consul,
                        'treatment_recommendation' => $ord_con['consultation']['treatment_recomendation'] ?? null,
                        'read_only'                => false
                    ];

                    $ticket[] = [
                        'type' => 'consultation',
                        'type_text' => 'CONSULTATION',
                        'doctor_name'              => $order_consultation['doctor']['name'],
                        'schedule_date'            => date('d F Y', strtotime($order_consultation['schedule_date'])),
                        'time'                     => date('H:i', strtotime($order_consultation['shift']['start'])).' - '.date('H:i', strtotime($order_consultation['shift']['end'])),
                        'queue'                    => $order_consultation['queue_code'] ?? 'TBD',
                    ];

                }
            }elseif($order['order_consultations']??false){
                foreach($order['order_consultations'] ?? [] as $order_consultation){

                    $consul = [];
                    if($order_consultation['consultation']){
                        $consul['queue_number']  = $order_consultation['queue_code'] ?? 'TBD';
                        $consul['schedule_date'] = date('d F Y', strtotime($order_consultation['schedule_date']));
                        $consul['grievance'] = [];
                        $consul['diagnostic'] = [];
                        if($order_consultation['consultation']['session_end'] == 1 || $order_consultation['consultation']['is_edit'] == 1){
                            foreach($order_consultation['consultation']['patient_grievance'] ?? [] as $grievance){
                                $consul['grievance'][] = $grievance['grievance']['grievance_name'];
                            }
                            foreach($order_consultation['consultation']['patient_diagnostic'] ?? [] as $diagnostic){
                                $consul['diagnostic'][] = $diagnostic['diagnostic']['diagnostic_name'];
                            }
                            $is_submit = $order_consultation['consultation']['session_end'];
                        }
                    }

                    $ord_consul[] = [
                        'order_consultation_id'    => $order_consultation['id'],
                        'title'                    => 'Consultation',
                        'doctor_id'                => $order_consultation['doctor']['id'],
                        'doctor_name'              => $order_consultation['doctor']['name'],
                        'schedule_date'            => date('d F Y', strtotime($order_consultation['schedule_date'])),
                        'time'                     => date('H:i', strtotime($order_consultation['shift']['start'])).'-'.date('H:i', strtotime($order_consultation['shift']['end'])),
                        'price_total'              => $order_consultation['order_consultation_grandtotal'],
                        'queue'                    => $order_consultation['queue_code'] ?? 'TBD',
                        'is_submit'                => $is_submit,
                        'consultation'             => $consul,
                        'treatment_recommendation' => $ord_con['consultation']['treatment_recomendation'] ?? null,
                        'read_only'                => true
                    ];

                }
            }

            $return = [
                'item_list' => [
                    'order_consultations' => $ord_consul,
                    'order_products' => $ord_prod,
                    'order_treatments' => $ord_treat,
                    'order_prescriptions'  => $ord_prescriptions,
                ],
                'ticket' => $ticket,
                'summary' => $summary,
            ];

            return $this->ok('Success get order data', $return);


        }else{
            return $this->error('ID not found');
        }

        return $this->ok('', []);
    }

    public function deleteOngoing(Request $request):mixed
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $order = Order::with(['order_products', 'order_consultations', 'order_prescriptions', 'child.order_consultations'])
        ->where('send_to_transaction', 0)
        ->where('status', 'Pending')
        ->where('id', $post['id_order'])
        ->first();

        DB::beginTransaction();
        if($order){

            if($order['order_products']){

                foreach($order['order_products'] ?? [] as $key2 => $order_product){

                    // $updateOrder = $order->update([
                    //     'order_subtotal'   => $order['order_subtotal'] - $order_product['order_product_subtotal'],
                    //     'order_gross'      => $order['order_gross'] - $order_product['order_product_subtotal'],
                    //     'order_grandtotal' => $order['order_grandtotal'] - $order_product['order_product_grandtotal'],
                    // ]);

                    // if(!$updateOrder){
                    //     DB::rollBack();
                    //     return $this->error('Failed update order');
                    // }

                    if($order_product['type'] == 'Product'){
                        $stock = ProductOutletStock::where('product_id', $order_product['product_id'])->where('outlet_id', $order['outlet_id'])->first();
                        if($stock){
                            $old_stock = clone $stock;
                            $stock->update([
                                'stock' =>  $stock['stock']+$order_product['qty']
                            ]);

                            if(!$stock){
                                DB::rollBack();
                                return $this->error('Failed update stock');
                            }
                            (new ProductController)->addLogProductStockLog($old_stock['id'], $order_product['qty'], $old_stock['stock'], $stock['stock'], 'Cancel Booking Order', null);
                        }
                    }

                    if($order_product['type'] == 'Treatment' && $order_product['treatment_patient_id']){
                        $step =  TreatmentPatientStep::where('id', $order_product['treatment_patient_step_id'])->where('treatment_patient_id', $order_product['treatment_patient_id'])->where('status', 'Pending')->first();
                        if($step){
                            OrderProduct::where('id', $order_product['id'])->update(['treatment_patient_step_id' => null]);
                            $delete_step = $step->delete();
                            if($delete_step){
                                $treatment_patient = TreatmentPatient::with(['steps'])->where('id', $order_product['treatment_patient_id'])->first();
                                if($treatment_patient){
                                    if(count($treatment_patient['steps']) <= 0){
                                        OrderProduct::where('treatment_patient_id', $treatment_patient['id'])->update(['treatment_patient_id' => null]);
                                        $delete_treatment_patient = $treatment_patient->delete();
                                        if(!$delete_treatment_patient){
                                            DB::rollBack();
                                            return $this->error('Failed delete treament patient');
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
                                    DB::rollBack();
                                    return $this->error('Failed to get treatment patient');
                                }
                            }else{
                                DB::rollBack();
                                return $this->error('Failed to delete step');
                            }
                        }else{
                            DB::rollBack();
                            return $this->error('Failed to get treatment patient step');
                        }

                    }

                }

            }

            foreach($order['order_prescriptions'] ?? [] as $key3 => $order_prescription){

                // $updateOrder = $order->update([
                //     'order_subtotal'   => $order['order_subtotal'] - $order_prescription['order_prescription_subtotal'],
                //     'order_gross'      => $order['order_gross'] - $order_prescription['order_prescription_subtotal'],
                //     'order_grandtotal' => $order['order_grandtotal'] - $order_prescription['order_prescription_grandtotal'],
                // ]);

                // if(!$updateOrder){
                //     DB::rollBack();
                //     return $this->error('Failed update order');
                // }

                $prescription = Prescription::with(['prescription_container', 'prescription_substances'])->where('id', $order_prescription['prescription_id'])->first();
                if($prescription){

                    if($prescription['is_custom'] == 0){

                        $stock = PrescriptionOutlet::where('prescription_id', $order_prescription['prescription_id'])->where('outlet_id', $order['outlet_id'])->first();

                        if($stock){
                            $old_stock = clone $stock;
                            $stock->update([
                                'stock' =>  $stock['stock']+$order_prescription['qty']
                            ]);

                            if(!$stock){
                                DB::rollBack();
                                return $this->error('Failed update stock');
                            }

                            (new PrescriptionController)->addLogPrescriptionStockLog($old_stock['id'], $order_prescription['qty'], $old_stock['stock'], $stock['stock'], 'Cancel Booking Order', null);

                        }
                    }else{
                        if($prescription['prescription_container'] ?? false){
                            $stock = ContainerStock::where('container_id', $prescription['prescription_container']['container']['id'])->where('outlet_id', $order['outlet_id'])->first();

                            if($stock){
                                $old_stock = clone $stock;
                                $stock->update([
                                    'qty' =>  $stock['qty']+$order_prescription['qty']
                                ]);

                                if(!$stock){
                                    DB::rollBack();
                                    return $this->error('Failed update stock');
                                }

                                (new PrescriptionController)->addLogContainerStockLog($old_stock['id'], $order_prescription['qty'], $old_stock['qty'], $stock['qty'], 'Cancel Booking Order', null);

                            }
                        }

                        foreach($prescription['prescription_substances'] ?? [] as $key_sub => $sub){
                            $stock = SubstanceStock::where('substance_id', $sub['substance']['id'])->where('outlet_id', $order['outlet_id'])->first();

                            if($stock){
                                $old_stock = clone $stock;
                                $stock->update([
                                    'qty' =>  $stock['qty']+($order_prescription['qty']*$sub['qty'])
                                ]);

                                if(!$stock){
                                    DB::rollBack();
                                    return $this->error('Failed update stock');
                                }

                                (new PrescriptionController)->addLogSubstanceStockLog($old_stock['id'], ($order_prescription['qty']*$sub['qty']), $old_stock['qty'], $stock['qty'], 'Cancel Booking Order', null);

                            }
                        }
                    }

                    // $delete_order_prescription = OrderPrescription::where('id', $order_prescription['id'])->delete();
                    // if(!$delete_order_prescription){
                    //     return $this->error('Failed to delete order precription');
                    // }

                }else{
                    DB::rollBack();
                    return $this->error('Prescription Not Found');
                }
            }

            if($order['child']['order_consultations']??false){
                foreach($order['child']['order_consultations'] ?? [] as $order_consultation){
                    $consultation = Consultation::where('order_consultation_id', $order_consultation['id'])->first();
                    if($consultation){
                        $patient_grievances = PatientGrievance::where('consultation_id', $consultation['id'])->get();
                        if($patient_grievances){
                            $patient_grievances->each->delete();
                        }

                        $patient_diagnostics = PatientDiagnostic::where('consultation_id', $consultation['id'])->get();
                        if($patient_diagnostics){
                            $patient_diagnostics->each->delete();
                        }

                        $consultation->delete();
                    }
                }

                $delete_sec_order = Order::where('id', $order['child']['id'])->update([
                    'status' => 'Cancelled',
                    'cancel_date' => date('Y-m-d H:i:s'),
                ]);
                if(!$delete_sec_order){
                    return $this->error('Failed to delete order');
                }

            }elseif($order['order_consultations']??false){
                foreach($order['order_consultations'] ?? [] as $order_consultation){
                    $consultation = Consultation::where('order_consultation_id', $order_consultation['id'])->first();
                    if($consultation){
                        $patient_grievances = PatientGrievance::where('consultation_id', $consultation['id'])->get();
                        if($patient_grievances){
                            $patient_grievances->each->delete();
                        }

                        $patient_diagnostics = PatientDiagnostic::where('consultation_id', $consultation['id'])->get();
                        if($patient_diagnostics){
                            $patient_diagnostics->each->delete();
                        }

                        $consultation->delete();
                    }
                }
            }

            $delete_order = $order->update([
                'status' => 'Cancelled',
                'cancel_date' => date('Y-m-d H:i:s'),
            ]);
            if(!$delete_order){
                return $this->error('Failed to delete order');
            }

            DB::commit();
            return $this->ok('Success delete order data', []);
        }

        return $this->ok('', []);

    }
}
