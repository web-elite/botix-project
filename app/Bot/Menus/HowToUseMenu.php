<?php

namespace App\Bot\Menus;

use App\Models\User;
use Pest\Plugins\Retry;
use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class HowToUseMenu extends InlineMenu
{

    public function start(Nutgram $bot)
    {
        $bot->sendMessage(".\n😬🙄 هنوز این بخش درحال بروزرسانی");
        return;
        $this->clearButtons();

        $this->menuText($message, ['parse_mode' => ParseMode::MARKDOWN])
            ->addButtonRow(InlineKeyboardButton::make('آموزش اندروید', callback_data: "pause@handle"))
            ->orNext('cancel')->showMenu();
    }

    public function cancel()
    {
        $bot->sendMessage(".\n🤔 چه کاری میخوای انجام بدی؟ از منو ربات انتخاب کن");
    }
}
