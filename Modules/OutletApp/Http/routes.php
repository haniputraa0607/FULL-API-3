<?php

Route::group(['middleware' => ['auth:outlet-app', 'log_activities_outlet_apps'], 'prefix' => 'api/outletapp', 'namespace' => 'Modules\OutletApp\Http\Controllers'], function()
{
    Route::any('/update-token', 'ApiOutletApp@updateToken');
    Route::any('/delete-token', 'ApiOutletApp@deleteToken');
    Route::any('/order', 'ApiOutletApp@listOrder');
    Route::post('order/detail', 'ApiOutletApp@detailWebview');
    Route::post('order/accept', 'ApiOutletApp@acceptOrder');
    Route::post('order/ready', 'ApiOutletApp@setReady');
    Route::post('order/taken', 'ApiOutletApp@takenOrder');
    Route::post('order/reject', 'ApiOutletApp@rejectOrder');
    Route::get('profile', 'ApiOutletApp@profile');
    Route::get('category', 'ApiOutletApp@listCategory');
    Route::get('product', 'ApiOutletApp@listProduct');
    Route::post('product', 'ApiOutletApp@productList');
    Route::post('product/sold-out', 'ApiOutletApp@productSoldOut');
    Route::get('schedule', 'ApiOutletApp@listSchedule');
    Route::post('schedule/update', 'ApiOutletApp@updateSchedule');
    Route::post('history', 'ApiOutletApp@history');
    Route::post('report/summary', 'ApiOutletAppReport@summary');
    Route::post('report/transaction', 'ApiOutletAppReport@transactionList');
    Route::post('report/item', 'ApiOutletAppReport@itemList');
});

Route::group(['prefix' => 'api/outletapp', 'middleware' => 'log_activities_outlet_apps', 'namespace' => 'Modules\OutletApp\Http\Controllers'], function()
{
    Route::post('order/detail/view', 'ApiOutletApp@detailWebviewPage');
});
