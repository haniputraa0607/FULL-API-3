<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:*'], 'prefix' => 'user-feedback'], function () {
    Route::post('create', 'ApiUserFeedbackController@store');
    Route::post('get-detail', 'ApiUserFeedbackController@getDetail');
});

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:ap'], 'prefix' => 'user-feedback'], function () {
    Route::post('/', ['middleware' => 'feature_control:179', 'uses' => 'ApiUserFeedbackController@index']);
    Route::post('detail', ['middleware' => 'feature_control:211', 'uses' => 'ApiUserFeedbackController@show']);
    // Route::post('delete', 'ApiUserFeedbackController@destroy');
    Route::group(['prefix'=>'rating-item'],function(){
	    Route::get('/', ['middleware' => 'feature_control:212', 'uses' => 'ApiRatingItemController@index']);
	    Route::post('update', ['middleware' => 'feature_control:213', 'uses' => 'ApiRatingItemController@update']);
    });
});
