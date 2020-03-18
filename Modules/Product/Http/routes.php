<?php

Route::group(['prefix' => 'api/product','middleware' => ['log_activities','auth:api', 'scopes:apps'], 'namespace' => 'Modules\Product\Http\Controllers'], function()
{
    /* product */
    Route::post('search', 'ApiCategoryController@search');
    Route::any('list', 'ApiProductController@listProduct');
    Route::post('detail', 'ApiProductController@detail');
    Route::any('sync', 'ApiSyncProductController@sync');
    Route::get('next/{id}', 'ApiProductController@getNextID');

    /* category */
    Route::group(['prefix' => 'category'], function() {

    	Route::any('list', 'ApiCategoryController@listCategory');
    	Route::any('list/tree', 'ApiCategoryController@listCategoryTree');
    });
//	Route::group(['prefix' => 'discount'], function() {
//        Route::post('create', 'ApiDiskonProductController@create');
//        Route::post('update', 'ApiDiskonProductController@update');
//		Route::post('delete', 'ApiDiskonProductController@delete');
//	});
});

Route::group(['prefix' => 'api/product','middleware' => ['log_activities','auth:api', 'scopes:be'], 'namespace' => 'Modules\Product\Http\Controllers'], function()
{
    Route::any('be/list', 'ApiProductController@listProduct');
    Route::any('be/list/image', 'ApiProductController@listProductImage');
    Route::any('be/imageOverride', 'ApiProductController@imageOverride');
    Route::post('category/assign', 'ApiProductController@categoryAssign');
    Route::post('price/update', 'ApiProductController@priceUpdate');
    Route::post('create', 'ApiProductController@create');
    Route::post('update', 'ApiProductController@update');
    Route::post('update/allow_sync', 'ApiProductController@updateAllowSync');
    Route::post('update/visibility/global', 'ApiProductController@updateVisibility');
    Route::post('update/visibility', 'ApiProductController@visibility');
    Route::post('position/assign', 'ApiProductController@positionProductAssign');//product position
    Route::post('delete', 'ApiProductController@delete');
    Route::post('import', 'ApiProductController@import');
    Route::get('list/price/{id_outlet}', 'ApiProductController@listProductPriceByOutlet');
    Route::post('export', 'ApiProductController@export');
    Route::post('import', 'ApiProductController@import');
    Route::post('ajax-product-brand', 'ApiProductController@ajaxProductBrand');

    /* photo */
    Route::group(['prefix' => 'photo'], function() {
        Route::post('create', 'ApiProductController@uploadPhotoProduct');
        Route::post('update', 'ApiProductController@updatePhotoProduct');
        Route::post('createAjax', 'ApiProductController@uploadPhotoProductAjax');
        Route::post('overrideAjax', 'ApiProductController@overrideAjax');
        Route::post('delete', 'ApiProductController@deletePhotoProduct');
        Route::post('default', 'ApiProductController@photoDefault');
    });

    /* product modifier */
    Route::group(['prefix' => 'modifier'], function() {
        Route::any('/', 'ApiProductModifierController@index');
        Route::get('type', 'ApiProductModifierController@listType');
        Route::post('detail', 'ApiProductModifierController@show');
        Route::post('create', 'ApiProductModifierController@store');
        Route::post('update', 'ApiProductModifierController@update');
        Route::post('delete', 'ApiProductModifierController@destroy');
        Route::post('list-price', 'ApiProductModifierController@listPrice');
        Route::post('update-price', 'ApiProductModifierController@updatePrice');
    });

    Route::group(['prefix' => 'category'], function() {
        Route::any('be/list', 'ApiCategoryController@listCategory');
        Route::post('position/assign', 'ApiCategoryController@positionCategoryAssign');
        Route::get('all', 'ApiCategoryController@getAllCategory');
        Route::post('create', 'ApiCategoryController@create');
        Route::post('update', 'ApiCategoryController@update');
        Route::post('delete', 'ApiCategoryController@delete');
    });

    Route::group(['prefix' => 'promo-category'], function() {
        Route::any('/', 'ApiPromoCategoryController@index')->middleware(['feature_control:238']);
        Route::post('reorder', 'ApiPromoCategoryController@reorder')->middleware(['feature_control:241']);
        Route::post('create', 'ApiPromoCategoryController@create')->middleware(['feature_control:240']);
        Route::post('show', 'ApiPromoCategoryController@show')->middleware(['feature_control:239']);
        Route::post('update', 'ApiPromoCategoryController@update')->middleware(['feature_control:241']);
        Route::post('delete', 'ApiPromoCategoryController@destroy')->middleware(['feature_control:242']);
    });

    /* PRICES */
    Route::post('prices', 'ApiProductController@productPrices');

    /* tag */
    Route::group(['prefix' => 'tag'], function() {
        Route::any('list', 'ApiTagController@list');
        Route::post('create', 'ApiTagController@create');
        Route::post('update', 'ApiTagController@update');
        Route::post('delete', 'ApiTagController@delete');
    });

    /* product tag */
    Route::group(['prefix' => 'product-tag'], function() {
        Route::post('create', 'ApiTagController@createProductTag');
        Route::post('delete', 'ApiTagController@deleteProductTag');
    });
});
