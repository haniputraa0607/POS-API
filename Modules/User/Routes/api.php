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

use Modules\User\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization');
Route::middleware('auth:api')->get('/banks', function () {
    return response()->json([
        'banks' => config('bank')
    ]);
});


Route::middleware('auth:api')->controller(UserController::class)->prefix('/user')->group(function () {
    $user = '{user}';
    Route::get('', 'index')->name('user.list');
    Route::get('doctor', 'doctor')->name('group.doctor');
    Route::get('cashier', 'cashier')->name('group.cashier');
    Route::post('', 'store')->name('user.create');
    Route::get($user, 'show')->name('user.detail');
    Route::patch($user, 'update')->name('user.update');
    Route::delete($user, 'destroy')->name('user.delete');
});

Route::middleware(['auth:api','scopes:be'])->controller(UserController::class)->prefix('be')->group(function (){
    Route::get('user', 'detailUser');
    Route::get('list-service', 'listService');
    Route::post('user/upload-image', 'uploadImage');

});

Route::prefix('webhook')->group(function(){
    Route::prefix('user')->group(function(){
        Route::get('all', [UserController::class, 'allUser']);
        Route::post('set_id', [UserController::class, 'setEqualIdUser']);
        Route::get('verified/{equal_id}', [UserController::class, 'getVerifiedUser']);
        Route::post('verified', [UserController::class, 'setVerifiedUser']);
    });
});

