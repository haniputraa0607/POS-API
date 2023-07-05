<?php

namespace Modules\Outlet\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Outlet\Entities\Partner;

class PartnerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Partner::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->name();
        return [
            'partner_name' => $name,
            'partner_code' => $name.rand(1000,9999),
            'partner_email' => $this->faker->safeEmail(),
            'partner_phone' => $this->faker->phoneNumber(),
            'partner_address' => $this->faker->address(),
        ];
    }
}

