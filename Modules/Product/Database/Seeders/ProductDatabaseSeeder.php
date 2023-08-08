<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductCategory;

class ProductDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $categories = ['Cream & Lotion', 'Serum', 'Toner', 'Mask', 'Facial Wash', 'Package & Other'];
        $products = [
            'Paket Hemat Glowing Series', 'Paket Glowing Series', 'Beauty Charge Face Mist',
            'Paket Hemat Glowing Series', 'Grape Glowing Booster',
            'Apricot Glowing Series',
        ];

        $treatments = ['Microdermabaison', 'Laser Rejuvination', 'Mesoteraphy'];

        foreach ($categories as $category) {
            ProductCategory::create([
                'product_category_name' => $category,
                'description' => "Lorem ipsum dolor sit amet consectetur adipisicing elit.",
            ]);
        }
        foreach ($products as $key => $product) {
            Product::create([
                'product_category_id' => ProductCategory::inRandomOrder()->first()->id,
                'product_code' => 'PRO-000' . $key,
                'product_name' => $product,
                'type' => 'Product',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit.',
                'is_active' => rand(0, 1),
                'need_recipe_status' =>  rand(0, 1),
            ]);
        }
        foreach ($treatments as $key => $treatment) {
            Product::create([
                'product_code' => 'TRE-000' . $key,
                'product_name' => $treatment,
                'type' => 'Treatment',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit.',
                'is_active' =>  rand(0, 1),
                'need_recipe_status' =>  rand(0, 1),
            ]);
        }
    }
}
