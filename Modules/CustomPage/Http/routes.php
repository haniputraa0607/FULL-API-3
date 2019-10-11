<?php

Route::group(['middleware' => ['auth:api', 'log_activities'], 'prefix' => 'api/custom-page', 'namespace' => 'Modules\CustomPage\Http\Controllers'], function () {
    Route::get('list', 'ApiCustomPageController@index');
    Route::post('create', 'ApiCustomPageController@store');
    Route::post('detail', 'ApiCustomPageController@show');
    Route::post('update', 'ApiCustomPageController@store');
    Route::post('delete', 'ApiCustomPageController@destroy');
    Route::get('list_custom_page', 'ApiCustomPageController@listCustomPage');
    Route::get('webview/{id}', 'ApiCustomPageController@webviewCustomPage');
});
