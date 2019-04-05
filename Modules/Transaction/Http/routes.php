<?php

Route::group(['middleware' => ['auth:api', 'log_request'], 'prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function()
{
    Route::post('/outlet', 'ApiNotification@adminOutlet');
    Route::post('/admin/confirm', 'ApiNotification@adminOutletComfirm');
    Route::get('/rule', 'ApiTransaction@transactionRule');
    Route::post('/rule/update', 'ApiTransaction@transactionRuleUpdate');
    Route::get('/courier', 'ApiTransaction@internalCourier');
    Route::post('/point', 'ApiTransaction@pointUser');
    Route::post('/point/filter', 'ApiTransaction@pointUserFilter');
    Route::post('/balance', 'ApiTransaction@balanceUser');
    Route::post('/balance/filter', 'ApiTransaction@balanceUserFilter');
    Route::post('/admin', 'ApiNotification@adminOutletNotification');
    Route::post('/setting', 'ApiSettingTransaction@settingTrx');

    Route::group(['prefix' => 'manualpayment'], function()
    {
        Route::get('/bank', 'ApiTransactionPaymentManual@bankList');
        Route::post('/bank/delete', 'ApiTransactionPaymentManual@bankDelete');
        Route::post('/bank/create', 'ApiTransactionPaymentManual@bankCreate');
        Route::get('/bankmethod', 'ApiTransactionPaymentManual@bankmethodList');
        Route::post('/bankmethod/delete', 'ApiTransactionPaymentManual@bankmethodDelete');
        Route::post('/bankmethod/create', 'ApiTransactionPaymentManual@bankmethodCreate');
        Route::get('/list', 'ApiTransaction@manualPaymentList');
        Route::post('/edit', 'ApiTransaction@manualPaymentEdit');
        Route::post('/update', 'ApiTransaction@manualPaymentUpdate');
        Route::post('/create', 'ApiTransaction@manualPaymentCreate');
        Route::post('/detail', 'ApiTransaction@manualPaymentDetail');
        Route::post('/delete', 'ApiTransaction@manualPaymentDelete');

        Route::group(['prefix' => 'data'], function()
        {
            Route::get('/{type}', 'ApiTransactionPaymentManual@manualPaymentList');
            Route::post('/detail', 'ApiTransactionPaymentManual@detailManualPaymentUnpay');
            Route::post('/confirm', 'ApiTransactionPaymentManual@manualPaymentConfirm');
            Route::post('/filter/{type}', 'ApiTransactionPaymentManual@transactionPaymentManualFilter');

        });

        Route::post('/method/save', 'ApiTransaction@manualPaymentMethod');
        Route::post('/method/delete', 'ApiTransaction@manualPaymentMethodDelete');
    });
});

Route::group(['middleware' => ['auth:api', 'log_request'], 'prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function()
{
    Route::get('/', 'ApiTransaction@transactionList');
    Route::any('/filter', 'ApiTransaction@transactionFilter');
    Route::post('/detail', 'ApiTransaction@transactionDetail');
    Route::post('/point/detail', 'ApiTransaction@transactionPointDetail');
    Route::post('/balance/detail', 'ApiTransaction@transactionBalanceDetail');

    Route::post('history', 'ApiHistoryController@historyAll');
    Route::post('history-trx', 'ApiHistoryController@historyTrx');
    Route::post('history-ongoing', 'ApiHistoryController@historyTrxOnGoing');
    Route::post('history-point', 'ApiHistoryController@historyPoint');
    Route::post('history-balance', 'ApiHistoryController@historyBalance');

    Route::post('/shipping', 'ApiTransaction@getShippingFee');
    Route::get('/address', 'ApiTransaction@getAddress');
    Route::post('/address/add', 'ApiTransaction@addAddress');
    Route::post('/address/update', 'ApiTransaction@updateAddress');
    Route::post('/address/delete', 'ApiTransaction@deleteAddress');
    Route::post('/void', 'ApiTransaction@transactionVoid');

    Route::post('/new', 'ApiOnlineTransaction@newTransaction');
    Route::post('/confirm', 'ApiConfirm@confirmTransaction');
    Route::post('/prod/confirm', 'ApiTransactionProductionController@confirmTransaction2');
    Route::get('/{key}', 'ApiTransaction@transactionList');
});

Route::group(['middleware' => 'auth_client', 'prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function()
{
    Route::post('/province', 'ApiTransaction@getProvince');
    Route::post('/city', 'ApiTransaction@getCity');
    Route::post('/subdistrict', 'ApiTransaction@getSubdistrict');
    Route::post('/courier', 'ApiTransaction@getCourier');
    Route::any('/grand-total', 'ApiSettingTransactionV2@grandTotal');
    
    Route::post('/new-transaction', 'ApiTransaction@transaction');

    Route::post('/shipping/gosend', 'ApiTransaction@shippingCostGoSend');
});

Route::group(['prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function()
{
    Route::any('/finish', 'ApiTransaction@transactionFinish');
    Route::any('/cancel', 'ApiTransaction@transactionCancel');
    Route::any('/error', 'ApiTransaction@transactionError');
    Route::any('/notif', 'ApiNotification@receiveNotification');
});
    
Route::group(['prefix' => 'api/transaction', 'middleware' => ['log_request'], 'namespace' => 'Modules\Transaction\Http\Controllers'], function()
{
    Route::post('/detail/webview', 'ApiWebviewController@webview');
    Route::post('/detail/webview/point', 'ApiWebviewController@webviewPoint');
    Route::post('/detail/webview/balance', 'ApiWebviewController@webviewBalance');

    Route::post('/detail/webview/success', 'ApiWebviewController@trxSuccess');
});

Route::group(['middleware' => 'auth:api', 'prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function()
{
    Route::post('/dump', 'ApiDumpController@dumpData');
    Route::post('/gen', 'ApiDumpController@generateNumber');
});

Route::group(['middleware' => 'auth_client', 'prefix' => 'api/manual-payment', 'namespace' => 'Modules\Transaction\Http\Controllers'], function()
{
    Route::get('/', 'ApiTransactionPaymentManual@listPaymentMethod');
    Route::get('/list', 'ApiTransactionPaymentManual@list');
    Route::post('/method', 'ApiTransactionPaymentManual@paymentMethod');

});

Route::group(['middleware' => ['auth:api', 'log_request'], 'prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function()
{
    Route::get('setting/cashback', 'ApiSettingCashbackController@list');
    Route::post('setting/cashback/update', 'ApiSettingCashbackController@update');
});

Route::group(['prefix' => 'api/cron/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function()
{
    Route::get('/pickup/completed', 'ApiCronTrxController@completeTransactionPickup');
    Route::get('/expire', 'ApiCronTrxController@cron');
    Route::get('/schedule', 'ApiCronTrxController@checkSchedule');
});

Route::group(['prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function()
{
    Route::get('/data/decript', function() {
        return response()->json(App\Lib\MyHelper::decrypt2019('RVpHb1g0ZFV0djAwR3Q4TnNhYU1IWjN6cG1RU2lXckZNS2x0MkZaVU0rZ3hyT00yanpkdnVSZzh4NFBnZjlpVFdaYmNWN2NMMFkzSVNEVUJUZkFlWExKdWlkaG1CNnByMGw4OGJXSXB6L0h6blhFTFZQUlJOYWRQUjBtZGQvYjdrajU5b3FjNVFSdUkvUWFIWDhVc0I0OHE0S1JsVmNYdnJmdzluRlBmRVgxTFpsQjZLMXNxOGpNODY3Q2ExQ3lhUnU3elB6S2NpL0RseXhndWlOUGhkZ1J2M3o0TlplNExyQWR4VDhnb1BqWHVCWFpIQXFMMitsMkN0WG8rQnJVcFFsRDg1TGc1TnNJL1lvTTdTYUE3TUlPdlRrdWExS0M0bTlHSUdFOEwrclRhQjdWOVJIcEgrblZ3STRONkoxNmQvZUkxTXQremVERE1BMXI0cjVFM21mUkxmNDVmSUV2My9keDNOZFRuNTQ0ZkUxRzRuNHhMUnVtTHhYZlI3bWdpTHdMY1BNcCtxQkxwQWhGbnNobXN6Uldrb0w3Y3U0MjRtZFlYb0FkeGZpd0dGZFNWcFlMN3NvRnlldDd4RzV0Um82M1pmb0NTeWZWQ3pTRlRaRHowRVJzKzAxZ2txblJaRDdrN2tpQVBudXVYQlZUbm12SkxqV040OCtodDBqVmcyUlZpVEsrZUtiMVdyYTFydmFtczRHWWhmNlBHTFVOenJidTBUYUc4MVRaVG1RaWZ3bHJaWkZYK2JLeW1SMm94ZXRtN0s5TWhJVVRYb0NiS1lIOEhmSkh6NzFiRmNTL3NTOVVoRHltVmkvRDRLL0NNMkRVRE1sMEtJL1BMWEFtcTdUbWNhV21wNWUxdElEaUs1S3JxN25vczZDWlQvWkN4eVpoQTN0dzc2bWJoRGJxY1hGRVF6NGRJTFJNVUNKb29DRnNEaTVDTXY2ZXkwbFhjQXowckJ5ajJYM05NV3dMaEZlY3dxMzBhbzl5R2lJdGRFQjZMTUVlSWNlR3dZVVQ1MmVMUFl1SllEMFdyblhRaC9XdFY5Wm1TRmM4NjVnVGIrTEFUTytGNGlMUnl2UFNkRUN1VlY5ejc5bzRNMGNsSXJGVXVQNy9ZTWdFL3RkSUhUdUFhaU5jNDdDVzJHMy90bDROdDkyY1VGVlMwcTR1THphbEtIcnE2WkZNajVlZUVKNW90TnV3cWw0dTVEY0FFTW9QYjVLOFkzRGo4QWkvMDBJYXk2eC81VitVVUhoK3Jkd0xPR054Yk5udEUzay84d2VPNWJab1JBNkszajlwY2RzR2hTd3dwTng5R0FRV0ZVMzB1Q3dFV3d0RXVaR2h1bXREMXByWnJ1ZGNhUjUzU0tiT2Rmd0JWbUJ0NFNVK2hCZE16bEsxdU5LMGlWdWhYcWR5SWxkNUk5bUU1eDZtVWVzOUdna3RqSzdWQjZJN2VnK3kyTzZLZHY1eWM1TlFkMEF0WWRKQTJZOUZhWTRFRVJXZFBGeCt0VmUvMnRDeVBkUXhPWXZPa0l4K3htWTVzUTlRWHdxRG84OXFhSHd6Y1hrWjZFbFNGblJiYTFJaTZpeWE3NzBOZGpVVmNBMWNBSml4VDRxV2xWVXUwNnd2Zw'));
        
        return response()->json(App\Lib\MyHelper::encrypt2019('testing'));
    });
});