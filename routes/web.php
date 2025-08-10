<?php

use App\Http\Controllers\FrontController;
use App\Models\User;
use App\Services\Notifications\NotificationAdminHelperService;
use App\Services\Notifications\NotificationUserHelperService;
use App\Services\Payment\PaymentService;
use App\Services\UserService;
use App\Services\UserSyncService;
use App\Services\xui\XUIApiService;
use App\Services\xui\XUIDataService;
use App\Services\xui\XUINotifService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use SergiX44\Nutgram\Nutgram;

Route::post('/webhook', [FrontController::class, '__invoke']);

Route::get('/', function () {
    // echo "hi";
    // return;
    $bot         = new Nutgram(env('TELEGRAM_TOKEN'));
    $adminNotif  = app(NotificationAdminHelperService::class);
    $tg_id       = '816899083';
    $user        = User::find(25);
    $userService = app(UserService::class);
    $userSyncService = app(UserSyncService::class);
    $xuiData     = app(XUIDataService::class);
    $xui         = app(XUIApiService::class);
    $xuiNotif         = app(XUINotifService::class);
    $p           = app(PaymentService::class);
    $inbounds = $xui->getInbounds();

    //////////////////////////////////////
//    Artisan::call('calc:time');
  //  echo Artisan::output() . "<br>";

    // dd($xuiNotif->calculateUserBandwidth());
    // dd($adminNotif->sendTelegramNotification('test message'));
    // dd($xui->getOnlineClients());
    // dd($userService->getUserSubscriptions($tg_id));
    // dd($xuiData->getUsersData());
    // $userSyncService->syncXuiUsers();
    // app(NotificationAdminHelperService::class)->sendTelegramNotification("اشتراک تستی جدید توسط کاربر @{$user->telegram_username} ثبت شد.");
    // dd($user->telegram_data);

    // $d = $userService->hasActiveXUISub($tg_id);

    // dd($d, $inbounds);
});

Route::get('/notif', function () {
    $userNotif = app(NotificationUserHelperService::class);
    $userNotif;
});

Route::get('/admin', function () {});
