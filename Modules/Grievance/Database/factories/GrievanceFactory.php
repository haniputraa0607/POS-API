<?php

namespace Modules\Grievance\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Grievance\Entities\Grievance;

class GrievanceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Grievance::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'grievance_name' => $this->faker->word(),
            'description' => $this->faker->sentence(10),
            'is_active' => rand(0,1),
        ];
    }
}

