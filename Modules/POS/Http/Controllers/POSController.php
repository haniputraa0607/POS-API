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

class POSController extends Controller
{

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function home(Request $request):JsonResponse
    {
        $cashier = $request->user();
        $outlet = $cashier->outlet;

        if($outlet){

            $status = true;
            $product = null;
            $treatment = null;
            $consultation = null;
            $schedule = $outlet->outlet_schedule->where('day', date('l'))->first();
            if(!$schedule){
                $status = false;
            }elseif($schedule['is_closed'] == 1){
                $status = false;
            }elseif(($schedule['open'] && date('H:i') < date('H:i', strtotime($schedule['open']))) || ($schedule['close'] && date('H:i') > date('H:i', strtotime($schedule['close'])))){
                $status = false;
            }

            if($status){

                $product = OrderProduct::whereHas('order',function($order) use($outlet){ $order->where('outlet_id', $outlet['id'])->whereDate('order_date', date('Y-m-d'));})->where('type', 'Product')->orderBy('queue', 'desc')->first()['queue_code'] ?? null;
                $treatment = OrderProduct::whereHas('order',function($order) use($outlet){ $order->where('outlet_id', $outlet['id']);})->whereDate('schedule_date', date('Y-m-d'))->where('type', 'Treatment')->orderBy('queue', 'desc')->first()['queue_code'] ?? null;
                $consultation = OrderConsultation::whereHas('order',function($order) use($outlet){ $order->where('outlet_id', $outlet['id']);})->whereDate('schedule_date', date('Y-m-d'))->orderBy('queue', 'desc')->first()['queue_code'] ?? null;

            }

            $data = [
                'status_outlet' => $status,
                'queue' => [
                    'product' => $product,
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

    public function splash(Request $request):JsonResponse
    {
        $splash = Setting::where('key', '=', 'splash_pos_apps')->first();
        $duration = Setting::where('key', '=', 'splash_pos_apps_duration')->pluck('value')->first();

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

    public function getOrder(Request $request):mixed
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(isset($post['id_customer'])){

            return $this->getDataOrder(['id_customer' => $post['id_customer']],'');
        }

        return $this->ok('', $return);

    }

    public function getDataOrder($data, $message):mixed
    {
        $id_customer = $data['id_customer'];

        $return = [];
        $order = Order::with([
            'order_products.product',
            'order_products.treatment_patient.steps' => function($step) {
                $step->where('status', 'Pending');
            },
            'order_consultations.shift',
            'order_consultations.doctor'
        ])->where('patient_id', $id_customer)
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

                    $progress = null;
                    if($ord_pro['treatment_patient'] && isset($ord_pro['treatment_patient']['doctor_id']) && count($ord_pro['treatment_patient']['steps']) > 0){
                        if($ord_pro['treatment_patient']['steps'][0]['step'] != 1 && $ord_pro['treatment_patient']['step'] != 1){
                            $progress = $ord_pro['treatment_patient']['steps'][0]['step'].'/'.$ord_pro['treatment_patient']['step'];
                        }
                    }

                    $ord_treat[] = [
                        'order_product_id' => $ord_pro['id'],
                        'product_id'       => $ord_pro['product']['id'],
                        'product_name'     => $ord_pro['product']['product_name'],
                        'schedule_date'    => date('d F Y', strtotime($ord_pro['schedule_date'])),
                        'price_total'      => $ord_pro['order_product_grandtotal'],
                        'queue'            => $ord_pro['queue_code'],
                        'progress'         => $progress
                    ];
                }
            }

            foreach($order['order_consultations'] ?? [] as $key => $ord_con){

                $ord_consul[] = [
                    'order_consultation_id' => $ord_con['id'],
                    'doctor_id'             => $ord_con['doctor']['id'],
                    'doctor_name'           => $ord_con['doctor']['name'],
                    'schedule_date'         => date('d F Y', strtotime($ord_con['schedule_date'])),
                    'time'                  => date('H:i', strtotime($ord_con['shift']['start'])).'-'.date('H:i', strtotime($ord_con['shift']['end'])),
                    'price_total'           => $ord_con['order_consultation_grandtotal'],
                    'queue'                 => $ord_con['queue_code'],
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

        return $this->ok($message, $return);

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
            ->where('send_to_transaction', 0)
            ->latest()
            ->first();

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
                return $this->getDataOrder(['id_customer' => $post['id_customer']], 'Succes to add new order');

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

    public function deleteOrder(Request $request):mixed
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

        return $this->deleteOrderData([
            'outlet' => $outlet,
            'type' => $post['type'] ?? null,
            'post' => [
                'id_customer' => $post['id_customer'],
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
            $order_product = OrderProduct::with(['order'])->whereHas('order', function($order) use($post){
                $order->where('patient_id', $post['id_customer']);
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

            if($delete_order_product && ($type??false) == 'treatment'){
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
            return $this->getDataOrder(['id_customer' => $post['id_customer']], 'Succes to delete order');

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

    public function editOrder(Request $request):JsonResponse
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

        if(($post['qty']??false) == 0){

            return $this->deleteOrderData([
                'outlet' => $outlet,
                'type' => $post['type'] ?? null,
                'post' => [
                    'id_customer' => $post['id_customer'],
                    'id' => $post['id']
                ]
            ]);

        }elseif(($post['qty']??false) >= 1){

            if(($post['type']??false) == 'product'){

                DB::beginTransaction();
                $order_product = OrderProduct::with(['order'])->whereHas('order', function($order) use($post){
                    $order->where('patient_id', $post['id_customer']);
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
                        'order_grandtotal' => $order_product['order']['order_grandtotal'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_grand_total']),
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
                        'order_grandtotal' => $order_product['order']['order_grandtotal'] - $old_order_product['order_product_subtotal'] + ($order_product['order_product_grand_total']),
                    ]);

                }else{
                    return $this->getDataOrder(['id_customer' => $post['id_customer']], 'Succes to update order');
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
                return $this->getDataOrder(['id_customer' => $post['id_customer']], 'Succes to delete order');

            }else{
                return $this->error('Type is invalid');
            }

        }else{
            return $this->error('Qty can be null');
        }



    }
}
