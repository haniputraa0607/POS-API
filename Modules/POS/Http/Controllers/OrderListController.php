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
            ->where('status', 'Pending')
            ->where('send_to_transaction', 0)
            ->orderBy('order_date', 'desc')
            ->get()->toArray();


        }elseif($post['type'] == 'history'){

        }elseif($post['type'] == 'ticket'){

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
            ->orderBy('order_date', 'desc')
            ->get()->toArray();

        }else{
            return $this->error('Invalid Type');
        }

        $data = [];
        if($post['type'] != 'ticket'){
            foreach($orders ?? [] as $order){

                $order_name = [];
                $order_queue = [];
                $types = [];

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

                if(isset($order['child']['order_consultations'])){
                    $order_name[] = 'Consul';
                    $types[] = 'Consultation';
                    if($order['child']['order_consultations'][0]['queue_code']){
                        $order_queue[] = $order['child']['order_consultations'][0]['queue_code'];
                    }

                }elseif(isset($order['order_consultations'])){
                    $order_name[] = 'Consul';
                    $types[] = 'Consultation';
                    if($order['order_consultations'][0]['queue_code']){
                        $order_queue[] = $order['order_consultations'][0]['queue_code'];
                    }
                }

                if($treatment > 0){
                    $order_name[] = 'Treatment-'.$treatment;
                    $types[] = 'Treatment';
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
                    'id_customer' => $order['patient']['id'],
                    'order_name' => $order['patient']['name'].' - '.implode(', ', $order_name),
                    'times' => $last_created,
                    'ticket' => implode(' / ', $order_queue),
                    'type' => implode(', ', $types)
                ];

                $check = array_search(date('d F Y', strtotime($date)), array_column($data??[], 'date'));
                if($check !== false){
                    array_push($data[$check]['list'], $new_list);
                }else{
                    $data[] = [
                        'date' => date('d F Y', strtotime($date)),
                        'list' => [
                            $new_list
                        ]
                    ];
                }
            }
        }
        return $this->ok('Success to get order', $data);

    }
}
