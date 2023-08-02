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
use Modules\Grievance\Http\Controllers\GrievanceController;
// header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization');
Route::middleware(['auth:api'])->controller(GrievanceController::class)->prefix('grievance')->group(function () {
    $grievance = '{grievance}';
    Route::get('', 'index')->name('grievance.list');
    Route::post('', 'store')->name('grievance.store');
    Route::get($grievance, 'show')->name('grievance.show');
    Route::patch($grievance, 'update')->name('grievance.update');
    Route::delete($grievance, 'destroy')->name('grievance.destroy');
});
