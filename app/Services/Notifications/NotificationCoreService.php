<?php
namespace App\Services\Notifications;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class NotificationCoreService
{
    /**
     * Notify Users To Telegram
     *
     * @param int $tg_id
     * @param string $message
     */
    public function sendTelegramNotification(int $tg_id, string $message, ?string $status = '', ?array $btnArray = null): void
    {
        try {
            $bot = new Nutgram(env('TELEGRAM_TOKEN'));

            if (filled($btnArray)) {

                $keyboard = InlineKeyboardMarkup::make();
                foreach ($btnArray as $text => $callbackData) {
                    $keyboard->addRow(
                        InlineKeyboardButton::make($text, callback_data: $callbackData)
                    );
                }

                $bot->sendMessage(
                    chat_id: $tg_id,
                    reply_markup: $keyboard,
                    text: escape_markdown($message),
                    parse_mode: ParseMode::MARKDOWN,
                    disable_web_page_preview: true,
                );

            } else {
                $bot->sendMessage(
                    chat_id: $tg_id,
                    text: escape_markdown($message),
                    parse_mode: ParseMode::MARKDOWN,
                    disable_web_page_preview: true,
                );
            }

        } catch (Exception $e) {
            Log::channel('bot')->error('Telegram Notification Error: ' . $e->getMessage());
        }
    }

    /**
     * Notify Users To SMS
     *
     * @param User $user
     * @param string $message
     */
    public function sendSmsNotification(User $user, string $message): bool
    {
        try {
            $response = Http::post('https://api.kavenegar.com/v1/' . config('services.sms.api_key') . '/sms/send.json', [
                'receptor' => $user->phone,
                'message'  => $message,
            ]);

            return $response->successful();
        } catch (Exception $e) {
            report($e);
            return false;
        }
    }

    private function getStatusStickerId(?string $status): ?string
    {
        $stickerFileId = match ($status) {
            'success' or 'true' or true => "CAACAgEAAxkBAAEzyjdn_qHB6SiUCeYGaYWRMoM3sLTh-gACnwMAAonfWETOikC8ytx7RTYE", // ✅
            'error' or 'fail' or false => "CAACAgEAAxkBAAEzyj1n_qH79yTBnqL0RXNsUWoFB8I0MgAC_gIAAoEiIEQJoqI2DvPFOzYE",  // ❌
            'bug' or 'report' => "CAACAgEAAxkBAAEzpC5n-WETF--Z8cCPwNg3kMb_WMrMuwACpgEAAp-ZWEU7P-6NXtpGaTYE",           // ❗
            'warning' or 'warn' => "CAACAgEAAxkBAAEzpC9n-WETG0g1q2bX3v4a5r7m6z8cYwACpgEAAp-ZWEU7P-6NXtpGaTYE",         // ⚠️
            'info' or 'information' => "CAACAgEAAxkBAAEzymtn_qVGeavXX1G5blktB-galL-mkwACeQMAAo13GUSF6cj_mt_hyjYE",     // 💡
            'question' or 'ask' => "CAACAgEAAxkBAAEzylln_qQHRvt3NPuRkEvujXtkNd0_hwACJwMAAomLGUQ6CBl_OWz2VTYE",         // ❓
            'loading' or 'wait' => "CAACAgEAAxkBAAEzymdn_qTrXzRI3298_KegbhzfMBXxmQACRgMAAiqHGURoXzCXdu7QsTYE",         // 🔍
            'thanks' or 'thank you' => "CAACAgEAAxkBAAEzyltn_qQrEZxZIhgd6rKHC-bA2As8PwACbwIAAt3HIURJx-VpfO6OTDYE",     // 💖
            default => null,
        };

        return $stickerFileId;
    }
}
