<?php

use Illuminate\Support\Facades\Request;

$searchController = '\Pvtl\VoyagerFrontend\Http\Controllers\SearchController';

Route::group([
    'as' => 'voyager-frontend.pages.',
    'prefix' => 'admin/pages/',
    'middleware' => ['web', 'admin.user'],
    'namespace' => '\Pvtl\VoyagerFrontend\Http\Controllers'
], function () {
    Route::post('layout/{id?}', ['uses' => 'PageController@changeLayout', 'as' => 'layout']);
});

/**
 * Let's get some search going
 */
Route::get('/search', "$searchController@index")
    ->middleware(['web'])
    ->name('voyager-frontend.search');
