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
});

/* Webview */
Route::group(['middleware' => ['web'], 'prefix' => 'webview'], function () {
    Route::any('subscription/{id_subscription}', 'ApiSubscriptionWebview@webviewSubscriptionDetail');
});