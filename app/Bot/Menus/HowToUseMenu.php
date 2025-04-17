<?php
namespace App\Bot\Menus;

use App\Services\UserService;
use Illuminate\Support\Facades\Http;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class HowToUseMenu extends InlineMenu
{

    public function start(Nutgram $bot)
    {
        $this->clearButtons();
        $this->menuText("*📚 آموزش اتصال به فیلترشکن:*\n\n" .
            "لطفاً ابتدا سیستم‌عامل خودت رو انتخاب کن👇", ['parse_mode' => ParseMode::MARKDOWN])
            ->addButtonRow(
                InlineKeyboardButton::make('📱 اندروید', callback_data: 'V2rayNg@howto_android'),
                InlineKeyboardButton::make('🍏 آیفون', callback_data: 'Streisand@howto_ios')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('💻🪟 ویندوز', callback_data: 'V2rayN@howto_windows'),
            )
            ->addButtonRow(
                InlineKeyboardButton::make('💻🍏 مک', callback_data: 'V2rayN@howto_macos'),
                InlineKeyboardButton::make('💻🐧 لینوکس', callback_data: 'V2rayN@howto_linux'),
            )
            ->orNext('cancel')
            ->showMenu();
    }

    public function howto_android(Nutgram $bot)
    {
        $this->clearButtons();
        $text = "*📱 آموزش اندروید :*\n\n"
        . $this->getDownloadStepsText($bot)
            . "3. حالا کافیه وارد برنامه بشید و روی گزینه + اون بالا لمس کنید.\n"
            . "4. از گزینه‌های نمایش داده شده گزینه دوم رو انتخاب کنید. (import from Clipboard)\n"
            . "💡 شما با این کار لینک اشتراکتون رو وارد نرم افزار کردید و اشتراک شما داخل نرم افزار اضافه شد.\n"
            . "5. حالا روی 3 نقطه بالا لمس کنید و گزینه آخر رو لمس کنید.\n"
            . "💡 شما با این کار آخرین نسخه سرورهارو دریافت کردید و داخل نرم افزارتون اضافه شد.\n"
            . "6. در مرحله آخر روی سرور مورد نظر لمس کن و سپس روی دایره پایین کلیک کن تا متصل شی 🚀"
            . "\n"
            . "😍 راستی میدونستی سرورهای ما بدون نیاز به هیچ کاری خودشون آپدیت میشن";

        $this->menuText(escape_markdown($text), ['parse_mode' => ParseMode::MARKDOWN])
            ->addButtonRow(
                InlineKeyboardButton::make('📥 دانلود نسخه عمومی', url: $this->getLatestReleaseDownloadLink('2dust', 'v2rayNG', 'universal.apk'))
            )
            ->addButtonRow(
                InlineKeyboardButton::make('📥 دانلود نسخه armeabi-v7a', url: $this->getLatestReleaseDownloadLink('2dust', 'v2rayNG', 'armeabi-v7a.apk')),
            )
            ->addButtonRow(
                InlineKeyboardButton::make('📥 دانلود نسخه arm64-v8a', url: $this->getLatestReleaseDownloadLink('2dust', 'v2rayNG', 'arm64-v8a.apk'))
            )
            ->addButtonRow(
                InlineKeyboardButton::make('📥 دانلود نسخه x86', url: $this->getLatestReleaseDownloadLink('2dust', 'v2rayNG', 'x86.apk')),
                InlineKeyboardButton::make('📥 دانلود نسخه x86_64', url: $this->getLatestReleaseDownloadLink('2dust', 'v2rayNG', 'x86_64.apk'))
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🔙 بازگشت', callback_data: 'howtouse')
            )
            ->showMenu();
    }

    public function howto_ios(Nutgram $bot)
    {
        $this->clearButtons();
        $text = "*🍏 آموزش آیفون:*\n\n"
        . $this->getDownloadStepsText($bot)
            . "3. حالا کافیه وارد برنامه بشید و روی گزینه + اون بالا لمس کنید.\n"
            . "4. از گزینه‌های نمایش داده شده گزینه سوم رو انتخاب کنید. (import from Clipboard)\n"
            . "💡 شما با این کار لینک اشتراکتون رو وارد نرم افزار کردید و سرورها از طریق اشتراک شما داخل نرم افزار اضافه شدند.\n"
            . "💡 اشتراک با نام @Dariche_VPN میتونی ببینی، که اگه روش نگه داری میتونی ویرایش یا حذف یا آخرین نسخه سرورهارو دریافت کنی یا ...\n"
            . "5. در مرحله آخر روی سرور مورد نظر لمس کن تا انتخاب بشه (سرور انتخاب شده کنارش یک نقطه زرد داره) بعدش باید روی دکمه آبی بالا لمس کنی تا متصل شی 🚀"
            . "\n\n"
            . "⁉️ دوست داری سرورهات خودکار و بدون هیچ کلیکی آپدیت بشه؟\n"
            . "1. وارد تنظیمات بشید. (گزینه Settings با علامت ⚙️ در پایین صفحه)\n"
            . "2. حالا وارد تنظیمات مربوط به اشتراکتون بشید. (گزینه Subscriptions با علامت 🔗).\n"
            . "3. گزینه Update On Open رو فعال کنید.\n"
            . "💡 شما با این کار هربار وقتی وارد برنامه بشید، آخرین نسخه سرورهارو دریافت می‌کنید.\n"
        ;

        $this->menuText(escape_markdown($text), ['parse_mode' => ParseMode::MARKDOWN])
            ->addButtonRow(
                InlineKeyboardButton::make('📥 لینک دانلود Streisand', url: 'https://apps.apple.com/us/app/streisand/id6450534064')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🔙 بازگشت', callback_data: 'howtouse')
            )
            ->showMenu();
    }

    public function howto_windows(Nutgram $bot)
    {
        $this->clearButtons();
        $text = "*💻 آموزش ویندوز:*\n\n"
            . "1. نرم‌افزار *v2rayN* رو از دکمه زیر دانلود کن.\n"
            . "2. فایل رو از حالت فشرده خارج و اجرا کن.\n"
            . "3. از داخل نرم‌افزار لینک اشتراک رو ایمپورت کن.\n"
            . "4. روی کانکشن کلیک کن تا متصل بشی 🔐";

        $this->menuText(escape_markdown($text), ['parse_mode' => ParseMode::MARKDOWN])
            ->addButtonRow(
                InlineKeyboardButton::make('📥 دانلود v2rayN', url: $this->getLatestReleaseDownloadLink('2dust', 'v2rayNG', 'windows-64-desktop.zip'))
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🔙 بازگشت', callback_data: 'howtouse')
            )
            ->showMenu();
    }

    public function howto_linux(Nutgram $bot)
    {
        $this->clearButtons();
        $text = "*🐧 آموزش لینوکس / مک:*\n\n"
            . "1. نرم‌افزار *v2rayN* رو از ریپازیتوری زیر بگیر (با Wine هم قابل اجراست).\n"
            . "2. لینک اشتراکتو داخلش ایمپورت کن و متصل شو.\n\n"
            . "💡 اگه با GUI راحت نیستی، می‌تونیم راهنمای CLI هم بهت بدیم.";

        $this->menuText(escape_markdown($text), ['parse_mode' => ParseMode::MARKDOWN])
            ->addButtonRow(
                InlineKeyboardButton::make('📥 دانلود v2rayN', url: $this->getLatestReleaseDownloadLink('2dust', 'v2rayNG', 'linux-64.AppImage'))
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🔙 بازگشت', callback_data: 'howtouse')
            )
            ->showMenu();
    }

    public function howto_macos(Nutgram $bot)
    {
        $this->clearButtons();
        $text = "*🐧 آموزش مک:*\n\n"
            . "1. نرم‌افزار *v2rayN* رو از ریپازیتوری زیر بگیر (با Wine هم قابل اجراست).\n"
            . "2. لینک اشتراکتو داخلش ایمپورت کن و متصل شو.\n\n"
            . "💡 اگه با GUI راحت نیستی، می‌تونیم راهنمای CLI هم بهت بدیم.";

        $this->menuText(escape_markdown($text), ['parse_mode' => ParseMode::MARKDOWN])
            ->addButtonRow(
                InlineKeyboardButton::make('📥 دانلود v2rayN', url: $this->getLatestReleaseDownloadLink('2dust', 'v2rayNG', 'macos-64.zip'))
            )
            ->addButtonRow(
                InlineKeyboardButton::make('🔙 بازگشت', callback_data: 'howtouse')
            )
            ->showMenu();
    }

    public function cancel(Nutgram $bot)
    {
        $bot->sendMessage("🤔 چه کاری می‌خوای انجام بدی؟ از منوی ربات انتخاب کن.");
    }

    private function getLatestReleaseDownloadLink($owner, $repo, $match = null)
    {
        $response = Http::get("https://api.github.com/repos/{$owner}/{$repo}/releases/latest");

        if ($response->failed()) {
            return null;
        }

        $release = $response->json();

        // اگر بخوای یه فایل خاص رو فیلتر کنی مثلاً apk یا zip
        foreach ($release['assets'] as $asset) {
            if ($match === null || str_contains($asset['name'], $match)) {
                return $asset['browser_download_url'];
            }
        }

        return null;
    }

    private function getDownloadStepsText(Nutgram $bot): string
    {
        $userSubsciptions = app(UserService::class)->getUserSubscriptions($bot->chatId());
        $appName          = $bot->callbackQuery()->data;
        return "1. از طریق دکمه‌های زیر این پیام نرم افزار *$appName* رو دانلود و نصب کن.\n"
            . "2. بعد از نصب، لینک اشتراکتو از این پایین کپی کن.\n"
            . "💡 لینک اشتراک (حرفه‌ای یا معمولی هرکدوم برات بهتره) روش کلیک کن تا کپی شه\n"
            . "{$userSubsciptions}";
    }
}
