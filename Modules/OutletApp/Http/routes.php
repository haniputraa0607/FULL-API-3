<?php

Route::group(['middleware' => ['auth:outlet-app', 'outlet_device_location', 'log_activities_outlet_apps'], 'prefix' => 'api/outletapp', 'namespace' => 'Modules\OutletApp\Http\Controllers'], function()
{
    Route::any('/update-token', 'ApiOutletApp@updateToken');
    Route::any('/delete-token', 'ApiOutletApp@deleteToken');
    Route::any('/order', 'ApiOutletApp@listOrder');
    Route::post('order/detail', 'ApiOutletApp@detailWebview');
    Route::post('order/accept', 'ApiOutletApp@acceptOrder');
    Route::post('order/ready', 'ApiOutletApp@setReady');
    Route::post('order/taken', 'ApiOutletApp@takenOrder');
    Route::post('order/reject', 'ApiOutletApp@rejectOrder');
    Route::any('profile', 'ApiOutletApp@profile');
    Route::get('category', 'ApiOutletApp@listCategory');
    Route::get('product', 'ApiOutletApp@listProduct');
    Route::post('product', 'ApiOutletApp@productList');
    Route::post('product/sold-out', 'ApiOutletApp@productSoldOut')->middleware('validateUserOutlet:Update Stock Status');
    Route::get('schedule', 'ApiOutletApp@listSchedule');
    Route::post('schedule/update', 'ApiOutletApp@updateSchedule')->middleware('validateUserOutlet:Update Schedule');
    Route::get('holiday', 'ApiOutletApp@listHoliday');
    Route::post('holiday/delete', 'ApiOutletApp@deleteHoliday')->middleware('validateUserOutlet:Delete Holiday');
    Route::post('holiday/update', 'ApiOutletApp@updateHoliday')->middleware('validateUserOutlet:Update Holiday');
    Route::post('holiday/create', 'ApiOutletApp@createHoliday')->middleware('validateUserOutlet:Create Holiday');
    Route::post('history', 'ApiOutletApp@history');
    Route::post('report/summary', 'ApiOutletAppReport@summary');
    Route::post('report/transaction', 'ApiOutletAppReport@transactionList');
    Route::post('report/item', 'ApiOutletAppReport@itemList');
    Route::post('report/item/all', 'ApiOutletAppReport@allItemList');
    Route::post('request_otp', 'ApiOutletApp@requestOTP');
    Route::post('stock_summary', 'ApiOutletApp@stockSummary');
    Route::post('book-delivery', 'ApiOutletApp@bookDelivery');
    Route::post('cancel-delivery', 'ApiOutletApp@cancelDelivery');
    Route::post('refresh-delivery-status', 'ApiOutletApp@refreshDeliveryStatus');
    Route::post('transaction/detail', 'ApiOutletApp@transactionDetail');
    Route::post('shift/start', 'ApiOutletApp@start_shift');
    Route::post('shift/end', 'ApiOutletApp@end_shift');
    Route::get('payment-method', 'ApiOutletApp@listPaymentMethod');
    Route::post('phone/update', 'ApiOutletApp@updatePhone');
});

Route::group(['prefix' => 'api/outletapp', 'middleware' => 'log_activities_outlet_apps', 'namespace' => 'Modules\OutletApp\Http\Controllers'], function()
{
    Route::post('order/detail/view', 'ApiOutletApp@detailWebviewPage');
    Route::any('splash','ApiOutletApp@splash');
});
