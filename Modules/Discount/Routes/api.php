<?php

use Illuminate\Http\Request;
use Modules\Discount\Http\Controllers\DiscountController;

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

Route::middleware('auth:api')->get('/discount', function (Request $request) {
    return $request->user();
});

Route::prefix('webhook')->group(function(){
    Route::prefix('discount')->group(function(){
        Route::get('all', [DiscountController::class, 'allDiscount']);
        Route::post('set_id', [DiscountController::class, 'setEqualIdDiscount']);
        Route::get('verified/{equal_id}', [DiscountController::class, 'getVerifiedDiscount']);
        Route::post('verified', [DiscountController::class, 'setVerifiedDiscount']);
    });
});