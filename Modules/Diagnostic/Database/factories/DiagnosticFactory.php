<?php

namespace Modules\Diagnostic\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DiagnosticFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Diagnostic\Entities\Diagnostic::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'diagnostic_name' => $this->faker->word(),
            'description' => $this->faker->sentence(10),
            'is_active' => rand(0,1),
        ];
    }
}

