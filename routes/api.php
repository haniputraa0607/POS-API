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
use App\Http\Controllers\Api\UploadFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization');
Route::controller(AccessTokenController::class)->prefix('/login')->group(function () {
    Route::post('cms', 'loginCMS')->name('login.cms');
    Route::post('doctor', 'loginDoctor')->name('login.doctor');
    Route::post('cashier', 'loginCashier')->name('login.cashier');
});

Route::get('/logout', [AccessTokenController::class, 'logout'])->name('logout')->middleware('auth:api');;

Route::get('test-log', function () {
    Log::channel('db_log')->info("test log debug test", ['message' => 'test logging from url', "run"]);
    return ["result" => true];
});


Route::post('/upload-file', [UploadFile::class,'upload']);
