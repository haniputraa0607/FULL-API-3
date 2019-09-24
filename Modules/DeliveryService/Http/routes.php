<?php

Route::group(['middleware' => ['log_request', 'auth:api'], 'prefix' => 'api/delivery-service', 'namespace' => 'Modules\DeliveryService\Http\Controllers'], function () {
    Route::get('/', 'ApiDeliveryServiceController@index');
    Route::get('webview', 'ApiDeliveryServiceController@detailWebview');
});
