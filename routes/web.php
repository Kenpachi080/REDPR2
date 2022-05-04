<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController as AuthController;
use App\Http\Controllers\IndexController as IndexController;
use App\Http\Controllers\BasketController as BasketController;
use App\Http\Controllers\ItemController as ItemController;
use App\Http\Controllers\FavoriteController as FavoriteController;
use App\Http\Controllers\OrderController as OrderController;

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

Route::get('/', function () {
    return view('welcome');
});


Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});

Route::post('/test', function () {
    return response('privet', 200);
});
