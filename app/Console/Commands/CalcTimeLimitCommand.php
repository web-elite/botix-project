<?php
namespace App\Console\Commands;

use App\Services\Notifications\NotificationAdminHelperService;
use App\Services\Notifications\NotificationCoreService;
use App\Services\xui\XUINotifService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CalcTimeLimitCommand extends Command
{
    protected $signature   = 'calc:time';
    protected $description = 'Calculate time limits for users';

    public function handle(NotificationCoreService $notifier)
    {
        try {
            $adminNotif   = app(NotificationAdminHelperService::class);
            $xui          = app(XUINotifService::class);
            $expiredUsers = $xui->prepareExpiringData();
            foreach ($expiredUsers as $user) {
                $cacheKey = 'timelimit_warned_' . $user['tg_id'];
                if (Cache::has($cacheKey)) {
                    continue;
                }
                $notifier->sendTelegramNotification($user['tg_id'], $user['message']);
                Cache::put($cacheKey, true, now()->addDays(7));
            }
            $this->info("✅ Done! Notified About Time Expiration to " . count($expiredUsers) . " users.");
            $adminNotif->sendTelegramNotification("✅ Done! Notified About Time Expiration to " . count($expiredUsers) . " users.");
            Log::channel('cron')->info("✅ Done! Notified About Time Expiration to " . count($expiredUsers) . " users.");
        } catch (\Throwable $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $adminNotif->sendTelegramNotification("❌ Error: Notified About Time Expiration to " . count($expiredUsers) . " users. Error:\n" . $e->getMessage());
            Log::channel('cron')->error("Error in calc:time command", [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }
}