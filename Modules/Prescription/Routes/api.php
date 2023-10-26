<?php

use Illuminate\Http\Request;

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

Route::middleware(['auth:api','scopes:doctor'])->prefix('doctor')->group(function (){

    Route::prefix('prescription')->controller(PrescriptionController::class)->group(function () {
        Route::post('list', 'list');

        Route::prefix('custom')->group(function () {
            Route::post('/', 'getCustom');
            Route::post('add', 'addCustom');
            Route::middleware(['log_activities_doctor'])->post('submit', 'submitCustom');
            Route::post('list', 'listCustom');
            Route::get('categories', 'categoriesCustom');
            Route::middleware(['log_activities_doctor'])->post('create', 'createCustom');
            Route::post('list-container', 'listContainer');
            Route::post('list-substance', 'listSubstance');
        });
    });
});
