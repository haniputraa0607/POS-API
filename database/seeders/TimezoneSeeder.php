<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use KodePandai\Indonesia\Models\Province;

class TimezoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wib = [
            'Aceh',
            'Sumatera Utara',
            'Sumatera Barat',
            'Riau',
            'Jambi',
            'Sumatera Selatan',
            'Bengkulu',
            'Lampung',
            'Kepulauan Bangka Belitung',
            'Kepulauan Riau',
            'DKI Jakarta',
            'Jawa Barat',
            'Jawa Tengah',
            'DAERAH ISTIMEWA YOGYAKARTA',
            'Jawa Timur',
            'Banten',
            'Kalimantan Barat',
            'Kalimantan Tengah',
        ];
        $new_wib=[];
        $new_wita=[];
        $new_wit=[];

        $wita = [
            'Bali',
            'NUSA TENGGARA BARAT',
            'NUSA TENGGARA TIMUR',
            'Kalimantan Selatan',
            'Kalimantan Timur',
            'Kalimantan Utara',
            'Sulawesi Utara',
            'Sulawesi Tengah',
            'Sulawesi Selatan',
            'Sulawesi Tenggara',
            'Gorontalo',
            'Sulawesi Barat',
        ];

        $wit = [
            'Maluku',
            'Maluku Utara',
            'Papua',
            'Papua Barat',
        ];

        foreach ($wib as $key => $value) {
            array_push( $new_wib, Str::upper($value));
        }
        foreach ($wita as $key => $value) {
            array_push( $new_wita, Str::upper($value));
        }
        foreach ($wit as $key => $value) {
            array_push( $new_wit, Str::upper($value));
        }
        // dd($new_wib);

        Province::whereIn('name', $new_wib)->update(['timezone' => 7]);
        Province::whereIn('name', $new_wita)->update(['timezone' => 8]);
        Province::whereIn('name', $new_wit)->update(['timezone' => 9]);
    }


}
