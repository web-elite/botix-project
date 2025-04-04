<?php

use SergiX44\Nutgram\Nutgram;

if (! function_exists('escape_markdown')) {
    function escape_markdown(string $text): string
    {
        $characters = ['[', ']', '(', ')', '~', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        foreach ($characters as $char) {
            $text = str_replace($char, '\\' . $char, $text);
        }
        return $text;
    }
}

if (! function_exists('format_bytes')) {
    function format_bytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

if (! function_exists('show_loading_bot')) {
    function show_loading_bot(Nutgram $bot, ?string $text = null): void
    {
        try {
            $chatId = $bot->chatId();
            $text   = $text ?? "درحال بارگذاری اطلاعات ...";

            $sticker = $bot->sendSticker(
                sticker: 'CAACAgUAAxkDAAEBzGhn7Vpnh5w4yUQDj3eMF_opeKbILgACuxQAAgv1oVTktTcypYISJDYE',
                chat_id: $chatId
            );

            $msg = $bot->sendMessage(
                chat_id: $chatId,
                text: $text,
            );

            $bot->setUserData('loading_message_id', $msg->message_id, $chatId);
            $bot->setUserData('loading_sticker_id', $sticker->message_id, $chatId);
        } catch (Exception $e) {
            Log::channel('bot')->error("Unexpected error in show_loading_bot: " . $e->getMessage());
        }
    }
}

if (! function_exists('hide_loading_bot')) {
    function hide_loading_bot(Nutgram $bot): void
    {
        try {
            $chatId    = $bot->chatId();
            $messageId = $bot->getUserData('loading_message_id', $chatId);
            $stickerId = $bot->getUserData('loading_sticker_id', $chatId);

            if ($stickerId > 0) {
                $bot->deleteMessage($chatId, $stickerId);
            }

            if ($messageId > 0) {
                $bot->deleteMessage($chatId, $messageId);
            }
        } catch (Exception $e) {
            Log::channel('bot')->error("Error in hide_loading_bot: " . $e->getMessage());
            throw $e; // Re-throw for debugging
        }
    }
}

if (! function_exists('bytes_to_gb')) {
    function bytes_to_gb($bytes)
    {
        return number_format($bytes / (1024 ** 3), 2);
    }
}

if (! function_exists('calculate_time_left')) {
    function calculate_time_left($timeLimit)
    {
        $currentTime   = time() * 1000;
        $remainingTime = max(0, $timeLimit - $currentTime);

        return [
            'days'    => floor($remainingTime / (1000 * 60 * 60 * 24)),
            'hours'   => floor(($remainingTime % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
            'minutes' => floor(($remainingTime % (1000 * 60 * 60)) / (1000 * 60)),
        ];
    }
}
