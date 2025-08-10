<?php
namespace App\Bot\User\Menus;

use SergiX44\Nutgram\Conversations\InlineMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

class ProxyMenu extends InlineMenu
{
    public function start(Nutgram $bot)
    {
        $this->clearButtons();
        $text = "*🟢 پروکسی رایگان تلگرام (اختصاصی دریچه):*\n\n" .
            "برای اتصال به پروکسی ها کافیست روی دکمه‌های زیر کلیک کنید.";
        $this->menuText(escape_markdown($text), ['parse_mode' => ParseMode::MARKDOWN])
            ->addButtonRow(
                InlineKeyboardButton::make('اتصال به پروکسی 1', url: 'tg://proxy?server=5.75.203.244&port=443&secret=dd153ca8511cba7b7e143db0c1415a79ba')
            )
            ->addButtonRow(
                InlineKeyboardButton::make('اتصال به پروکسی 2', url: 'tg://proxy?server=5.75.203.244&port=443&secret=ee153ca8511cba7b7e143db0c1415a79ba7777772e636c6f7564666c')
            )
            ->orNext('cancel')
            ->showMenu();
    }

    public function cancel(Nutgram $bot)
    {
        $this->clearButtons();
    }

}
