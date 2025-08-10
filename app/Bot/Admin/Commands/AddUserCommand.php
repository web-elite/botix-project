<?php
namespace App\Bot\Admin\Commands;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class AddUserCommand
{
    public function __invoke(Nutgram $bot)
    {
        $bot->sendMessage(
            text: escape_markdown($this->getText()),
            reply_markup: InlineKeyboardMarkup::make()->addRow(
                InlineKeyboardButton::make('پشتیبانی 👨‍💻', url: "tg://resolve?domain=" . env('SUPPORT_TELEGRAM_USERNAME'))
            ),
            parse_mode: ParseMode::MARKDOWN,
        );
    }

    public function getText(): string
    {
        return "کاربران تستی با موفقیت حذف شدند! ✅\n\n" .
            "اگر نیاز به حذف کاربران بیشتری دارید، لطفاً دوباره این دستور را اجرا کنید.\n" .
            "با تشکر از همکاری شما! 🙏";
    }

}
