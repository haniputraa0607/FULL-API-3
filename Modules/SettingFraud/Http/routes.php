<?php

Route::group(['middleware' => ['auth:api','log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'api/setting-fraud', 'namespace' => 'Modules\SettingFraud\Http\Controllers'], function()
{
    Route::any('/', ['middleware' => 'feature_control:193', 'uses' => 'ApiSettingFraud@listSettingFraud']);
    Route::post('/update', ['middleware' => 'feature_control:192', 'uses' => 'ApiSettingFraud@updateSettingFraud']);
    Route::post('update/status', ['middleware' => 'feature_control:192', 'uses' => 'ApiSettingFraud@updateStatus']);

    Route::any('list/log/{type}', ['middleware' => 'feature_control:193', 'uses' => 'ApiSettingFraud@logFraud']);

    Route::any('detail/log/device', ['middleware' => 'feature_control:193', 'uses' => 'ApiSettingFraud@detailFraudDevice']);
    Route::any('detail/log/transaction-day', ['middleware' => 'feature_control:194', 'uses' => 'ApiSettingFraud@detailFraudTransactionDay']);
    Route::any('detail/log/transaction-week', ['middleware' => 'feature_control:195', 'uses' => 'ApiSettingFraud@detailFraudTransactionWeek']);
    Route::any('detail/log/update', ['middleware' => 'feature_control:192', 'uses' => 'ApiSettingFraud@updateLog']);
    Route::any('device-login/update-status', ['middleware' => 'feature_control:192', 'uses' => 'ApiSettingFraud@updateDeviceLoginStatus']);
});

Route::group(['middleware' => ['auth:api','log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'api/fraud', 'namespace' => 'Modules\SettingFraud\Http\Controllers'], function()
{
    Route::any('list/user', ['middleware' => 'feature_control:196', 'uses' => 'ApiSettingFraud@listUserFraud']);
    Route::any('detail/log/user', ['middleware' => 'feature_control:196', 'uses' => 'ApiSettingFraud@detailLogUser']);
});
