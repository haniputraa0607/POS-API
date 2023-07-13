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

use Illuminate\Support\Facades\Route;
use Modules\Diagnostic\Http\Controllers\DiagnosticController;

Route::middleware('auth:api')->controller(DiagnosticController::class)->prefix('diagnostic')->group(function () {
    $diagnostic = '{diagnostic}';
    Route::get('', 'index')->name('diagnostic.list');
    Route::post('', 'store')->name('diagnostic.store');
    Route::get($diagnostic, 'show')->name('diagnostic.show');
    Route::patch($diagnostic, 'update')->name('diagnostic.update');
    Route::delete($diagnostic, 'destroy')->name('diagnostic.destroy');
});
