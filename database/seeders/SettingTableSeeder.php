<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Http\Models\Setting;

class SettingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $rows = array(
            0 =>
            array(
                'id' => 1,
                'key' => 'splash_pos_apps',
                'value' => NULL,
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            1 =>
            array(
                'id' => 2,
                'key' => 'splash_pos_apps_duration',
                'value' => 2,
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            2 =>
            array(
                'id' => 3,
                'key' => 'splash_doctor_apps',
                'value' => NULL,
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
            3 =>
            array(
                'id' => 4,
                'key' => 'splash_doctor_apps_duration',
                'value' => 2,
                'value_text' => NULL,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ),
        );

        foreach ($rows as $row) {
            if (Setting::where('key', $row['key'])->exists()) continue;
            Setting::create([
                'key' => $row['key'],
                'value' => $row['value'] ?? null,
                'value_text' => $row['value_text'] ?? null,
            ]);
        }
    }
}
