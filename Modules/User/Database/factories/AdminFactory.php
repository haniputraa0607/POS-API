<?php

namespace Modules\User\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\User\Entities\Admin;
use Modules\User\Entities\User;

class AdminFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Admin::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $adminUser = User::inRandomOrder()->admin()->first();
        return [
            'id' => $adminUser->id,
            'name' => 'Admin '.$this->faker->name(),
        ];
    }
}

