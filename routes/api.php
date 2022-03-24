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
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/test', function (Request $request) {
    return response($request);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::group(['prefix' => 'auth'], function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/rebootpassword', [AuthController::class, 'rebootpassword'])->name('rebootpassword');
    Route::post('/change', [AuthController::class, 'change'])->name('change');
    Route::post('/forgot', [AuthController::class, 'forgot'])->name('forgot');
});


Route::post('/index', [IndexController::class, 'index'])->name('title');
Route::post('/partner', [IndexController::class, 'partner'])->name('partner');
Route::post('/payment', [IndexController::class, 'payment'])->name('payment');
Route::post('/news', [IndexController::class, 'news'])->name('news');
Route::post('/company', [IndexController::class, 'company'])->name('company');
Route::post('/advantage', [IndexController::class, 'advantage'])->name('advantage');
Route::post('/about', [IndexController::class, 'about'])->name('about');

Route::group(['prefix' => 'items'], function () {
    Route::post('/', [ItemController::class, 'items'])->name('items');
    Route::post('/{item_id}', [ItemController::class, 'viewitem'])->name('viewitem');
    Route::post('/search', [ItemController::class, 'search'])->name('item/search');
});

Route::group(['prefix' => 'basket'], function () {
    Route::post('/add', [BasketController::class, 'addItem'])->name('basket/addItem');
    Route::post('/view', [BasketController::class, 'view'])->name('basket/view');
    Route::post('/delete', [BasketController::class, 'delete'])->name('basket/delete');
});
Route::group(['prefix' => 'order'], function (){
    Route::post('/create', [OrderController::class, 'create'])->name('order/create');
});
