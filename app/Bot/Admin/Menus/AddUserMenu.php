<?php

namespace App\Bot\Admin\Menus;

use App\Services\UserService;
use Illuminate\Support\Facades\Http;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class AddUserMenu extends InlineMenu
{

    public function start(Nutgram $bot)
    {
        $this->clearButtons();
        $this->menuText("لطفا یک نام انتخاب", ['parse_mode' => ParseMode::MARKDOWN])
            ->addButtonRow(
                InlineKeyboardButton::make('ثبت ✅', callback_data: 'createUser'),
            )
            ->orNext('cancel')
            ->showMenu();
    }

    public function cancel(Nutgram $bot)
    {
        $bot->sendMessage("🤔 چه کاری می‌خوای انجام بدی؟ از منوی ربات انتخاب کن.");
    }

    private function backInlineKeyboardButton(): InlineKeyboardButton
    {
        return InlineKeyboardButton::make('🔙 برگشت به منوی اصلی', callback_data: 'howtouse');
    }

    private function createUser(): string
    {
        return "2. پس از دانلود فایل zip، فایل رو از حالت فشرده خارج و اجرا کن.\n"
            . "3. در نوار بالای نرم افزار روی گزینه Subscription Group (گروه‌های اشتراک) کلیک کنید و گزینه Add (افزودن) رو انتخاب کنید.\n"
            . "4. فیلد اول یک نام دلخواه وارد کنید. (فیلد REMARK)\n"
            . "5. فیلد دوم لینک اشتراکتون (حرفه ای یا معمولی) رو وارد کنید. (فیلد URL)\n"
            . "💡 حتما گزینه Enable Update (فعالسازی بروزرسانی) فعال باشه تا بروزترین نسخه سرورتون رو همیشه داشته باشید."
            . "6. در نهایت روی گزینه Confirm (تایید) کلیک کنید.\n"
            . "7. به صفحه اصلی نرم افزار برگردید و سرور مورد نظرتون رو یک بار روش کلیک کنید و Enter بزنید تا Active (فعال) بشه."
            . "8. حالا کافیه در نوار پایین نرم افزار گزینه Set system Proxy (تنظیم پروکسی سیستم) رو انتخاب کنید و روی گزینه Connect (اتصال) کلیک کنید.\n"
            . "💡 شما با این کار پروکسی سیستم شما تنظیم میشه و تمام نرم افزارها از این پروکسی استفاده میکنند.\n"
            . "💡 میتونید گزینه Enable TUN (فعالسازی تون) رو هم فعال کنید تا کل سیستم از فیلترشکن استفاده کنه.\n"
            . "حالا میتونید با خیال راحت از نرم افزارها و وبسایت‌های فیلتر شده استفاده کنید.\n";
    }
}
