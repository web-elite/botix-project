<?php

namespace App\Console\Commands;

use App\Services\Notifications\NotificationAdminHelperService;
use App\Services\Notifications\NotificationCoreService;
use App\Services\xui\XUINotifService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CalcBandwidthLimitCommand extends Command
{
    protected $signature = 'calc:bandwidth';
    protected $description = 'Calculate users bandwidth usage and notify';

    public function handle(NotificationCoreService $notifier)
    {
        try {
            $adminNotif = app(NotificationAdminHelperService::class);
            $xuiNotif = app(XUINotifService::class);
            $expiredUser = $xuiNotif->calculateUserBandwidth();
            foreach ($expiredUser as $user) {
                $cacheKey = 'bandwidth_warned_' . $user['tg_id'];
                if (Cache::has($cacheKey)) {
                    continue;
                }
                $notifier->sendTelegramNotification($user['tg_id'], $user['message']);
                Cache::put($cacheKey, true, now()->addHours(2));
            }

            $this->info("✅ Done! Notified About Bandwidth Limit to " . count($expiredUser) . " users.");
            $adminNotif->sendTelegramNotification("✅ Done! Notified About Bandwidth Limit to " . count($expiredUser) . " users.");
        } catch (\Throwable $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $adminNotif->sendTelegramNotification("❌ Error: Notified About Bandwidth Limit to " . count($expiredUser) . " users. Error:\n" . $e->getMessage());
            Log::channel('cron')->error("Error in calc:bandwidth command", [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }
}
