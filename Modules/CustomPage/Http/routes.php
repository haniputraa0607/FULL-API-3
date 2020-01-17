<?php

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:ap'], 'prefix' => 'api/custom-page', 'namespace' => 'Modules\CustomPage\Http\Controllers'], function () {
    Route::get('be/list', ['middleware' => 'feature_control:149', 'uses' =>'ApiCustomPageController@index']);
    Route::post('create', ['middleware' => 'feature_control:150', 'uses' =>'ApiCustomPageController@store']);
    Route::post('detail', ['middleware' => 'feature_control:153', 'uses' =>'ApiCustomPageController@show']);
    Route::post('update', ['middleware' => 'feature_control:151', 'uses' =>'ApiCustomPageController@store']);
    Route::post('delete', ['middleware' => 'feature_control:152', 'uses' =>'ApiCustomPageController@destroy']);
    Route::get('list_custom_page', ['middleware' => 'feature_control:149', 'uses' =>'ApiCustomPageController@listCustomPage']);
});

Route::group(['middleware' => ['auth:api', 'log_activities', 'scopes:*'], 'prefix' => 'api/custom-page', 'namespace' => 'Modules\CustomPage\Http\Controllers'], function () {
    Route::get('list', 'ApiCustomPageController@index');
    Route::get('webview/{id}', 'ApiCustomPageController@webviewCustomPage');
    Route::get('webview/{id}', ['uses' => 'ApiCustomPageController@webviewCustomPage']);
});