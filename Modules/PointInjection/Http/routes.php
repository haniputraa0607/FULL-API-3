<?php

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'api/point-injection', 'namespace' => 'Modules\PointInjection\Http\Controllers'], function () {
    Route::post('list', ['middleware' => 'feature_control:205', 'uses' => 'ApiPointInjectionController@index']);
    Route::post('create', ['middleware' => 'feature_control:207', 'uses' => 'ApiPointInjectionController@store']);
    Route::post('update', ['middleware' => 'feature_control:208', 'uses' => 'ApiPointInjectionController@update']);
    Route::post('delete', ['middleware' => 'feature_control:209', 'uses' => 'ApiPointInjectionController@destroy']);
    Route::post('review', ['middleware' => 'feature_control:206', 'uses' => 'ApiPointInjectionController@review']);
    Route::post('getUserList', ['middleware' => 'feature_control:206', 'uses' => 'ApiPointInjectionController@getUserList']);
});
Route::group(['prefix' => 'api/point-injection', 'namespace' => 'Modules\PointInjection\Http\Controllers'], function () {
    Route::get('getPointInjection', 'ApiPointInjectionController@getPointInjection');
});
