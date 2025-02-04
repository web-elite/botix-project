<?php
namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\KeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\ReplyKeyboardMarkup;

class StarterController extends Controller
{
    public function __invoke(Nutgram $bot)
    {
        $adminIds = $this->getAdminIds();

        if ($this->isAdmin($bot->chatId(), $adminIds)) {
            $this->handleAdmin($bot);
        } else {
            $this->handleUser($bot);
        }
    }

    protected function getAdminIds(): array
    {
        return array_map(
            fn($id) => trim($id),
            explode(',', env('TELEGRAM_BOT_ADMIN', ''))
        );
    }

    protected function isAdmin(string $chatId, array $adminIds): bool
    {
        return in_array($chatId, $adminIds, true);
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
                KeyboardButton::make('خرید اشتراک 💳'),
                KeyboardButton::make('وضعیت اشتراک من 👤'),
            )
            ->addRow(
                KeyboardButton::make('دعوت دوستان 🔗'),
                KeyboardButton::make('چرا دریچه؟ 😎'),
                KeyboardButton::make('سوالات متداول ⁉️'),
            )
            ->addRow(
                KeyboardButton::make('پشتیبانی هوش مصنوعی 🤖'),
                KeyboardButton::make('آموزش ها 📚'),
                KeyboardButton::make('پشتیبانی 🫂'),
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
