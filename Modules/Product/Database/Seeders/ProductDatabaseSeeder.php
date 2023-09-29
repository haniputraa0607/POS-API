<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductCategory;
use Modules\Product\Entities\ProductFinest;
use Modules\Product\Entities\ProductFinestList;
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
            'Chocolate Body Scrub',
            'Acne Gel Strawberry',
            'Day Cream Acne',
            'Toner Acne',
            'Grape Glowing Booster',
            'Apricot Glowing Series',
        ];
        $images_product = [
            [
                'https://daviena.belum.live/images/products/Rectangle 114.jpg'
            ],
            [
                'https://daviena.belum.live/images/products/Rectangle 115.jpg'
            ],
            [
                'https://daviena.belum.live/images/products/Rectangle 116.jpg'
            ],
            [
                'https://daviena.belum.live/images/products/Rectangle 111.jpg'
            ],
            [
                'https://daviena.belum.live/images/products/Kandungan-Skincare 2.jpg',
                'https://daviena.belum.live/images/products/Rectangle 109.jpg',
                'https://daviena.belum.live/images/products/Rectangle 110.jpg'
            ],
            [
                'https://daviena.belum.live/images/products/Rectangle 111.jpg'
            ]
        ];

        $treatments = ['Microdermabaison', 'Laser Rejuvination', 'Mesoteraphy'];
        $images_treatments = [
            'https://daviena.belum.live/images/treatment/1.png',
            'https://daviena.belum.live/images/treatment/2.png',
            'https://daviena.belum.live/images/treatment/3.png',
        ];
        $packages = [
            'Paket Glowing Series',
            'Paket Gold Series',
            'Paket Acne Series',
            'Paket Acne Series 2'
        ];
        $trending = [1,2,3,4];
        $product_finest = [1,2,3,4];

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
                'image' => json_encode($images_product[$key]),
                'is_active' => rand(0, 1),
                'need_recipe_status' =>  rand(0, 1),
            ]);
        }
        foreach ($treatments as $key => $treatment) {
            Product::create([
                'product_code' => 'TRE-000' . $key,
                'product_name' => $treatment,
                'type' => 'Treatment',
                'image' => json_encode($images_treatments[$key]),
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit.',
                'is_active' =>  rand(0, 1),
                'need_recipe_status' =>  rand(0, 1),
            ]);
        }
        foreach ($packages as $key => $package) {
            $product = Product::create([
                'product_category_id' => 6,
                'product_name' => $package,
                'product_code' => 'PCG-000'.$key,
                'type' => 'Package',
                'image' => json_encode('https://daviena.belum.live/images/products/Rectangle 111.jpg'),
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit.',
                'is_active' =>  rand(0, 1),
                'need_recipe_status' =>  rand(0, 1),
                'product_groups' => json_encode([
                    [
                        'id' => 1,
                    ],
                    [
                        'id' => 2,
                    ],
                    [
                        'id' => 3,
                    ]
                ])
            ]);
            $id_package = $product->id;
            // $data_packages = [1, 2, 3];
            // foreach($data_packages as $data_package){
            //     $product_package = ProductPackage::create([
            //         'package_id' => $id_package,
            //         'product_id' => $data_package
            //     ]);
            // }
        }

        foreach($trending as $key){
            ProductTrending::create([
                'product_id' => $key
            ]);
        }

        ProductFinest::create([
            'title' => 'THE BEST BEAUTY SUPPLEMENT BACKED BY RESEARCH',
            'description' => 'Take your skincare routine to the next level with Daviena Skin Care. clean and pure ingredients help plump skin.'
        ]);

        foreach($trending as $key){
            ProductFinestList::create([
                'product_id' => $key
            ]);
        }

    }
}
