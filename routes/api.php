<?php

use App\Http\Controllers\Api\Service1688Controller;
use Illuminate\Support\Facades\Route;

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
Route::group(['prefix' => '1688'], function(){
    Route::post('/',[Service1688Controller::class, 'store'])->name('store.1688');
    Route::post('/suggest',[Service1688Controller::class, 'storeSuggest'])->name('store.storeSuggest');
    Route::get('/signature',[Service1688Controller::class, 'signature'])->name('signature');
    Route::any('/token',[Service1688Controller::class, 'getToken'])->name('getToken');
    Route::any('/refresh-token',[Service1688Controller::class, 'refreshToken'])->name('refreshToken');
    Route::any('/callback-message',[Service1688Controller::class, 'callbackMessage'])->name('callbackMessage');
    Route::any('/channel',[Service1688Controller::class, 'channel'])->name('channel');
    Route::post('/preview-before-order', [Service1688Controller::class, 'previewBeforeOrder'])->name('previewBeforeOrder');
    Route::post('/create-payment', [Service1688Controller::class, 'payment'])->name('payment');
    Route::post('/category', [Service1688Controller::class, 'category'])->name('category');
    Route::post('/search', [Service1688Controller::class, 'search'])->name('search');
    Route::post('/create-order/{order:order_number}', [Service1688Controller::class, 'createOrder'])->name('createOrder');
    Route::post('/view-order/{order:order_number}', [Service1688Controller::class, 'viewOrder'])->name('viewOrder');
    Route::post('/cancel-order/{order:order_number}', [Service1688Controller::class, 'cancelOrder'])->name('cancelOrder');
});