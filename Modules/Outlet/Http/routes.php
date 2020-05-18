<?php
Route::group(['prefix' => 'api/outlet', 'middleware' => ['log_activities', 'auth:api', 'user_agent', 'scopes:apps'], 'namespace' => 'Modules\Outlet\Http\Controllers'], function()
{
    Route::any('list', 'ApiOutletController@listOutlet');
    Route::any('list/simple', 'ApiOutletController@listOutletSimple');
    Route::any('list/ordernow', 'ApiOutletController@listOutletOrderNow');
    Route::any('list/gofood', 'ApiOutletGofoodController@listOutletGofood');
    Route::any('filter', 'ApiOutletController@filter');
    Route::any('filter/gofood', 'ApiOutletController@filter');

    /*WEBVIEW*/
    Route::any('webview/{id}', 'ApiOutletWebview@detailWebview');
    Route::any('detail/mobile', 'ApiOutletWebview@detailOutlet');
    Route::any('webview/gofood/list', 'ApiOutletWebview@listOutletGofood');
    Route::any('webview/gofood/list/v2', 'ApiOutletWebview@listOutletGofood');

    Route::get('city', 'ApiOutletController@cityOutlet');
    // Route::any('filter', 'ApiOutletController@filter');
    Route::any('nearme/geolocation', 'ApiOutletController@nearMeGeolocation');
    Route::any('filter/geolocation', 'ApiOutletController@filterGeolocation');
    Route::any('sync', 'ApiSyncOutletController@sync');//SYNC

});

Route::group(['prefix' => 'api/outlet', 'middleware' => ['log_activities', 'auth_client'],'namespace' => 'Modules\Outlet\Http\Controllers'], function()
{
    Route::any('list/mobile', 'ApiOutletController@listOutlet');
    Route::any('/detail', 'ApiOutletController@detailTransaction');
    Route::any('filter/android', 'ApiOutletController@filter');
    Route::any('nearme', 'ApiOutletController@nearMe');
});

Route::group(['prefix' => 'api/outlet', 'middleware' => ['log_activities', 'auth:api','user_agent', 'scopes:be'], 'namespace' => 'Modules\Outlet\Http\Controllers'], function()
{
    Route::any('be/list', ['middleware' => 'feature_control:24', 'uses' =>'ApiOutletController@listOutlet']);
    Route::any('be/filter', ['middleware' => 'feature_control:24', 'uses' =>'ApiOutletController@filter']);
    Route::any('list/code', ['middleware' => 'feature_control:24', 'uses' =>'ApiOutletController@getAllCodeOutlet']);
    Route::any('ajax_handler','ApiOutletController@ajaxHandler');
    Route::post('different_price','ApiOutletController@differentPrice');
    Route::post('different_price/update','ApiOutletController@updateDifferentPrice');

    /* photo */
    Route::group(['prefix' => 'photo'], function() {
        Route::post('create', ['middleware' => 'feature_control:29', 'uses' =>'ApiOutletController@upload']);
        Route::post('update', ['middleware' => 'feature_control:30', 'uses' =>'ApiOutletController@updatePhoto']);
        Route::post('delete', ['middleware' => 'feature_control:30', 'uses' =>'ApiOutletController@deleteUpload']);
    });

    /* holiday */
    Route::group(['prefix' => 'holiday'], function() {
        Route::any('list', ['middleware' => 'feature_control:34', 'uses' =>'ApiOutletController@listHoliday']);
        Route::post('create', ['middleware' => 'feature_control:36', 'uses' =>'ApiOutletController@createHoliday']);
        Route::post('update', ['middleware' => 'feature_control:37', 'uses' =>'ApiOutletController@updateHoliday']);
        Route::post('delete', ['middleware' => 'feature_control:38', 'uses' =>'ApiOutletController@deleteHoliday']);
    });

    // admin outlet
    Route::group(['prefix' => 'admin'], function() {
        Route::post('create', ['middleware' => 'feature_control:40', 'uses' =>'ApiOutletController@createAdminOutlet']);
        Route::post('detail', ['middleware' => 'feature_control:39', 'uses' =>'ApiOutletController@detailAdminOutlet']);
        Route::post('update', ['middleware' => 'feature_control:41', 'uses' =>'ApiOutletController@updateAdminOutlet']);
        Route::post('delete', ['middleware' => 'feature_control:42', 'uses' =>'ApiOutletController@deleteAdminOutlet']);
    });

    Route::post('import-brand', 'ApiOutletController@importBrand');
    Route::post('create', ['middleware' => 'feature_control:26', 'uses' =>'ApiOutletController@create']);
    Route::post('update', ['middleware' => 'feature_control:27', 'uses' =>'ApiOutletController@update']);
    Route::post('batch-update', 'ApiOutletController@batchUpdate');
    Route::post('update/status', 'ApiOutletController@updateStatus');
    Route::post('update/pin', 'ApiOutletController@updatePin');
    Route::post('delete', 'ApiOutletController@delete');
    Route::post('export', 'ApiOutletController@export');
    Route::post('export-city', 'ApiOutletController@exportCity');
    Route::post('import', 'ApiOutletController@import');
    Route::post('max-order', 'ApiOutletController@listMaxOrder');
    Route::post('max-order/update', 'ApiOutletController@updateMaxOrder');
    Route::any('schedule/save', 'ApiOutletController@scheduleSave');

    /*user franchise*/
    Route::any('list/user-franchise', 'ApiOutletController@listUserFranchise');
    Route::any('detail/user-franchise', 'ApiOutletController@detailUserFranchise');
});
