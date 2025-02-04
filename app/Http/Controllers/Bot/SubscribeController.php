<?php
namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;

class SubscribeController extends Controller
{
    public function __invoke()
    {
        $bot->sendMessage(
            text: $this->text(),
            reply_markup: $this->keyboard(),
            parse_mode: ParseMode::HTML,
        );
    }

    protected function text()
    {
        return "سلام دوست عزیز! 👀❤️🌟\nبا دریچه همیشه و هرجا که هستی، اینترنت بدون مرز رو تجربه کن! 🚀✨\nفرقی نداره کجا باشی یا چقدر محدودیت باشه، با سرویس‌های فوق‌العاده ما همیشه آنلاین بمون و سرعت واقعی اینترنت رو حس کن! ⚡️💎\n\n📢 برای دریافت اخبار مهم، آپدیت سرویس‌ها و تخفیف‌های ویژه، همین حالا به کانال ما بپیوند:\n@Dariche_VPN 💬\n\nاز منوی زیر می‌تونی به‌راحتی خدمات موردنظرت رو انتخاب کنی و اینترنت آزاد رو شروع کنی! 👇\nبا دریچه، همیشه یه راه هست! 🔥";
    }

    protected function keyboard()
    {
        return InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('خرید اشتراک 💳'),
                InlineKeyboardButton::make('وضعیت اشتراک من 👤'),
            )
            ->addRow(
                InlineKeyboardButton::make('دعوت دوستان 🔗'),
                InlineKeyboardButton::make('چرا دریچه؟ 😎'),
                InlineKeyboardButton::make('سوالات متداول ⁉️'),
            )
            ->addRow(
                InlineKeyboardButton::make('پشتیبانی هوش مصنوعی 🤖'),
                InlineKeyboardButton::make('آموزش ها 📚'),
                InlineKeyboardButton::make('پشتیبانی 🫂'),
            );
    }
}
