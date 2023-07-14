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

Route::middleware(['auth:api','scopes:be'])->prefix('be')->group(function (){
    Route::prefix('product-category')->controller(ProductCategoryController::class)->group(function () {
        Route::post('create', 'create');
    });

    Route::prefix('product')->controller(ProductController::class)->group(function () {
        Route::post('create', 'create');
    });

});

Route::middleware(['auth:api','scopes:pos'])->prefix('pos')->group(function (){
    Route::prefix('product-category')->controller(ProductCategoryController::class)->group(function () {
        Route::get('list', 'list');
    });

    Route::prefix('product')->controller(ProductController::class)->group(function () {
        Route::post('list', 'list');
    });

});
