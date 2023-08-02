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

// header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization');
Route::middleware(['auth:api','scopes:pos'])->controller(CustomerController::class)->prefix('pos/customer')->group(function(){
    $customer = '{customer}';
    Route::post('detail', 'showByPhone')->name('customer.show.byPhone');
    Route::post('register', 'store')->name('customer.store');
    Route::post('edit', 'update')->name('customer.update');
    Route::get('', 'index')->name('customer.list');
    Route::get('{id}', 'show')->name('customer.show');
    Route::delete($customer, 'destroy')->name('customer.delete');
});
