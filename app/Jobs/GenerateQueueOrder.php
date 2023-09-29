<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Order\Entities\Order;
use Modules\Order\Entities\OrderConsultation;
use Modules\Order\Entities\OrderProduct;

class GenerateQueueOrder implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $order = Order::with([
            'order_products',
            'order_consultations',
        ])->where('id', $this->data['id'])->first();

        if ($order) {
            foreach ($order['order_products'] ?? [] as $order_product) {
                if ($order_product['type'] == 'Product') {
                    $queue = OrderProduct::whereHas('order', function ($ord) use ($order) {
                        $ord->where('id', '<>', $order['id']);
                        $ord->whereDate('order_date', date('Y-m-d'));
                        $ord->where('outlet_id', $order['outlet_id']);
                    })->where('type', 'Product')->max('queue') + 1;

                    if ($queue < 10) {
                        $queue_code = 'P00' . $queue;
                    } elseif ($queue < 100) {
                        $queue_code = 'P0' . $queue;
                    } else {
                        $queue_code = 'P' . $queue;
                    }

                    $update = OrderProduct::where('id', $order_product['id'])->update(['queue' => $queue,'queue_code' => $queue_code]);
                } elseif ($order_product['type'] == 'Treatment') {
                    $queue = OrderProduct::whereHas('order', function ($ord) use ($order) {
                        $ord->where('id', '<>', $order['id']);
                        $ord->where('outlet_id', $order['outlet_id']);
                    })->whereDate('schedule_date', date('Y-m-d', strtotime($order_product['schedule_date'])))
                    ->where('type', 'Treatment')
                    ->max('queue') + 1;

                    if ($queue < 10) {
                        $queue_code = 'T00' . $queue;
                    } elseif ($queue < 100) {
                        $queue_code = 'T0' . $queue;
                    } else {
                        $queue_code = 'T' . $queue;
                    }

                    $update = OrderProduct::where('id', $order_product['id'])->update(['queue' => $queue,'queue_code' => $queue_code]);
                }
            }

            foreach ($order['order_consultations'] ?? [] as $order_consultation) {
                $queue = OrderConsultation::whereHas('order', function ($ord) use ($order) {
                    $ord->where('id', '<>', $order['id']);
                    $ord->where('outlet_id', $order['outlet_id']);
                })->whereDate('schedule_date', date('Y-m-d', strtotime($order_consultation['schedule_date'])))
                ->max('queue') + 1;

                if ($queue < 10) {
                    $queue_code = 'C00' . $queue;
                } elseif ($queue < 100) {
                    $queue_code = 'C0' . $queue;
                } else {
                    $queue_code = 'C' . $queue;
                }

                $update = OrderConsultation::where('id', $order_consultation['id'])->update(['queue' => $queue,'queue_code' => $queue_code]);
            }
        }
    }
}
