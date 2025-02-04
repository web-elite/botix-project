<?php

use App\Http\Controllers\Bot\StarterController;
use App\Http\Middleware\GlobalBotMiddleware;
use SergiX44\Nutgram\Nutgram;

/*
|--------------------------------------------------------------------------
| Nutgram Handlers
|--------------------------------------------------------------------------
|
| Here is where you can register telegram handlers for Nutgram. These
| handlers are loaded by the NutgramServiceProvider. Enjoy!
|
*/

// Global Middleware - (Check User Join in channel Or Not)
$bot->middleware(GlobalBotMiddleware::class);

// Starter Message Handler
$bot->onCommand('start', StarterController::class);
$bot->onCallbackQueryData('bot:restart', StarterController::class);

// User Subscribe
$bot->onText('خرید اشتراک 💳', SubscribeController::class);
