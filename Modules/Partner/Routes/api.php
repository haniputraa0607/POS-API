<?php

use Illuminate\Http\Request;
use Modules\Partner\Http\Controllers\PartnerController;

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

Route::prefix('landing-page')->group(function(){
    Route::prefix('official-partner')->controller(PartnerController::class)->group(function(){
        Route::get('', 'official_partner');
        Route::get('home', 'official_partner_home');
    });
});

Route::prefix('landing-page')->group(function(){
    Route::controller(PartnerController::class)->prefix('partner')->group(function () {
        $partner = '{partner}';
        Route::post('', 'index')->name('partner.list');
        Route::get($partner, 'show')->name('partner.show');
    });
});
