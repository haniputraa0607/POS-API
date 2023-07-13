<?php

namespace Modules\Diagnostic\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Diagnostic\Entities\Diagnostic;

class DiagnosticDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        Diagnostic::factory(10)->create();
    }
}
