<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Order\Entities\Order;
use Modules\Transaction\Entities\Transaction;
use Modules\Transaction\Entities\TransactionCash;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Entities\TreatmentPatient;
use Modules\Customer\Entities\TreatmentPatientStep;

class TransactionController extends Controller
{

    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function confirm(Request $request):JsonResponse
    {
        $cashier = $request->user();
        $outlet = $cashier->outlet;
        $post = $request->json()->all();

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(!isset($post['id_customer']) || !isset($post['payment_method']) || !isset($post['payment_gateway'])){
            return $this->error('Request invalid');
        }

        $order = Order::with(['order_products' => function($order_products) {
            $order_products->where('type', 'Treatment');
        }])
        ->where('patient_id', $post['id_customer'])
        ->where('outlet_id', $outlet['id'])
        ->where('send_to_transaction', 0)
        ->latest()->first();

        if(!$order){
            return $this->error('Order Not Found');
        }

        if($order['is_submited'] == 1 && $order['is_submited_doctor'] == 0){
            return $this->error('Cant confirm order, order has not been submited by the doctor');
        }

        $type = null;
        $image = null;
        $url = null;
        DB::beginTransaction();

        $last_code = Transaction::where('outlet_id', $outlet['id'])->latest('transaction_receipt_number')->first()['transaction_receipt_number']??'';
        $last_code_outlet = explode('-',$outlet['outlet_code'])[1];
        $last_code = $last_code == '' ? 0 : explode('-',$last_code)[1];
        $last_code = (int)$last_code + 1;
        $transaction_receipt_number = 'TRX'.$last_code_outlet.'-'.sprintf("%05d", $last_code);

        $trx = Transaction::create([
            'order_id'                   => $order['id'],
            'outlet_id'                  => $order['outlet_id'],
            'customer_id'                => $order['patient_id'],
            'user_id'                    => $cashier['id'],
            'transaction_receipt_number' => $transaction_receipt_number,
            'transaction_date'           => date('Y-m-d H:i:s'),
            'transaction_subtotal'       => $order['order_subtotal'],
            'transaction_gross'          => $order['order_gross'],
            'transaction_discount'       => $order['order_discount'],
            'transaction_tax'            => $order['order_tax'],
            'transaction_grandtotal'     => $order['order_grandtotal'],
        ]);

        if(!$trx){
            DB::rollBack();
            return $this->error('Failed to created transaction');
        }

        if($post['payment_method'] == 'Cash'){

            if($post['payment_gateway'] == 'Cash'){

                if(!isset($post['nominal'])){
                    DB::rollBack();
                    return $this->error('Please input nominal received');
                }

                $cash = TransactionCash::create([
                    'transaction_id' => $trx['id'],
                    'cash_total'     => $trx['transaction_grandtotal'],
                    'cash_received'  => $post['nominal'],
                    'cash_change'    => (int)$post['nominal'] - (int)$trx['transaction_grandtotal'],
                ]);

                if(!$cash){
                    DB::rollBack();
                    return $this->error('Failed to created transaction cash');
                }

                $update_trx = $trx->update([
                    'completed_at'               => date('Y-m-d H:i:s'),
                    'transaction_payment_type'   => 'Cash',
                    'transaction_payment_status' => 'Completed'
                ]);

                if(!$update_trx){
                    DB::rollBack();
                    return $this->error('Failed to updated transaction');
                }

                $type = 'Cash';
                $image = config('payment_method.cash.logo');

            }else{
                DB::rollBack();
                return $this->error('Payment Gateway Invalid');
            }

        }elseif($post['payment_method'] == 'Midtrans'){

        }else{
            DB::rollBack();
            return $this->error('Payment Method Invalid');
        }

        foreach($order['order_products'] ?? [] as $order_product){

            $step = TreatmentPatientStep::where('id', $order_product['treatment_patient_step_id'])->where('status', 'Pending')->first();
            $traitment_patient = TreatmentPatient::where('id', $order_product['treatment_patient_id'])->where('status', '<>', 'Finished')->first();
            if($step && $traitment_patient){
                $update_step = $step->update([
                    'status' => 'Finished'
                ]);

                if(!$update_step){
                    DB::rollBack();
                    return $this->error('Failed update traitment patient step');
                }

                $update_traitment_patient = $traitment_patient->update([
                    'progress' => $step['step']
                ]);

                if(!$update_traitment_patient){
                    DB::rollBack();
                    return $this->error('Failed update traitment patient');
                }

                if($traitment_patient['progress'] == $traitment_patient['step']){
                    $update_status_traitment_patient = $traitment_patient->update([
                        'status' => 'Finished'
                    ]);

                    if(!$update_status_traitment_patient){
                        DB::rollBack();
                        return $this->error('Failed update traitment patient status');
                    }
                }
            }

        }

        $update_order = $order->update([
            'send_to_transaction' => 1
        ]);

        if(!$update_order){
            DB::rollBack();
            return $this->error('Failed to updated order');
        }

        DB::commit();
        $return = [
            'id_transaction' => $trx['id'],
            'type'           => $type,
            'image_url'      => $image,
            'url'            => $url,
            'price_total'    => $trx['transaction_grandtotal']
        ];
        return $this->ok('Succes to confirm transaction', $return);

    }

    public function done(Request $request):mixed
    {
        $cashier = $request->user();
        $outlet = $cashier->outlet;
        $post = $request->json()->all();

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(!isset($post['id_transaction'])){
            return $this->error('Request invalid');
        }
        return 123;
    }
}
