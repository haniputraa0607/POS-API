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

Route::middleware('scopes:pos')->controller(POSController::class)->prefix('pos-order')->group(function (){
    Route::get('home', 'home');
    Route::get('list-service', 'listService');

});
