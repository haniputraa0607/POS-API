<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Outlet\Http\Controllers\OutletController;
use Illuminate\Http\JsonResponse;
use Modules\Order\Entities\Order;
use Modules\Order\Entities\OrderProduct;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductOutletStock;
use Modules\Product\Http\Controllers\ProductController;

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
            $data = [
                'status_outlet' => true,
                'queue' => [
                    'product' => 'P06',
                    'treatment' => 'T11',
                    'consultation' => 'C08'
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
        $data = [
            'service' => [
                'product' => in_array('product',$outlet_service) ?? false,
                'treatment' => in_array('treatment',$outlet_service) ?? false,
                'consultation' => in_array('consultation',$outlet_service) ?? false
            ]
            ];

        return $this->ok('', $data);

    }

    public function getOrder(Request $request):JsonResponse
    {
        $post = $request->json()->all();
        $cashier = $request->user();
        $outlet =  $cashier->outlet;

        if(!$outlet){
            return $this->error('Outlet not found');
        }

        if(isset($post['id_customer'])){

            $return = [];
            $order = Order::with(['order_products.product'])->where('patient_id', $post['id_customer'])
            ->where('send_to_transaction', 0)
            ->latest()
            ->first();

            if($order){
                foreach($order['order_products'] ?? [] as $key => $ord_pro){

                    $ord_prod[] = [
                        'order_product_id' => $ord_pro['id'],
                        'product_name'     => $ord_pro['product']['product_name'],
                        'qty'              => $ord_pro['qty'],
                        'price_total'      => $ord_pro['order_product_grandtotal'],
                    ];
                }

                $return = [
                    'order_id'       => $order['id'],
                    'order_code'     => $order['order_code'],
                    'order_products' => $ord_prod,
                    'sumarry'        => [
                        'subtotal'    => $order['order_subtotal'],
                        'tax'         => (float)$order['order_tax'],
                        'grand_total' => $order['order_grandtotal'],
                    ],
                ];
            }

        }

        return $this->ok('', $return);

    }

    public function addOrder(Request $request):mixed
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

            if(!$order){
                $order = Order::create([
                    'patient_id' => $post['id_customer'],
                    'outlet_id'  => $outlet['id'],
                    'cashier_id' => $cashier['id'],
                    'order_date' => date('Y-m-d H:i:s'),
                    'order_code' => 'ORD-02',
                    'notes'      => $post['notes'] ?? null
                ]);
            }

            if(($post['type']??false) == 'product'){
                $product = Product::with(['global_price','outlet_price' => function($outlet_price) use ($outlet){$outlet_price->where('outlet_id',$outlet['id']);}])
                ->where('id', $post['order']['id'])
                ->product()->first();

                if(!$product){
                    return $this->error('Product not found');
                }

                $price = $product['outlet_price'][0]['price'] ?? $product['global_price']['price'];
                $order_product = OrderProduct::where('order_id', $order['id'])->where('product_id', $product['id'])->first();

                if($order_product){
                    $order_product->update([
                        'qty'                      => $order_product['qty'] + $post['order']['qty'],
                        'order_product_price'      => $order_product['order_product_price'] + ($post['order']['qty']*$price),
                        'order_product_subtotal'   => $order_product['order_product_subtotal'] + ($post['order']['qty']*$price),
                        'order_product_grandtotal' => $order_product['order_product_grandtotal'] + ($post['order']['qty']*$price),
                    ]);
                }else{
                    $order_product = OrderProduct::create([
                        'order_id'                 => $order['id'],
                        'product_id'               => $product['id'],
                        'type'                     => $product['type'],
                        'qty'                      => $post['order']['qty'],
                        'order_product_price'      => $post['order']['qty']*$price,
                        'order_product_subtotal'   => $post['order']['qty']*$price,
                        'order_product_grandtotal' => $post['order']['qty']*$price,
                    ]);
                }

                $stock = ProductOutletStock::where('product_id', $product['id'])->where('outlet_id', $outlet['id'])->first();
                if($stock){
                    $old_stock = clone $stock;
                    $stock->update([
                        'stock' =>  $stock['stock']-$post['order']['qty']
                    ]);

                    (new ProductController)->addLogProductStockLog($old_stock['id'], -$post['order']['qty'], $old_stock['stock'], $stock['stock'], 'Booking Order', null);
                }

                $order->update([
                    'order_subtotal'   => $order['order_subtotal'] + ($post['order']['qty']*$price),
                    'order_gross'      => $order['order_gross'] + ($post['order']['qty']*$price),
                    'order_grandtotal' => $order['order_grandtotal'] + ($post['order']['qty']*$price),
                ]);

                return $this->ok('Succes to add new order', $order);

            }elseif(($post['type']??false) == 'treatment'){

            }elseif(($post['type']??false) == 'consultation'){

            }elseif(($post['type']??false) == 'prescription'){

            }else{
                return $this->error('Type is invalid');
            }

        }else{
            return $this->error('Customer not found');
        }

    }
}
