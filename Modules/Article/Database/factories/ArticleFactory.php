<?php

namespace Modules\Article\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Article\Entities\Article::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence(2),
            'image'=>$this->faker->imageUrl(),
            'writer'=>$this->faker->name(),
            'release_date' =>$this->faker->date(),
            'description' => $this->faker->realText(10),
        ];
    }
}

