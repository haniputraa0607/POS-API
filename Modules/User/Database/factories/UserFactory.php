<?php

namespace Modules\User\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\User\Entities\User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $latestId = rand(11111, 99999);
        $name = $this->faker->firstName() . ' ' . $this->faker->lastName();
        $type = Arr::random(config('user_type'));
        return [
            'equal_id' => $this->faker->unique()->randomNumber(),
            'name' => $name,
            'username' => ucfirst($type == 'salesman' ? 'Dok' : 'Kas') . explode(' ', $name)[0] . $latestId,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->phoneNumber(),
            'idc' => rand(1111111111111111, 9999999999999999),
            'birthdate' => $this->faker->date(),
            'type' => $type,
            'outlet_id' => rand(1, 4),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

