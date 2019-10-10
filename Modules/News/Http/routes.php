<?php

Route::group(['prefix' => 'api/news', 'middleware' => 'log_activities', 'namespace' => 'Modules\News\Http\Controllers'], function()
{
	Route::group(['middleware' => 'auth:api'], function() {
    	Route::any('list', 'ApiNews@listNews');
        Route::any('category', 'ApiNews@listCategory');
	
        // get news for custom form webview
        Route::post('get', 'ApiNews@getNewsById');
        // submit custom form webview (user not login)
        Route::post('custom-form', 'ApiNews@customForm');
        // upload file in custom form webview
        Route::post('custom-form/file', 'ApiNews@customFormUploadFile');
    });
    
    Route::group(['prefix'=>'category','middleware' => 'auth:api'], function() {
        Route::any('/', 'ApiNewsCategoryController@index');
        // create news category
        Route::post('create', 'ApiNewsCategoryController@store');
        // update news category
        Route::post('update', 'ApiNewsCategoryController@update');
        // delete news category
        Route::post('delete', 'ApiNewsCategoryController@destroy');
    });

	/* AUTH */
	Route::group(['middleware' => 'auth:api'], function() {
    	Route::post('create', 'ApiNews@create');
    	Route::post('create/relation', 'ApiNews@createRelation');
    	Route::post('delete/relation', 'ApiNews@deleteRelation');
    	Route::post('update', 'ApiNews@update');
        Route::post('delete', 'ApiNews@delete');
        // get news form data
		Route::post('form-data', 'ApiNews@formData');

        // submit custom form webview (user logged in)
        Route::post('custom-form/auth', 'ApiNews@customForm');
	});
    
});

Route::group(['prefix' => 'api/news', 'middleware' => ['log_activities', 'auth:api'], 'namespace' => 'Modules\News\Http\Controllers'], function()
{
        Route::any('list/test', 'ApiNews@listNews');
        // Route::any('list/web', 'ApiNews@listNews');
        // Route::any('list', 'ApiNews@listNews');
        Route::any('webview', 'ApiNews@webview');
});