<?php

Route::group(['middleware' => ['auth:api-be', 'user_agent'], 'prefix' => 'api/reward', 'namespace' => 'Modules\Reward\Http\Controllers'], function()
{
    Route::any('/list', ['middleware' => 'feature_control:130', 'uses' => 'ApiReward@list']);
    Route::post('/create', ['middleware' => 'feature_control:132', 'uses' => 'ApiReward@create']);
    Route::post('/update', ['middleware' => 'feature_control:133', 'uses' => 'ApiReward@update']);
    Route::post('/delete', ['middleware' => 'feature_control:134', 'uses' => 'ApiReward@delete']);
    Route::get('/active', ['middleware' => 'feature_control:130', 'uses' => 'ApiReward@listActive']);
    Route::get('/my-coupon', ['middleware' => 'feature_control:131', 'uses' => 'ApiReward@myCoupon']);
    Route::post('/buy', ['middleware' => 'feature_control:131', 'uses' => 'ApiReward@buyCoupon']);
    Route::post('/winner', ['middleware' => 'feature_control:131', 'uses' => 'ApiReward@setWinnerChoosen']);
});
