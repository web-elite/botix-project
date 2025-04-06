<?php

use App\Bot\Commands\StartCommand;
use App\Bot\Menus\HowToUseMenu;
use App\Bot\Menus\ProfileMenu;
use App\Bot\Menus\SubscribeMenu;
use App\Http\Middleware\GlobalBotMiddleware;
use Illuminate\Support\Facades\Log;
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

// On Every Message run start command
$bot->onMessage(StartCommand::class);

// Global Middleware - (Check User Join in channel Or Not)
$bot->middleware(GlobalBotMiddleware::class);

// Starter Message Handler
$bot->onCommand('start', StartCommand::class);
$bot->onCallbackQueryData('restart', StartCommand::class);

// User Subscribe
$bot->onText('خرید اشتراک 💳', SubscribeMenu::class);
$bot->onCallbackQueryData('buy_subscription', SubscribeMenu::class);
$bot->onCallbackQueryData('renewal', SubscribeMenu::class);

// User Profile
$bot->onText('اشتراک من 👤', ProfileMenu::class);
$bot->onCallbackQueryData('profile', ProfileMenu::class);

// Learn More
$bot->onText('آموزش ها 📚', HowToUseMenu::class);
$bot->onCallbackQueryData('howtouse', HowToUseMenu::class);
$bot->onCallbackQueryData('howtouse:main', HowToUseMenu::class);
