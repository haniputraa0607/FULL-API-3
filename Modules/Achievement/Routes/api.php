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

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'achievement'], function () {
    Route::any('category', 'ApiAchievement@category');
    Route::any('create', 'ApiAchievement@create');
});

Route::middleware('auth:api')->get('/achievement', function (Request $request) {
    return $request->user();
});
