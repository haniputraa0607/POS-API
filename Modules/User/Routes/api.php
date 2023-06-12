<?php


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

use Modules\User\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/banks', function () {
    return response()->json([
        'banks' => config('bank')
    ]);
});


Route::controller(UserController::class)->prefix('/user')->group(function () {
    $user = '{user}';
    Route::get('', 'index')->name('user.list');
    Route::get('doctor', 'doctor')->name('group.doctor');
    Route::get('cashier', 'cashier')->name('group.cashier');
    Route::post('', 'store')->name('user.create');
    Route::get($user, 'show')->name('user.detail');
    Route::patch($user, 'update')->name('user.update');
    Route::delete($user, 'destroy')->name('user.delete');
});