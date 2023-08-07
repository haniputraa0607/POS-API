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
        $order_product_id = $this->data['order_product_id'];
        $order_id = $this->data['order_id'];
        $outlet_id = $this->data['outlet_id'];
        $type = $this->data['type'];

        if ($type == 'product') {
            $queue = OrderProduct::whereHas('order', function ($order) use ($order_id, $outlet_id) {
                $order->where('id', '<>', $order_id);
                $order->whereDate('order_date', date('Y-m-d'));
                $order->where('outlet_id', $outlet_id);
            })->where('type', 'Product')->max('queue') + 1;

            if ($queue < 10) {
                $queue_code = 'P00' . $queue;
            } elseif ($queue < 100) {
                $queue_code = 'P0' . $queue;
            } else {
                $queue_code = 'P' . $queue;
            }

            $update = OrderProduct::where('id', $order_product_id)->update(['queue' => $queue,'queue_code' => $queue_code]);
        } elseif ($type == 'treatment') {
            $queue = OrderProduct::whereHas('order', function ($order) use ($order_id, $outlet_id) {
                $order->where('id', '<>', $order_id);
                $order->where('outlet_id', $outlet_id);
            })->whereDate('schedule_date', date('Y-m-d', strtotime($this->data['schedule_date'])))
            ->where('type', 'Treatment')
            ->max('queue') + 1;

            if ($queue < 10) {
                $queue_code = 'T00' . $queue;
            } elseif ($queue < 100) {
                $queue_code = 'T0' . $queue;
            } else {
                $queue_code = 'T' . $queue;
            }

            $update = OrderProduct::where('id', $order_product_id)->update(['queue' => $queue,'queue_code' => $queue_code]);
        } elseif ($type == 'consultation') {
            $queue = OrderConsultation::whereHas('order', function ($order) use ($order_id, $outlet_id) {
                $order->where('id', '<>', $order_id);
                $order->where('outlet_id', $outlet_id);
            })->whereDate('schedule_date', date('Y-m-d', strtotime($this->data['schedule_date'])))
            ->max('queue') + 1;

            if ($queue < 10) {
                $queue_code = 'C00' . $queue;
            } elseif ($queue < 100) {
                $queue_code = 'C0' . $queue;
            } else {
                $queue_code = 'C' . $queue;
            }

            $update = OrderConsultation::where('id', $order_product_id)->update(['queue' => $queue,'queue_code' => $queue_code]);
        }
    }
}
