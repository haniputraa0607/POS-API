<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Http\Models\Feature;

class FeaturesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = array(
            0   => array(
                'id'             => 1,
                'feature_type'   => 'Report',
                'feature_module' => 'Dashboard',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            1   => array(
                'id'             => 2,
                'feature_type'   => 'List',
                'feature_module' => 'Users',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            2   => array(
                'id'             => 3,
                'feature_type'   => 'Detail',
                'feature_module' => 'Users',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            3   => array(
                'id'             => 4,
                'feature_type'   => 'Create',
                'feature_module' => 'Users',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            4   => array(
                'id'             => 5,
                'feature_type'   => 'Update',
                'feature_module' => 'Users',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            5   => array(
                'id'             => 6,
                'feature_type'   => 'Delete',
                'feature_module' => 'Users',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            6  => array(
                'id'             => 7,
                'feature_type'   => 'List',
                'feature_module' => 'Outlet',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            7  => array(
                'id'             => 8,
                'feature_type'   => 'Detail',
                'feature_module' => 'Outlet',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            8  => array(
                'id'             => 9,
                'feature_type'   => 'Create',
                'feature_module' => 'Outlet',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            9  => array(
                'id'             => 10,
                'feature_type'   => 'Update',
                'feature_module' => 'Outlet',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            10  => array(
                'id'             => 11,
                'feature_type'   => 'Delete',
                'feature_module' => 'Outlet',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            11  => array(
                'id'             => 12,
                'feature_type'   => 'List',
                'feature_module' => 'Product',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            12  => array(
                'id'             => 13,
                'feature_type'   => 'Detail',
                'feature_module' => 'Product',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            13  => array(
                'id'             => 14,
                'feature_type'   => 'Create',
                'feature_module' => 'Product',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            14  => array(
                'id'             => 15,
                'feature_type'   => 'Update',
                'feature_module' => 'Product',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            15  => array(
                'id'             => 16,
                'feature_type'   => 'Delete',
                'feature_module' => 'Product',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            16  => array(
                'id'             => 17,
                'feature_type'   => 'List',
                'feature_module' => 'Transaction',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            17  => array(
                'id'             => 18,
                'feature_type'   => 'Detail',
                'feature_module' => 'Transaction',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            18  => array(
                'id'             => 19,
                'feature_type'   => 'List',
                'feature_module' => 'Deals',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            19  => array(
                'id'             => 20,
                'feature_type'   => 'Detail',
                'feature_module' => 'Deals',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            20  => array(
                'id'             => 21,
                'feature_type'   => 'Create',
                'feature_module' => 'Deals',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            21  => array(
                'id'             => 22,
                'feature_type'   => 'Update',
                'feature_module' => 'Deals',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            22  => array(
                'id'             => 23,
                'feature_type'   => 'Delete',
                'feature_module' => 'Deals',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            23  => array(
                'id'             => 24,
                'feature_type'   => 'List',
                'feature_module' => 'Partner',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            24  => array(
                'id'             => 25,
                'feature_type'   => 'Detail',
                'feature_module' => 'Partner',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            25  => array(
                'id'             => 26,
                'feature_type'   => 'Create',
                'feature_module' => 'Partner',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            26  => array(
                'id'             => 27,
                'feature_type'   => 'Update',
                'feature_module' => 'Partner',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            27  => array(
                'id'             => 28,
                'feature_type'   => 'Delete',
                'feature_module' => 'Partner',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            28  => array(
                'id'             => 29,
                'feature_type'   => 'List',
                'feature_module' => 'Outlet Schedule',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            29  => array(
                'id'             => 30,
                'feature_type'   => 'Detail',
                'feature_module' => 'Outlet Schedule',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            30  => array(
                'id'             => 31,
                'feature_type'   => 'Create',
                'feature_module' => 'Outlet Schedule',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            31  => array(
                'id'             => 32,
                'feature_type'   => 'Update',
                'feature_module' => 'Outlet Schedule',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            32  => array(
                'id'             => 33,
                'feature_type'   => 'Delete',
                'feature_module' => 'Outlet Schedule',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            33  => array(
                'id'             => 34,
                'feature_type'   => 'List',
                'feature_module' => 'Cashier Schedule',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            34  => array(
                'id'             => 35,
                'feature_type'   => 'Detail',
                'feature_module' => 'Cashier Schedule',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            35  => array(
                'id'             => 36,
                'feature_type'   => 'Create',
                'feature_module' => 'Cashier Schedule',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            36  => array(
                'id'             => 37,
                'feature_type'   => 'Update',
                'feature_module' => 'Cashier Schedule',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            37  => array(
                'id'             => 38,
                'feature_type'   => 'Delete',
                'feature_module' => 'Cashier Schedule',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            38  => array(
                'id'             => 39,
                'feature_type'   => 'List',
                'feature_module' => 'Doctor Schedule',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            39  => array(
                'id'             => 40,
                'feature_type'   => 'Detail',
                'feature_module' => 'Doctor Schedule',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            40  => array(
                'id'             => 41,
                'feature_type'   => 'Create',
                'feature_module' => 'Doctor Schedule',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            41  => array(
                'id'             => 42,
                'feature_type'   => 'Update',
                'feature_module' => 'Doctor Schedule',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),
            42  => array(
                'id'             => 43,
                'feature_type'   => 'Delete',
                'feature_module' => 'Doctor Schedule',
                'created_at'     => date('Y-m-d H:00:00'),
                'updated_at'     => date('Y-m-d H:00:00'),
            ),

        );

        foreach ($rows as $row) {
            Feature::updateOrCreate(['id' => $row['id']], [
                'feature_type'   => $row['feature_type'],
                'feature_module' => $row['feature_module'],
            ]);
        }
    }
}
