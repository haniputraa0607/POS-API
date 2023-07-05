<?php

namespace Modules\Consultation\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Consultation\Entities\Consultation;
use Modules\Customer\Entities\Customer;
use Modules\EmployeeSchedule\Entities\EmployeeSchedule;
use Modules\Queue\Entities\Queue;

class ConsultationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Consultation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $schedule = EmployeeSchedule::inRandomOrder()->first();
        return [
            'customer_id' => Customer::inRandomOrder()->first()->id,
            'employee_schedule_id' => $schedule->id,
            'queue_id' => Queue::inRandomOrder()->first()->id,
            'consultation_date' => $schedule->date,
            'treatment_recomendation' => rand(0,1) == 1 ? $this->faker->sentence(100): null,
            'session_end' => rand(0,1),
        ];
    }
}
