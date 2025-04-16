<?php

use App\Bot\Commands\AboutCommand;
use App\Bot\Commands\StartCommand;
use App\Bot\Menus\HowToUseMenu;
use App\Bot\Menus\ProfileMenu;
use App\Bot\Menus\SubscribeMenu;
use App\Bot\Menus\TestPlanMenu;
use App\Http\Middleware\GlobalBotMiddleware;

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
$bot->onText('خرید یا تمدید اشتراک 💳', SubscribeMenu::class);
$bot->onCallbackQueryData('buy_subscription', SubscribeMenu::class);
$bot->onCallbackQueryData('renewal', SubscribeMenu::class);

// User Subscribe
$bot->onText('دریافت اشتراک تستی 🎁', TestPlanMenu::class);
$bot->onCallbackQueryData('test_plan', TestPlanMenu::class);

// User Profile
$bot->onText('اشتراک‌های من 👤', ProfileMenu::class);
$bot->onCallbackQueryData('profile', ProfileMenu::class);

// Learn More
$bot->onText('آموزش ها 📚', HowToUseMenu::class);
$bot->onCallbackQueryData('howtouse', HowToUseMenu::class);

// About Us
$bot->onText('چرا دریچه؟ 😎', AboutCommand::class);
$bot->onCallbackQueryData('aboutus', AboutCommand::class);
