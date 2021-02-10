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

Route::group(['prefix' => 'user-franchise'], function () {

    Route::group(['middleware' => ['auth:user-franchise', 'scopes:franchise']], function () {
        Route::any('/', 'ApiUserFranchiseController@index');
        Route::post('store', 'ApiUserFranchiseController@store');
        Route::post('detail', 'ApiUserFranchiseController@detailUserFranchise');
    });
});