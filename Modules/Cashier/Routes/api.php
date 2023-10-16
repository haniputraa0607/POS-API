<?php

use Illuminate\Http\Request;
use Modules\Cashier\Http\Controllers\CashierController;

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
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization');

Route::middleware(['auth:api','scopes:pos'])->controller(CashierController::class)->prefix('pos')->group(function (){

    Route::prefix('cashier')->controller(CashierController::class)->group(function () {
        Route::get('histories', 'histories');
        Route::get('list', 'listCashier');
        Route::post('all-schedule', 'scheduleAll');
    });
});
