<?php

namespace Modules\User\Database\Seeders;

use App\Http\Models\Feature;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\User\Entities\Admin;
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

        User::factory()->create(['username' => 'superadmin', 'level' => 'Super Admin']);
        User::factory()->create(['username' => 'admin']);
        User::factory()->create(['username' => 'dokter', 'type' => 'salesman']);
        User::factory()->create(['username' => 'kasir', 'type' => 'cashier']);
        User::factory(10)->create();

        $users = User::limit(5)->get();
        foreach ($users as $user) {
            Admin::factory()->create(['id' => $user->id]);
        }

    }
}
