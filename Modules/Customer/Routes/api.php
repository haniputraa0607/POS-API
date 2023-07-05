<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\CustomerController;

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

Route::middleware('auth:api')->controller(CustomerController::class)->prefix('customer')->group(function(){
    $customer = '{customer}';
    Route::get('', 'index')->name('customer.list');
    Route::get('show-by-phone', 'showByPhone')->name('customer.show.byPhone');
    Route::post('', 'store')->name('customer.store');
    Route::get('{id}', 'show')->name('customer.show');
    Route::patch($customer, 'update')->name('customer.update');
    Route::delete($customer, 'destroy')->name('customer.delete');
});