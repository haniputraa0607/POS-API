<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductController;

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
        Route::get('list', 'list');
    });

    Route::controller(ProductController::class)->prefix('/product')->group(function () {
        $product = '{product}';
        Route::get('', 'index')->name('product.list');
        Route::post('', 'store')->name('product.create');
        Route::get($product, 'show')->name('product.show');
        Route::patch($product, 'update')->name('product.update');
        Route::delete($product, 'destroy')->name('product.delete');
    });

    Route::prefix('product')->controller(LandingPageController::class)->group(function () {
        Route::post('detail', 'detail');
        Route::post('datatable_list', 'datatable_list');
        Route::post('table_list', 'table_list');

    });

    Route::prefix('product')->controller(ProductController::class)->group(function () {
        Route::post('create', 'create');
        Route::post('upload-image', 'uploadImage');
    });

    Route::controller(ProductController::class)->prefix('/product')->group(function () {
        $product = '{product}';
        Route::get('', 'index')->name('product.list');
        Route::post('', 'store')->name('product.create');
        Route::get($product, 'show')->name('product.show');
        Route::patch($product, 'update')->name('product.update');
        Route::delete($product, 'destroy')->name('product.delete');
    });


    Route::prefix('product')->controller(LandingPageController::class)->group(function () {
        Route::post('detail', 'detail');
        Route::post('datatable_list', 'datatable_list');
        Route::post('table_list', 'table_list');
    });

});

Route::middleware(['auth:api','scopes:pos'])->prefix('pos')->group(function (){
    Route::prefix('product-category')->controller(ProductCategoryController::class)->group(function () {
        Route::get('list', 'list');
    });

    Route::prefix('product')->controller(ProductController::class)->group(function () {
        Route::post('list', 'list');
    });

    Route::prefix('treatment')->controller(TreatmentController::class)->group(function () {
        Route::post('list', 'list');
        Route::post('list-date', 'listDate');
        Route::post('customer-history', 'customerHistory');
    });
});

Route::prefix('landing-page')->group(function(){
    Route::prefix('product')->controller(LandingPageController::class)->group(function(){
        Route::post('list', 'list');
        Route::get('detail/{id}', 'detail');
        Route::get('trending', 'product_trending');
        Route::get('finest', 'product_finest');
    });
    Route::prefix('treatment')->controller(LandingPageController::class)->group(function(){
        Route::post('list', 'treatment');
        Route::get('detail/{id}', 'detail');
    });
    Route::prefix('product-category')->controller(LandingPageController::class)->group(function () {
        Route::get('list', 'product_category');
    });
});

Route::middleware('client')->prefix('webhook')->group(function(){
    Route::prefix('product')->controller(ProductController::class)->group(function (){
        Route::post('bulk_create', 'webHookCreateBulk');
        Route::post('create', 'webHookCreate');
        Route::patch('update', 'webHookUpdate');
        Route::delete('delete', 'webHookDelete');
    });
    Route::prefix('product-category')->controller(ProductCategoryController::class)->group(function (){
        Route::post('bulk_create', 'webHookCreateBulk');
        Route::post('create', 'webHookCreate');
        Route::patch('update', 'webHookUpdate');
        Route::delete('delete', 'webHookDelete');
    });
});

Route::middleware(['auth:api','scopes:doctor'])->prefix('doctor')->group(function (){
    Route::prefix('product-category')->controller(ProductCategoryController::class)->group(function () {
        Route::get('list', 'list');
    });

    Route::prefix('product')->controller(ProductController::class)->group(function () {
        Route::post('list', 'list');
    });

    Route::prefix('treatment')->controller(TreatmentController::class)->group(function () {
        Route::post('list', 'list');
        Route::post('list-date', 'listDate');
        Route::post('customer-history', 'customerHistory');
    });

});
