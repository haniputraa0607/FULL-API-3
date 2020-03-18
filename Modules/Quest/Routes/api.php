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

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'quest'], function () {
    Route::any('/', 'ApiQuest@index');
    Route::any('category', 'ApiQuest@category');
    Route::any('create', 'ApiQuest@create');
    Route::any('detail', 'ApiQuest@show');
    Route::any('detail/update', 'ApiQuest@update');
    Route::any('destroy', 'ApiQuest@destroy');
});

Route::middleware('auth:api')->get('/quest', function (Request $request) {
    return $request->user();
});
