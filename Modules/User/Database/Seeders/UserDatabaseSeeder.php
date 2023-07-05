<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\User\Entities\User;

class UserDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        User::factory()->create(['username' => 'admin']);
        User::factory()->create(['username' => 'dokter', 'type' => 'salesman']);
        User::factory()->create(['username' => 'kasir', 'type' => 'cashier']);
        User::factory(10)->create();

    }
}
