<?php

Route::group(['middleware' => 'auth:outlet-app', 'prefix' => 'api/outletapp', 'namespace' => 'Modules\OutletApp\Http\Controllers'], function()
{
    Route::any('/order', 'ApiOutletApp@listOrder');
    Route::post('order/detail', 'ApiOutletApp@detailOrder');
    Route::post('order/accept', 'ApiOutletApp@acceptOrder');
    Route::post('order/ready', 'ApiOutletApp@setReady');
    Route::post('order/taken', 'ApiOutletApp@takenOrder');
    Route::post('order/reject', 'ApiOutletApp@rejectOrder');
    Route::get('profile', 'ApiOutletApp@profile');
    Route::get('product', 'ApiOutletApp@listProduct');
    Route::post('product/sold-out', 'ApiOutletApp@productSoldOut');
});
