<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use KodePandai\Indonesia\IndonesiaDatabaseSeeder;
use Modules\Outlet\Database\Seeders\OutletDatabaseSeeder;
use Modules\User\Database\Seeders\UserDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IndonesiaDatabaseSeeder::class,
            OutletDatabaseSeeder::class,
            UserDatabaseSeeder::class,
        ]);
    
    }
}
