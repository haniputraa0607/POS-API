<?php

namespace Modules\EmployeeSchedule\Database\factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\User\Entities\User;

class EmployeeScheduleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\EmployeeSchedule\Entities\EmployeeSchedule::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $startTime = rand(8,20);
        return [
            'user_id' => User::where('type', 'salesman')->inRandomOrder()->first()->id,
            'date' => $this->faker->dateTimeThisYear()->format('Y-m-d'),
            'start_time' => Carbon::createFromFormat('H', $startTime)->format('H:i:s'),
            'end_time' => Carbon::createFromFormat('H',$startTime)->addHour(3)->format('H:i:s'),
        ];
    }
}
