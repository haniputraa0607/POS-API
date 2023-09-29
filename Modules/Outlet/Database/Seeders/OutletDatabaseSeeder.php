<?php

namespace Modules\Outlet\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Outlet\Entities\BannerClinic;
use Modules\Outlet\Entities\Outlet;
use Modules\Outlet\Entities\Partner;

class OutletDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        Partner::factory(10)->create();
        Outlet::factory()->create([
            'activities' => json_encode(['product']),
        ]);
        Outlet::factory()->create([
            'activities' => json_encode([ 'consultation']),
        ]);
        Outlet::factory()->create([
            'activities' => json_encode(['treatment']),
        ]);
        Outlet::factory()->create([
            'activities' => json_encode(['product', 'treatment']),
        ]);
        Outlet::factory()->create([
            'activities' => json_encode(['treatment', 'consultation']),
        ]);
        Outlet::factory()->create([
            'activities' => json_encode(['product', 'treatment', 'consultation']),
        ]);

        $banner_image = [
            'img/banner_clinic/1.jpg',
            'img/banner_clinic/2.jpg',
            'img/banner_clinic/3.jpg',
            'img/banner_clinic/4.jpg'
        ];
        foreach($banner_image as $key){
            BannerClinic::create([
                'image' => $key
            ]);
        }

    }
}
