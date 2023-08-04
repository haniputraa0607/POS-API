<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Doctor\Http\Controllers\DoctorController;

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

Route::middleware(['auth:api','scopes:doctor'])->controller(DoctorController::class)->prefix('doctor')->group(function (){
    Route::get('home', 'home');
    Route::get('list-service', 'listService');
    Route::get('next', 'nextQueue');
    Route::get('splash', 'splash');

});

Route::middleware(['auth:api','scopes:pos'])->controller(DoctorController::class)->prefix('pos/consultation')->group(function(){
    Route::prefix('doctor')->group(function () {
        Route::post('/', 'getDoctor');
    });
});
