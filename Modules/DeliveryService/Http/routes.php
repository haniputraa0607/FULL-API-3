<?php

Route::group(['middleware' => 'web', 'prefix' => 'deliveryservice', 'namespace' => 'Modules\DeliveryService\Http\Controllers'], function () {
    Route::get('/', 'ApiDeliveryServiceController@index');
});
