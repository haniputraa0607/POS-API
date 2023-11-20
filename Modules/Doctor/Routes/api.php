<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Doctor\Http\Controllers\DoctorController;
use Modules\Doctor\Http\Controllers\SuggestionHistoriesController;

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
// header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization');
Route::controller(DoctorController::class)->prefix('doctor')->group(function (){
    Route::get('splash', 'splash');
});

Route::middleware(['auth:api','scopes:doctor'])->controller(DoctorController::class)->prefix('doctor')->group(function (){
    Route::middleware(['log_activities_doctor'])->get('home', 'home');
    Route::get('list-service', 'listService');
    Route::middleware(['log_activities_doctor'])->get('next', 'nextQueue');
    Route::post('patient-list', 'patientList');

    Route::prefix('order')->controller(DoctorController::class)->group(function () {
        Route::middleware(['log_activities_doctor'])->post('/', 'getOrder');
        Route::post('add', 'addOrder');
        Route::post('delete', 'deleteOrder');
        Route::middleware(['log_activities_doctor'])->post('submit', 'submitOrder');
    });

    Route::middleware(['log_activities_doctor'])->prefix('suggestion')->controller(SuggestionHistoriesController::class)->group(function () {
        Route::post('list', 'list');
        Route::post('detail', 'detail');
    });

    Route::prefix('medical-record')->controller(MedicalRecordController::class)->group(function () {
        Route::post('patient-data', 'patientData');
        Route::post('update-patient-data', 'updatePatientData');
        Route::get('allergy', 'Allergy');
        Route::post('patient-allergy', 'patientAllergy');
        Route::post('update-patient-allergy', 'updatePatientAllergy');
        Route::prefix('medical-resume')->group(function(){
            Route::get('treatment-record-type', 'treatmentRecordType');
            Route::get('product-category', 'productCategory');
            Route::get('product', 'product');
            Route::post('medical-history', 'medicalHistory');
            Route::post('update-medical-history', 'updateMedicalHistory');
            
            Route::post('life-style', 'getLifeStyle');
            Route::post('update-life-style', 'updateLifeStyle');
        });
    });

});

Route::middleware(['auth:api','scopes:pos'])->controller(DoctorController::class)->prefix('pos/consultation')->group(function(){
    Route::prefix('doctor')->group(function () {
        Route::post('/', 'getDoctor');
        Route::post('list-date', 'getDoctorDate');
    });
});
