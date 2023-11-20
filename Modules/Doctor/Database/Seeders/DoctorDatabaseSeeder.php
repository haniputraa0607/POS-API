<?php

namespace Modules\Doctor\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Doctor\Entities\TreatmentRecordType;

class DoctorDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $name = [
            'by Daviena',
            'Outsite Product',
            'Treatment & Therapy'
        ];
        foreach($name as $key){
            TreatmentRecordType::create([
                'name' => $key
            ]);
        }
        // $this->call("OthersTableSeeder");
    }
}
