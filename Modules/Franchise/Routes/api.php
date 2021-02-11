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

Route::group(['prefix' => 'franchise'], function () {

    Route::group(['middleware' => ['auth:franchise', 'scopes:be']], function () {
        Route::get('outlets', 'ApiUserFranchiseController@allOutlet');

        Route::group(['prefix' => 'user'], function() {
            Route::any('/', 'ApiUserFranchiseController@index');
            Route::post('store', 'ApiUserFranchiseController@store');
            Route::post('detail', 'ApiUserFranchiseController@detail');
            Route::post('update', 'ApiUserFranchiseController@update');
        });

	    Route::group(['prefix' => 'transaction'], function () {

		    Route::any('filter', 'ApiTransactionFranchiseController@transactionFilter');
		});
    });

});