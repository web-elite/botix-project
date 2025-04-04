<?php

use App\Http\Controllers\FrontController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
Route::post('/webhook', [FrontController::class, '__invoke']);
Route::post('/payment/callback', [PaymentController::class, 'paymentCallback']);

Route::get('/', function () {
    return view('welcome');
});
