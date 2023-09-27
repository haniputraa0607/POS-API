<?php

namespace Modules\Outlet\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use KodePandai\Indonesia\Models\District;
use Modules\Outlet\Entities\Outlet;
use Modules\Outlet\Entities\Partner;
use Modules\Partner\Entities\PartnerEqual;

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
        $name = $this->faker->city();
        return [
            'id_partner' => Partner::inRandomorder()->first()->id,
            'partner_equal_id' => PartnerEqual::orderByRaw('RAND()')->first()->id,
            'name' => $name,
            'outlet_code' => ucfirst(substr($name,0,1)).rand(1000,9999),
            'outlet_phone' => $this->faker->phoneNumber(),
            'outlet_email' => $this->faker->safeEmail(),
            'address' => $this->faker->address(),
            'district_code' => District::InRandomOrder()->first()->code,
            'postal_code' => $this->faker->postcode(),
            'coordinates' => json_encode($this->faker->localCoordinates()),
            'images' => json_encode("img/outlet/outlet_room.jpg")
        ];
    }
}

