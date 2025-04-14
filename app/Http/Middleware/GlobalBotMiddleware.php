<?php
namespace App\Http\Middleware;

use App\Models\User;
use App\Services\UserSyncService;
use Exception;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class GlobalBotMiddleware
{
    /**
     * Handle the incoming bot request.
     *
     * @param Nutgram $bot
     * @param callable $next
     * @return void
     */
    public function __invoke(Nutgram $bot, callable $next): void
    {

        // $bot->sendMessage(
        //     text: "🔃 ربات در حال بروزرسانی می‌باشد... \n📢 آدرس کانال دریچه: " . env('TELEGRAM_BOT_ADMIN_CHANNEL') . "\n آدرس پشتیبانی: @Dariche_vpn_admin\n"
        // );
        // return;
        $this->saveLastMessage($bot);
        $this->saveUserInfo($bot);

        if ($this->checkUserIsMember($bot)) {
            $next($bot);
        }
    }

    /**
     * Save the last message ID for the user.
     *
     * @param Nutgram $bot
     * @return void
     */
    protected function saveLastMessage(Nutgram $bot): void
    {
        $chatId    = $bot->chatId();
        $messageId = $bot->messageId(); // Get the current message ID
        if ($messageId) {
            $bot->setUserData('last_message_id', $messageId, $chatId); // Save the message ID
        }
    }

    /**
     * Save or update user information.
     *
     * @param Nutgram $bot
     * @return void
     */
    protected function saveUserInfo(Nutgram $bot): void
    {
        $userData = $bot->user();
        $usersync = app(UserSyncService::class);
        $usersync->syncTelegramUser($userData);
    }

    /**
     * Check if the user is a member of the specified channel.
     *
     * @param Nutgram $bot
     * @return bool
     */
    protected function checkUserIsMember(Nutgram $bot): bool
    {
        try {
            $chatId   = $bot->chatId();

            if (this_id_is_admin($chatId)) {
                return true;
            }
            $channelUsername = env('TELEGRAM_BOT_ADMIN_CHANNEL');

            $chatMemberInfo = $bot->getChatMember($channelUsername, $chatId);
            $joinStatus     = $chatMemberInfo->status->value;
            if (in_array($joinStatus,['member','administrator','creator'])) {
                $this->deleteJoinMessage($bot, $chatId);
                return true;
            }

            $this->logNonMemberAttempt($chatMemberInfo, $chatId, $joinStatus);
        } catch (Exception $e) {
            Log::channel('bot')->error("Error in checkUserIsMember on Line " . $e->getLine() . ": " . $e->getMessage());
        }

        $this->handleNonMemberResponse($bot);
        return false;
    }

    /**
     * Delete the "please join" message if the user joins the channel.
     *
     * @param Nutgram $bot
     * @param int $chatId
     * @return void
     */
    protected function deleteJoinMessage(Nutgram $bot, int $chatId): void
    {
        try {
            $messageId = $bot->getUserData('pls_join_message_id', $chatId);
            if ($messageId > 0) {
                $bot->deleteMessage($chatId, $messageId);
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'delete not found') === false) {
                Log::channel('bot')->error("Unexpected error in deleteJoinMessage: " . $e->getMessage());
            }
        }
    }

    /**
     * Log attempts by non-members to use the bot.
     *
     * @param object $chatMemberInfo
     * @param int $chatId
     * @param string $joinStatus
     * @return void
     */
    protected function logNonMemberAttempt(object $chatMemberInfo, int $chatId, string $joinStatus): void
    {
        $username = $chatMemberInfo->user->username ?? 'Unknown';
        // Log::channel('bot')->info("http://t.me/{$username} ($chatId) attempted to use the bot but join status is: $joinStatus");
    }

    /**
     * Handle the response for non-members.
     *
     * @param Nutgram $bot
     * @return void
     */
    protected function handleNonMemberResponse(Nutgram $bot): void
    {
        $chatId = $bot->chatId();

        if ($bot->isCallbackQuery()) {
            $bot->answerCallbackQuery(text: 'شما داخل کانال عضو نیستید.');
        } else {
            $messageId = $bot->sendMessage(
                text: "📢 لطفا ابتدا در کانال دریچه عوض شوید. \n" . env('TELEGRAM_BOT_ADMIN_CHANNEL'),
                reply_markup: $this->getJoinKeyboard()
            );
            $bot->setUserData('pls_join_message_id', $messageId->message_id, $chatId);
        }
    }

    /**
     * Generate the "please join" inline keyboard.
     *
     * @return InlineKeyboardMarkup
     */
    protected function getJoinKeyboard(): InlineKeyboardMarkup
    {
        $channelUsername = ltrim(env('TELEGRAM_BOT_ADMIN_CHANNEL'), '@');

        return InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('عضویت در کانال 📢', "tg://resolve?domain=$channelUsername")
            )
            ->addRow(
                InlineKeyboardButton::make('عضو شدم ✅', callback_data: 'bot:restart')
            );
    }
}
