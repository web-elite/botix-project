<?php
namespace App\Http\Middleware;

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
        $this->saveUserInfo($bot);
        $passed = $this->checkUserIsMember($bot);

        if ($passed) {
            $next($bot);
        }
    }

    protected function saveUserInfo(Nutgram $bot)
    {
        $chatId              = $bot->chatId();

    }

    protected function checkUserIsMember(Nutgram $bot)
    {
        try {
            $chatId              = $bot->chatId();
            $channelUsername     = env('TELEGRAM_BOT_ADMIN_CHANNEL');
            $chatMemberInfo      = $bot->getChatMember($channelUsername, $chatId);
            $joinStatus          = $chatMemberInfo->status->value;
            if ($joinStatus === 'member') {
                return true;
            }
            Log::channel('bot')->error("http://t.me/{$chatMemberInfo->user->username} ($chatId) want to use robot but join status is: $joinStatus");
        } catch (Exception $e) {
            Log::channel('bot')->error("Error checkUserIsMember on Line " . $e->getLine() . " : " . $e->getMessage());
        }

        $bot->sendMessage(
            text: "📢 لطفا ابتدا در کانال دریچه عوض شوید. \n$channelUsername",
            reply_markup: $this->pls_join_keyboards(),
        );
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

    protected function setUserInfo(Nutgram $bot)
    {
        // $user = get_current_user_from_db($bot->userId());
        // $bot->set('user', $user);
    }
}
