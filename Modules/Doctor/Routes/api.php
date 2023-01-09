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
    Route::get('detail/{id}', ['uses' => 'ApiDoctorController@showAdmin']);
    Route::post('delete', ['uses' => 'ApiDoctorController@destroy']);
    Route::post('change-password', ['uses' => 'ApiDoctorController@changePassword']);

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
    Route::group(['prefix' => 'be/specialist-category'], function () {
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
    Route::group(['prefix' => 'schedule'], function () {
        Route::post('/', ['uses' => 'ApiScheduleController@index']);
        Route::post('store', ['uses' => 'ApiScheduleController@store']);
        //Route::get('{id}', ['uses' => 'ApiScheduleController@show']);
        Route::post('delete', ['uses' => 'ApiScheduleController@destroy']);
    });

    Route::group(['prefix' => 'recomendation'], function () {
        Route::post('store', ['uses' => 'ApiDoctorController@updateRecomendationStatus']);
    });

    Route::post('list/outlet', ['uses' => 'ApiDoctorController@listAllDoctor']);

    Route::group(['prefix' => 'update-data'], function () {
        Route::post('list', ['middleware' => 'feature_control:428,429', 'uses' => 'ApiDoctorUpdateData@list']);
        Route::post('detail', ['middleware' => 'feature_control:429', 'uses' => 'ApiDoctorUpdateData@detail']);
        Route::post('update', ['middleware' => 'feature_control:430', 'uses' => 'ApiDoctorUpdateData@update']);
    });
});

Route::group(['middleware' => ['auth:api', 'user_agent', 'scopes:apps'], 'prefix' => 'doctor'], function () {
    Route::post('list', ['uses' => 'ApiDoctorController@listDoctor']);
    Route::get('detail-apps/{id}', ['uses' => 'ApiDoctorController@show']);
    Route::get('outlet/option', ['uses' => 'ApiDoctorController@listOutletOption']);
    Route::get('outlet/list', ['uses' => 'ApiDoctorController@listAllOutletWithDoctor']);

    Route::get('specialist-category', ['uses' => 'ApiDoctorSpecialistCategoryController@index']);

    Route::post('schedule/list', ['uses' => 'ApiScheduleController@getSchedule']);
    Route::get('recommendation', ['uses' => 'ApiDoctorController@getDoctorRecomendation']);
});

Route::group(['prefix' => 'auth/doctor'], function () {
    Route::post('phone/check', ['uses' => 'AuthDoctorController@phoneCheck']);
    Route::post('pin/check', ['uses' => 'AuthDoctorController@checkPin']);
    Route::post('pin/forgot', ['uses' => 'AuthDoctorController@forgotPin']);
    Route::post('pin/verify', 'AuthDoctorController@verifyPin')->middleware('decrypt_pin');
    Route::post('pin/change', 'AuthDoctorController@changePin')->middleware(['decrypt_pin:pin_new','decrypt_pin:pin_old']);

    Route::post('otp-verification', ['uses' => 'AuthDoctorController@otpVerification']);
    Route::post('forgot-password', ['uses' => 'AuthDoctorController@forgotPassword']);
    Route::post('change-password', ['uses' => 'AuthDoctorController@changePassword']);
});

Route::group(['middleware' => ['auth:doctor-apps', 'user_agent', 'scopes:doctor-apps'], 'prefix' => 'doctor'], function () {
    Route::post('home', ['uses' => 'ApiHomeController@home']);
    Route::get('schedule/my', ['uses' => 'ApiScheduleController@getMySchedule']);
    Route::post('schedule/my/store', ['uses' => 'ApiScheduleController@storeMySchedule']);
    Route::get('get-infobip-token', ['uses' => 'ApiDoctorController@getInfobipToken']);

    Route::get('cron-status', ['uses' => 'ApiDoctorController@cronUpdateDoctorStatus']);

    Route::get('data-update-request', 'ApiDoctorUpdateData@listField');
    Route::post('data-update-request/save', 'ApiDoctorUpdateData@updateRequest');

    Route::get('my/settings', ['uses' => 'ApiDoctorController@getMySettings']);
    Route::post('my/settings', ['uses' => 'ApiDoctorController@updateMySettings']);
    Route::get('my/profile', ['uses' => 'ApiDoctorController@myProfile']);
    Route::post('pin/change', 'AuthDoctorController@changePinLoggedUser')->middleware(['decrypt_pin:pin_new','decrypt_pin:pin_old']);

    Route::post('submission/store', ['uses' => 'ApiDoctorController@submissionChangeDataStore']);

    Route::group(['prefix' => 'rating'], function () {
        Route::get('summary', 'ApiDoctorController@ratingSummary');
        Route::get('comment', 'ApiDoctorController@ratingComment');
    });

    Route::group(['prefix' => 'inbox'], function () {
        Route::post('marked', 'ApiDoctorInbox@markedInbox');
        Route::post('unmark', 'ApiDoctorInbox@unmarkInbox');
        Route::post('unread', 'ApiDoctorInbox@unread');
        Route::post('/{mode?}', 'ApiDoctorInbox@listInbox');
    });
});
