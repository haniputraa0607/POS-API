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
use Database\Seeders\SettingTableSeeder;
use Modules\Article\Database\Seeders\ArticleDatabaseSeeder;
use Modules\Banner\Database\Seeders\BannerDatabaseSeeder;
use Modules\Contact\Database\Seeders\ContactDatabaseSeeder;
use Modules\Partner\Database\Seeders\PartnerDatabaseSeeder;
use Modules\Product\Database\Seeders\ProductDatabaseSeeder;
use Modules\Setting\Database\Seeders\SettingDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IndonesiaDatabaseSeeder::class,
            PartnerDatabaseSeeder::class,
            OutletDatabaseSeeder::class,
            UserDatabaseSeeder::class,
            GrievanceDatabaseSeeder::class,
            DiagnosticDatabaseSeeder::class,
            // EmployeeScheduleDatabaseSeeder::class,
            CustomerDatabaseSeeder::class,
            // QueueDatabaseSeeder::class,
            // ConsultationDatabaseSeeder::class
            FeaturesTableSeeder::class,
            ProductDatabaseSeeder::class,
            BannerDatabaseSeeder::class,
            ArticleDatabaseSeeder::class,
            ContactDatabaseSeeder::class,
            SettingDatabaseSeeder::class
        ]);

    }
}
