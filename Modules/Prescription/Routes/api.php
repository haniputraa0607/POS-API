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
            Route::get('categories', 'categoriesCustom');
            Route::post('create', 'createCustom');
            Route::post('list-container', 'listContainer');
            Route::post('list-substance', 'listSubstance');
        });
    });
});
