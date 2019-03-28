<?php

Route::group(['middleware' => ['auth:outlet-app', 'log_request'], 'prefix' => 'api/outletapp', 'namespace' => 'Modules\OutletApp\Http\Controllers'], function()
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
    Route::get('product', 'ApiOutletApp@listProduct');
    Route::post('product/sold-out', 'ApiOutletApp@productSoldOut');
});

Route::group(['prefix' => 'api/outletapp', 'middleware' => 'log_request', 'namespace' => 'Modules\OutletApp\Http\Controllers'], function()
{
    Route::post('order/detail/view', 'ApiOutletApp@detailWebviewPage');
});
