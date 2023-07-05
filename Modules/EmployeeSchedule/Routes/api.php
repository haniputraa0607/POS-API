<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\EmployeeSchedule\Http\Controllers\EmployeeScheduleController;

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

Route::middleware('auth:api')->controller(EmployeeScheduleController::class)->prefix('/employee-schedule')->group(function () {
    $schedule = '{schedule}';
    Route::get('', 'index')->name('employee-schedule.list');
    Route::get('mine', 'mine')->name('employee-schedule.mine');
    Route::get('doctor', 'doctor')->name('shedule.doctor');
    Route::get('doctor/{id}', 'doctorDetail')->name('shedule.doctor.detail');
    Route::get('cashier', 'cashier')->name('shedule.cashier');
    Route::get('cashier/{id}', 'cashierDetail')->name('shedule.cashier.detail');
    Route::post('', 'store')->name('employee-schedule.create');
    Route::get($schedule, 'show')->name('employee-schedule.detail');
    Route::patch($schedule, 'update')->name('employee-schedule.update');
    Route::delete($schedule, 'destroy')->name('employee-schedule.delete');
});
