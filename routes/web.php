<?php
namespace App\Http\Controllers;
use App\events;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        $syndicationController = new DistributorSyndicationController();
        $syndication = $syndicationController->getCurrentUserSyndication();
        $products = $syndicationController->getCurrentUuserProducts($syndication->synd_id);
        $syndication_user = $syndicationController->get_userByID($syndication->user_id);
        // echo "<pre>"; print_r($syndication_user->email); exit;
        return view('dashboard')->with('syndication', $syndication)->with('standard_products', $products['standard'])->with('st_products_count', count($products['standard']))->with('contracted_products', $products['contracted'])->with('ct_products_count', count($products['contracted']))->with('user', $syndication_user);
    })->name('dashboard');

    // CSV - Cron
    Route::get('/generate-csv', 'App\Http\Controllers\DistributorSyndicationController@generatecsv');
});

Route::get('/logout', 'App\Http\Controllers\DistributorSyndicationController@logout')->name('logout');