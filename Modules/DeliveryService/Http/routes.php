<?php

Route::group(['middleware' => ['log_activities', 'auth:api','user_agent'], 'prefix' => 'api/delivery-service', 'namespace' => 'Modules\DeliveryService\Http\Controllers'], function () {
    Route::get('/', 'ApiDeliveryServiceController@index');
    Route::post('store', 'ApiDeliveryServiceController@store');
});

Route::group(['middleware' => 'auth:api', 'prefix' => 'api/delivery-service', 'namespace' => 'Modules\DeliveryService\Http\Controllers'], function () {
    Route::any('webview', 'ApiDeliveryServiceWebview@detailWebview');
});