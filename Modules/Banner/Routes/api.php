<?php

use Illuminate\Http\Request;
use Modules\Banner\Http\Controllers\BannerController;

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

Route::prefix('landing-page')->group(function(){
    Route::prefix('banner')->controller(BannerController::class)->group(function(){
        Route::get('/', 'index');
        Route::get('{id}', 'show');
    });
});
