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
use Modules\Grievance\Http\Controllers\GrievanceController;
// header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization');
Route::middleware(['auth:api'])->controller(GrievanceController::class)->prefix('grievance')->group(function () {
    $grievance = '{grievance}';
    Route::get('', 'index')->name('grievance.list');
    Route::post('', 'store')->name('grievance.store');
    Route::get($grievance, 'show')->name('grievance.show');
    Route::patch($grievance, 'update')->name('grievance.update');
    Route::delete($grievance, 'destroy')->name('grievance.destroy');
});

Route::middleware(['auth:api','scopes:doctor'])->prefix('doctor')->group(function (){
    Route::prefix('consul')->group(function () {
        Route::prefix('grievance')->controller(GrievanceController::class)->group(function () {
            Route::post('', 'getOrderGrievance');
            Route::get('list', 'show');
            Route::post('add', 'addGrievancePatient');
            Route::post('delete', 'deleteGrievancePatient');
        });
    });
});


Route::middleware(['auth:api','scopes:doctor'])->prefix('doctor')->group(function (){
    Route::prefix('medical-record')->controller(GrievanceController::class)->group(function () {
        Route::prefix('medical-history')->group(function(){
            Route::get('grievance', 'show');
        });
    });
});


Route::middleware(['auth:api','scopes:pos'])->prefix('pos')->group(function (){
    Route::prefix('consultation')->group(function () {
        Route::prefix('grievance')->controller(GrievanceController::class)->group(function () {
            Route::get('', 'show');
            Route::post('add', 'addGrievancePatientPOS');
        });
    });
});
