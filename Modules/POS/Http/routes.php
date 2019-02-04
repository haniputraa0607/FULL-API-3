<?php

Route::group(['middleware' => 'web', 'prefix' => 'pos', 'namespace' => 'Modules\POS\Http\Controllers'], function()
{
    Route::get('/', 'ApiPOS@index');
});

Route::group(['prefix' => 'api/v1/pos/', 'namespace' => 'Modules\POS\Http\Controllers'], function()
{
    Route::group(['middleware' => ['auth_client','log_request']], function() {
        Route::any('check/member', 'ApiPOS@checkMember');
        Route::any('check/voucher', 'ApiPOS@checkVoucher');
        Route::any('voucher/void', 'ApiPOS@voidVoucher');
        Route::post('outlet/sync', 'ApiPOS@syncOutlet');
        Route::post('menu/sync', 'ApiPOS@syncMenu');
        Route::any('transaction', 'ApiPOS@transaction');
        Route::any('transaction/refund', 'ApiPOS@transactionRefund');
        Route::any('transaction/detail', 'ApiPOS@transactionDetail');
    });
    Route::group(['middleware' => 'auth_client'], function() {
        Route::any('menu', 'ApiPOS@syncMenuReturn');
        Route::post('transaction/last', 'ApiPOS@getLastTransaction');
    });
});
