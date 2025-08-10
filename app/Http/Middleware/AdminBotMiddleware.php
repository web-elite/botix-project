<?php

namespace App\Http\Middleware;

use Exception;
use SergiX44\Nutgram\Nutgram;

class AdminBotMiddleware
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
        if (this_id_is_admin($bot->chatId())) {
            $next($bot);
        }
    }
}
