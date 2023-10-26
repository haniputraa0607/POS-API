<?php

use Illuminate\Http\Request;
use Modules\Transaction\Http\Controllers\TransactionController;
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

Route::middleware('auth:api')->get('/transaction', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:api','scopes:pos'])->prefix('pos')->group(function (){

    Route::middleware(['log_activities_pos'])->prefix('transaction')->controller(TransactionController::class)->group(function () {
        Route::post('confirm', 'confirm');
        Route::post('done', 'done');
    });
});
