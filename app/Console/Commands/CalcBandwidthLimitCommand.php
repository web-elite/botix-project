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
            $xuiNotif   = app(XUINotifService::class);

            $expiredUsers = $xuiNotif->calculateUserBandwidth();
            if ($expiredUsers === null || empty($expiredUsers)) {
                $this->error("info: No users found with expire bandwidth limit.");
                Log::channel('cron')->error("info in calc:bandwidth command", [
                    'message' => "No users found with expire bandwidth limit.",
                ]);
                return;
            }

            $notifiedUsers = [];

            $milestones = [10, 5, 1];

            foreach ($expiredUsers as $user) {
                $cacheKey = "bandwidth_last_{$user['tg_id']}";
                $milestoneKey = "bandwidth_milestone_{$user['tg_id']}";

                $currentRemaining = (float) $user['remaining_gb'];
                $lastRemaining    = Cache::get($cacheKey);
                $lastMilestone    = Cache::get($milestoneKey);

                $shouldNotify = false;
                $message = "{$user['name']} عزیز\n📊 حجم باقی‌مانده: {$currentRemaining} گیگ";

                if ($lastRemaining === null) {
                    $shouldNotify = true;
                } elseif (
                    ($lastRemaining - $currentRemaining >= 1) ||
                    ($currentRemaining > 0 && ($lastRemaining - $currentRemaining) / $lastRemaining >= 0.10)
                ) {
                    $shouldNotify = true;
                }

                foreach ($milestones as $m) {
                    if ($currentRemaining <= $m && ($lastMilestone === null || $lastMilestone > $m)) {
                        $shouldNotify = true;
                        $message = "{$user['name']} عزیز\n⚠️ حجم شما کمتر از {$m} گیگ شده.\n📊 حجم باقی‌مانده: {$currentRemaining} گیگ";
                        Cache::put($milestoneKey, $m, now()->addDays(30));
                        break;
                    }
                }

                $message .= "\n\nبرای خرید یا تمدید اشتراک، از منوی زیر استفاده کنید. 🌐💳";

                if ($shouldNotify) {
                    $notifier->sendTelegramNotification($user['tg_id'], $message);
                    $notifiedUsers[] = "{$user['tg_id']}: {$message}";
                    Cache::put($cacheKey, $currentRemaining, now()->addDays(3));
                }
            }

            $this->info("✅ Done! Notified About Bandwidth Limit to " . count($notifiedUsers) . " users.");

            if (count($notifiedUsers) > 0) {
                $adminNotif->sendTelegramNotification(
                    "✅ Done! Notified About Bandwidth Limit to " . count($notifiedUsers) . " users.\n" . implode("\n", $notifiedUsers)
                );
            }

            Log::channel('cron')->info("✅ Bandwidth check completed", [
                'notified_users' => count($notifiedUsers),
            ]);
        } catch (\Throwable $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $adminNotif->sendTelegramNotification(
                "❌ Error in bandwidth check. Error:\n" . $e->getMessage()
            );
            Log::channel('cron')->error("Error in calc:bandwidth command", [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
        }
    }
}
