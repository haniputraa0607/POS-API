<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/partner', function (Request $request) {
    return $request->user();
});


Route::middleware(['auth:api','scopes:be'])->prefix('webhook')->group(function(){
    Route::prefix('partner')->controller(PartnerController::class)->group(function (){
        Route::post('create', 'webHookCreate');
        Route::patch('update', 'webHookUpdate');
        Route::delete('delete', 'webHookDelete');
    });
});
