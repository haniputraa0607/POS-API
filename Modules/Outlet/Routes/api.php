<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Outlet\Http\Controllers\OutletController;
use Modules\Outlet\Http\Controllers\PartnerController;

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
// header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization');

Route::middleware('auth:api')->controller(OutletController::class)->prefix('outlet')->group(function () {
    $outlet = '{outlet}';
    Route::get('', 'index')->name('outlet.list');
    Route::get('activities', 'activities')->name('outlet.activities');
    Route::post('', 'store')->name('outlet.store');
    Route::get($outlet, 'show')->name('outlet.show');
    Route::patch($outlet, 'update')->name('outlet.update');
    Route::delete($outlet, 'destroy')->name('outlet.delete');
});

Route::prefix('landing-page')->group(function(){
    Route::post('clinic', [OutletController::class, 'clinic']);
    Route::get('clinic/banner', [OutletController::class, 'banner_clinic']);
    Route::get('clinic/{id}', [OutletController::class, 'clinic_detail']);
});

Route::middleware('auth:api')->prefix('webhook')->group(function(){
    Route::prefix('outlet')->group(function(){
        Route::get('all', [OutletController::class, 'allOutlet']);
        Route::post('set_id', [OutletController::class, 'setEqualIdOutlet']);
        Route::get('verified/{equal_id}', [OutletController::class, 'getVerifiedOutlet']);
        Route::post('verified', [OutletController::class, 'setVerifiedOutlet']);
    });
});
