<?php

namespace Modules\Consultation\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Consultation\Entities\Consultation;

class ConsultationDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        Consultation::factory(50)->create();
    }
}
