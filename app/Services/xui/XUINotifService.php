<?php

namespace App\Services\xui;

use App\Models\User;
use App\Services\UserSyncService;
use Illuminate\Support\Facades\Log;

class XUINotifService
{
    protected UserSyncService $userSync;

    public function __construct(UserSyncService $userSync)
    {
        $this->api = $userSync;
    }

    public function prepareExpiringData(): array
    {
        $this->api->syncXuiUsers();
        $users = User::whereNotNull('tg_id')->get();
        $data  = [];

        foreach ($users as $user) {
            $data[] = $this->checkExpiringSubscription($user);
        }

        return array_filter($data);
    }

    /**
     * Notify user about expiring subscriptions.
     *
     * @param  User  $user
     * @return array
     */

    public function checkExpiringSubscription(User $user): array
    {
        //Log::channel('cron')->info('🔍 Starting checkExpiringSubscription', ['user_id' => $user->id]);

        if (empty($user->tg_id)) {
            //Log::channel('cron')->info('⛔️ User has no Telegram ID', ['user_id' => $user->id]);
            return [];
        }

        $xuiData = $user->xui_data ?? [];
        //Log::channel('cron')->info('📦 Loaded XUI data', ['count' => count($xuiData)]);

        $expiringSubscriptions = collect($xuiData)->filter(function ($subscription) {
            //Log::channel('cron')->info('🔁 Checking subscription', ['name' => $subscription['name'] ?? 'unknown']);

            if ($subscription['status'] !== true) {
                //Log::channel('cron')->info('🚫 Skipped: status not active');
                return false;
            }

            $hasTimeLimit = isset($subscription['time_limit'])
                && is_numeric($subscription['time_limit'])
                && $subscription['time_limit'] > 0;

            if (! $hasTimeLimit) {
                //Log::channel('cron')->info('🚫 Skipped: no valid time_limit');
                return false;
            }

            $expiryTimestamp = (int) ($subscription['time_limit'] / 1000);
            $threeDaysLater  = now()->addDays(3)->timestamp;

            $willExpire = $expiryTimestamp <= $threeDaysLater;
            //Log::channel('cron')->info('📅 Expiry Check', [
            //     'expiry' => $expiryTimestamp,
            //     'threshold' => $threeDaysLater,
            //     'result' => $willExpire
            // ]);

            return $willExpire;
        });

        if ($expiringSubscriptions->isEmpty()) {
            //Log::channel('cron')->info('✅ No expiring subscriptions found');
            return [];
        }

        //Log::channel('cron')->info('⚠️ Expiring subscriptions found', ['count' => $expiringSubscriptions->count()]);

        $message = $expiringSubscriptions->map(function ($subscription, $subId) {
            $expiryDate = toShamsi((int) ($subscription['time_limit'] / 1000));
            $subName    = get_clean_name($subscription['name']) ?? $subId;
            $usage      = $this->formatUsage($subscription);

            //Log::channel('cron')->info('📤 Building message block', [
            //     'sub_id' => $subId,
            //     'name' => $subName,
            //     'expiry' => $expiryDate,
            //     'usage' => $usage,
            // ]);

            $timeLeftDetails = calculate_time_left($subscription['time_limit']);
            $timeLeft = sprintf(
                '%d روز، %d ساعت و %d دقیقه',
                $timeLeftDetails['days'],
                $timeLeftDetails['hours'],
                $timeLeftDetails['minutes']
            );

            $panelBase = sprintf(
                "%s://%s:%s",
                env('XUI_SSL_ACTIVE') ? 'https' : 'http',
                env('XUI_SUB_DOMAIN'),
                env('XUI_SUB_PORT')
            );

            $subscriptionId = $subscription['subscription'] ?? '';
            $subUrl  = "{$panelBase}/" . env('XUI_SUB_PATH') . "/{$subscriptionId}";
            $jsonUrl = "{$panelBase}/" . env('XUI_SUB_JSON_PATH') . "/{$subscriptionId}";

            $expiryTimestamp = (int) ($subscription['time_limit'] / 1000);
            $isExpired = $expiryTimestamp < now()->timestamp;

            if ($isExpired) {
                return <<<MSG
⛔️ *اشتراک شما منقضی شده است!*

🔹 *نام اشتراک:* {$subName}
📅 *تاریخ انقضا:* {$expiryDate}

📊 *مصرف قبلی:* {$usage}

🔗 *لینک اشتراک قبلی:*
`{$subUrl}`

📌 برای تمدید روی این /renewal کلیک کنید یا از طریق منو گزینه **خرید یا تمدید اشتراک** را انتخاب کنید.
MSG;
            } else {
                return <<<MSG
🚨 *هشدار: اشتراک در حال انقضا!*

🔹 *نام اشتراک:* {$subName}
📅 *تاریخ انقضا:* {$expiryDate}
⏳ *زمان باقی‌مانده:* {$timeLeft}

📊 *مصرف فعلی:* {$usage}

🔗 *لینک اشتراک:*
`{$subUrl}`

📌 برای تمدید روی این /renewal کلیک کنید یا از طریق منو گزینه **خرید یا تمدید اشتراک** را انتخاب کنید.
MSG;
            }
        })->implode("\n\n━━━━━━━━━━━━━━\n\n");

        //Log::channel('cron')->info('✅ Final message prepared', ['tg_id' => $user->tg_id]);

        return [
            'tg_id'   => $user->tg_id,
            'message' => $message,
        ];
    }

    /**
     * فرمت کردن میزان مصرف
     */
    protected function formatUsage(array $subscription): string
    {
        $usage   = $subscription['usage'] ?? null;
        $totalGB = $subscription['totalGB'] ?? 0;

        if ($usage === null) {
            return 'نامحدود';
        }

        $totalFormatted = $totalGB > 0 ? number_format($totalGB / 1073741824, 2) . 'GB' : 'نامحدود';
        return number_format($usage, 2) . ' از ' . $totalFormatted;
    }

    public function calculateUserBandwidth()
    {
        $results = [];

        $xui = app(XUIDataService::class);
        $usersByTgId = $xui->getXUIUsersData();

        foreach ($usersByTgId as $tgId => $users) {
            foreach ($users as $user) {
                $used = $user['up'] + $user['down'];
                $limit = $user['total'] ?: $user['totalGB']; // در صورت خالی بودن یکی از دو مقدار
                $remain = $limit - $used;

                if ($limit <= 0) continue;

                $name = get_clean_name($user['email'] ?? 'کاربر');
                if ($used >= $limit && $user['enable']) {
                    $xui->disbaleUser($user['subId']);
                    $results[] = [
                        'tg_id' => $tgId,
                        'message' => "$name جان 😢،\n\n❌ حجم فیلترشکنت تموم شد و سرویس غیرفعال شد! برای ادامه، روی دکمه 'تمدید یا خرید اشتراک' بزن 💳"
                    ];
                } elseif ($remain < (env('WARNING_BANDLIMIT') * 1024 * 1024 * 1024)) {
                    $remainingGB = round($remain / (1024 * 1024 * 1024), 2);

                    $results[] = [
                        'tg_id' => $tgId,
                        'message' => "$name جان 😎،\n\n⚠️ فقط {$remainingGB} گیگابایت از حجم فیلترشکنت باقی مونده! برای شارژ سریع، روی دکمه 'تمدید یا خرید اشتراک' بزن 🚀"
                    ];
                }
            }
        }

        return $results;
    }
}
