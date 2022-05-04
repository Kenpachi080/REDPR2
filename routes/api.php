<?php

use App\Models\User as User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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

use Illuminate\Http\Request;
use App\Http\Controllers\AuthController as AuthController;
use App\Http\Controllers\IndexController as IndexController;
use App\Http\Controllers\ItemController as ItemController;
use App\Http\Controllers\FavoriteController as FavoriteController;
use App\Http\Controllers\OrderController as OrderController;


Route::post('/test', function (Request $request) {
    return response($request, 200);
});

Route::post('/callback', function (Request $request) {
    return response($request, 200);
});

Route::group(['prefix' => 'auth'], function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/forgot', [AuthController::class, 'forgot'])->name('forgot');
});

Route::group(['prefix' => 'auth', 'middleware' => "api_auth"], function () {
    Route::post('/rebootpassword', [AuthController::class, 'rebootpassword'])->name('rebootpassword');
    Route::post('/change', [AuthController::class, 'change'])->name('change');
});



Route::get('/index', [IndexController::class, 'index'])->name('title');
Route::get('/partner', [IndexController::class, 'partner'])->name('partner');
Route::get('/payment', [IndexController::class, 'payment'])->name('payment');
Route::get('/news', [IndexController::class, 'news'])->name('news');
Route::get('/news/{id}', [IndexController::class, 'viewnews'])->name('viewnews');
Route::get('/company', [IndexController::class, 'company'])->name('company');
Route::get('/advantage', [IndexController::class, 'advantage'])->name('advantage');
Route::get('/about', [IndexController::class, 'about'])->name('about');
Route::get('/popular', [ItemController::class, 'popular']);
Route::get('/banners', [IndexController::class, 'banners']);
Route::get('/sliders', [IndexController::class, 'sliders']);


Route::group(['prefix' => 'items'], function () {
    Route::get('/', [ItemController::class, 'items'])->name('items');
    Route::get('/category', [ItemController::class, 'category'])->name('category');
    Route::get('/category/item', [ItemController::class, 'categoryitem'])->name('categoryitem');
    Route::get('/subcategory/item', [ItemController::class, 'subcategoryitem'])->name('subcategoryitem');
    Route::get('/searchproducts', [ItemController::class, 'searchproducts'])->name('item/search');
    Route::get('/description', [ItemController::class, 'description'])->name('item/description');
    Route::get('/{item_id}', [ItemController::class, 'viewitem'])->name('viewitem');
});

Route::group(['prefix' => 'favorite', 'middleware' => "api_auth"], function () {
    Route::post('/add', [FavoriteController::class, 'addItem'])->name('basket/addItem');
    Route::post('/view', [FavoriteController::class, 'view'])->name('basket/view');
    Route::post('/delete', [FavoriteController::class, 'delete'])->name('basket/delete');
});

Route::group(['prefix' => 'order'], function () {
    Route::post('/create', [OrderController::class, 'create'])->name('order/create');
});

