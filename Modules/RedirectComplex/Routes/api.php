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
Route::group(['middleware' => ['auth:api', 'log_activities', 'scopes:apps'], 'prefix' => 'redirect-complex'], function () {
    Route::post('detail', 'ApiRedirectComplex@detail');
});

Route::group(['middleware' => ['auth:api', 'log_activities', 'scopes:be'], 'prefix' => 'redirect-complex'], function () {
	Route::group(['prefix' => 'be'], function () {
	    Route::get('list', 'ApiRedirectComplex@index');
	    Route::post('edit', 'ApiRedirectComplex@edit');
	    Route::post('list/active', 'ApiRedirectComplex@listActive');
	});
    Route::post('create', 'ApiRedirectComplex@create');
    Route::post('update', 'ApiRedirectComplex@update');
    Route::post('delete', 'ApiRedirectComplex@delete');
    Route::post('getData', 'ApiRedirectComplex@getData');
});