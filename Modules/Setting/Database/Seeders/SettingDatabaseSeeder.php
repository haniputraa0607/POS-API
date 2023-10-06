<?php

namespace Modules\Setting\Database\Seeders;

use App\Http\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class SettingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        Setting::create([
            'key' => 'doctor_commission',
            'value' => 0.0
        ]);
    }
}
