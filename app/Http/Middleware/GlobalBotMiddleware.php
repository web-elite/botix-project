<?php
namespace App\Http\Middleware;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

class GlobalBotMiddleware
{

    public function __invoke(Nutgram $bot, $next)
    {
        // $this->setUserInfo($bot);
        $passed = true;
        $this->saveLastMsg($bot);
        $this->saveUserInfo($bot);
        $passed = $this->checkUserIsMember($bot);

        if ($passed) {
            $next($bot);
        }
    }

    protected function saveLastMsg(Nutgram $bot)
    {
        $chatId = $bot->chatId();
        $msgId  = $bot->getMessageId();
        $bot->setUserData('last_message_id', $msgId, $chatId);
    }

    protected function saveUserInfo(Nutgram $bot)
    {
        $chatId = $bot->chatId();
        $user   = $bot->user();
        $user   = User::updateOrCreate(
            ['tg_id' => $chatId],
            ['tg_data' => json_encode($user)]
        );
        $bot->setUserData('user_id', $user->id, $chatId);
    }

    protected function checkUserIsMember(Nutgram $bot)
    {
        try {
            $chatId          = $bot->chatId();
            $channelUsername = env('TELEGRAM_BOT_ADMIN_CHANNEL');
            $chatMemberInfo  = $bot->getChatMember($channelUsername, $chatId);
            $joinStatus      = $chatMemberInfo->status->value;
            if ($joinStatus === 'member') {
                $msgID = $bot->getUserData('pls_join_message_id', $chatId);
                $bot->deleteMessage($chatId, $msgID);
                return true;
            }
            Log::channel('bot')->error("http://t.me/{$chatMemberInfo->user->username} ($chatId) want to use robot but join status is: $joinStatus");
        } catch (Exception $e) {
            Log::channel('bot')->error("Error checkUserIsMember on Line " . $e->getLine() . " : " . $e->getMessage());
        }

            $bot->setUserData('pls_join_message_id', $msgID->message_id, $chatId);
        return false;
    }

    protected function pls_join_keyboards()
    {
        $channelUsername     = env('TELEGRAM_BOT_ADMIN_CHANNEL');
        $channelUsernameLink = ltrim($channelUsername, '@');
        return InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('عضویت در کانال 📢', "tg://resolve?domain=$channelUsernameLink"),
            )
            ->addRow(
                InlineKeyboardButton::make('عضو شدم ✅'),
            );
    }

}
