<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\POS\Http\Controllers\POSController;

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
Route::middleware(['auth:api','scopes:pos'])->controller(POSController::class)->prefix('pos')->group(function (){
    Route::get('home', 'home');
    Route::get('list-service', 'listService');
    Route::get('splash', 'splash');

    Route::prefix('order')->controller(POSController::class)->group(function () {
        Route::post('/', 'getOrder');
        Route::post('add', 'addOrder');
        Route::post('delete', 'deleteOrder');
        Route::post('submit', 'submitOrder');
    });
});
