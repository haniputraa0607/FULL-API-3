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
    Route::post('/consultation/update', 'ApiTransactionConsultationController@updateConsultationFromAdmin');
    Route::post('/consultation/get-schedule-time', 'ApiTransactionConsultationController@getScheduleTimeFromAdmin');
    Route::post('/consultation/detail/export', 'ApiTransactionConsultationController@exportDetail');
});

Route::any('consultation/detail/chat.html', 'ApiTransactionConsultationController@getChatView');
Route::any('consultation/detail/chat/updateIdUserInfobip', 'ApiTransactionConsultationController@updateIdUserInfobip');
Route::post('consultation/message/received', 'ApiTransactionConsultationController@receivedChatFromInfobip');

Route::group(['middleware' => ['auth:api', 'user_agent', 'scopes:apps'], 'prefix' => 'consultation'], function () {
    Route::post('/start', 'ApiTransactionConsultationController@startConsultation');
    Route::post('/done', 'ApiTransactionConsultationController@doneConsultation');
    Route::group(['prefix' => 'transaction'], function () {
        Route::post('/check', 'ApiTransactionConsultationController@checkTransaction');
        Route::post('/new', 'ApiTransactionConsultationController@newTransaction');
        Route::post('/get', 'ApiTransactionConsultationController@getTransaction');
        Route::get('/reminder/list', 'ApiTransactionConsultationController@getSoonConsultationList');
        Route::post('/reminder/detail', 'ApiTransactionConsultationController@getSoonConsultationDetail');
        Route::post('/history/list', 'ApiTransactionConsultationController@getHistoryConsultationList');
        Route::post('/detail', 'ApiTransactionConsultationController@transactionDetail');
    });

    Route::group(['prefix' => 'reschedule'], function () {
        Route::post('/edit', 'ApiTransactionConsultationController@editRescheduleConsultation');
        Route::post('/update', 'ApiTransactionConsultationController@submitRescheduleConsultation');
    });

    Route::post('/message/date-time', 'ApiTransactionConsultationController@getDateAndRemainingTimeConsultation');

    Route::post('/cron/autoend', 'ApiTransactionConsultationController@cronAutoEndConsultation');

    Route::group(['prefix' => '/detail'], function () {
        Route::post('/infobip', 'ApiTransactionConsultationController@getDetailInfobip');
        Route::post('/summary', 'ApiTransactionConsultationController@getDetailSummary');
        Route::post('/product-recomendation', 'ApiTransactionConsultationController@getProductRecomendation');
        Route::post('/drug-recomendation', 'ApiTransactionConsultationController@getDrugRecomendation');
        Route::post('/drug-recomendation/download', 'ApiTransactionConsultationController@downloadDrugRecomendation');
    });
});
Route::get('consultation/detail/drug-recomendation/{consultation}/medical-prescription.pdf', 'ApiTransactionConsultationController@downloadDrugRecomendationById');

Route::group(['middleware' => ['auth:doctor-apps', 'user_agent', 'scopes:doctor-apps'], 'prefix' => 'doctor'], function () {
    Route::post('/consultation', 'ApiTransactionConsultationController@getHandledConsultation');
    Route::post('/consultation/start', 'ApiTransactionConsultationController@startConsultation');
    Route::post('/consultation/done', 'ApiTransactionConsultationController@doneConsultation');
    Route::post('/consultation/complete', 'ApiTransactionConsultationController@completeConsultation');
    Route::post('/consultation/message/refresh', 'ApiTransactionConsultationController@refreshMessage');
    Route::post('/consultation/message/get', 'ApiTransactionConsultationController@getMessage');
    Route::post('/consultation/message/get-new', 'ApiTransactionConsultationController@getNewMessage');
    Route::post('/consultation/message/create', 'ApiTransactionConsultationController@createMessage');
    Route::post('/consultation/message/date-time', 'ApiTransactionConsultationController@getDateAndRemainingTimeConsultation');
    Route::post('/consultation/detail/soon', 'ApiTransactionConsultationController@getSoonConsultationDetail');

    Route::post('/consultation/option', 'ApiTransactionConsultationController@getConsultationSettings');

    Route::group(['prefix' => '/consultation/detail'], function () {
        Route::post('/infobip', 'ApiTransactionConsultationController@getDetailInfobip');
        Route::post('/summary', 'ApiTransactionConsultationController@getDetailSummary');
        Route::post('/product/list', 'ApiTransactionConsultationController@getProductList');
        Route::post('/drug/list', 'ApiTransactionConsultationController@getDrugList');
        Route::post('/product-recomendation', 'ApiTransactionConsultationController@getProductRecomendation');
        Route::post('/drug-recomendation', 'ApiTransactionConsultationController@getDrugRecomendation');

        Route::post('/update-consultation-detail', 'ApiTransactionConsultationController@updateConsultationDetail');
        Route::post('/update-recomendation', 'ApiTransactionConsultationController@updateRecomendation');
    });
});
