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
Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:apps'], 'prefix' => 'subscription'], function () {

    /* MASTER SUBSCRIPTION */
    Route::any('list', 'ApiSubscription@listSubscription');
    Route::any('list/v2', 'ApiSubscription@listSubscriptionV2');
    Route::any('detail', 'ApiSubscriptionWebview@subscriptionDetail');
    Route::any('me', 'ApiSubscription@mySubscription');
    Route::any('me/v2', 'ApiSubscription@mySubscriptionV2');

    /* CLAIM */
    Route::group(['prefix' => 'claim'], function () {
        Route::post('/', 'ApiSubscriptionClaim@claim');
        Route::post('cancel', 'ApiSubscriptionClaimPay@cancel');
        Route::post('paid', 'ApiSubscriptionClaimPay@claim');
        Route::post('pay-now', 'ApiSubscriptionClaimPay@bayarSekarang');
    });
    Route::post('mysubscription', 'ApiSubscriptionWebview@mySubscription');
    Route::post('later', 'ApiSubscriptionWebview@subsLater');
});

/* CRON */
Route::group(['prefix' => 'cron/subscription'], function () {
    Route::any('/expire', 'ApiSubscriptionCron@cron');
});

/* Webview */
Route::group(['middleware' => ['web', 'user_agent'], 'prefix' => 'webview'], function () {
    Route::any('subscription/{id_subscription}', 'ApiSubscriptionWebview@webviewSubscriptionDetail');
    Route::any('subscription/success/{id_subscription_user}', 'ApiSubscriptionWebview@subscriptionSuccess');
});

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'subscription'], function () {
    Route::any('be/list/ajax', ['uses' => 'ApiSubscription@listSubscriptionAjax']);
    Route::any('be/list', ['middleware' => 'feature_control:173', 'uses' => 'ApiSubscription@listSubscription']);
    Route::any('be/list-complete', ['middleware' => 'feature_control:173', 'uses' => 'ApiSubscription@listCompleteSubscription']);
    Route::post('step1', ['middleware' => 'feature_control:172', 'uses' => 'ApiSubscription@create']);
    Route::post('step2', ['middleware' => 'feature_control:172', 'uses' => 'ApiSubscription@updateRule']);
    Route::post('step3', ['middleware' => 'feature_control:172', 'uses' => 'ApiSubscription@updateContent']);
    Route::post('updateDetail', ['middleware' => 'feature_control:175', 'uses' => 'ApiSubscription@updateAll']);
    Route::post('show-step1', ['middleware' => 'feature_control:174', 'uses' => 'ApiSubscription@showStep1']);
    Route::post('show-step2', ['middleware' => 'feature_control:174', 'uses' => 'ApiSubscription@showStep2']);
    Route::post('show-step3', ['middleware' => 'feature_control:174', 'uses' => 'ApiSubscription@showStep3']);
    Route::post('show-detail', ['middleware' => 'feature_control:174', 'uses' => 'ApiSubscription@detail']);
    Route::post('participate-ajax', ['middleware' => 'feature_control:175', 'uses' => 'ApiSubscription@participateAjax']);
    Route::post('trx', ['middleware' => 'feature_control:177', 'uses' => 'ApiSubscription@transaction']);
    Route::post('delete', 'ApiSubscription@delete');
    Route::post('update-complete', ['middleware' => 'feature_control:175', 'uses' => 'ApiSubscription@updateComplete']);
    Route::post('text-replace', ['middleware' => 'feature_control:174', 'uses' => 'ApiSubscription@textReplace']);

    /* Transaction report*/
    Route::post('transaction-report', ['middleware' => 'feature_control:174', 'uses' => 'ApiSubscription@transactionReport']);

});

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'welcome-subscription'], function () {
	Route::any('setting', ['middleware' => 'feature_control:265', 'uses' => 'ApiWelcomeSubscription@setting']);
    Route::post('setting/update', ['middleware' => 'feature_control:267', 'uses' => 'ApiWelcomeSubscription@settingUpdate']);
    Route::post('setting/update/status', ['middleware' => 'feature_control:267', 'uses' => 'ApiWelcomeSubscription@settingUpdateStatus']);
    Route::any('list', ['middleware' => 'feature_control:264', 'uses' => 'ApiWelcomeSubscription@list']);
});