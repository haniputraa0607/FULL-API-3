<?php

Route::group(['middleware' => ['api','log_activities', 'auth:api', 'user_agent', 'scopes:be'], 'prefix' => 'api/report', 'namespace' => 'Modules\Report\Http\Controllers'], function()
{
    Route::post('/global', ['middleware' => 'feature_control:125', 'uses' => 'ApiReport@global']);
    Route::post('/product', ['middleware' => 'feature_control:127', 'uses' => 'ApiReport@product']);
    Route::post('/product/detail', ['middleware' => 'feature_control:127', 'uses' => 'ApiReport@productDetail']);
    Route::post('/customer/summary', ['middleware' => 'feature_control:126', 'uses' => 'ApiReport@customerSummary']);
    Route::post('/customer/detail', ['middleware' => 'feature_control:126', 'uses' => 'ApiReport@customerDetail']);

    /* PRODUCT */
    Route::post('trx/product', ['middleware' => 'feature_control:127', 'uses' => 'ApiReportDua@transactionProduct']);
    Route::post('trx/product/detail', ['middleware' => 'feature_control:127', 'uses' => 'ApiReportDua@transactionProductDetail']);
    Route::post('trx/transaction', ['middleware' => 'feature_control:127', 'uses' => 'ApiReportDua@transactionTrx']);
    Route::post('trx/transaction/user', ['middleware' => 'feature_control:127', 'uses' => 'ApiReportDua@transactionUser']);
    Route::post('trx/transaction/point', ['middleware' => 'feature_control:127', 'uses' => 'ApiReportDua@transactionPoint']);
    Route::post('trx/transaction/treatment', ['middleware' => 'feature_control:127', 'uses' => 'ApiReportDua@reservationTreatment']);

    /* OUTLET */
    Route::post('trx/outlet', ['middleware' => 'feature_control:128', 'uses' => 'ApiReportDua@transactionOutlet']);
    Route::post('trx/outlet/detail', ['middleware' => 'feature_control:128', 'uses' => 'ApiReportDua@transactionOutletDetail']);
    Route::post('outlet/detail/trx', ['middleware' => 'feature_control:128', 'uses' => 'ApiReportDua@outletTransactionDetail']);

    /* MAGIC REPORT */
    Route::post('magic', ['middleware' => 'feature_control:129', 'uses' => 'ApiMagicReport@magicReport']);
    Route::get('magic/exclude', ['middleware' => 'feature_control:129', 'uses' => 'ApiMagicReport@getExclude']);
    Route::any('magic/recommendation', ['middleware' => 'feature_control:129', 'uses' => 'ApiMagicReport@getProductRecommendation']);
    Route::any('magic/newtop/{type}', ['middleware' => 'feature_control:129', 'uses' => 'ApiMagicReport@newTopProduct']);

    Route::get('min_year', ['middleware' => 'feature_control:129', 'uses' => 'ApiMagicReport@getMinYear']);
    Route::post('trx/tag/detail', ['middleware' => 'feature_control:129', 'uses' => 'ApiMagicReport@transactionTagDetail']);

    /* SINGLE REPORT */
    Route::post('/single', 'ApiSingleReport@getReport');
    Route::get('/single/year-list', 'ApiSingleReport@getReportYear');
    Route::get('/single/outlet-list', 'ApiSingleReport@getOutletList');
    Route::get('/single/product-list', 'ApiSingleReport@getProductList');
    Route::get('/single/membership-list', 'ApiSingleReport@getMembershipList');
    Route::get('/single/deals-list', 'ApiSingleReport@getDealsList');

    Route::post('/single/trx', 'ApiSingleReport@getTrxReport');
    Route::post('/single/product', 'ApiSingleReport@getProductReport');
    Route::post('/single/membership', 'ApiSingleReport@getMembershipReport');
    Route::post('/single/voucher', 'ApiSingleReport@getVoucherReport');

    /* COMPARE REPORT */
    Route::post('/compare', 'ApiCompareReport@getReport');
    Route::post('/compare/trx', 'ApiCompareReport@getTrxReport');
    Route::post('/compare/product', 'ApiCompareReport@getProductReport');
    Route::post('/compare/membership', 'ApiCompareReport@getMembershipReport');
    Route::post('/compare/voucher', 'ApiCompareReport@getVoucherReport');
    // Route::post('/compare/reg', 'ApiCompareReport@getRegReport');

    /* Report Gosend */
    Route::any('gosend', 'ApiReportGosend@getReport');

    /* Update Report Trx Total Item */
    Route::post('total-item', 'ApiCronUpdateReport@cronUpdate');

    /* Report Payment */
    Route::any('payment/midtrans', 'ApiReportPayment@getReportMidtrans');
    Route::any('payment/ipay88', 'ApiReportPayment@getReportIpay88');
});