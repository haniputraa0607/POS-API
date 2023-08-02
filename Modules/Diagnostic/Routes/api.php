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
// header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization');

Route::middleware('auth:api')->controller(DiagnosticController::class)->prefix('diagnostic')->group(function () {
    $diagnostic = '{diagnostic}';
    Route::get('', 'index')->name('diagnostic.list');
    Route::post('', 'store')->name('diagnostic.store');
    Route::get($diagnostic, 'show')->name('diagnostic.show');
    Route::patch($diagnostic, 'update')->name('diagnostic.update');
    Route::delete($diagnostic, 'destroy')->name('diagnostic.destroy');
});
