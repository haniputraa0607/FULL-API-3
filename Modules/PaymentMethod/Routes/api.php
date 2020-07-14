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

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'payment-method'], function () {
    Route::get('/', 'PaymentMethodController@index');
    Route::get('/item/{id}', 'PaymentMethodController@getItemWithID');
    Route::get('edit/{id}', 'PaymentMethodController@edit');
    Route::post('store', 'PaymentMethodController@store');
    Route::post('update/{id}', 'PaymentMethodController@update');
    Route::get('delete/{id}', 'PaymentMethodController@destroy');

    //outlet different payment method
    Route::group(['prefix' => 'outlet'], function(){
        Route::post('different-payment-method/list/{id}', 'PaymentMethodController@getDifferentPaymentMethod');
        Route::post('different-payment-method/update', 'PaymentMethodController@updateDifferentPaymentMethod');
    });
    
});

Route::group(['middleware' => ['auth:api', 'log_activities', 'user_agent', 'scopes:be'], 'prefix' => 'payment-method-category'], function () {
    Route::get('/', 'PaymentMethodCategoryController@index');
    Route::get('edit/{id}', 'PaymentMethodCategoryController@edit');
    Route::post('store', 'PaymentMethodCategoryController@store');
    Route::post('update/{id}', 'PaymentMethodCategoryController@update');
    Route::get('delete/{id}', 'PaymentMethodCategoryController@destroy');
});