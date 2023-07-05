<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Outlet\Http\Controllers\OutletController;

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

Route::middleware('auth:api')->controller(OutletController::class)->prefix('outlet')->group(function () {
    $outlet = '{outlet}';
    Route::get('', 'index')->name('outlet.list');
    Route::get('activities', 'activities')->name('outlet.activities');
    Route::post('', 'store')->name('outlet.store');
    Route::get($outlet, 'show')->name('outlet.show');
    Route::patch($outlet, 'update')->name('outlet.update');
    Route::delete($outlet, 'destroy')->name('outlet.delete');
});
