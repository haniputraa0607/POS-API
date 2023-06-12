<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use KodePandai\Indonesia\IndonesiaDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IndonesiaDatabaseSeeder::class,
            UserSeeder::class,
        ]);
    
    }
}
