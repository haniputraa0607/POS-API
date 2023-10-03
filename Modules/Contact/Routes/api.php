<?php

use Illuminate\Http\Request;
use Modules\Contact\Http\Controllers\ContactController;
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

Route::middleware('auth:api')->get('/contact', function (Request $request) {
    return $request->user();
});

Route::prefix('landing-page')->group(function(){
    Route::prefix('contact')->group(function(){
        Route::post('send_message', [ContactController::class, 'send_message']);
        Route::get('official', [ContactController::class, 'official']);
        Route::get("consultation_ordering", [ContactController::class, 'consultation_ordering']);
    });
});
