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

Route::group(['middleware' => ['auth:api', 'user_agent', 'scopes:be'], 'prefix' => 'doctor'], function () {
    //Route::any('be/list', ['uses' => 'ApiDoctorClinicController@index']);
    Route::post('/', ['uses' => 'ApiDoctorController@index']);
    Route::post('store', ['uses' => 'ApiDoctorController@store']);
    Route::get('detail/{id}', ['uses' => 'ApiDoctorController@show']);
    Route::post('delete', ['uses' => 'ApiDoctorController@destroy']);

    Route::group(['prefix' => 'clinic'], function () {
        Route::any('/', ['uses' => 'ApiDoctorClinicController@index']);
        Route::post('store', ['uses' => 'ApiDoctorClinicController@store']);
        Route::get('{id}', ['uses' => 'ApiDoctorClinicController@show']);
        Route::post('delete', ['uses' => 'ApiDoctorClinicController@destroy']);
    });
    Route::group(['prefix' => 'service'], function () {
        Route::any('/', ['uses' => 'ApiDoctorServiceController@index']);
        Route::post('store', ['uses' => 'ApiDoctorServiceController@store']);
        Route::get('{id}', ['uses' => 'ApiDoctorServiceController@show']);
        Route::post('delete', ['uses' => 'ApiDoctorServiceController@destroy']);
    });
    Route::group(['prefix' => 'specialist-category'], function () {
        Route::any('/', ['uses' => 'ApiDoctorSpecialistCategoryController@index']);
        Route::post('store', ['uses' => 'ApiDoctorSpecialistCategoryController@store']);
        Route::get('{id}', ['uses' => 'ApiDoctorSpecialistCategoryController@show']);
        Route::post('delete', ['uses' => 'ApiDoctorSpecialistCategoryController@destroy']);
    });
    Route::group(['prefix' => 'specialist'], function () {
        Route::any('/', ['uses' => 'ApiDoctorSpecialistController@index']);
        Route::post('store', ['uses' => 'ApiDoctorSpecialistController@store']);
        Route::get('{id}', ['uses' => 'ApiDoctorSpecialistController@show']);
        Route::post('delete', ['uses' => 'ApiDoctorSpecialistController@destroy']);
    });

    Route::group(['prefix' => 'schedule-time'], function () {
        Route::any('/', ['uses' => 'ApiDoctorScheduleTimeController@index']);
        Route::post('store', ['uses' => 'ApiDoctorScheduleTimeController@store']);
        Route::get('{id}', ['uses' => 'ApiDoctorScheduleTimeController@show']);
        Route::post('delete', ['uses' => 'ApiDoctorScheduleTimeController@destroy']);
    });
});

Route::group(['middleware' => ['auth:api', 'user_agent', 'scopes:apps'], 'prefix' => 'doctor'], function () {
    Route::post('list', ['uses' => 'ApiDoctorController@listDoctor']);
    Route::get('detail-apps/{id}', ['uses' => 'ApiDoctorController@show']);

    Route::post('schedule/list', ['uses' => 'ApiScheduleController@getSchedule']);
});

Route::group(['middleware' => ['auth:doctor-apps', 'user_agent', 'scopes:doctor-apps'], 'prefix' => 'doctor'], function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::post('check', ['uses' => 'AuthDoctorController@checkPhoneNumber']);
        Route::post('otp-verification', ['uses' => 'AuthDoctorController@otpVerification']);
        Route::post('forgot-password', ['uses' => 'AuthDoctorController@forgotPassword']);
        Route::post('change-password', ['uses' => 'AuthDoctorController@changePassword']);
    });
});