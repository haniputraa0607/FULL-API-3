<?php

Route::group(['middleware' => ['auth:api', 'log_activities'], 'prefix' => 'api/deals', 'namespace' => 'Modules\Deals\Http\Controllers'], function () {
    /* MASTER DEALS */
    Route::any('list', 'ApiDeals@listDeal');
    Route::any('me', 'ApiDeals@myDeal');
    Route::any('detail', 'ApiDealsWebview@dealsDetail');
    Route::post('create', 'ApiDeals@createReq');
    Route::post('update', 'ApiDeals@updateReq');
    Route::post('delete', 'ApiDeals@deleteReq');
    Route::post('user', 'ApiDeals@listUserVoucher');
    Route::post('voucher', 'ApiDeals@listVoucher');

    /* DEAL VOUCHER */
    Route::group(['prefix' => 'voucher'], function () {
        Route::post('create', 'ApiDealsVoucher@createReq');
        Route::post('delete', 'ApiDealsVoucher@deleteReq');
        Route::post('user', 'ApiDealsVoucher@voucherUser');
    });

    /* CLAIM */
    Route::group(['prefix' => 'claim'], function () {
        Route::post('/', 'ApiDealsClaim@claim');
        Route::post('paid', 'ApiDealsClaimPay@claim');
        Route::post('pay-now', 'ApiDealsClaimPay@bayarSekarang');
    });

    /* INVALIDATE */
    Route::group(['prefix' => 'invalidate', 'middleware' => 'log_activities'], function () {
        Route::post('/', 'ApiDealsInvalidate@invalidate');
    });

    /* TRANSACTION */
    Route::group(['prefix' => 'transaction'], function () {
        Route::any('/', 'ApiDealsTransaction@listTrx');
    });

    /* MANUAL PAYMENT */
    Route::group(['prefix' => 'manualpayment'], function () {
        Route::get('/{type}', 'ApiDealsPaymentManual@manualPaymentList');
        Route::post('/detail', 'ApiDealsPaymentManual@detailManualPaymentUnpay');
        Route::post('/confirm', 'ApiDealsPaymentManual@manualPaymentConfirm');
        Route::post('/filter/{type}', 'ApiDealsPaymentManual@transactionPaymentManualFilter');
    });
});

Route::group(['prefix' => 'api/deals', 'namespace' => 'Modules\Deals\Http\Controllers'], function () {
    Route::get('range/point', 'ApiDeals@rangePoint');
});

Route::group(['middleware' => ['auth:api', 'log_activities'], 'prefix' => 'api/voucher', 'namespace' => 'Modules\Deals\Http\Controllers'], function () {
    Route::any('me', 'ApiDealsVoucher@myVoucher');
});

Route::group(['middleware' => ['auth:api', 'log_activities'], 'prefix' => 'api/hidden-deals', 'namespace' => 'Modules\Deals\Http\Controllers'], function () {
    /* MASTER DEALS */
    Route::post('create', 'ApiHiddenDeals@createReq');
    Route::post('create/autoassign', 'ApiHiddenDeals@autoAssign');
});


/* DEALS SUBSCRIPTION */
Route::group(['middleware' => ['auth:api', 'log_activities'], 'prefix' => 'api/deals-subscription', 'namespace' => 'Modules\Deals\Http\Controllers'], function () {
    Route::post('create', 'ApiDealsSubscription@create');
    Route::post('update', 'ApiDealsSubscription@update');
    Route::get('delete/{id_deals}', 'ApiDealsSubscription@destroy');
});

/* Webview */
Route::group(['middleware' => ['web'], 'prefix' => 'api/webview', 'namespace' => 'Modules\Deals\Http\Controllers'], function () {
    Route::get('deals/{id_deals}/{deals_type}', 'ApiDealsWebview@webviewDealsDetail');
    Route::get('mydeals/{id_deals_user}', 'ApiDealsWebview@dealsClaim');
    Route::any('voucher/{id_deals_user}', 'ApiDealsVoucherWebviewController@voucherDetail');
    Route::any('voucher/v2/{id_deals_user}', 'ApiDealsVoucherWebviewController@voucherDetailV2');
    Route::any('voucher/used/{id_deals_user}', 'ApiDealsVoucherWebviewController@voucherUsed');
});
