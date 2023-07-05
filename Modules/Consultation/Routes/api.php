<?php


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use Illuminate\Support\Facades\Route;
use Modules\Consultation\Http\Controllers\ConsultationController;

Route::middleware('auth:api')->controller(ConsultationController::class)->prefix('consultation')->group(function () {
    Route::get('mine', 'mine')->name('consultation.mine');
    Route::get('mine-today', 'mineToday')->name('consultation.mine.today');
});