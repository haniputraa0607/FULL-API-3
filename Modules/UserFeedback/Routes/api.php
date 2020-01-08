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

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent'], 'prefix' => 'user-feedback'], function () {
    Route::any('refuse', 'ApiUserFeedbackController@refuse');
    Route::post('create', 'ApiUserFeedbackController@store');
    Route::post('get-detail', 'ApiUserFeedbackController@getDetail');
});

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent'], 'prefix' => 'user-feedback'], function () {
    Route::post('/', ['middleware' => 'feature_control:179', 'uses' => 'ApiUserFeedbackController@index']);
    Route::post('detail', 'ApiUserFeedbackController@show');
    Route::post('delete', 'ApiUserFeedbackController@destroy');
    Route::group(['prefix'=>'rating-item'],function(){
	    Route::get('/', ['middleware' => 'feature_control:179', 'uses' => 'ApiRatingItemController@index']);
	    Route::post('create', 'ApiRatingItemController@store');
	    Route::post('update', 'ApiRatingItemController@update');
	    Route::post('delete', 'ApiRatingItemController@destroy');
    });
});
