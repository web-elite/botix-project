<?php

namespace App\Bot\Menus;

use App\Models\User;
use App\Services\UserService;
use Pest\Plugins\Retry;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class ProfileMenu extends InlineMenu
{

    /**
     * Start the profile menu.
     *
     * @param Nutgram $bot
     * @return void
     */
    public function start(Nutgram $bot)
    {
        $this->clearButtons();
        show_loading_bot($bot);

        $userService = new UserService;
        $subscriptions = $userService->getUserXuiData($bot->chatId());
        $subCount = count($subscriptions);

        if ($subCount === 0) {
            $message = "❌ اشتراکی برای شما پیدا نشد.";
            $this->menuText($message, ['parse_mode' => ParseMode::MARKDOWN])
                ->addButtonRow(InlineKeyboardButton::make('🛒 خرید اشتراک 🛒', callback_data: "buy_subscription@handle"))
                ->orNext('cancel')->showMenu();
            return;
        }

        $message = "👤 *وضعیت کامل اشتراک‌های شما* ($subCount عدد)\n\n";

        foreach ($subscriptions as $subId => $data) {
            $message .= $userService->formatUserSubInfo($subId, $data);
        }

        hide_loading_bot($bot);

        $this->menuText(escape_markdown($message), ['parse_mode' => ParseMode::MARKDOWN])
            // ->addButtonRow(InlineKeyboardButton::make('❄️ غیرفعالسازی موقت اشتراک ❄️', callback_data: "pause@handle"))
            ->addButtonRow(InlineKeyboardButton::make('✅ تمدید اشتراک ✅', callback_data: "renewal"))
            ->addButtonRow(InlineKeyboardButton::make('📚 آموزش نحوه استفاده 🎥', callback_data: "howtouse"))
            ->orNext('cancel')->showMenu();
    }

    public function cancel(Nutgram $bot)
    {
        $this->clearButtons();
    }
}
