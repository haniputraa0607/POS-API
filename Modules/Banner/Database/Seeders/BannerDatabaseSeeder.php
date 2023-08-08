<?php

namespace Modules\Banner\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Banner\Entities\Banner;
use Modules\Product\Entities\Product;

class BannerDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $titles = ['Reveal Your Beauty', 'Inspiring Beauty', 'Natural Beauty'];
        foreach ($titles as $title) {
            Banner::create([
                'title' => $title,
                'product_id' => Product::InRandomOrder()->first()->id,
            ]);
        }
    }
}
