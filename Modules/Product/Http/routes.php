<?php

Route::group(['prefix' => 'api/product', 'namespace' => 'Modules\Product\Http\Controllers'], function()
{
    /**
     * product
     */
    Route::group(['middleware' => 'auth_client'], function() {
        Route::any('list', 'ApiProductController@listProduct');

        /* Sync */
        Route::any('sync', 'ApiSyncProductController@sync');
    });
    
    /**
     * auth
     */
    Route::group(['middleware' => 'auth:api'], function() {
        Route::post('category/assign', 'ApiProductController@categoryAssign');
        Route::post('price/update', 'ApiProductController@priceUpdate');
        Route::post('create', 'ApiProductController@create');
        Route::post('update', 'ApiProductController@update');
        Route::post('update/allow_sync', 'ApiProductController@updateAllowSync');
        Route::post('update/visibility', 'ApiProductController@visibility');

        /* product position */
        Route::post('position/assign', 'ApiProductController@positionProductAssign');

        Route::group(['middleware' => 'log_request'], function() {
            Route::post('delete', 'ApiProductController@delete');
        });

        Route::post('import', 'ApiProductController@import');

        /**
         * photo
         */
        Route::group(['prefix' => 'photo'], function() {
            Route::post('create', 'ApiProductController@uploadPhotoProduct');
            Route::post('update', 'ApiProductController@updatePhotoProduct');
            Route::post('delete', 'ApiProductController@deletePhotoProduct');
            Route::post('default', 'ApiProductController@photoDefault');
        });

        /* PRICES */
        Route::post('prices', 'ApiProductController@productPrices');

         /**
         * tag
         */
        Route::group(['prefix' => 'tag'], function() {
            Route::any('list', 'ApiTagController@list');
            Route::post('create', 'ApiTagController@create');
            Route::post('update', 'ApiTagController@update');
            Route::post('delete', 'ApiTagController@delete');
        });

         /**
         * product tag
         */
        Route::group(['prefix' => 'product-tag'], function() {
            Route::post('create', 'ApiTagController@createProductTag');
            Route::post('delete', 'ApiTagController@deleteProductTag');
        });

    });
    
    /**
     * category
     */
    Route::group(['prefix' => 'category'], function() {
    	
    	Route::any('list', 'ApiCategoryController@listCategory');
    	Route::any('list/tree', 'ApiCategoryController@listCategoryTree');
        Route::post('position/assign', 'ApiCategoryController@positionCategoryAssign');
        
    	/**
    	 * auth
    	 */
    	Route::group(['middleware' => 'auth:api'], function() {
    		Route::post('create', 'ApiCategoryController@create');
    		Route::post('update', 'ApiCategoryController@update');
    		Route::post('delete', 'ApiCategoryController@delete');
    	});
    });

    /**
     * diskon
     */

	Route::group(['prefix' => 'discount'], function() {
        Route::post('create', 'ApiDiskonProductController@create');
        Route::post('update', 'ApiDiskonProductController@update');
		Route::post('delete', 'ApiDiskonProductController@delete');
	});

});
