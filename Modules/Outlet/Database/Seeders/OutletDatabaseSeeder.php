<?php

namespace Modules\Outlet\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Outlet\Entities\Outlet;

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

    }
}
