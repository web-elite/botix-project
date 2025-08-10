<?php

use App\Bot\Admin\Commands\DeleteTestUsersCommand;
use App\Bot\StartCommand;
use App\Bot\User\Commands\AboutCommand;
use App\Bot\User\Menus\HowToUseMenu;
use App\Bot\User\Menus\ProfileMenu;
use App\Bot\User\Menus\ProxyMenu;
use App\Bot\User\Menus\SubscribeMenu;
use App\Bot\User\Menus\TestPlanMenu;
use App\Http\Middleware\AdminBotMiddleware;
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
$bot->onCommand('buy', SubscribeMenu::class);
$bot->onCommand('renewal', SubscribeMenu::class);

// User Subscribe
$bot->onText('دریافت اشتراک تستی 🎁', TestPlanMenu::class);
$bot->onCallbackQueryData('test_plan', TestPlanMenu::class);

// Free Telegram Proxy
$bot->onText('پروکسی رایگان تلگرام 🟢', ProxyMenu::class);
$bot->onCallbackQueryData('free_proxy', ProxyMenu::class);

// User Profile
$bot->onText('اشتراک‌های من 👤', ProfileMenu::class);
$bot->onCallbackQueryData('profile', ProfileMenu::class);
$bot->onCommand('profile', ProfileMenu::class);

// Learn More
$bot->onText('آموزش ها 📚', HowToUseMenu::class);
$bot->onCallbackQueryData('howtouse', HowToUseMenu::class);

// About Us
$bot->onText('چرا دریچه؟ 😎', AboutCommand::class);
$bot->onCallbackQueryData('aboutus', AboutCommand::class);
$bot->onCommand('support', AboutCommand::class);

/**
 * Admin Commands
 */

$bot->onText('حذف کاربران تستی 🗑️', DeleteTestUsersCommand::class)->middleware(AdminBotMiddleware::class);
$bot->onCallbackQueryData('delete_test_users', DeleteTestUsersCommand::class)->middleware(AdminBotMiddleware::class);
$bot->onCommand('delete_test_users', DeleteTestUsersCommand::class)->middleware(AdminBotMiddleware::class);

$bot->onText('افزودن کاربر 👤', SubscribeMenu::class)->middleware(AdminBotMiddleware::class);
$bot->onCallbackQueryData('add_user', SubscribeMenu::class)->middleware(AdminBotMiddleware::class);
$bot->onCommand('add_user', SubscribeMenu::class)->middleware(AdminBotMiddleware::class);
