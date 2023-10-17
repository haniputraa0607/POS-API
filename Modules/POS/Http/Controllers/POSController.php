<?php

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
use Modules\Doctor\Http\Controllers\DoctorController;

class POSController extends Controller
{

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function home(Request $request):mixed
    {
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        if($outlet){

            $status = true;
            $product = null;
            $treatment = null;
            $consultation = null;

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

            $config = $config[$outlet['id']]['schedule'][date('l')] ?? [];

            $status = $config['status'] ?? false;


            if($status && (($config['open'] && date('H:i') < date('H:i', strtotime($config['open']))) || ($config['close'] && date('H:i') > date('H:i', strtotime($config['close']))))){
                $status = false;
            }
            if($status){

                $prescription = OrderPrescription::whereHas('order',function($order) use($outlet){ $order->where('outlet_id', $outlet['id'])->whereDate('order_date', date('Y-m-d'));})->orderBy('queue', 'desc')->first()['queue_code'] ?? null;
                $treatment = OrderProduct::whereHas('order',function($order) use($outlet){ $order->where('outlet_id', $outlet['id']);})->whereDate('schedule_date', date('Y-m-d'))->where('type', 'Treatment')->orderBy('queue', 'desc')->first()['queue_code'] ?? null;
                $consultation = OrderConsultation::whereHas('order',function($order) use($outlet){ $order->where('outlet_id', $outlet['id']);})->whereDate('schedule_date', date('Y-m-d'))->orderBy('queue', 'desc')->first()['queue_code'] ?? null;

            }

            $data = [
                'status_outlet' => $status,
                'queue' => [
                    'prescription' => $prescription,
                    'treatment' => $treatment,
                    'consultation' => $consultation
                ]
            ];

            return $this->ok('', $data);
        }else{
            return $this->error('Outlet not found');
        }

    }

    public function listService(Request $request):JsonResponse
    {
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $outlet_service = json_decode($outlet['activities'], true) ?? [];
        $data['service'] = [];
        $default_icon = config('default_icon') ?? [];

        foreach($outlet_service ?? [] as $key => $serv){

            if($serv != 'prescription'){
                $data['service'][] = [
                    'icon' => $default_icon[$serv]['icon_inactive'] ?? null,
                    'icon_active' => $default_icon[$serv]['icon_active'] ?? null,
                    'title' => $serv == 'consultation' ? 'Consultation' : ($serv == 'product' ? 'Product' : ($serv == 'treatment' ? 'Treatment' : '')),
                    'key' => $serv == 'consultation' ? 1 : ($serv == 'product' ? 2 : ($serv == 'treatment' ? 3 : 0))
                ];
            }
        }

        return $this->ok('', $data);

    }

    public function splash(Request $request):mixed
    {
        $data = [];
        $make_new = false;
        $check_json = file_exists(storage_path() . "/json/splash.json");
        if($check_json){
            $config = json_decode(file_get_contents(storage_path() . "/json/splash.json"), true);
            if(isset($config['pos'])){
                if(date('Y-m-d H:i', strtotime($config['pos']['updated_at']. ' +6 hours')) <= date('Y-m-d H:i')){
                    $make_new = true;
                }
            }else{
                $make_new = true;
            }
        }else{
            $make_new = true;
        }

        if($make_new){

            $splash = Setting::where('key', '=', 'splash_pos_apps')->first();
            $duration = Setting::where('key', '=', 'splash_pos_apps_duration')->pluck('value')->first();

            if(!empty($splash)){
                $splash = env('STORAGE_URL_API').$splash['value'];
            } else {
                $splash = null;
            }

            $ext=explode('.', $splash);
            $config['pos'] = [
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

    public function getOrder(Request $request):mixed
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(isset($post['id_order'])){

            return $this->getDataOrder(true, ['id_outlet' => $outlet['id'], 'id_order' => $post['id_order']],'');
        }

        return $this->ok('', []);

    }

    public function getDataOrder($status = true, $data, $message):mixed
    {
        $id_order = $data['id_order'];
        $id_outlet = $data['id_outlet'];

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

        $order = Order::with([
            'patient',
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
            'child.order_consultations.consultation.patient_grievance.grievance',
            'child.order_consultations.shift',
            'child.order_consultations.doctor',
            'order_products.treatment_patient',
            'order_products.step' => function($step) {
                $step->where('status', 'Pending');
            },
        ])->where('id', $id_order)
        ->where('outlet_id', $id_outlet)
        ->where('send_to_transaction', 0)
        ->where(function($where){
            $where->where(function($where2){
                $where2->where('is_submited', 1)->where('is_submited_doctor', 1);
            });
            $where->orWhere(function($where2){
                $where2->where('is_submited', 0)->where('is_submited_doctor', 0);
            });
        })
        ->latest()->first();

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

        if($order){

            if($order && ($order['is_submited'] == 1 && $order['is_submited_doctor'] == 0)){
                return $this->error('Cant create order, order has not been submited by the doctor');
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
                        // if($ord_pro['step'] != 1 && $ord_pro['treatment_patient']['step'] != 1){
                        // }
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

            if($order['child']['order_consultations']??false){
                foreach($order['child']['order_consultations'] ?? [] as $key => $ord_con){
                    $consul = [];
                    $is_submit = 0;
                    $grievances = [];

                    if($ord_con['consultation']){
                        $consul['queue_number']  = $ord_con['queue_code'];
                        $consul['schedule_date'] = date('d F Y', strtotime($ord_con['schedule_date']));
                        $consul['grievance'] = [];
                        $consul['diagnostic'] = [];
                        foreach($ord_con['consultation']['patient_grievance'] ?? [] as $grievance){
                            if($grievance['from_pos'] == 1){
                                $consul['grievance'][] = $grievance['grievance']['grievance_name'];
                            }
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

                    $ord_consul[] = [
                        'order_consultation_id' => $ord_con['id'],
                        'doctor_id'             => $ord_con['doctor']['id'],
                        'doctor_name'           => $ord_con['doctor']['name'],
                        'schedule_date'         => date('d F Y', strtotime($ord_con['schedule_date'])),
                        'time'                  => date('H:i', strtotime($ord_con['shift']['start'])).'-'.date('H:i', strtotime($ord_con['shift']['end'])),
                        'price_total'           => $ord_con['order_consultation_grandtotal'],
                        'queue'                 => $ord_con['queue_code'],
                        'consultation'          => $consul,
                        'grievances'            => $grievances,
                        'submited_by_doctor'    => $is_submit,
                        'read_only'             => false
                    ];
                }
            }elseif($order['order_consultations']??false){
                foreach($order['order_consultations'] ?? [] as $key => $ord_con){
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

                    $ord_consul[] = [
                        'order_consultation_id' => $ord_con['id'],
                        'doctor_id'             => $ord_con['doctor']['id'],
                        'doctor_name'           => $ord_con['doctor']['name'],
                        'schedule_date'         => date('d F Y', strtotime($ord_con['schedule_date'])),
                        'time'                  => date('H:i', strtotime($ord_con['shift']['start'])).'-'.date('H:i', strtotime($ord_con['shift']['end'])),
                        'price_total'           => $ord_con['order_consultation_grandtotal'],
                        'queue'                 => $ord_con['queue_code'],
                        'consultation'          => $consul,
                        'grievances'            => $grievances,
                        'submited_by_doctor'    => $is_submit,
                        'read_only'             => true
                    ];
                }

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

            $bithdayDate = new DateTime($order['patient']['birth_date']);
            $now = new DateTime();
            $interval = $now->diff($bithdayDate)->y;

            $return = [
                'user'                => [
                    'id'    => $order['patient']['id'],
                    'name'  => $order['patient']['name'],
                    'gender'  => $order['patient']['gender'],
                    'birth_date_text' => date('d F Y', strtotime($order['patient']['birth_date'])),
                    'age'   => $interval.' years',
                    'email' => substr_replace($order['patient']['email'], str_repeat('x', (strlen($order['patient']['email']) - 6)), 3, (strlen($order['patient']['email']) - 6)),
                    'phone' => substr_replace($order['patient']['phone'], str_repeat('x', (strlen($order['patient']['phone']) - 7)), 4, (strlen($order['patient']['phone']) - 7)),
                    'time' => 120
                ],
                'order_id'            => $order['id'],
                'order_code'          => $order['order_code'],
                'order_products'      => $ord_prod,
                'order_treatments'    => $ord_treat,
                'order_consultations' => $ord_consul,
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
        }else{
            return $this->error('ID not found');
        }


        return $this->ok('', $return);


    }

    public function addOrder(Request $request):JsonResponse
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(isset($post['id_customer'])){

            $order = Order::where('patient_id', $post['id_customer'])
            ->where('outlet_id', $outlet['id'])
            ->where('send_to_transaction', 0)
            ->latest()
            ->first();

            if($order && ($order['is_submited'] == 1 && $order['is_submited_doctor'] == 0)){
                return $this->error('Cant create order, order has not been submited by the doctor');
            }

            DB::beginTransaction();
            if(!$order){
                $last_code = Order::where('outlet_id', $outlet['id'])->latest('order_code')->first()['order_code']??'';
                $last_code_outlet = explode('-',$outlet['outlet_code'])[1];
                $last_code = $last_code == '' ? 0 : explode('-',$last_code)[1];
                $last_code = (int)$last_code + 1;
                $order_code = 'ORD'.$last_code_outlet.'-'.sprintf("%05d", $last_code);

                $order = Order::create([
                    'patient_id' => $post['id_customer'],
                    'outlet_id'  => $outlet['id'],
                    'cashier_id' => $cashier['id'],
                    'order_date' => date('Y-m-d H:i:s'),
                    'order_code' => $order_code,
                    'notes'      => $post['notes'] ?? null
                ]);
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
                                    'id_customer' => $post['id_customer'],
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
                                return $this->getDataOrder(true, ['id_outlet' => $outlet['id'], 'id_customer' => $post['id_customer']], 'Success to update order');
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
                            return $this->getDataOrder(true, ['id_outlet' => $outlet['id'], 'id_customer' => $post['id_customer']], 'Success to delete order');

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
                        return $this->getDataOrder(false, ['id_outlet' => $outlet['id'], 'id_customer' => $post['id_customer']], 'Treatment already exist in order');
                    }else{

                        if(($post['order']['continue']??false) == 1){
                            $customerPatient = TreatmentPatient::where('patient_id', $post['id_customer'])
                            ->where('treatment_id', $product['id'])
                            ->where('status', '<>', 'Finished')
                            ->whereDate('expired_date', '>=', date('y-m-d', strtotime($post['order']['date'])))
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
                return $this->getDataOrder(true, ['id_outlet' => $outlet['id'], 'id_outlet' => $outlet['id'], 'id_customer' => $post['id_customer']], 'Success to add new order');

            }elseif(($post['type']??false) == 'consultation'){

                $doctor = User::with(['shifts' => function($query) use($post){
                    $query->where('doctor_shift_id', $post['order']['id_shift']);
                    $query->where('user_id', $post['order']['id']);
                }])
                ->whereHas('shifts',function($query) use($post){
                    $query->where('doctor_shift_id', $post['order']['id_shift']);
                    $query->where('user_id', $post['order']['id']);
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
                    return $this->getDataOrder(false, ['id_outlet' => $outlet['id'], 'id_customer' => $post['id_customer']], 'Consultation already exist in order');
                }else{

                    $price = $doctor['shifts'][0]['price'] ?? $doctor['consultation_price'] ?? $outlet['consultation_price'];
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

                    if(isset($post['order']['grievance']) && count($post['order']['grievance']) > 0){

                        $consultation = Consultation::where('order_consultation_id', $create_order_consultation['id'])->first();
                        if(!$consultation){
                            $consultation = Consultation::create([
                                'order_consultation_id' => $create_order_consultation['id'],
                            ]);
                        }

                        $patient_grievance = [];
                        foreach($post['order']['grievance'] ?? [] as $key_gre => $gre){
                            $patient_grievance[] = [
                                'consultation_id' => $consultation['id'],
                                'grievance_id'    => $gre['id'],
                                'from_pos'        => 1,
                                'notes'           => $gre['notes'],
                                'created_at'      => date('Y-m-d H:i:s'),
                                'updated_at'      => date('Y-m-d H:i:s'),
                            ];
                        }

                        $insert = PatientGrievance::insert($patient_grievance);
                        if(!$insert){
                            DB::rollBack();
                            return $this->error('Grievance error');
                        }

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
                    return $this->getDataOrder(true, ['id_outlet' => $outlet['id'], 'id_customer' => $post['id_customer']], 'Success to add new order');

                }

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
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(!isset($post['id_customer'])){
            return $this->error('Customer not found');
        }

        DB::beginTransaction();

        return $this->deleteOrderData([
            'outlet' => $outlet,
            'type' => $post['type'] ?? null,
            'post' => [
                'id_customer' => $post['id_customer'],
                'id' => $post['id']
            ]
        ]);

    }

    public function deleteOrderData($data):JsonResponse
    {
        $outlet =  $data['outlet'];
        $type =  $data['type'];
        $post =  $data['post'];


        if(($type??false) == 'product' || ($type??false) == 'treatment'){

            $order_product = OrderProduct::with(['order'])->whereHas('order', function($order) use($post){
                $order->where('patient_id', $post['id_customer']);
                $order->where('send_to_transaction', 0);
                $order->where(function($whereSubmit){
                    $whereSubmit->where('is_submited', 0)
                    ->orWhere(function($whereSubmitOr){
                        $whereSubmitOr->where('is_submited', 1)
                        ->where('is_submited_doctor', 1);
                    });
                });
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
                                    DB::rollBack();
                                    return $this->error('Failed to delete treatment patient');
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

            DB::commit();
            return $this->getDataOrder(true, ['id_outlet' => $outlet['id'], 'id_customer' => $post['id_customer']], 'Success to delete order');

        }elseif(($type??false) == 'consultation'){

            $order_consultation = OrderConsultation::with(['order'])->whereHas('order', function($order) use($post){
                $order->where('patient_id', $post['id_customer']);
                $order->where('send_to_transaction', 0);
                $order->where(function($whereSubmit){
                    $whereSubmit->where('is_submited', 0)
                    ->orWhere(function($whereSubmitOr){
                        $whereSubmitOr->where('is_submited', 1)
                        ->where('is_submited_doctor', 1);
                    });
                });
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

            if(!$order){
                DB::rollBack();
                return $this->error('Order not found');
            }

            $order_consultation->delete();

            DB::commit();
            return $this->getDataOrder(true, ['id_outlet' => $outlet['id'], 'id_customer' => $post['id_customer']], 'Success to delete order');

        }else{
            return $this->error('Type is invalid');
        }
    }

    public function submitOrder(Request $request): mixed
    {

        $cashier = $request->user();
        $outlet = $cashier->outlet;
        $post = $request->json()->all();

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $is_error = false;
        $errors = [];
        if(isset($post['id_customer'])){

            DB::beginTransaction();
            $last_code = Order::where('outlet_id', $outlet['id'])->latest('order_code')->first()['order_code']??'';
            $last_code_outlet = explode('-',$outlet['outlet_code'])[1];
            $last_code = $last_code == '' ? 0 : explode('-',$last_code)[1];
            $last_code = (int)$last_code + 1;
            $order_code = 'ORD'.$last_code_outlet.'-'.sprintf("%05d", $last_code);

            $order = Order::create([
                'patient_id' => $post['id_customer'],
                'outlet_id'  => $outlet['id'],
                'cashier_id' => $cashier['id'],
                'order_date' => date('Y-m-d H:i:s'),
                'order_code' => $order_code,
                'notes'      => $post['notes'] ?? null
            ]);

            if(!$order){
                DB::rollBack();
                return $this->error('Failed to create Order');
            }

            $only_consul = true;
            foreach($post['order_products'] ?? [] as $order_product){
                $product = Product::with([
                    'global_price','outlet_price' => function($outlet_price) use ($outlet){
                        $outlet_price->where('outlet_id',$outlet['id']);
                    }, 'outlet_stock' => function($outlet_stock) use ($outlet){
                        $outlet_stock->where('outlet_id',$outlet['id']);
                    }
                ])->where('id', $order_product['id'])->first();

                if(!$product){
                    $is_error = true;
                    $errors[] = 'Product not found';
                    continue;
                }

                $price = ($product['outlet_price'][0]['price'] ?? $product['global_price']['price']) ?? 0;
                $stock = $product['outlet_stock'][0]['stock'] ?? 0;

                if($order_product['qty'] > $stock){
                    $is_error = true;
                    $errors[] = $product['product_name']. ' out of stock';
                    continue;
                }

                $store_order_product = OrderProduct::create([
                    'order_id'                 => $order['id'],
                    'product_id'               => $product['id'],
                    'type'                     => 'Product',
                    'qty'                      => $order_product['qty'],
                    'order_product_price'      => $price,
                    'order_product_subtotal'   => $order_product['qty']*$price,
                    'order_product_grandtotal' => $order_product['qty']*$price,
                ]);

                $price_to_order = ($order_product['qty']*$price);
                if(!$store_order_product){
                    $is_error = true;
                    $errors[] = 'Failed to order '.$product['product_name'];
                    continue;
                }

                $stock = ProductOutletStock::where('product_id', $product['id'])->where('outlet_id', $outlet['id'])->first();
                if($stock){
                    $old_stock = clone $stock;
                    $stock->update([
                        'stock' =>  $stock['stock']-$order_product['qty']
                    ]);

                    if(!$stock){
                        $is_error = true;
                        $errors[] = 'Failed to update stock '.$product['product_name'];
                        continue;
                    }

                    (new ProductController)->addLogProductStockLog($old_stock['id'], -$order_product['qty'], $old_stock['stock'], $stock['stock'], 'Booking Order', null);
                }

                $order->update([
                    'order_subtotal'   => $order['order_subtotal'] + $price_to_order,
                    'order_gross'      => $order['order_gross'] + $price_to_order,
                    'order_grandtotal' => $order['order_grandtotal'] + $price_to_order,
                ]);
                $only_consul = false;
            }

            foreach($post['order_treatments'] ?? [] as $order_treatment){
                $treatment = Product::with([
                    'global_price','outlet_price' => function($outlet_price) use ($outlet){
                        $outlet_price->where('outlet_id',$outlet['id']);
                    }
                ])->where('id', $order_treatment['id'])->first();

                if(!$treatment){
                    $is_error = true;
                    $errors[] = 'Treatment not found';
                    continue;
                }

                $price = $treatment['outlet_price'][0]['price'] ?? $treatment['global_price']['price'];

                $get_order_treatment = OrderProduct::where('order_id', $order['id'])->where('product_id', $treatment['id'])->whereDate('schedule_date',$order_treatment['date'])->where('type', 'Treatment')->first();
                if($get_order_treatment){
                    $is_error = true;
                    $errors[] = 'Treatment already exist in order';
                    continue;
                }else{

                    if(($order_treatment['continue']??false) == 1){
                        $customerPatient = TreatmentPatient::where('patient_id', $post['id_customer'])
                        ->where('treatment_id', $treatment['id'])
                        ->where('status', '<>', 'Finished')
                        ->whereDate('expired_date', '>=', date('y-m-d', strtotime($order_treatment['date'])))
                        ->first();

                    }else{
                        $customerPatient = TreatmentPatient::create([
                            'treatment_id' => $treatment['id'],
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
                        $is_error = true;
                        $errors[] = 'Failed to create order treatment';
                        continue;
                    }

                    $existCustomerPatientStep = TreatmentPatientStep::where('treatment_patient_id', $customerPatient['id'])->max('step') ?? 0;
                    if(($existCustomerPatientStep+1) > $customerPatient['step']){
                        $is_error = true;
                        $errors[] = 'Step cannot exceed those specified';
                        continue;
                    }
                    $customerPatientStep = TreatmentPatientStep::create([
                        'treatment_patient_id' => $customerPatient['id'],
                        'step'                 => $existCustomerPatientStep + 1,
                        'date'                 => $order_treatment['date'],
                    ]);

                    if(!$customerPatientStep){
                        $is_error = true;
                        $errors[] = 'Failed to create order treatment';
                        continue;
                    }

                    $store_order_treatment = OrderProduct::create([
                        'order_id'                  => $order['id'],
                        'product_id'                => $treatment['id'],
                        'type'                      => 'Treatment',
                        'schedule_date'             => $order_treatment['date'],
                        'treatment_patient_id'      => $customerPatient['id'] ?? null,
                        'treatment_patient_step_id' => $customerPatientStep['id'] ?? null,
                        'qty'                       => 1,
                        'order_product_price'       => $price,
                        'order_product_subtotal'    => $price,
                        'order_product_grandtotal'  => $price,
                    ]);

                    if(!$store_order_treatment){
                        $is_error = true;
                        $errors[] = 'Failed to create order treatment';
                        continue;
                    }

                    $price_to_order = $price;

                    $order->update([
                        'order_subtotal'   => $order['order_subtotal'] + $price_to_order,
                        'order_gross'      => $order['order_gross'] + $price_to_order,
                        'order_grandtotal' => $order['order_grandtotal'] + $price_to_order,
                    ]);
                    $only_consul = false;
                }
            }

            $is_consultation = false;
            foreach($post['order_consultations'] ?? [] as $order_consultation){

                $doctor = User::with(['shifts' => function($query) use($order_consultation){
                    $query->where('doctor_shift_id', $order_consultation['id_shift']);
                    $query->where('user_id', $order_consultation['id']);
                }])
                ->whereHas('shifts',function($query) use($order_consultation){
                    $query->where('doctor_shift_id', $order_consultation['id_shift']);
                    $query->where('user_id', $order_consultation['id']);
                })
                ->whereHas('doctor_schedules.schedule_dates',function($query) use($order_consultation){
                    $query->where('schedule_month', date('m', strtotime($order_consultation['date'])));
                    $query->where('schedule_year', date('Y', strtotime($order_consultation['date'])));
                    $query->where('doctor_schedule_dates.date', date('Y-m-d', strtotime($order_consultation['date'])));
                })
                ->where('id', $order_consultation['id'])->first();

                if(!$doctor){
                    $is_error = true;
                    $errors[] = 'Doctor not found';
                    continue;
                }

                $check_consultation = OrderConsultation::whereHas('order',function($hasOrder) use($order){
                    $hasOrder->where('patient_id', $order['patient_id'])
                    ->where('status', 'Pending');
                })->where('doctor_id', $doctor['id'])
                ->where('schedule_date', date('Y-m-d', strtotime($order_consultation['date'])))
                ->where('doctor_shift_id', $order_consultation['id_shift'])
                ->first();
                if($check_consultation){
                    $is_error = true;
                    $errors[] = 'Consultation already exist in order';
                    continue;
                }else{
                    $price = $doctor['shifts'][0]['price'] ?? $doctor['consultation_price'] ?? $outlet['consultation_price'];

                    if(!$only_consul){
                        $order_sec = Order::create([
                            'patient_id' => $post['id_customer'],
                            'outlet_id'  => $outlet['id'],
                            'cashier_id' => $cashier['id'],
                            'order_date' => date('Y-m-d H:i:s'),
                            'order_code' => $order_code,
                            'notes'      => $post['notes'] ?? null,
                            'parent_id'  => $order['id']
                        ]);

                        if(!$order_sec){
                            $is_error = true;
                            $errors[] = 'Failed to create order consultation';
                            continue;
                        }
                    }

                    $store_order_consultation = OrderConsultation::create([
                        'order_id'                 => $only_consul == true ? $order['id'] : $order_sec['id'],
                        'doctor_id'                => $doctor['id'],
                        'schedule_date'            => $order_consultation['date'],
                        'doctor_shift_id'          => $order_consultation['id_shift'],
                        'order_consultation_price'      => $price,
                        'order_consultation_subtotal'   => $price,
                        'order_consultation_grandtotal' => $price,
                    ]);

                    if(!$store_order_consultation){
                        $is_error = true;
                        $errors[] = 'Failed to create order consultation';
                        continue;
                    }

                    if(isset($order_consultation['grievance']) && count($order_consultation['grievance']) > 0){

                        $consultation = Consultation::where('order_consultation_id', $store_order_consultation['id'])->first();
                        if(!$consultation){
                            $consultation = Consultation::create([
                                'order_consultation_id' => $store_order_consultation['id'],
                            ]);
                        }

                        $patient_grievance = [];
                        foreach($order_consultation['grievance'] ?? [] as $key_gre => $gre){
                            $patient_grievance[] = [
                                'consultation_id' => $consultation['id'],
                                'grievance_id'    => $gre['id'],
                                'from_pos'        => 1,
                                'notes'           => $gre['notes'],
                                'created_at'      => date('Y-m-d H:i:s'),
                                'updated_at'      => date('Y-m-d H:i:s'),
                            ];
                        }

                        $insert = PatientGrievance::insert($patient_grievance);
                        if(!$insert){
                            $is_error = true;
                            $errors[] = 'Grievance error';
                            continue;
                        }

                    }

                    $is_consultation = true;
                    if($only_consul){
                        $order->update([
                            'order_subtotal'   => $order['order_subtotal'] + $price,
                            'order_gross'      => $order['order_gross'] + $price,
                            'order_grandtotal' => $order['order_grandtotal'] + $price,
                        ]);
                    }else{
                        $order_sec->update([
                            'order_subtotal'   => $order_sec['order_subtotal'] + $price,
                            'order_gross'      => $order_sec['order_gross'] + $price,
                            'order_grandtotal' => $order_sec['order_grandtotal'] + $price,
                        ]);
                    }
                }
            }

            if($is_error){
                DB::rollBack();
                return $this->error($errors);
            }else{
                if($is_consultation){
                    if($only_consul){
                        $update = $order->update([
                            'is_submited' => 1,
                        ]);
                        $value_consul = true;
                    }else{
                        $update = $order_sec->update([
                            'is_submited' => 1,
                        ]);
                        $value_consul = false;

                    }
                    if(!$update){

                        $is_error = true;
                        $errors[] = 'Failed submit order';
                        DB::rollBack();

                        return $this->error($errors);
                    }

                    $generate = GenerateQueueOrder::dispatch($order)->onConnection('generatequeueorder');
                    if(!$only_consul){
                        $generate = GenerateQueueOrder::dispatch($order_sec)->onConnection('generatequeueorder');
                    }

                    DB::commit();

                    $return = [
                        'id_order' => $order['id'],
                        'consultation' => $value_consul,
                        'list_payment' => $this->availablePayment($order) ?? []
                    ];

                    return $this->ok('Success to submit order', $return);
                }else{

                    $generate = GenerateQueueOrder::dispatch($order)->onConnection('generatequeueorder');
                    DB::commit();

                    $return = [
                        'id_order' => $order['id'],
                        'consultation' => false,
                        'list_payment' => $this->availablePayment($order) ?? []
                    ];

                    return $this->ok('Success to submit order', $return);
                }

            }

        }elseif(isset($post['id_order'])){

            $order = Order::where('id', $post['id_order'])->first();
            $only_consul = true;
            $is_consultation = false;

            DB::beginTransaction();

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
                        $only_consul = false;
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
                    $only_consul = false;

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
                    $only_consul = false;
                }
            }

            if(!$is_error){
                if($add_prod){
                    $other_order_products = OrderProduct::where('order_id', $order['id'])->whereNotIn('product_id', $add_prod)->where('type', 'Product')->get()->toArray();
                }else{
                    $other_order_products = OrderProduct::where('order_id', $order['id'])->where('type', 'Product')->get()->toArray();
                }

                foreach($other_order_products ?? [] as $other_order_product){
                    $delete = (new DoctorController)->deleteOrderData([
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
                    $order = Order::where('id', $post['id_order'])->first();
                }
            }

            $add_treat = [];
            foreach($post['order_treatments'] ?? [] as $keyuu => $post_order_treatment){
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
                        $order = Order::where('id', $post['id_order'])->first();

                    }else{
                        $add_treat[] = [
                            'id' => $treatment['id'],
                            'schedule_date' => $post_order_treatment['date']
                        ];
                        $only_consul = false;
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
                    $customerPatient = TreatmentPatient::create([
                        'treatment_id' => $treatment['id'],
                        'patient_id' => $order['patient_id'],
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
                    'product_id'                => $treatment['id'],
                    'type'                      => $treatment['type'],
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

                $add_treat[] = [
                    'id' => $treatment['id'],
                    'schedule_date' => $post_order_treatment['date']
                ];
                $only_consul = false;

            }

            if(!$is_error){
                if($add_treat){
                    $other_order_products = OrderProduct::where('order_id', $order['id'])
                    ->where(function($where2) use ($add_treat){
                        foreach($add_treat ?? [] as $key_tr => $tr){
                            $where2->whereNot(function($where3) use($tr){
                                $where3->where('product_id', $tr['id'])->where('schedule_date', $tr['schedule_date']);
                            });
                        }
                    })
                    ->where('type', 'Treatment')->get()->toArray();
                }else{
                    $other_order_products = OrderProduct::where('order_id', $order['id'])->where('type', 'Treatment')->get()->toArray();
                }

                foreach($other_order_products ?? [] as $other_order_product){
                    $delete = (new DoctorController)->deleteOrderData([
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
                    $order = Order::where('id', $post['id_order'])->first();
                }
            }

            $add_prescript = [];
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

                    $order_prescription = OrderPrescription::where('order_id', $order['id'])->where('prescription_id', $prescription['id'])->first();
                    if($order_prescription){

                        if(($post_order_prescription['qty']??false) >= 1){

                            if($post_order_prescription['qty']>$order_prescription['qty']){

                                $old_order_prescription = clone $order_prescription;
                                $order_prescription->update([
                                    'qty'                      => $post_order_prescription['qty'],
                                    'order_prescription_subtotal'   => ($post_order_prescription['qty']*$order_prescription['order_prescription_price']),
                                    'order_prescription_grandtotal' => ($post_order_prescription['qty']*$order_prescription['order_prescription_price']),
                                ]);

                                $update_order = $order->update([
                                    'order_subtotal'   => $order_prescription['order']['order_subtotal'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_subtotal']),
                                    'order_gross'      => $order_prescription['order']['order_gross'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_subtotal']),
                                    'order_grandtotal' => $order_prescription['order']['order_grandtotal'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_grandtotal']),
                                ]);

                            }elseif($post_order_prescription['qty']<$order_prescription['qty']){

                                $old_order_prescription = clone $order_prescription;
                                $order_prescription->update([
                                    'qty'                      => $post_order_prescription['qty'],
                                    'order_prescription_subtotal'   => ($post_order_prescription['qty']*$order_prescription['order_prescription_price']),
                                    'order_prescription_grandtotal' => ($post_order_prescription['qty']*$order_prescription['order_prescription_price']),
                                ]);

                                $update_order = $order->update([
                                    'order_subtotal'   => $order_prescription['order']['order_subtotal'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_subtotal']),
                                    'order_gross'      => $order_prescription['order']['order_gross'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_subtotal']),
                                    'order_grandtotal' => $order_prescription['order']['order_grandtotal'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_grandtotal']),
                                ]);

                            }elseif($post_order_prescription['qty']==$order_prescription['qty']){
                                $add_prescript[] = $prescription['id'];
                                $only_consul = false;
                                continue;
                            }

                            if(!$update_order){
                                $is_error = true;
                                $errors[] = 'Order not found';
                                continue;
                            }

                            $stock = PrescriptionOutlet::where('prescription_id', $prescription['id'])->where('outlet_id', $outlet['id'])->first();

                            if($stock){
                                $old_stock = clone $stock;
                                if($post_order_prescription['qty']>$old_order_prescription['qty']){
                                    $qty = $post_order_prescription['qty'] - $old_order_prescription['qty'];
                                    $qty_log = -$qty;
                                    $stock->update([
                                        'stock' =>  $stock['stock']-$qty
                                    ]);
                                }elseif($post_order_prescription['qty']<$old_order_prescription['qty']){
                                    $qty = $old_order_prescription['qty'] - $post_order_prescription['qty'];
                                    $qty_log = $qty;
                                    $stock->update([
                                        'stock' =>  $stock['stock']+$qty
                                    ]);
                                }

                                if(!$stock){
                                    $is_error = true;
                                    $errors[] = 'Failed to update stock';
                                    continue;
                                }
                                (new PrescriptionController)->addLogPrescriptionStockLog($old_stock['id'], $qty_log, $old_stock['stock'], $stock['stock'], 'Update Booking Order', null);
                            }
                            $add_prescript[] = $prescription['id'];
                            $only_consul = false;

                        }

                    }

                }else{
                    $price = $prescription['price'];
                    $order_prescription = OrderPrescription::where('order_id', $order['id'])->where('prescription_id', $prescription['id'])->first();
                    if($order_prescription){

                        if(($post_order_prescription['qty']??false) >= 1){

                            if($post_order_prescription['qty']>$order_prescription['qty']){

                                $old_order_prescription = clone $order_prescription;
                                $order_prescription->update([
                                    'qty'                      => $post_order_prescription['qty'],
                                    'order_prescription_subtotal'   => ($post_order_prescription['qty']*$order_prescription['order_prescription_price']),
                                    'order_prescription_grandtotal' => ($post_order_prescription['qty']*$order_prescription['order_prescription_price']),
                                ]);

                                $update_order = $order->update([
                                    'order_subtotal'   => $order_prescription['order']['order_subtotal'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_subtotal']),
                                    'order_gross'      => $order_prescription['order']['order_gross'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_subtotal']),
                                    'order_grandtotal' => $order_prescription['order']['order_grandtotal'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_grandtotal']),
                                ]);

                            }elseif($post_order_prescription['qty']<$order_prescription['qty']){

                                $old_order_prescription = clone $order_prescription;
                                $order_prescription->update([
                                    'qty'                      => $post_order_prescription['qty'],
                                    'order_prescription_subtotal'   => ($post_order_prescription['qty']*$order_prescription['order_prescription_price']),
                                    'order_prescription_grandtotal' => ($post_order_prescription['qty']*$order_prescription['order_prescription_price']),
                                ]);

                                $update_order = $order->update([
                                    'order_subtotal'   => $order_prescription['order']['order_subtotal'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_subtotal']),
                                    'order_gross'      => $order_prescription['order']['order_gross'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_subtotal']),
                                    'order_grandtotal' => $order_prescription['order']['order_grandtotal'] - $old_order_prescription['order_prescription_subtotal'] + ($order_prescription['order_prescription_grandtotal']),
                                ]);

                            }elseif($post_order_prescription['qty']==$order_prescription['qty']){
                                $add_prescript[] = $prescription['id'];
                                $only_consul = false;
                                continue;
                            }

                            if(!$update_order){
                                $is_error = true;
                                $errors[] = 'Order not found';
                                continue;
                            }

                            if($prescription['prescription_container'] ?? false){
                                $stock = ContainerStock::where('container_id', $prescription['prescription_container']['container']['id'])->where('outlet_id', $outlet['id'])->first();

                                if($stock){
                                    $old_stock = clone $stock;
                                    if($post_order_prescription['qty']>$old_order_prescription['qty']){
                                        $qty = $post_order_prescription['qty'] - $old_order_prescription['qty'];
                                        $qty_log = -$qty;
                                        $stock->update([
                                            'qty' =>  $stock['qty']-$qty
                                        ]);
                                    }elseif($post_order_prescription['qty']<$old_order_prescription['qty']){
                                        $qty = $old_order_prescription['qty'] - $post_order_prescription['qty'];
                                        $qty_log = $qty;
                                        $stock->update([
                                            'qty' =>  $stock['qty']+$qty
                                        ]);
                                    }

                                    if(!$stock){
                                        $is_error = true;
                                        $errors[] = 'Failed to update stock';
                                        continue;
                                    }

                                    (new PrescriptionController)->addLogContainerStockLog($old_stock['id'], $qty_log, $old_stock['qty'], $stock['qty'], 'Update Booking Order', null);
                                }
                            }

                            foreach($prescription['prescription_substances'] ?? [] as $key_sub => $sub){

                                $stock = SubstanceStock::where('substance_id', $sub['substance']['id'])->where('outlet_id', $outlet['id'])->first();

                                if($stock){
                                    $old_stock = clone $stock;
                                    if($post_order_prescription['qty']>$old_order_prescription['qty']){
                                        $qty = ($post_order_prescription['qty'] - $old_order_prescription['qty'])*$sub['qty'];
                                        $qty_log = -$qty;
                                        $stock->update([
                                            'qty' =>  $stock['qty']-$qty
                                        ]);
                                    }elseif($post_order_prescription['qty']<$old_order_prescription['qty']){
                                        $qty = ($old_order_prescription['qty'] - $post_order_prescription['qty'])*$sub['qty'];
                                        $qty_log = $qty;
                                        $stock->update([
                                            'qty' =>  $stock['qty']+$qty
                                        ]);
                                    }

                                    if(!$stock){
                                        $is_error = true;
                                        $errors[] = 'Failed to update stock';
                                        continue;
                                    }

                                    (new PrescriptionController)->addLogSubstanceStockLog($old_stock['id'], $qty_log, $old_stock['qty'], $stock['qty'], 'Update Booking Order', null);
                                }
                            }
                            $add_prescript[] = $prescription['id'];
                            $only_consul = false;
                        }

                    }

                }
            }

            if(!$is_error){
                if($add_prescript){
                    $other_order_prescriptions = OrderPrescription::with(['prescription'])->where('order_id', $order['id'])->whereNotIn('prescription_id', $add_prescript)->get()->toArray();
                }else{
                    $other_order_prescriptions = OrderPrescription::with(['prescription'])->where('order_id', $order['id'])->get()->toArray();
                }

                foreach($other_order_prescriptions ?? [] as $other_order_prescription){
                    $delete = (new DoctorController)->deleteOrderData([
                        'outlet' => $outlet,
                        'type' => $other_order_prescription['prescription']['is_custom'] == 0 ? 'prescription' : 'prescription_custom',
                        'post' => [
                            'id_order' => $order['id'],
                            'id' => $other_order_prescription['id']
                        ]
                    ], $delete_errors);
                    if(!$delete){
                        $is_error = true;
                        $errors[] = $delete_errors;
                        continue;
                    }
                    $order = Order::where('id', $post['id_order'])->first();
                }
            }

            if($order['is_submited_doctor'] == 0){
                $order_sec = $order['child'];
                foreach($post['order_consultations'] ?? [] as $order_consultation){

                    $doctor = User::with(['shifts' => function($query) use($order_consultation){
                        $query->where('doctor_shift_id', $order_consultation['id_shift']);
                        $query->where('user_id', $order_consultation['id']);
                    }])
                    ->whereHas('shifts',function($query) use($order_consultation){
                        $query->where('doctor_shift_id', $order_consultation['id_shift']);
                        $query->where('user_id', $order_consultation['id']);
                    })
                    ->whereHas('doctor_schedules.schedule_dates',function($query) use($order_consultation){
                        $query->where('schedule_month', date('m', strtotime($order_consultation['date'])));
                        $query->where('schedule_year', date('Y', strtotime($order_consultation['date'])));
                        $query->where('doctor_schedule_dates.date', date('Y-m-d', strtotime($order_consultation['date'])));
                    })
                    ->where('id', $order_consultation['id'])->first();
                    if(!$doctor){
                        $is_error = true;
                        $errors[] = 'Doctor not found';
                        continue;
                    }

                    if($order_sec){
                        $get_order_consultation = OrderConsultation::where('order_id', $order_sec['id'])
                        ->whereNot(function($where2) use($order_consultation){
                            $where2->where('doctor_id', $order_consultation['id'])->where('doctor_shift_id', $order_consultation['id_shift'])->whereDate('schedule_date', date('Y-m-d', strtotime($order_consultation['date'])));
                        })
                        ->first();
                        if($get_order_consultation){
                            $delete = (new DoctorController)->deleteOrderData([
                                'outlet' => $outlet,
                                'type' => 'consultation',
                                'post' => [
                                    'id_order' => $order_sec['id'],
                                    'id' => $get_order_consultation['id']
                                ]
                            ], $delete_errors);
                            if(!$delete){
                                $is_error = true;
                                $errors[] = $delete_errors;
                                continue;
                            }

                            Order::where('id', $order_sec['id'])->delete();
                            $order = Order::where('id', $post['id_order'])->first();
                        }else{
                            continue;
                        }
                    }

                    $check_consultation = OrderConsultation::whereHas('order',function($hasOrder) use($order){
                        $hasOrder->where('patient_id', $order['patient_id'])
                        ->where('status', 'Pending');
                    })->where('doctor_id', $doctor['id'])
                    ->where('schedule_date', date('Y-m-d', strtotime($order_consultation['date'])))
                    ->where('doctor_shift_id', $order_consultation['id_shift'])
                    ->first();
                    if($check_consultation){
                        $is_error = true;
                        $errors[] = 'Consultation already exist in order';
                        continue;
                    }

                    $price = $doctor['shifts'][0]['price'] ?? $doctor['consultation_price'] ?? $outlet['consultation_price'];

                    if(!$only_consul){
                        $order_sec = Order::create([
                            'patient_id' => $order['patient_id'],
                            'outlet_id'  => $order['outlet_id'],
                            'cashier_id' => $cashier['id'],
                            'order_date' => date('Y-m-d H:i:s'),
                            'order_code' => $order['order_code'],
                            'notes'      => $post['notes'] ?? null,
                            'parent_id'  => $order['id']
                        ]);

                        if(!$order_sec){
                            $is_error = true;
                            $errors[] = 'Failed to create order consultation';
                            continue;
                        }
                    }

                    $store_order_consultation = OrderConsultation::create([
                        'order_id'                 => $only_consul == true ? $order['id'] : $order_sec['id'],
                        'doctor_id'                => $doctor['id'],
                        'schedule_date'            => $order_consultation['date'],
                        'doctor_shift_id'          => $order_consultation['id_shift'],
                        'order_consultation_price'      => $price,
                        'order_consultation_subtotal'   => $price,
                        'order_consultation_grandtotal' => $price,
                    ]);

                    if(!$store_order_consultation){
                        $is_error = true;
                        $errors[] = 'Failed to create order consultation';
                        continue;
                    }

                    if(isset($order_consultation['grievance']) && count($order_consultation['grievance']) > 0){

                        $consultation = Consultation::where('order_consultation_id', $store_order_consultation['id'])->first();
                        if(!$consultation){
                            $consultation = Consultation::create([
                                'order_consultation_id' => $store_order_consultation['id'],
                            ]);
                        }

                        $patient_grievance = [];
                        foreach($order_consultation['grievance'] ?? [] as $key_gre => $gre){
                            $patient_grievance[] = [
                                'consultation_id' => $consultation['id'],
                                'grievance_id'    => $gre['id'],
                                'from_pos'        => 1,
                                'notes'           => $gre['notes'],
                                'created_at'      => date('Y-m-d H:i:s'),
                                'updated_at'      => date('Y-m-d H:i:s'),
                            ];
                        }

                        $insert = PatientGrievance::insert($patient_grievance);
                        if(!$insert){
                            $is_error = true;
                            $errors[] = 'Grievance error';
                            continue;
                        }

                    }

                    $is_consultation = true;
                    if($only_consul){
                        $order->update([
                            'order_subtotal'   => $order['order_subtotal'] + $price,
                            'order_gross'      => $order['order_gross'] + $price,
                            'order_grandtotal' => $order['order_grandtotal'] + $price,
                        ]);
                    }else{
                        $order_sec->update([
                            'order_subtotal'   => $order_sec['order_subtotal'] + $price,
                            'order_gross'      => $order_sec['order_gross'] + $price,
                            'order_grandtotal' => $order_sec['order_grandtotal'] + $price,
                        ]);
                    }
                }
            }

            if($is_error){
                DB::rollBack();
                return $this->error($errors);
            }else{
                if($order['is_submited_doctor'] == 0 && $is_consultation){
                    if($only_consul){
                        $update = $order->update([
                            'is_submited' => 1,
                        ]);
                        $value_consul = true;
                    }else{
                        $update = $order_sec->update([
                            'is_submited' => 1,
                        ]);
                        $value_consul = false;

                    }
                    if(!$update){

                        $is_error = true;
                        $errors[] = 'Failed submit order';
                        DB::rollBack();

                        return $this->error($errors);
                    }

                    if($only_consul){
                        $update = $order->update([
                            'is_submited' => 1,
                        ]);
                        $value_consul = true;
                    }else{
                        $update = $order_sec->update([
                            'is_submited' => 1,
                        ]);
                        $value_consul = false;

                    }
                    if(!$update){

                        $is_error = true;
                        $errors[] = 'Failed submit order';
                        DB::rollBack();

                        return $this->error($errors);
                    }
                    $generate = GenerateQueueOrder::dispatch($order)->onConnection('generatequeueorder');
                    if(!$only_consul){
                        $generate = GenerateQueueOrder::dispatch($order_sec)->onConnection('generatequeueorder');
                    }
                    DB::commit();

                    $return = [
                        'id_order' => $order['id'],
                        'consultation' => $value_consul,
                        'list_payment' => $this->availablePayment($order) ?? []
                    ];

                    return $this->ok('Success to submit order', $return);
                }else{
                    $generate = GenerateQueueOrder::dispatch($order)->onConnection('generatequeueorder');
                    DB::commit();

                    $return = [
                        'id_order' => $order['id'],
                        'consultation' => false,
                        'list_payment' => $this->availablePayment($order) ?? []
                    ];

                    return $this->ok('Success to submit order', $return);
                }
            }

        }else{
            return $this->error('Customer not found');
        }

    }

    public function ticket(Request $request):mixed
    {
        $request->validate([
            'id_customer' => 'required',
        ]);

        $cashier = $request->user();
        $outlet = $cashier->outlet;
        $post = $request->json()->all();

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(isset($post['id_customer'])){
            $order = Order::with(['order_consultations.doctor', 'order_consultations.shift'])
            ->where('patient_id', $post['id_customer'])
            ->where('outlet_id', $outlet['id'])
            ->where('send_to_transaction', 0)
            ->latest()
            ->first();

            $consultation = $order['order_consultations'][0] ?? null;
            if(!$consultation){
                return $this->error('Ticket not found');
            }

            $return = [
                'title'  => 'CONSULTATION',
                'doctor' => $consultation['doctor']['name'],
                'date'   => date('d F Y', strtotime($consultation['schedule_date'])),
                'time'   => date('H:i', strtotime($consultation['shift']['start'])). ' - '.date('H:i', strtotime($consultation['shift']['end'])),
                'queue'  => $consultation['queue_code']
            ];
            return $this->ok('Success to submit order', $return);
        }else{
            return $this->error('Customer not found');
        }
    }

    public function cronDelete()
    {
        $log = MyHelper::logCron('Delete Order');
        try {

            $orders = Order::with(['order_products', 'order_consultations', 'order_prescriptions', 'child.order_consultations'])
            ->whereDate('order_date', '<' ,date('Y-m-d'))
            ->where('send_to_transaction', 0)
            ->where('status', 'Pending')
            ->orderBy('id', 'desc')
            ->get();

            DB::beginTransaction();
            foreach($orders ?? [] as $key => $order){

                if($order['order_consultations']){

                    foreach($order['child']['order_consultations'] ?? [] as $child_order_consultation){
                        if(date('Y-m-d', strtotime($child_order_consultation['schedule_date'])) >= date('Y-m-d')){
                            continue 2;
                        }
                    }

                    foreach($order['order_consultations'] ?? [] as $key3 => $order_consultation){

                        if(date('Y-m-d', strtotime($order_consultation['schedule_date'])) >= date('Y-m-d')){
                            continue 2;
                        }

                        // $updateOrder = $order->update([
                        //     'order_subtotal'   => $order['order_subtotal'] - $order_consultation['order_consultation_subtotal'],
                        //     'order_gross'      => $order['order_gross'] - $order_consultation['order_consultation_subtotal'],
                        //     'order_grandtotal' => $order['order_grandtotal'] - $order_consultation['order_consultation_grandtotal'],
                        // ]);

                        // if(!$updateOrder){
                        //     DB::rollBack();
                        //     $log->fail('Failed update order');
                        // }

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

                        // $delete_order_consultation = OrderConsultation::where('id', $order_consultation['id'])->delete();
                        // if(!$delete_order_consultation){
                        //     $log->fail('Failed to delete order consultation');
                        // }
                    }
                }

                if($order['order_products']){

                    foreach($order['order_products'] ?? [] as $key2 => $order_product){

                        // $updateOrder = $order->update([
                        //     'order_subtotal'   => $order['order_subtotal'] - $order_product['order_product_subtotal'],
                        //     'order_gross'      => $order['order_gross'] - $order_product['order_product_subtotal'],
                        //     'order_grandtotal' => $order['order_grandtotal'] - $order_product['order_product_grandtotal'],
                        // ]);

                        // if(!$updateOrder){
                        //     DB::rollBack();
                        //     $log->fail('Failed update order');
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
                                    $log->fail('Failed update stock');
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
                                                $log->fail('Failed delete treament patient');
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
                                        $log->fail('Failed to get treatment patient');
                                    }
                                }else{
                                    DB::rollBack();
                                    $log->fail('Failed to delete step');
                                }
                            }else{
                                DB::rollBack();
                                $log->fail('Failed to get treatment patient step');
                            }

                        }

                        // $delete_order_product = OrderProduct::where('id', $order_product['id'])->delete();
                        // if(!$delete_order_product){
                        //     $log->fail('Failed to delete order product');
                        // }
                    }

                }

                if($order['order_prescriptions']){

                    foreach($order['order_prescriptions'] ?? [] as $key3 => $order_prescription){

                        // $updateOrder = $order->update([
                        //     'order_subtotal'   => $order['order_subtotal'] - $order_prescription['order_prescription_subtotal'],
                        //     'order_gross'      => $order['order_gross'] - $order_prescription['order_prescription_subtotal'],
                        //     'order_grandtotal' => $order['order_grandtotal'] - $order_prescription['order_prescription_grandtotal'],
                        // ]);

                        // if(!$updateOrder){
                        //     DB::rollBack();
                        //     $log->fail('Failed update order');
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
                                        $log->fail('Failed update stock');
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
                                            $log->fail('Failed update stock');
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
                                            $log->fail('Failed update stock');
                                        }

                                        (new PrescriptionController)->addLogSubstanceStockLog($old_stock['id'], ($order_prescription['qty']*$sub['qty']), $old_stock['qty'], $stock['qty'], 'Cancel Booking Order', null);

                                    }
                                }
                            }

                            // $delete_order_prescription = OrderPrescription::where('id', $order_prescription['id'])->delete();
                            // if(!$delete_order_prescription){
                            //     $log->fail('Failed to delete order precription');
                            // }

                        }else{
                            DB::rollBack();
                            $log->fail('Prescription Not Found');
                        }
                    }
                }

                $delete_order = $order->update([
                    'status' => 'Cancelled',
                    'cancel_date' => date('Y-m-d H:i:s'),
                ]);
                if(!$delete_order){
                    $log->fail('Failed to delete order');
                }
            }

            DB::commit();
            $log->success();
        } catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        }

    }

    public function availablePayment($post): mixed
    {
        $availablePayment = config('payment_method');
        $active_payment_methods = Setting::where('key', '=', 'active_payment_methods')->first();
        $total = $post['order_grandtotal'] ?? 0;

        $setting  = json_decode($active_payment_methods['value_text'] ?? '[]', true) ?? [];
        $payments = [
            'total'     => $total,
            'cash'      => [],
            'e-payment' => [],
            'bank'      => [],
        ];

        $last_status = [];
        foreach ($setting ?? [] as $value) {
            $payment = $availablePayment[$value['code'] ?? ''] ?? false;
            if (!$payment) {
                unset($availablePayment[$value['code']]);
                continue;
            }

            if (is_array($payment['available_time'] ?? false)) {
                $available_time = $payment['available_time'];
                $current_time = time();
                if ($current_time < strtotime($available_time['start']) || $current_time > strtotime($available_time['end'])) {
                    $value['status'] = 0;
                }
            }

            if (!($payment['status'] ?? false)) {
                unset($availablePayment[$value['code']]);
                continue;
            }

            if(!is_numeric($payment['status'])){
                $var = explode(':',$payment['status']);
                if(($config[$var[0]]??false) != ($var[1]??true)) {
                    $last_status[$var[0]] = $value['status'];
                    unset($availablePayment[$value['code']]);
                    continue;
                }
            }

            if((int) $value['status'] == 0){
                continue;
            }

            if($payment['type'] == 'cash'){
                $payments['cash'][] = $total;
                $last_4 = substr($total, -4);
                $basic = str_split($total);
                $last = ((int)$basic[0]+1) * (pow(10,count($basic)-1));
                if((int)$last_4 != 0 && (int)$last_4 < 5000){
                    $payments['cash'][] =  (int) substr_replace($total, '5000', -4);
                }
                if(count($basic) > 5){
                    if((int)$last_4 != 0 && ((int)$basic[1]+1) * (pow(10,count($basic)-2)) < 50000){
                        $grand_plus_10 = (int) substr_replace($total, ((int)$basic[1]+1) * (pow(10,count($basic)-2)), -5);
                        $payments['cash'][] = $grand_plus_10;
                        if($grand_plus_10 < ($last - 50000)){
                            $payments['cash'][] = (int) substr_replace($total, 50000, -5);
                        }
                    }
                }

                $payments['cash'][] = $last;

                if(count($basic) <= 5){
                    $payments['cash'][] = 10 * (pow(10,count($basic)-1));
                }
            }elseif($payment['type'] == 'e-payment'){
                $payments['e-payment'][] = [
                    'code'                          => $value['code'] ?? '',
                    'payment_gateway'               => $payment['payment_gateway'] ?? '',
                    'payment_method'                => $payment['payment_method'] ?? '',
                    'logo'                          => $payment['logo'] ?? '',
                    'type'                          => $payment['type'] ?? '',
                    'text'                          => $payment['text'] ?? '',
                    'description'                   => $value['description'] ?? '',
                    'status'                        => (int) $value['status'] ? 1 : 0
                ];
            }
            unset($availablePayment[$value['code']]);
        }

        return $payments;
    }

    public function saveOrder(Request $request): mixed
    {
        $request->validate([
            'id_customer' => 'required',
        ]);

        $cashier = $request->user();
        $outlet = $cashier->outlet;
        $post = $request->json()->all();

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        $is_error = false;
        $errors = [];
        if(isset($post['id_customer'])){

            DB::beginTransaction();
            $last_code = Order::where('outlet_id', $outlet['id'])->latest('order_code')->first()['order_code']??'';
            $last_code_outlet = explode('-',$outlet['outlet_code'])[1];
            $last_code = $last_code == '' ? 0 : explode('-',$last_code)[1];
            $last_code = (int)$last_code + 1;
            $order_code = 'ORD'.$last_code_outlet.'-'.sprintf("%05d", $last_code);

            $order = Order::create([
                'patient_id' => $post['id_customer'],
                'outlet_id'  => $outlet['id'],
                'cashier_id' => $cashier['id'],
                'order_date' => date('Y-m-d H:i:s'),
                'order_code' => $order_code,
                'notes'      => $post['notes'] ?? null
            ]);

            if(!$order){
                DB::rollBack();
                return $this->error('Failed to create Order');
            }

            foreach($post['order_products'] ?? [] as $order_product){
                $product = Product::with([
                    'global_price','outlet_price' => function($outlet_price) use ($outlet){
                        $outlet_price->where('outlet_id',$outlet['id']);
                    }, 'outlet_stock' => function($outlet_stock) use ($outlet){
                        $outlet_stock->where('outlet_id',$outlet['id']);
                    }
                ])->where('id', $order_product['id'])->first();

                if(!$product){
                    $is_error = true;
                    $errors[] = 'Product not found';
                    continue;
                }

                $price = ($product['outlet_price'][0]['price'] ?? $product['global_price']['price']) ?? 0;
                $stock = $product['outlet_stock'][0]['stock'] ?? 0;

                if($order_product['qty'] > $stock){
                    $is_error = true;
                    $errors[] = $product['product_name']. ' out of stock';
                    continue;
                }

                $store_order_product = OrderProduct::create([
                    'order_id'                 => $order['id'],
                    'product_id'               => $product['id'],
                    'type'                     => 'Product',
                    'qty'                      => $order_product['qty'],
                    'order_product_price'      => $price,
                    'order_product_subtotal'   => $order_product['qty']*$price,
                    'order_product_grandtotal' => $order_product['qty']*$price,
                ]);

                $price_to_order = ($order_product['qty']*$price);
                if(!$store_order_product){
                    $is_error = true;
                    $errors[] = 'Failed to order '.$product['product_name'];
                    continue;
                }

                $stock = ProductOutletStock::where('product_id', $product['id'])->where('outlet_id', $outlet['id'])->first();
                if($stock){
                    $old_stock = clone $stock;
                    $stock->update([
                        'stock' =>  $stock['stock']-$order_product['qty']
                    ]);

                    if(!$stock){
                        $is_error = true;
                        $errors[] = 'Failed to update stock '.$product['product_name'];
                        continue;
                    }

                    (new ProductController)->addLogProductStockLog($old_stock['id'], -$order_product['qty'], $old_stock['stock'], $stock['stock'], 'Booking Order', null);
                }

                $order->update([
                    'order_subtotal'   => $order['order_subtotal'] + $price_to_order,
                    'order_gross'      => $order['order_gross'] + $price_to_order,
                    'order_grandtotal' => $order['order_grandtotal'] + $price_to_order,
                ]);
            }

            foreach($post['order_treatments'] ?? [] as $order_treatment){
                $treatment = Product::with([
                    'global_price','outlet_price' => function($outlet_price) use ($outlet){
                        $outlet_price->where('outlet_id',$outlet['id']);
                    }
                ])->where('id', $order_treatment['id'])->first();

                if(!$treatment){
                    $is_error = true;
                    $errors[] = 'Treatment not found';
                    continue;
                }

                $price = $treatment['outlet_price'][0]['price'] ?? $treatment['global_price']['price'];

                $get_order_treatment = OrderProduct::where('order_id', $order['id'])->where('product_id', $treatment['id'])->whereDate('schedule_date',$order_treatment['date'])->where('type', 'Treatment')->first();
                if($get_order_treatment){
                    $is_error = true;
                    $errors[] = 'Treatment already exist in order';
                    continue;
                }else{

                    if(($order_treatment['continue']??false) == 1){
                        $customerPatient = TreatmentPatient::where('patient_id', $post['id_customer'])
                        ->where('treatment_id', $treatment['id'])
                        ->where('status', '<>', 'Finished')
                        ->whereDate('expired_date', '>=', date('y-m-d', strtotime($order_treatment['date'])))
                        ->first();

                    }else{
                        $customerPatient = TreatmentPatient::create([
                            'treatment_id' => $treatment['id'],
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
                        $is_error = true;
                        $errors[] = 'Failed to create order treatment';
                        continue;
                    }

                    $existCustomerPatientStep = TreatmentPatientStep::where('treatment_patient_id', $customerPatient['id'])->max('step') ?? 0;
                    if(($existCustomerPatientStep+1) > $customerPatient['step']){
                        $is_error = true;
                        $errors[] = 'Step cannot exceed those specified';
                        continue;
                    }
                    $customerPatientStep = TreatmentPatientStep::create([
                        'treatment_patient_id' => $customerPatient['id'],
                        'step'                 => $existCustomerPatientStep + 1,
                        'date'                 => $order_treatment['date'],
                    ]);

                    if(!$customerPatientStep){
                        $is_error = true;
                        $errors[] = 'Failed to create order treatment';
                        continue;
                    }

                    $store_order_treatment = OrderProduct::create([
                        'order_id'                  => $order['id'],
                        'product_id'                => $treatment['id'],
                        'type'                      => 'Treatment',
                        'schedule_date'             => $order_treatment['date'],
                        'treatment_patient_id'      => $customerPatient['id'] ?? null,
                        'treatment_patient_step_id' => $customerPatientStep['id'] ?? null,
                        'qty'                       => 1,
                        'order_product_price'       => $price,
                        'order_product_subtotal'    => $price,
                        'order_product_grandtotal'  => $price,
                    ]);

                    if(!$store_order_treatment){
                        $is_error = true;
                        $errors[] = 'Failed to create order treatment';
                        continue;
                    }

                    $price_to_order = $price;

                    $order->update([
                        'order_subtotal'   => $order['order_subtotal'] + $price_to_order,
                        'order_gross'      => $order['order_gross'] + $price_to_order,
                        'order_grandtotal' => $order['order_grandtotal'] + $price_to_order,
                    ]);
                }
            }

            $is_consultation = false;
            foreach($post['order_consultations'] ?? [] as $order_consultation){

                $doctor = User::with(['shifts' => function($query) use($order_consultation){
                    $query->where('doctor_shift_id', $order_consultation['id_shift']);
                    $query->where('user_id', $order_consultation['id']);
                }])
                ->whereHas('shifts',function($query) use($order_consultation){
                    $query->where('doctor_shift_id', $order_consultation['id_shift']);
                    $query->where('user_id', $order_consultation['id']);
                })
                ->whereHas('doctor_schedules.schedule_dates',function($query) use($order_consultation){
                    $query->where('schedule_month', date('m', strtotime($order_consultation['date'])));
                    $query->where('schedule_year', date('Y', strtotime($order_consultation['date'])));
                    $query->where('doctor_schedule_dates.date', date('Y-m-d', strtotime($order_consultation['date'])));
                })
                ->where('id', $order_consultation['id'])->first();

                if(!$doctor){
                    $is_error = true;
                    $errors[] = 'Doctor not found';
                    continue;
                }

                $get_order_consultation = OrderConsultation::where('order_id', $order['id'])->first();
                if($get_order_consultation){
                    $is_error = true;
                    $errors[] = 'Consultation already exist in order';
                    continue;
                }else{
                    $price = $doctor['shifts'][0]['price'] ?? $doctor['consultation_price'] ?? $outlet['consultation_price'];

                    $order_sec = Order::create([
                        'patient_id' => $post['id_customer'],
                        'outlet_id'  => $outlet['id'],
                        'cashier_id' => $cashier['id'],
                        'order_date' => date('Y-m-d H:i:s'),
                        'order_code' => $order_code,
                        'notes'      => $post['notes'] ?? null,
                        'parent_id'  => $order['id']
                    ]);

                    if(!$order_sec){
                        $is_error = true;
                        $errors[] = 'Failed to create order consultation';
                        continue;
                    }

                    $store_order_consultation = OrderConsultation::create([
                        'order_id'                 => $order_sec['id'],
                        'doctor_id'                => $doctor['id'],
                        'schedule_date'            => $order_consultation['date'],
                        'doctor_shift_id'          => $order_consultation['id_shift'],
                        'order_consultation_price'      => $price,
                        'order_consultation_subtotal'   => $price,
                        'order_consultation_grandtotal' => $price,
                    ]);

                    if(!$store_order_consultation){
                        $is_error = true;
                        $errors[] = 'Failed to create order consultation';
                        continue;
                    }

                    if(isset($order_consultation['grievance']) && count($order_consultation['grievance']) > 0){

                        $consultation = Consultation::where('order_consultation_id', $store_order_consultation['id'])->first();
                        if(!$consultation){
                            $consultation = Consultation::create([
                                'order_consultation_id' => $store_order_consultation['id'],
                            ]);
                        }

                        $patient_grievance = [];
                        foreach($order_consultation['grievance'] ?? [] as $key_gre => $gre){
                            $patient_grievance[] = [
                                'consultation_id' => $consultation['id'],
                                'grievance_id'    => $gre['id'],
                                'from_pos'        => 1,
                                'notes'           => $gre['notes'],
                                'created_at'      => date('Y-m-d H:i:s'),
                                'updated_at'      => date('Y-m-d H:i:s'),
                            ];
                        }

                        $insert = PatientGrievance::insert($patient_grievance);
                        if(!$insert){
                            $is_error = true;
                            $errors[] = 'Grievance error';
                            continue;
                        }

                    }

                    $is_consultation = true;
                    $order_sec->update([
                        'order_subtotal'   => $order_sec['order_subtotal'] + $price,
                        'order_gross'      => $order_sec['order_gross'] + $price,
                        'order_grandtotal' => $order_sec['order_grandtotal'] + $price,
                    ]);
                }
            }

            if($is_error){
                DB::rollBack();
                return $this->error($errors);
            }else{

                $generate = GenerateQueueOrder::dispatch($order)->onConnection('generatequeueorder');
                DB::commit();

                return $this->ok('Success to save order', []);

            }

        }else{
            return $this->error('Customer not found');
        }

    }
}
