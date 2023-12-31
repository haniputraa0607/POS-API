<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Article\Http\Controllers\ArticleController;
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

Route::prefix('landing-page')->group(function(){
    Route::prefix('article')->controller(ArticleController::class)->group(function(){
        Route::post('', 'index');
        Route::get('detail/{id}', 'show');
        Route::get('other', 'otherArticle');
        Route::get('recommendation', 'recommendationArticle');
    });
});
