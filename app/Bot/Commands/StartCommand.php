<?php
namespace App\Bot\Commands;

use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;

class StartCommand
{
    public function __invoke(Nutgram $bot)
    {
        if (this_id_is_admin($bot->chatId())) {
            $this->handleAdmin($bot);
        } else {
            $this->handleUser($bot);
        }
    }

    protected function handleAdmin(Nutgram $bot)
    {
        $bot->sendMessage(
            text: 'Welcome Admin!',
            reply_markup: $this->adminKeyboard(),
            parse_mode: ParseMode::HTML,
        );
    }

    protected function handleUser(Nutgram $bot)
    {
        try {
            $bot->sendMessage(
                text: $this->userMessage(),
                reply_markup: $this->userKeyboard(),
                parse_mode: ParseMode::HTML,
            );
        } catch (\Throwable $e) {
            Log::channel('nutgram')->error("Telegram sendMessage failed: " . $e->getMessage());
        }
    }

    protected function userMessage(): string
    {
        return "سلام دوست عزیز! 👀❤️🌟\nبا دریچه همیشه و هرجا که هستی، اینترنت بدون مرز رو تجربه کن! 🚀✨\nفرقی نداره کجا باشی یا چقدر محدودیت باشه، با سرویس‌های فوق‌العاده ما همیشه آنلاین بمون و سرعت واقعی اینترنت رو حس کن! ⚡️💎\n\n📢 برای دریافت اخبار مهم، آپدیت سرویس‌ها و تخفیف‌های ویژه، همین حالا به کانال ما بپیوند:\n@Dariche_VPN 💬\n\nاز منوی زیر می‌تونی به‌راحتی خدمات موردنظرت رو انتخاب کنی و اینترنت آزاد رو شروع کنی! 👇\nبا دریچه، همیشه یه راه هست! 🔥";
    }

    protected function userKeyboard(): ReplyKeyboardMarkup
    {
        return ReplyKeyboardMarkup::make()
            ->addRow(
                KeyboardButton::make('خرید یا تمدید اشتراک 💳'),
                KeyboardButton::make('اشتراک‌های من 👤'),
            )
            ->addRow(
                KeyboardButton::make('دریافت اشتراک تستی 🎁'),
                // KeyboardButton::make('پروکسی رایگان تلگرام 🟢'),
                // KeyboardButton::make('دریافت کد تخفیف 🎟️'),
            )
            ->addRow(
                // KeyboardButton::make('دعوت دوستان 🔗'),
            )
            ->addRow(
                // KeyboardButton::make('پشتیبانی هوش مصنوعی 🤖'),
                KeyboardButton::make('آموزش ها 📚'),
                KeyboardButton::make('چرا دریچه؟ 😎'),
            );
    }

    protected function adminKeyboard(): ReplyKeyboardMarkup
    {
        return ReplyKeyboardMarkup::make()
            ->addRow(
                KeyboardButton::make('User Statistics 📊'),
                KeyboardButton::make('Send Broadcast 📢')
            )
            ->addRow(
                KeyboardButton::make('Server Status 🖥️'),
                KeyboardButton::make('Error Logs 📜')
            );
    }
}
