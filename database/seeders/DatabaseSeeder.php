<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use KodePandai\Indonesia\IndonesiaDatabaseSeeder;
use Modules\Consultation\Database\Seeders\ConsultationDatabaseSeeder;
use Modules\Customer\Database\Seeders\CustomerDatabaseSeeder;
use Modules\EmployeeSchedule\Database\Seeders\EmployeeScheduleDatabaseSeeder;
use Modules\Outlet\Database\Seeders\OutletDatabaseSeeder;
use Modules\Queue\Database\Seeders\QueueDatabaseSeeder;
use Modules\User\Database\Seeders\UserDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IndonesiaDatabaseSeeder::class,
            OutletDatabaseSeeder::class,
            UserDatabaseSeeder::class,
            EmployeeScheduleDatabaseSeeder::class,
            CustomerDatabaseSeeder::class,
            QueueDatabaseSeeder::class,
            ConsultationDatabaseSeeder::class
        ]);
    
    }
}
