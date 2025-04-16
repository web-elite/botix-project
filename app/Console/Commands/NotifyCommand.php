<?php
namespace App\Console\Commands;

use App\Services\Notifications\NotificationCoreService;
use App\Services\xui\XUINotifService;
use Illuminate\Console\Command;

class NotifyCommand extends Command
{
    protected $signature   = 'notify:expiring-subscriptions';
    protected $description = 'Notify users via Telegram and SMS';

    public function handle(NotificationCoreService $notifier)
    {
        $xui         = app(XUINotifService::class);
        $expiredUser = $xui->prepareExpiringData();
        foreach ($expiredUser as $user) {
            $notifier->sendTelegramNotification($user['tg_id'], $user['message']);
        }
        $this->info("Done! Notified " . count($expiredUser) . " users.");
    }
}
