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
use Modules\Doctor\Entities\DoctorSuggestion;
use Modules\Doctor\Entities\DoctorSuggestionProduct;
use Modules\Doctor\Entities\DoctorSuggestionPrescription;

class SuggestionHistoriesController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function list(Request $request):mixed
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

        $order = Order::where('id', $post['id_order'])
        ->where('outlet_id', $outlet['id'])
        ->where('send_to_transaction', 0)
        ->where('is_submited', 1)
        ->first();

        if(!$order){
            return $this->error('Order not found');
        }

        $customer = Customer::with([
            'suggestions' => function($sgg){
                $sgg->with(['order'])
                ->orderBy('suggestion_date', 'desc');
            }
        ])->where('id', $order['patient_id'])
        ->first();

        if(!$customer){
            return $this->error('Patient not found');
        }

        $bithdayDate = new DateTime($customer['birth_date']);
        $now = new DateTime();
        $interval = $now->diff($bithdayDate)->y;

        $data = [
            'header' => [
                'id' => $customer['id'],
                'name'  => $customer['name'],
                'birth_date_text' => date('d F Y', strtotime($customer['birth_date'])),
                'age'   => $interval.' years',
                'no_rm'  => null,
                'date' => date('d F Y'),
                'id_doctor' => $doctor['id'],
                'name_doctor' => $doctor['name'],
            ],
            'histories' => [
                [
                    "date" => date('Y'),
                    "text" => date('Y'),
                    "list" => []
                ],
                [
                    "date" => date('Y', strtotime('-1 years')),
                    "text" => date('Y', strtotime('-1 years')),
                    "list" => []
                ],
                [
                    "date" => date('Y', strtotime('-2 years')),
                    "text" => date('Y', strtotime('-2 years')).' & Before',
                    "list" => []
                ],
            ]
        ];

        foreach($customer['suggestions'] ?? [] as $suggestion){

            $list = [
                'id_suggestion' => $suggestion['id'],
                'suggestion_date' => date('Y-m-d', strtotime($suggestion['suggestion_date'])),
                'suggestion_date_text' => date('d F Y', strtotime($suggestion['suggestion_date'])),
                'order_code' => $suggestion['order']['order_code']
            ];

            $check = array_search(date('Y', strtotime($suggestion['suggestion_date'])), array_column($data['histories']??[], 'date'));
            if($check !== false){
                array_push($data['histories'][$check]['list'], $list);
            }else{
                array_push($data['histories'][2]['list'], $list);
            }
        }

        return $this->ok('Succes to get list suggestions', $data);

    }

    public function detail(Request $request):mixed
    {
        $request->validate([
            'id_suggestion' => 'required',
        ]);

        $doctor = $request->user();
        $outlet = $doctor->outlet;
        $post = $request->json()->all();


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

        $suggestion = DoctorSuggestion::with([
            'patient',
            'suggestion_products.product',
            'suggestion_products.doctor',
            'suggestion_products.nurse',
            'suggestion_products.beautician',
            'suggestion_prescriptions.prescription',
            'order.order_products.product',
            'order.order_products.doctor',
            'order.order_products.nurse',
            'order.order_products.beautician',
            'order.order_products.treatment_patient',
            'order.order_products.step',
            'order.order_prescriptions.prescription.category',
            'order.order_consultations.consultation.patient_diagnostic.diagnostic',
            'order.order_consultations.consultation.patient_grievance.grievance',
            'order.order_consultations.shift',
            'order.order_consultations.doctor',
        ])->whereHas('order')
        ->where('id', $post['id_suggestion'])
        ->first();

        if(!$suggestion){
            return $this->error('Suggestion not found');
        }

        $bithdayDate = new DateTime($suggestion['patient']['birth_date']);
        $now = new DateTime();
        $interval = $now->diff($bithdayDate)->y;

        $data_original = [
            'item_list' => [
                'order_consultations' => [],
                'order_products' => [],
                'order_treatments' => [],
                'order_prescriptions' => [],
            ],
            'ticket' => [],
            'summary' => [
                [
                    'label' => 'Subtotal',
                    'value' => $suggestion['order_subtotal']
                ],
                [
                    'label' => 'Tax',
                    'value' => (float)$suggestion['order_tax']
                ],
                [
                    'label' => 'Payable Ammount',
                    'value' => $suggestion['order_grandtotal']
                ],
            ]
        ];

        $original_same_purchase = true;
        $id_suggstion_products = [];
        $id_suggstion_prescriptions = [];
        foreach($suggestion['suggestion_products'] ?? [] as $suggestion_product){

            if($suggestion_product['type'] == 'Product'){
                $data_original['item_list']['order_products'][] = [
                    'suggestion_product_id' => $suggestion_product['id'],
                    'product_id'            => $suggestion_product['product']['id'],
                    'product_name'          => $suggestion_product['product']['product_name'],
                    'qty'                   => $suggestion_product['qty'],
                    'price_total'           => $suggestion_product['order_product_grandtotal'],
                    'is_purchased'          => $suggestion_product['not_purchase']  == 0 ? 1 : 0
                ];
            }else{
                $data_original['item_list']['order_treatments'][] = [
                    'suggestion_product_id' => $suggestion_product['id'],
                    'product_id'            => $suggestion_product['product']['id'],
                    'product_name'          => $suggestion_product['product']['product_name'],
                    'schedule_date'         => date('d F Y', strtotime($suggestion_product['schedule_date'])),
                    'schedule'              => date('Y-m-d', strtotime($suggestion_product['schedule_date'])),
                    'price_total'           => $suggestion_product['order_product_grandtotal'],
                    'queue'                 => $suggestion_product['not_purchase']  == 0 ? ($suggestion_product['queue_code'] ?? null) : null,
                    'progress'              => $suggestion_product['step'].'/'.$suggestion_product['total_step'],
                    'doctor_name'           => $suggestion_product['doctor']['name'] ?? null,
                    'nurse_name'            => $suggestion_product['nurse']['name'] ?? null,
                    'beautician_name'       => $suggestion_product['beautician']['name'] ?? null,
                    'is_purchased'          => $suggestion_product['not_purchase']  == 0 ? 1 : 0
                ];

                $data_original['ticket'][] = [
                    'type'          => 'treatment',
                    'type_text'     => 'TREATMENT',
                    'product_name'  => $suggestion_product['product']['product_name'],
                    'schedule_date' => date('d F Y', strtotime($suggestion_product['schedule_date'])),
                    'time'          => date('H:i', strtotime($outlet_shift['open'])).' - '.date('H:i', strtotime($outlet_shift['close'])),
                    'queue'         => $suggestion_product['queue_code'] ?? 'CANCELLED',
                    'is_purchased'  => $suggestion_product['not_purchase']  == 0 ? 1 : 0
                ];
            }
            if($suggestion_product['not_purchase']  == 1){
                $original_same_purchase = false;
            }else{
                $id_suggstion_products[] = $suggestion_product['order_product_id'];
            }
        }

        $total_suggestion_prescription = 0;
        $queue_suggestion_prescription = null;
        foreach($suggestion['suggestion_prescriptions'] ?? [] as $key => $suggestion_prescription){

            $data_original['item_list']['order_prescriptions'][] = [
                'suggestion_prescription_id' => $suggestion_prescription['id'],
                'prescription_id'            => $suggestion_prescription['prescription']['id'],
                'prescription_name'          => $suggestion_prescription['prescription']['prescription_name'],
                'type'                       => $suggestion_prescription['prescription']['category']['category_name'] ?? null,
                'unit'                       => $suggestion_prescription['prescription']['unit'],
                'qty'                        => $suggestion_prescription['qty'],
                'price_total'                => $suggestion_prescription['order_prescription_grandtotal'],
                'is_purchased'               => $suggestion_prescription['not_purchase']  == 0 ? 1 : 0
            ];

            if($suggestion_prescription['not_purchase'] == 0){
                $total_suggestion_prescription = $total_suggestion_prescription + 1;
                $queue_suggestion_prescription = $suggestion_prescription['queue_code'];
                $id_suggstion_products[] = $suggestion_product['order_product_id'];
            }else{
                $original_same_purchase = false;
            }

        }

        if($total_suggestion_prescription > 0){
            $data_original['ticket'][] = [
                'type' => 'prescription',
                'type_text' => 'PRESCRIPTION',
                'schedule_date' => date('d F Y', strtotime($suggestion['suggestion_date'])),
                'qty' => $total_suggestion_prescription.' item prescription',
                'customer' => $suggestion['patient']['name'],
                'queue' => $queue_suggestion_prescription ?? 'CANCELLED'
            ];

        }

        if($suggestion['order']['send_to_transaction'] == 1){
            $data_purchased = [
                'item_list' => [
                    'order_consultations' => [],
                    'order_products' => [],
                    'order_treatments' => [],
                    'order_prescriptions' => [],
                ],
                'ticket' => [],
                'summary' => [
                    [
                        'label' => 'Subtotal',
                        'value' => $suggestion['order']['order_subtotal']
                    ],
                    [
                        'label' => 'Tax',
                        'value' => (float)$suggestion['order']['order_tax']
                    ],
                    [
                        'label' => 'Payable Ammount',
                        'value' => $suggestion['order']['order_grandtotal']
                    ],
                ]
            ];
        }else{
            $data_purchased = [
                'item_list' => [],
                'ticket' => [],
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
                ]
            ];
        }

        foreach($suggestion['order']['order_consultations'] ?? [] as $key => $ord_con){
            $consul = [];
            $is_submit = 0;
            $grievances = [];

            if($ord_con['consultation']){
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
                $is_submit = $ord_con['consultation']['session_end'];
                $is_submit = 0;
            }

            foreach($ord_con['consultation']['patient_grievance'] ?? [] as $grievance){
                if($grievance['from_pos'] == 1){
                    $grievances[] = [
                        'id'             => $grievance['grievance']['id'],
                        'grievance_name' => $grievance['grievance']['grievance_name'],
                        'notes'          => $grievance['notes'] ?? null,
                    ];
                }
            }

            $order_consultation = [
                'order_consultation_id' => $ord_con['id'],
                'doctor_id'             => $ord_con['doctor']['id'],
                'doctor_name'           => $ord_con['doctor']['name'],
                'schedule_date'         => date('d F Y', strtotime($ord_con['schedule_date'])),
                'time'                  => date('H:i', strtotime($ord_con['shift']['start'])).'-'.date('H:i', strtotime($ord_con['shift']['end'])),
                'price_total'           => $ord_con['order_consultation_grandtotal'],
                'queue'                 => $ord_con['queue_code'] ?? 'TBD',
                'consultation'          => $consul,
                'grievances'            => $grievances,
                'submited_by_doctor'    => $is_submit,
                'is_purchased'          => 1
            ];

            $data_original['item_list']['order_consultations'][] = $order_consultation;
            if($suggestion['order']['send_to_transaction'] == 1){
                $data_purchased['item_list']['order_consultations'][] = $order_consultation;
            }
        }

        if($suggestion['order']['send_to_transaction'] == 1){
            foreach($suggestion['order']['order_products'] ?? [] as $order_product){

                if($order_product['type'] == 'Product'){
                    $data_purchased['item_list']['order_products'][] = [
                        'order_product_id' => $order_product['id'],
                        'product_id'       => $order_product['product']['id'],
                        'product_name'     => $order_product['product']['product_name'],
                        'qty'              => $order_product['qty'],
                        'price_total'      => $order_product['order_product_grandtotal'],
                        'is_purchased'     => 1
                    ];
                }else{
                    $progress = null;
                    if($order_product['treatment_patient'] && isset($order_product['treatment_patient']['doctor_id']) && isset($order_product['step'])){
                        $progress = $order_product['step']['step'].'/'.$order_product['treatment_patient']['step'];
                    }

                    $data_purchased['item_list']['order_treatments'][] = [
                        'order_product_id' => $order_product['id'],
                        'product_id'       => $order_product['product']['id'],
                        'product_name'     => $order_product['product']['product_name'],
                        'schedule_date'    => date('d F Y', strtotime($order_product['schedule_date'])),
                        'schedule'         => date('Y-m-d', strtotime($order_product['schedule_date'])),
                        'price_total'      => $order_product['order_product_grandtotal'],
                        'queue'            => $order_product['queue_code'] ?? null,
                        'progress'         => $progress,
                        'doctor_name'      => $order_product['doctor']['name'] ?? null,
                        'nurse_name'       => $order_product['nurse']['name'] ?? null,
                        'beautician_name'  => $order_product['beautician']['name'] ?? null,
                        'is_purchased'     => 1
                    ];

                    $data_purchased['ticket'][] = [
                        'type'          => 'treatment',
                        'type_text'     => 'TREATMENT',
                        'product_name'  => $order_product['product']['product_name'],
                        'schedule_date' => date('d F Y', strtotime($order_product['schedule_date'])),
                        'time'          => date('H:i', strtotime($outlet_shift['open'])).' - '.date('H:i', strtotime($outlet_shift['close'])),
                        'queue'         => $order_product['queue_code'] ?? 'TBD',
                        'is_purchased'  => 1
                    ];
                }

                if($original_same_purchase){
                    $check_exist = array_search($order_product['id'], $id_suggstion_products);
                    if($check_exist === false){
                        $original_same_purchase = false;
                    }
                }
            }

            $total_prescription = 0;
            $queue_prescription = null;
            foreach($suggestion['order']['order_prescriptions'] ?? [] as $key => $order_prescription){

                $data_purchased['item_list']['order_prescriptions'][] = [
                    'order_prescription_id' => $order_prescription['id'],
                    'prescription_id'       => $order_prescription['prescription']['id'],
                    'prescription_name'     => $order_prescription['prescription']['prescription_name'],
                    'type'                  => $order_prescription['prescription']['category']['category_name'] ?? null,
                    'unit'                  => $order_prescription['prescription']['unit'],
                    'qty'                   => $order_prescription['qty'],
                    'price_total'           => $order_prescription['order_prescription_grandtotal'],
                    'is_purchased'          => 1
                ];

                $total_prescription = $total_prescription + 1;
                $queue_prescription = $order_prescription['queue_code'];

                if($original_same_purchase){
                    $check_exist = array_search($order_prescription['id'], $id_suggstion_prescriptions);
                    if($check_exist === false){
                        $original_same_purchase = false;
                    }
                }
            }

            if($total_prescription > 0){
                $data_purchased['ticket'][] = [
                    'type' => 'prescription',
                    'type_text' => 'PRESCRIPTION',
                    'schedule_date' => date('d F Y', strtotime($suggestion['suggestion_date'])),
                    'qty' => $total_prescription.' item prescription',
                    'customer' => $suggestion['patient']['name'],
                    'queue' => $queue_prescription ?? 'TBD'
                ];

            }
        }

        $data = [
            'header' => [
                'id' => $suggestion['patient']['id'],
                'name'  => $suggestion['patient']['name'],
                'birth_date_text' => date('d F Y', strtotime($suggestion['patient']['birth_date'])),
                'age'   => $interval.' years',
                'no_rm'  => null,
                'date' => date('d F Y'),
                'id_doctor' => $doctor['id'],
                'name_doctor' => $doctor['name'],
            ],
            'original' => $data_original,
            'status' => $original_same_purchase,
            'purchased' => $data_purchased,
        ];

        return $this->ok('Succes to get detail suggestion', $data);

    }
}
