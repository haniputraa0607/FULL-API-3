<?php

Route::group(['middleware' => 'auth:api', 'prefix' => 'api/news', 'middleware' => 'log_activities', 'namespace' => 'Modules\News\Http\Controllers'], function()
{
    Route::any('list', 'ApiNews@listNews');
    Route::any('category', 'ApiNewsCategoryController@index');
});

Route::group(['prefix' => 'api/news', 'middleware' => ['log_activities', 'auth:api'], 'namespace' => 'Modules\News\Http\Controllers'], function()
{
        Route::any('list/test', 'ApiNews@listNews');
        // Route::any('list/web', 'ApiNews@listNews');
        // Route::any('list', 'ApiNews@listNews');
        Route::any('webview', 'ApiNews@webview');
});


Route::group(['prefix' => 'news', 'namespace' => 'Modules\News\Http\Controllers','middleware' => 'auth:api'], function()
{
    Route::any('/webview/{id}', 'ApiNewsWebview@detail');
});

Route::group(['middleware' => 'auth:api-be', 'prefix' => 'api/news', 'middleware' => 'log_activities', 'namespace' => 'Modules\News\Http\Controllers'], function()
{
    Route::any('be/list', 'ApiNews@listNews');
    Route::any('be/category', 'ApiNews@listCategory');

    Route::post('get', 'ApiNews@getNewsById');// get news for custom form webview
    Route::post('custom-form', 'ApiNews@customForm');// submit custom form webview (user not login)
    Route::post('custom-form/file', 'ApiNews@customFormUploadFile');// upload file in custom form webview

    Route::post('create', 'ApiNews@create');
    Route::post('create/relation', 'ApiNews@createRelation');
    Route::post('delete/relation', 'ApiNews@deleteRelation');
    Route::post('update', 'ApiNews@update');
    Route::post('delete', 'ApiNews@delete');
    Route::post('form-data', 'ApiNews@formData');// get news form data
    Route::post('custom-form/auth', 'ApiNews@customForm');// submit custom form webview (user logged in)

    Route::any('be/category', 'ApiNewsCategoryController@index');
    Route::group(['prefix'=>'category'], function() {
        Route::post('create', 'ApiNewsCategoryController@store');// create news category
        Route::post('update', 'ApiNewsCategoryController@update');// update news category
        Route::post('delete', 'ApiNewsCategoryController@destroy');// delete news category
    });
});
