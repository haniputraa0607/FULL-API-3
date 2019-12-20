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
Route::group(['middleware' => ['auth:api', 'log_activities'], 'prefix' => 'subscription'], function () {

    /* MASTER SUBSCRIPTION */
    Route::any('list', 'ApiSubscription@listSubscription');
    Route::any('detail', 'ApiSubscriptionWebview@subscriptionDetail');
    Route::any('me', 'ApiSubscription@mySubscription');

    /* CLAIM */
    Route::group(['prefix' => 'claim'], function () {
        Route::post('/', 'ApiSubscriptionClaim@claim');
        Route::post('paid', 'ApiSubscriptionClaimPay@claim');
        Route::post('pay-now', 'ApiSubscriptionClaimPay@bayarSekarang');
    });
});

/* CRON */
Route::group(['prefix' => 'cron/subscription'], function () {
    Route::any('/expire', 'ApiSubscriptionCron@cron');
});

/* Webview */
Route::group(['middleware' => ['web'], 'prefix' => 'webview'], function () {
    Route::any('subscription/{id_subscription}', 'ApiSubscriptionWebview@webviewSubscriptionDetail');
    Route::any('mysubscription/{id_subscription_user}', 'ApiSubscriptionWebview@mySubscription');
    Route::any('subscription/success/{id_subscription_user}', 'ApiSubscriptionWebview@subscriptionSuccess');
});

Route::group(['middleware' => ['auth:api-be', 'log_activities'], 'prefix' => 'subscription'], function () {
    Route::any('be/list', 'ApiSubscription@listSubscription');
    Route::post('step1', 'ApiSubscription@create');
    Route::post('step2', 'ApiSubscription@updateRule');
    Route::post('step3', 'ApiSubscription@updateContent');
    Route::post('updateDetail', 'ApiSubscription@updateAll');
    Route::post('show-step1', 'ApiSubscription@showStep1');
    Route::post('show-step2', 'ApiSubscription@showStep2');
    Route::post('show-step3', 'ApiSubscription@showStep3');
    Route::post('show-detail', 'ApiSubscription@detail');
    Route::post('participate-ajax', 'ApiSubscription@participateAjax');
    Route::post('trx', 'ApiSubscription@transaction');
});