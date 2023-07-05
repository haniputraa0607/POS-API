<?php

namespace Modules\Customer\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Customer\Entities\Customer;

class CustomerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'gender' => $this->faker->randomElement(Customer::GENDER),
            'birth_date' => $this->faker->date,
            'phone' => preg_replace( '/[^0-9]/', '', $this->faker->phoneNumber()),
            'email' => $this->faker->safeEmail(),
        ];
    }
}

