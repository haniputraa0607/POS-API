<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

         /**
         * Delete Order
         * run every minute
         */
        $schedule->call('Modules\POS\Http\Controllers\POSController@cronDelete')->dailyAt('00:05');

        /**
         * Expired Treatment Patient
         * run every minute
         */
        $schedule->call('Modules\Product\Http\Controllers\TreatmentController@cronCheckTreatment')->dailyAt('00:10');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
