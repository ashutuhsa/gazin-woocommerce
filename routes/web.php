<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//
//Route::get('/', function () {
//    return view('welcome');
//});


Route::get('integration', function () {
    Route::get('products', 'ProductController@index');
});


Route::prefix('/')->group(function () {
    Route::prefix('/integration')->group(function () {
        Route::get('save-categories', 'ProductController@save_categories')->name('integration_products');
        Route::get('save-products', 'ProductController@save_products')->name('save_products');
    });
});
