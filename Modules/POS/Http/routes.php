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
        Route::any('menu', 'ApiPOS@syncMenuReturn');
        Route::any('outlet/menu', 'ApiPOS@syncOutletMenuReturn');
        
        Route::post('menu/sync', 'ApiPOS@syncMenu');
        Route::any('outlet/menu/sync', 'ApiPOS@syncOutletMenu');
        Route::any('transaction', 'ApiPOS@transaction');
        Route::any('transaction/refund', 'ApiPOS@transactionRefund');
        Route::any('transaction/detail', 'ApiPOS@transactionDetail');

        Route::any('/order', 'ApiOrder@listOrder');
        Route::post('order/detail', 'ApiOrder@detailOrder');
        Route::post('order/accept', 'ApiOrder@acceptOrder');
        Route::post('order/ready', 'ApiOrder@setReady');
        Route::post('order/taken', 'ApiOrder@takenOrder');
        Route::post('order/reject', 'ApiOrder@rejectOrder');
        Route::get('profile', 'ApiOrder@profile');
        Route::get('product', 'ApiOrder@listProduct');
        Route::post('product/sold-out', 'ApiOrder@productSoldOut');
    });
    Route::group(['middleware' => 'auth_client'], function() {
        Route::post('transaction/last', 'ApiPOS@getLastTransaction');
        
        Route::post('order/detail/view', 'ApiOrder@detailWebviewPage');
    });
});

Route::group(['prefix' => 'api/quinos', 'namespace' => 'Modules\POS\Http\Controllers'], function()
{
    Route::group(['middleware' => ['auth:quinos']], function() {
        Route::any('log', 'ApiQuinos@log');
        Route::get('log/detail/{id}', 'ApiQuinos@detailLog');
    });

    Route::group(['middleware' => ['auth_client']], function() {
        Route::post('user/new', 'ApiQuinos@createQuinosUser');
        Route::post('user/update', 'ApiQuinos@updateQuinosUser');
    });
});

Route::group(['prefix' => 'api/pos/cron', 'namespace' => 'Modules\POS\Http\Controllers'], function()
{
    Route::any('queue', 'ApiTransactionSync@transaction');
});