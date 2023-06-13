<?php

namespace Modules\Outlet\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use KodePandai\Indonesia\Models\District;
use Modules\Outlet\Entities\Outlet;

class OutletFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Outlet::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->city(),
            'address' => $this->faker->address(),
            'district_code' => District::InRandomOrder()->first()->code,
            'postal_code' => $this->faker->postcode(),
            'coordinates' => json_encode($this->faker->localCoordinates())
        ];
    }
}

