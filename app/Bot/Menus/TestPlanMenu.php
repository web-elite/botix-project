<?php
namespace App\Bot\Menus;

use App\Models\User;
use App\Services\Notifications\NotificationAdminHelperService;
use App\Services\UserService;
use App\Services\xui\XUIDataService;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class TestPlanMenu extends InlineMenu
{

    /**
     * Start the Test Plan Menu.
     *
     * @param Nutgram $bot
     * @return void
     */
    public function start(Nutgram $bot)
    {
        $this->clearButtons();
        $userXuiSubs = app(UserService::class)->getUserXuiData($bot->chatId(), 'active');
        if (! filled($userXuiSubs)) {
            $message = "❌ اشتراک تستی فقط برای کاربرانی که هنوز اشتراک خریداری نکرده‌اند، فعال می‌شود.\n\n";
            $message .= "شما قبلاً اشتراک خریداری کرده‌اید و نمی‌توانید از اشتراک تستی استفاده کنید.\n\n";
            $this->menuText(escape_markdown($message), ['parse_mode' => ParseMode::MARKDOWN])
                ->addButtonRow(InlineKeyboardButton::make('👀 مشاهده اشتراک های من 👤', callback_data: "profile"))
                ->addButtonRow(InlineKeyboardButton::make('📚 آموزش نحوه استفاده 🎥', callback_data: "howtouse"))
                ->orNext('cancel')->showMenu();
            return;
        }

        $message = "🎁 *اشتراک تستی* 🎁\n\n";
        $message .= "با استفاده از اشتراک تستی، می‌تونی به مدت ۲۴ ساعت از سرویس‌های ما به صورت رایگان استفاده کنی! 🚀✨\n\n";
        $message .= "برای فعال‌سازی اشتراک تستی، کافیه روی دکمه زیر کلیک کنی.\n\n";

        $this->menuText(escape_markdown($message), ['parse_mode' => ParseMode::MARKDOWN])
            ->addButtonRow(InlineKeyboardButton::make('✅ فعالسازی اشتراک تست ✅', callback_data: "active_test_plan@active_test_plan"))
            ->orNext('cancel')->showMenu();
    }

    public function active_test_plan(Nutgram $bot)
    {
        $xui        = app(XUIDataService::class);
        $user       = User::findByTgId($bot->chatId())->first();
        $clientData = $xui->createTestClient($user);
        if (isset($clientData['status'])) {
            app(NotificationAdminHelperService::class)->sendTelegramNotification("ثبت اشتراک تستی جدید توسط کاربر @{$user->telegram_username} با خطا مواجه شد.");
            $this->menuText("❌ خطا در فعال‌سازی اشتراک تستی: " . $clientData['message'])
                ->addButtonRow(InlineKeyboardButton::make('🔄 تلاش مجدد 🔄', callback_data: "test_plan"))
                ->orNext('cancel')->showMenu();
            return;
        }
        app(NotificationAdminHelperService::class)->sendTelegramNotification("اشتراک تستی جدید توسط کاربر @{$user->telegram_username} ثبت شد.");
        $message = "🎁 اشتراک تستی شما فعال شد! 🎁\n\n";
        $message .= "شما به مدت ۲۴ ساعت از سرویس‌های ما به صورت رایگان استفاده می‌کنید. 🚀✨\n\n";
        $this->menuText(escape_markdown($message), ['parse_mode' => ParseMode::MARKDOWN])
            ->addButtonRow(InlineKeyboardButton::make('👀 مشاهده اشتراک های من 👤', callback_data: "profile"))
            ->addButtonRow(InlineKeyboardButton::make('📚 آموزش نحوه استفاده 🎥', callback_data: "howtouse"))
            ->orNext('cancel')->showMenu();

    }

    public function cancel(Nutgram $bot)
    {
        $this->clearButtons();
    }
}
