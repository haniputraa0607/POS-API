<?php

namespace Modules\Queue\Database\factories;

use App\Lib\MyHelper;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Modules\Outlet\Entities\Outlet;
use Modules\Queue\Entities\Queue;

class QueueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Queue::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $type = Arr::random(Queue::QUEUE_TYPE);
        return [
            'code' => MyHelper::addLeadingZeros(ucfirst(substr($type, 0, 1)).rand(1,30)),
            'type' => $type,
            'outlet_id' => Outlet::inRandomOrder()->first()->id,
        ];
    }
}

