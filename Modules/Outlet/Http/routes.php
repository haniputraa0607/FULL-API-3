<?php
Route::group(['prefix' => 'api/outlet', 'middleware' => ['log_activities', 'auth:api'], 'namespace' => 'Modules\Outlet\Http\Controllers'], function()
{
    Route::any('list', 'ApiOutletController@listOutlet');
    Route::any('list/ordernow', 'ApiOutletController@listOutletOrderNow');
    Route::any('list/gofood', 'ApiOutletGofoodController@listOutletGofood');
    Route::any('filter', 'ApiOutletController@filter');
    Route::any('filter/gofood', 'ApiOutletController@filter');

    /*WEBVIEW*/
    Route::any('webview/{id}', 'ApiOutletWebview@detailWebview');
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

Route::group(['prefix' => 'api/outlet', 'middleware' => ['log_activities', 'auth:api-be'], 'namespace' => 'Modules\Outlet\Http\Controllers'], function()
{
    Route::any('be/list', 'ApiOutletController@listOutlet');
    Route::any('be/filter', 'ApiOutletController@filter');
    Route::any('ajax_handler', 'ApiOutletController@ajaxHandler');

    /* photo */
    Route::group(['prefix' => 'photo'], function() {
        Route::post('create', 'ApiOutletController@upload');
        Route::post('update', 'ApiOutletController@updatePhoto');
        Route::post('delete', 'ApiOutletController@deleteUpload');
    });

    /* holiday */
    Route::group(['prefix' => 'holiday'], function() {
        Route::any('list', 'ApiOutletController@listHoliday');
        Route::post('create', 'ApiOutletController@createHoliday');
        Route::post('update', 'ApiOutletController@updateHoliday');
        Route::post('delete', 'ApiOutletController@deleteHoliday');
    });

    // admin outlet
    Route::group(['prefix' => 'admin'], function() {
        Route::post('create', 'ApiOutletController@createAdminOutlet');
        Route::post('detail', 'ApiOutletController@detailAdminOutlet');
        Route::post('update', 'ApiOutletController@updateAdminOutlet');
        Route::post('delete', 'ApiOutletController@deleteAdminOutlet');
    });

    Route::post('create', 'ApiOutletController@create');
    Route::post('update', 'ApiOutletController@update');
    Route::post('batch-update', 'ApiOutletController@batchUpdate');
    Route::post('update/status', 'ApiOutletController@updateStatus');
    Route::post('update/pin', 'ApiOutletController@updatePin');
    Route::post('delete', 'ApiOutletController@delete');
    Route::post('export', 'ApiOutletController@export');
    Route::post('import', 'ApiOutletController@import');
    Route::post('max-order', 'ApiOutletController@listMaxOrder');
    Route::post('max-order/update', 'ApiOutletController@updateMaxOrder');
    Route::any('schedule/save', 'ApiOutletController@scheduleSave');
});

