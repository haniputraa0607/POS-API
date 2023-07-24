<?php

namespace Modules\Doctor\Database\factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Modules\Doctor\Entities\DoctorShift;
use Modules\User\Entities\User;

class DoctorDayFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DoctorShift::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $start = rand(8,18);
        $end = $start+4;
        return [
            'user_id' => User::inRadnomOrder()->doctor()->first()->id,
            'day' => Arr::random(DoctorShift::DAYS),
            'name' => Arr::random(config('outlet_shift')),
            'start' => Carbon::now()->setHour($start)->setMinute(0)->setSecond(0)->format('H:i:s'),
            'end' => Carbon::now()->setHour($end)->setMinute(0)->setSecond(0)->format('H:i:s'),
            'price' => rand(1,9)+100_000,
        ];
    }
}

