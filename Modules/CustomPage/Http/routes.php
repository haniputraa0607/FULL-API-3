<?php

Route::group(['middleware' => 'web', 'prefix' => 'custompage', 'namespace' => 'Modules\CustomPage\Http\Controllers'], function () {
    Route::get('/', 'ApiCustomPageController@index');
});
