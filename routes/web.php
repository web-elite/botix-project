<?php

use App\Http\Controllers\FrontController;
use App\Models\User;
use App\Services\xui\XUIApiService;
use App\Services\xui\XUIDataService;
use Illuminate\Support\Facades\Route;
Route::post('/webhook', [FrontController::class, '__invoke']);

Route::get('/', function () {
    $xui = new XUIApiService;
    $xuiData = new XUIDataService($xui);
    $user =  User::find(1);
    dd($user);
});
