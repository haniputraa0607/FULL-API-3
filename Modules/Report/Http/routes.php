<?php

Route::group(['middleware' => 'api', 'prefix' => 'api/report', 'namespace' => 'Modules\Report\Http\Controllers'], function()
{
    Route::post('/global', 'ApiReport@global');
    Route::post('/product', 'ApiReport@product');
    Route::post('/product/detail', 'ApiReport@productDetail');
    Route::post('/customer/summary', 'ApiReport@customerSummary');
    Route::post('/customer/detail', 'ApiReport@customerDetail');


    
    /* PRODUCT */
    Route::post('trx/product', 'ApiReportDua@transactionProduct');
    Route::post('trx/product/detail', 'ApiReportDua@transactionProductDetail');
    Route::post('trx/transaction', 'ApiReportDua@transactionTrx');
    Route::post('trx/transaction/user', 'ApiReportDua@transactionUser');
    Route::post('trx/transaction/point', 'ApiReportDua@transactionPoint');
    Route::post('trx/transaction/treatment', 'ApiReportDua@reservationTreatment');
    
    /* OUTLET */
    Route::post('trx/outlet', 'ApiReportDua@transactionOutlet');
    Route::post('trx/outlet/detail', 'ApiReportDua@transactionOutletDetail');
    Route::post('outlet/detail/trx', 'ApiReportDua@outletTransactionDetail');

    /* MAGIC REPORT */
    Route::post('magic', 'ApiMagicReport@magicReport');
    Route::get('magic/exclude', 'ApiMagicReport@getExclude');
    Route::any('magic/recommendation', 'ApiMagicReport@getProductRecommendation');
    Route::any('magic/newtop/{type}', 'ApiMagicReport@newTopProduct');

    Route::get('min_year', 'ApiMagicReport@getMinYear');
    Route::post('trx/tag/detail', 'ApiMagicReport@transactionTagDetail');
});

Route::group(['prefix' => 'api/cron', 'namespace' => 'Modules\Report\Http\Controllers'], function()
{
    Route::get('daily-transaction', 'ApiCronReport@transactionCron');
});

