<?php

Route::group(['middleware' => ['auth:api','log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'api/setting-fraud', 'namespace' => 'Modules\SettingFraud\Http\Controllers'], function()
{
    Route::any('/', ['middleware' => 'feature_control:193', 'uses' => 'ApiSettingFraud@listSettingFraud']);
    Route::post('/update', ['middleware' => 'feature_control:192', 'uses' => 'ApiSettingFraud@updateSettingFraud']);
    Route::post('update/status', ['middleware' => 'feature_control:192', 'uses' => 'ApiSettingFraud@updateStatus']);
    Route::any('config', ['uses' => 'ApiSettingFraud@fraudConfig']);
});

Route::group(['middleware' => ['auth:api','log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'api/fraud', 'namespace' => 'Modules\SettingFraud\Http\Controllers'], function()
{
    Route::any('list/user', ['middleware' => 'feature_control:196', 'uses' => 'ApiFraud@listUserFraud']);
    Route::any('detail/log/user', ['middleware' => 'feature_control:196', 'uses' => 'ApiFraud@detailLogUser']);
    Route::any('list/log/{type}', ['middleware' => 'feature_control:193', 'uses' => 'ApiFraud@logFraud']);
    Route::any('detail/log/device', ['middleware' => 'feature_control:193', 'uses' => 'ApiFraud@detailFraudDevice']);
    Route::any('detail/log/transaction-day', ['middleware' => 'feature_control:194', 'uses' => 'ApiFraud@detailFraudTransactionDay']);
    Route::any('detail/log/transaction-week', ['middleware' => 'feature_control:195', 'uses' => 'ApiFraud@detailFraudTransactionWeek']);
    Route::any('detail/log/transaction-between', ['middleware' => 'feature_control:195', 'uses' => 'ApiFraud@detailFraudTransactionBetween']);
    Route::any('detail/log/promo-code','ApiFraud@detailFraudPromoCode');
    Route::any('detail/log/update', ['middleware' => 'feature_control:192', 'uses' => 'ApiFraud@updateLog']);
    Route::any('device-login/update-status', ['middleware' => 'feature_control:192', 'uses' => 'ApiFraud@updateDeviceLoginStatus']);

});

Route::group(['prefix' => 'api/fraud/cron', 'namespace' => 'Modules\SettingFraud\Http\Controllers'], function()
{
    Route::any('referral', ['uses' => 'ApiFraud@fraudCheckReferral']);
});
