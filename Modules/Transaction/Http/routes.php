<?php

Route::any('api/shipment/notification', 'Modules\Transaction\Http\Controllers\ApiShipperController@updateStatuShipment');
Route::group(['middleware' => ['auth:api'],'prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {

    Route::any('available-payment', 'ApiOnlineTransaction@availablePayment');
    Route::any('available-payment/update', 'ApiOnlineTransaction@availablePaymentUpdate')->middleware('scopes:be');
    Route::any('be/available-delivery', 'ApiOnlineTransaction@listAvailableDelivery')->middleware('scopes:be');
    Route::post('available-delivery/update', 'ApiOnlineTransaction@availableDeliveryUpdate')->middleware('scopes:be');
    Route::any('trigger-reversal', 'ApiOnlineTransaction@triggerReversal')->middleware('scopes:be');
});
Route::any('api/transaction/update-gosend', 'Modules\Transaction\Http\Controllers\ApiGosendController@updateStatus');
Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {

    Route::group(['prefix' => 'invalid-flag'], function () {

        Route::any('filter', ['middleware' => 'feature_control:274', 'uses' => 'ApiInvalidTransactionController@filterMarkFlag']);
        Route::post('mark-as-valid/update', ['uses' => 'ApiInvalidTransactionController@markAsValidUpdate']);
        Route::post('mark-as-invalid/add', ['uses' => 'ApiInvalidTransactionController@markAsInvalidAdd']);
        Route::post('mark-as-pending-invalid/add', ['uses' => 'ApiInvalidTransactionController@markAsPendingInvalidAdd']);
    });
    Route::get('list-all', 'ApiTransaction@transactionList');
    Route::post('list-all', 'ApiTransaction@transactionList');
    Route::post('outlet/list-payment', 'ApiTransaction@listPaymentDetailOutlet');
    Route::post('/outlet', 'ApiNotification@adminOutlet');
    Route::post('/admin/confirm', 'ApiNotification@adminOutletComfirm');
    Route::get('setting/cashback', 'ApiSettingCashbackController@list');
    Route::post('setting/cashback/update', 'ApiSettingCashbackController@update');
    Route::post('/dump', 'ApiDumpController@dumpData');
    Route::get('rule', 'ApiTransaction@transactionRule');
    Route::post('rule/update', 'ApiTransaction@transactionRuleUpdate');
    Route::get('/courier', 'ApiTransaction@internalCourier');
    Route::post('/point', 'ApiTransaction@pointUser');
    Route::post('/point/filter', 'ApiTransaction@pointUserFilter');
    Route::post('/balance', 'ApiTransaction@balanceUser');
    Route::post('/balance/filter', 'ApiTransaction@balanceUserFilter');
    Route::post('/admin', 'ApiNotification@adminOutletNotification');
    Route::post('/setting', 'ApiSettingTransaction@settingTrx');
    Route::any('/setting/package-detail-delivery', 'ApiSettingTransaction@packageDetailDelivery');
    Route::any('/setting/image-delivery', 'ApiSettingTransaction@imageDelivery');
    Route::post('/setting/mdr', 'ApiSettingTransaction@settingMdr');
    Route::post('/setting/withdrawal', 'ApiSettingTransaction@settingWithdrawal');
    Route::any('be/filter', 'ApiTransaction@transactionFilter');
    Route::post('retry-void-payment/retry', 'ApiTransaction@retry');
    Route::group(['prefix' => 'manualpayment'], function () {

        Route::get('/bank', ['middleware' => 'feature_control:64', 'uses' => 'ApiTransactionPaymentManual@bankList']);
        Route::post('/bank/delete', ['middleware' => 'feature_control:68', 'uses' => 'ApiTransactionPaymentManual@bankDelete']);
        Route::post('/bank/create', ['middleware' => 'feature_control:66', 'uses' => 'ApiTransactionPaymentManual@bankCreate']);
        Route::get('/bankmethod', ['middleware' => 'feature_control:64', 'uses' => 'ApiTransactionPaymentManual@bankmethodList']);
        Route::post('/bankmethod/delete', ['middleware' => 'feature_control:68', 'uses' => 'ApiTransactionPaymentManual@bankmethodDelete']);
        Route::post('/bankmethod/create', ['middleware' => 'feature_control:66', 'uses' => 'ApiTransactionPaymentManual@bankmethodCreate']);
        Route::get('/list', ['middleware' => 'feature_control:64', 'uses' => 'ApiTransaction@manualPaymentList']);
        Route::post('/edit', ['middleware' => 'feature_control:67', 'uses' => 'ApiTransaction@manualPaymentEdit']);
        Route::post('/update', ['middleware' => 'feature_control:67', 'uses' => 'ApiTransaction@manualPaymentUpdate']);
        Route::post('/create', ['middleware' => 'feature_control:66', 'uses' => 'ApiTransaction@manualPaymentCreate']);
        Route::post('/detail', ['middleware' => 'feature_control:65', 'uses' => 'ApiTransaction@manualPaymentDetail']);
        Route::post('/delete', ['middleware' => 'feature_control:68', 'uses' => 'ApiTransaction@manualPaymentDelete']);
        Route::group(['prefix' => 'data'], function () {

            Route::get('/{type}', ['middleware' => 'feature_control:64', 'uses' => 'ApiTransactionPaymentManual@manualPaymentList']);
            Route::post('/detail', ['middleware' => 'feature_control:65', 'uses' => 'ApiTransactionPaymentManual@detailManualPaymentUnpay']);
            Route::post('/confirm', 'ApiTransactionPaymentManual@manualPaymentConfirm');
            Route::post('/filter/{type}', 'ApiTransactionPaymentManual@transactionPaymentManualFilter');
        });
        Route::post('/method/save', ['middleware' => 'feature_control:67', 'uses' => 'ApiTransaction@manualPaymentMethod']);
        Route::post('/method/delete', ['middleware' => 'feature_control:68', 'uses' => 'ApiTransaction@manualPaymentMethodDelete']);
    });
    Route::post('/be/new', 'ApiOnlineTransaction@newTransaction');
    Route::get('be/{key}', 'ApiTransaction@transactionList');
    Route::any('log-invalid-flag/list', 'ApiInvalidTransactionController@logInvalidFlag');
    Route::any('log-invalid-flag/detail', 'ApiInvalidTransactionController@detailInvalidFlag');
    Route::post('failed-void-payment', 'ApiManualRefundController@listFailedVoidPayment');
    Route::post('failed-void-payment/confirm', 'ApiManualRefundController@confirmManualRefund');
});
Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:apps'], 'prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {


    Route::post('sync-subtotal', 'ApiOnlineTransaction@syncDataSubtotal');
    Route::get('/', 'ApiTransaction@transactionList');
    Route::any('/filter', 'ApiTransaction@transactionFilter');
    Route::post('/detail', 'ApiTransaction@transactionDetail');
    Route::post('/detailVA', 'ApiTransaction@transactionDetailVA');
    Route::post('group/detail', 'ApiTransaction@transactionGroupDetail');
    Route::post('/item', 'ApiTransaction@transactionDetailTrx');
    Route::post('/point/detail', 'ApiTransaction@transactionPointDetail');
    Route::post('/balance/detail', 'ApiTransaction@transactionBalanceDetail');
    Route::post('order-received', 'ApiTransaction@orderReceived');
/*History V2*/
    Route::post('history-balance', 'ApiHistoryController@historyBalanceV2');
// Route::post('history', 'ApiHistoryController@historyAll');
    Route::post('history', 'ApiHistoryController@historyTrxV2');
    Route::post('history-ongoing/{mode?}', 'ApiHistoryController@historyTrxOnGoing');
// Route::post('history-point', 'ApiHistoryController@historyPoint');
    //Route::post('history-balance/{mode?}', 'ApiHistoryController@historyBalance');

    Route::post('/shipping', 'ApiTransaction@getShippingFee');
    Route::any('/address', 'ApiTransaction@getAddress');
    Route::any('/address', 'ApiTransaction@getAddress');
    Route::post('/address/add', 'ApiTransaction@addAddress');
    Route::post('/address/update', 'ApiTransaction@updateAddress');
    Route::post('/address/delete', 'ApiTransaction@deleteAddress');
    Route::post('/address/nearby', 'ApiTransaction@getNearbyAddress');
    Route::post('/address/default', 'ApiTransaction@getDefaultAddress');
    Route::post('/address/detail', 'ApiTransaction@detailAddress');
    Route::post('/void', 'ApiTransaction@transactionVoid');
    Route::post('cart', 'ApiOnlineTransaction@cartTransaction');
    Route::post('/check', 'ApiOnlineTransaction@checkTransaction');
    Route::post('/new', 'ApiOnlineTransaction@newTransaction')->middleware('decrypt_pin:pin,request');
    Route::post('/confirm', 'ApiConfirm@confirmTransaction');
    Route::post('/cancel', 'ApiOnlineTransaction@cancelTransaction');
    Route::post('/prod/confirm', 'ApiTransactionProductionController@confirmTransaction2');
    Route::post('fake-update-why', 'ApiWehelpyouController@updateFakeStatus');
    Route::get('/{key}', 'ApiTransaction@transactionList');
});
Route::group(['middleware' => ['auth_client', 'user_agent'], 'prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {

    Route::post('/province', 'ApiTransaction@getProvince');
    Route::post('/city', 'ApiTransaction@getCity');
    Route::post('/subdistrict', 'ApiTransaction@getSubdistrict');
    Route::post('/courier', 'ApiTransaction@getCourier');
    Route::any('/grand-total', 'ApiSettingTransactionV2@grandTotal');
    Route::any('/cc-payment', 'ApiSettingTransaction@ccPayment');
    Route::post('/new-transaction', 'ApiTransaction@transaction');
    Route::post('/shipping/gosend', 'ApiTransaction@shippingCostGoSend');
});
Route::group(['prefix' => 'api/transaction', 'middleware' => ['log_activities', 'user_agent'], 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {

    Route::any('/finish', 'ApiTransaction@transactionFinish');
// Route::any('/cancel', 'ApiTransaction@transactionCancel');
    Route::any('/error', 'ApiTransaction@transactionError');
    Route::any('/notif', 'ApiNotification@receiveNotification');
});
Route::group(['prefix' => 'api/transaction', 'middleware' => ['log_activities', 'auth:api', 'user_agent', 'scopes:be'], 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {

    Route::post('be/detail/webview/{mode?}', 'ApiWebviewController@webview');
    Route::post('be/detail', 'ApiTransaction@transactionDetail');
});
Route::group(['middleware' => ['auth:api', 'user_agent', 'scopes:apps'], 'prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {

    Route::post('/gen', 'ApiDumpController@generateNumber');
});
Route::group(['middleware' => ['auth_client', 'user_agent'], 'prefix' => 'api/manual-payment', 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {

    Route::get('/', 'ApiTransactionPaymentManual@listPaymentMethod');
    Route::get('/list', 'ApiTransactionPaymentManual@list');
    Route::post('/method', 'ApiTransactionPaymentManual@paymentMethod');
});
Route::group(['prefix' => 'api/cron/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {

    Route::any('/pickup/completed', 'ApiCronTrxController@completeTransactionPickup');
    Route::any('/expire', 'ApiCronTrxController@cron');
    Route::any('/schedule', 'ApiCronTrxController@checkSchedule');
    Route::any('reversal/new', 'ApiOvoReversal@insertReversal');
    Route::any('reversal/process', 'ApiOvoReversal@processReversal');
});
Route::group(['middleware' => ['auth:api', 'log_activities'],'prefix' => 'api/transaction/void', 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {

    Route::any('ovo', 'ApiOvoReversal@void');
});
Route::group(['prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {

    Route::get('/data/decript/{data}', function ($data) {

        return response()->json(json_decode(App\Lib\MyHelper::decrypt2019($data)));
    });
    Route::get('/data/encrypt/{data}', function ($data) {

        // return response()->json(App\Lib\MyHelper::decrypt2019($data));

        return response()->json(App\Lib\MyHelper::encrypt2019($data));
    });
});
Route::group(['prefix' => 'api/transaction', 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {

    Route::any('/web/view/detail', 'ApiWebviewController@detail');
    Route::any('/web/view/detail/check', 'ApiWebviewController@check');
    Route::any('/web/view/detail/point', 'ApiWebviewController@detailPoint');
    Route::any('/web/view/detail/balance', 'ApiWebviewController@detailBalance');
    Route::any('/web/view/trx', 'ApiWebviewController@success');
    Route::any('/web/view/outletapp', 'ApiWebviewController@receiptOutletapp');
});
Route::group(['prefix' => 'api/transaction', 'middleware' => ['log_activities', 'auth:api', 'user_agent', 'scopes:apps'], 'namespace' => 'Modules\Transaction\Http\Controllers'], function () {


    Route::post('/detail/webview/point', 'ApiWebviewController@webviewPoint');
    Route::post('/detail/webview/balance', 'ApiWebviewController@webviewBalance');
    Route::post('/detail/webview/{mode?}', 'ApiWebviewController@webview');
    Route::post('/detail/webview/success', 'ApiWebviewController@trxSuccess');
});
