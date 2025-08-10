<?php
namespace App\Bot\User\Commands;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class AboutCommand
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
        return "*👑 فیلترشکن دریچه 🚪 | سرعت و امنیت بی‌نهایت 🔥*\n\n" .
            "🌀 *ما کی هستیم؟*\n" .
            "فیلترشکن *V2Ray* با آخرین تکنولوژی روز (✅ _Noise_، ✅ _Fragmentation_، ✅ _بروزرسانی خودکار اشتراک‌ها_)\n" .
            "برای دور زدن هر نوع محدودیت، با امنیت بالا، پایداری بی‌نظیر و سرعت رویایی! 💨💥\n\n" .

            "*🛡 ویژگی‌های دریچه:*\n\n" .

            "🔰 *بدون قطعی، همیشه متصل*\n" .
            "🟢 با خیال راحت بگیم که قطعی نداریم!\n\n" .

            "🚀 *سرعت دانلود و آپلود عالی*\n" .
            "📶 مناسب گیم، استریم، وبگردی و...\n\n" .

            "🌍 *سرورهای اختصاصی برای هر نوع اینترنت*\n" .
            "🔑 مخصوص همراه اول، ایرانسل، رایتل، شاتل، مخابرات و...\n\n" .

            "🖥📱📲 *سازگار با همه سیستم‌عامل‌ها*\n" .
            "💻 ویندوز، 🍏 iOS، 🤖 اندروید، 🐧 لینوکس، 🍃 مک\n\n" .

            "🧠 *کنترل حالت خانواده*\n" .
            "🔘 امکان بستن سایت‌های پورن و بزرگسال با یک کلیک!\n\n" .

            "🔄 *دریافت سرورهای جدید تنها با چند کلیک*\n" .
            "📲 بدون نیاز به دانش فنی، همه چیز اتومات!\n\n" .

            "🌐 *سرورهای قدرتمند آلمان 🇩🇪 و دیگر کشورها*\n\n" .

            "⚡ *اینترنت نیم‌بها*\n" .
            "یعنی فقط نصف حجم مصرفی کم میشه! 😍\n" .
            "مثال: 1 گیگابایت دانلود کنید و 500 مگابایت از بسته اینترنت شما کسر خواهد شد. 🎇\n\n" .

            "📩 *پشتیبانی سریع و پاسخگو ❤️*\n\n" .

            "✨ _با دریچه همیشه یک راهی هست 🚪🛸_";
    }

}
