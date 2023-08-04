<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\AccessTokenController;
use Illuminate\Support\Facades\Route;

// header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization');
Route::controller( AccessTokenController::class)->prefix('/login')->group(function(){
    Route::post('cms', 'loginCMS')->name('login.cms');
    Route::post('doctor', 'loginDoctor')->name('login.doctor');
    Route::post('cashier', 'loginCashier')->name('login.cashier');
});

Route::get('/logout', [AccessTokenController::class, 'logout'])->name('logout')->middleware('auth:api');;