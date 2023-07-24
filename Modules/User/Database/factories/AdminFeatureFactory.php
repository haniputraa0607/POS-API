<?php

namespace Modules\User\Database\factories;

use App\Http\Models\Feature;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\User\Entities\Admin;
use Modules\User\Entities\AdminFeature;

class AdminFeatureFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AdminFeature::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'admin_id' => Admin::inRandomOrder()->first()->id,
            'feature_id' => Feature::inRandomOrder()->first()->id,
        ];
    }
}

