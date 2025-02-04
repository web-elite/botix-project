<?php

use App\Http\Controllers\FrontController;
use Illuminate\Support\Facades\Route;
Route::post('/webhook', [FrontController::class, '__invoke']);

Route::get('/', function () {
    return view('welcome');
});
