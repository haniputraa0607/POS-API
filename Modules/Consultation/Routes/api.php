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
use Modules\Consultation\Http\Controllers\TreatmentConsultationController;
// header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization');

Route::middleware('auth:api')->controller(ConsultationController::class)->prefix('consultation')->group(function () {
    Route::get('mine', 'mine')->name('consultation.mine');
    Route::get('mine-today', 'mineToday')->name('consultation.mine.today');
});

Route::controller(TreatmentConsultationController::class)->prefix('landing-page')->group(function(){
    Route::prefix('treatment_consultation')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'update');
    });
});

Route::middleware(['auth:api','scopes:doctor'])->prefix('doctor')->group(function (){
    Route::prefix('consul')->controller(ConsultationController::class)->group(function () {
        Route::post('submit', 'submit');
        Route::post('edit', 'edit');
    });
});
