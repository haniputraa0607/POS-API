<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Setting\Http\Controllers\SettingController;

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
Route::middleware(['auth:api','scopes:be'])->prefix('be')->group(function (){
    Route::prefix('setting')->controller(SettingController::class)->group(function () {
        Route::post('splash/upload-image', 'uploadImage');

    });
});

Route::prefix('webhook')->group(function(){
    Route::prefix('tax')->controller(SettingController::class)->group(function(){
        Route::post('create', 'createTax');
        Route::patch('update', 'updateTax');
        Route::delete('delete', 'deleteEqual');
    });
});