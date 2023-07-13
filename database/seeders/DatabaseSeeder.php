<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use KodePandai\Indonesia\IndonesiaDatabaseSeeder;
use Modules\Consultation\Database\Seeders\ConsultationDatabaseSeeder;
use Modules\Customer\Database\Seeders\CustomerDatabaseSeeder;
use Modules\Diagnostic\Database\Seeders\DiagnosticDatabaseSeeder;
use Modules\EmployeeSchedule\Database\Seeders\EmployeeScheduleDatabaseSeeder;
use Modules\Grievance\Database\Seeders\GrievanceDatabaseSeeder;
use Modules\Outlet\Database\Seeders\OutletDatabaseSeeder;
use Modules\Queue\Database\Seeders\QueueDatabaseSeeder;
use Modules\User\Database\Seeders\UserDatabaseSeeder;
use Database\Seeders\FeaturesTableSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IndonesiaDatabaseSeeder::class,
            OutletDatabaseSeeder::class,
            UserDatabaseSeeder::class,
            GrievanceDatabaseSeeder::class,
            DiagnosticDatabaseSeeder::class,
            // EmployeeScheduleDatabaseSeeder::class,
            CustomerDatabaseSeeder::class,
            // QueueDatabaseSeeder::class,
            // ConsultationDatabaseSeeder::class,
            FeaturesTableSeeder::class
        ]);

    }
}
