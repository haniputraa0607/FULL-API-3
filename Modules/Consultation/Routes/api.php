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

Route::middleware('auth:api')->get('/consultation', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['auth:api', 'user_agent', 'scopes:be'], 'prefix' => 'be'], function () {
    Route::post('/consultation', 'ApiTransactionConsultationController@getConsultationFromAdmin');
    Route::get('/consultation/detail/{id}', 'ApiTransactionConsultationController@getConsultationDetailFromAdmin');
});

Route::group(['middleware' => ['auth:api', 'user_agent', 'scopes:apps'], 'prefix' => 'consultation'], function () {
    Route::post('/start', 'ApiTransactionConsultationController@startConsultation');
    Route::post('/done', 'ApiTransactionConsultationController@doneConsultation');
    Route::group(['prefix' => 'transaction'], function () {
        Route::post('/check', 'ApiTransactionConsultationController@checkTransaction');
        Route::post('/new', 'ApiTransactionConsultationController@newTransaction');
        Route::post('/get', 'ApiTransactionConsultationController@getTransaction');
        Route::get('/reminder/list', 'ApiTransactionConsultationController@getSoonConsultationList');
        Route::post('/reminder/detail', 'ApiTransactionConsultationController@getSoonConsultationDetail');
        Route::get('/history/list', 'ApiTransactionConsultationController@getHistoryConsultationList');
    });
});

Route::group(['middleware' => ['auth:doctor-apps', 'user_agent', 'scopes:doctor-apps'], 'prefix' => 'doctor'], function () {
    Route::post('/consultation', 'ApiTransactionConsultationController@getHandledConsultation');

    Route::group(['prefix' => '/consultation/detail'], function () {
        Route::post('/summary', 'ApiTransactionConsultationController@getDetailSummary');
        Route::post('/product/list', 'ApiTransactionConsultationController@getProductList');
        Route::post('/drug/list', 'ApiTransactionConsultationController@getDrugList');
        Route::post('/product-recomendation', 'ApiTransactionConsultationController@getProductRecomendation');
        Route::post('/drug-recomendation', 'ApiTransactionConsultationController@getDrugRecomendation');

        Route::post('/update-consultation-detail', 'ApiTransactionConsultationController@updateConsultationDetail');
        Route::post('/update-recomendation', 'ApiTransactionConsultationController@updateRecomendation');
    });
});