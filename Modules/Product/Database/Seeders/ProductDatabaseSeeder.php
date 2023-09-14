<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductCategory;
use Modules\Product\Entities\ProductPackage;
use Modules\Product\Entities\ProductTrending;

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
        $packages = ['Paket Glowing Series', 'Paket Gold Series', 'Paket Acne Series', 'Paket Acne Series'];
        $trending = [1,2,3,4];

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
        foreach ($packages as $key => $package) {
            $product = Product::create([
                'product_code' => 'PCG-000' . $key,
                'product_name' => $package,
                'type' => 'Package',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit.',
                'is_active' =>  rand(0, 1),
                'need_recipe_status' =>  rand(0, 1),
            ]);
            $id_package = $product->id;
            $data_packages = [1, 2, 3];
            foreach($data_packages as $data_package){
                $product_package = ProductPackage::create([
                    'package_id' => $id_package,
                    'product_id' => $data_package
                ]);
            }
        }

        foreach($trending as $key){
            ProductTrending::create([
                'product_id' => $key
            ]);
        }

    }
}
