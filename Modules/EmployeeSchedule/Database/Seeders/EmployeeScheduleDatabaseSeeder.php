<?php

namespace Modules\EmployeeSchedule\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\EmployeeSchedule\Entities\EmployeeSchedule;

class EmployeeScheduleDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        EmployeeSchedule::factory(50)->create();
    }
}
