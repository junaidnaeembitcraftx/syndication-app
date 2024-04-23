<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/distributor-syndication', 'App\Http\Controllers\DistributorSyndicationController@syndicationHandler');
Route::post('/get-syndication', 'App\Http\Controllers\DistributorSyndicationController@getDistributorSyndication');
Route::post('/add-product', 'App\Http\Controllers\DistributorSyndicationController@addPIMProduct');
Route::post('/update-product', 'App\Http\Controllers\DistributorSyndicationController@updateProductInformation');
Route::post('/update-password', 'App\Http\Controllers\DistributorSyndicationController@updateUserPassword');
Route::get('/notify-user', 'App\Http\Controllers\DistributorSyndicationController@notifyUseronProductUpdates');

// Route::get('/users', 'App\Http\Controllers\DistributorSyndicationController@index');
