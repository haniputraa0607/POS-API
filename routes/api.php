<?php

use App\Http\Controllers\ApiUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::get('/banks', function () {
    return response()->json([
        'banks' => config('bank')
    ]);
});


Route::controller(ApiUserController::class)->prefix('/user')->group(function () {
    $user = '{user}';
    Route::get('', 'index')->name('user.list');
    Route::get('doctor', 'doctor')->name('group.doctor');
    Route::get('cashier', 'cashier')->name('group.cashier');
    Route::post('', 'store')->name('user.create');
    Route::get($user, 'show')->name('user.detail');
    Route::patch($user, 'update')->name('user.update');
    Route::delete($user, 'destroy')->name('user.delete');
});