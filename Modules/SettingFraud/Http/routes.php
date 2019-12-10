<?php

Route::group(['middleware' => ['auth:api','log_activities'], 'prefix' => 'api/setting-fraud', 'namespace' => 'Modules\SettingFraud\Http\Controllers'], function()
{
    Route::any('/', 'ApiSettingFraud@listSettingFraud');
    Route::post('/update', 'ApiSettingFraud@updateSettingFraud');
    Route::post('update/status', 'ApiSettingFraud@updateStatus');

    Route::any('list/log/{type}', 'ApiSettingFraud@logFraud');

    Route::any('detail/log/device', 'ApiSettingFraud@detailFraudDevice');
    Route::any('detail/log/transaction-day', 'ApiSettingFraud@detailFraudTransactionDay');
    Route::any('detail/log/transaction-week', 'ApiSettingFraud@detailFraudTransactionWeek');
    Route::any('detail/log/update', 'ApiSettingFraud@updateLog');
    Route::any('device-login/update-status', 'ApiSettingFraud@updateDeviceLoginStatus');
});

Route::group(['middleware' => ['auth:api','log_activities'], 'prefix' => 'api/fraud', 'namespace' => 'Modules\SettingFraud\Http\Controllers'], function()
{
    Route::any('list/user', 'ApiSettingFraud@listUserFraud');
    Route::any('detail/log/user', 'ApiSettingFraud@detailLogUser');
});
