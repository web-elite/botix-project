<?php

namespace App\Services;

use App\Models\User;
use App\Services\UserSyncService;

class UserService
{
    public function __construct(UserSyncService $userSyncService)
    {
        $this->syncService = $userSyncService;
    }

    /**
     * Retrieve synced XUI user data by Telegram ID.
     *
     * @param string $tgId
     * @param string $type - all | active | subscription ID | test
     * @return array
     */
    public function getUserXuiData(string $tgId, string $type = 'all'): array
    {
        $this->syncService->syncXuiUsers();
        $user    = User::where('tg_id', $tgId)->first();
        $xuiData = $user->xui_data ?? [];

        if ($type === 'all') {
            return $xuiData;
        }

        if ($type === 'active') {
            return collect($xuiData)->filter(function ($sub) {
                return $sub['status'] === true;
            })->toArray();
        }

        if ($type === 'test') {
            return collect($xuiData)
                ->filter(function ($sub, $key) {
                    $keyContainsTest = str_contains(strtolower($key), 'test');
                    $hasStatus = ($sub['status'] != 'deleted');
                    return $keyContainsTest && $hasStatus;
                })
                ->toArray(); // Return filtered results
        }

        return $xuiData[$type] ?? [];
    }

    /**
     * Determine if a given timestamp (ms) is expired.
     *
     * @param int $timeLimit
     * @return bool
     */
    private function isExpired(int $timeLimit): bool
    {
        return intval($timeLimit / 1000) <= time();
    }

    /**
     * Format user's subscription information.
     *
     * @param string $subId
     * @param array $data
     * @return string
     */
    public function formatUserSubInfo(string $subId, array $data): string
    {
        $statusMap = [
            'active'    => '✅ فعال',
            'expired'   => '❌ منقضی',
            'pending'   => '⏳ در انتظار فعالسازی',
            'suspended' => '⛔ غیرفعال',
            'canceled'  => '❌ لغو شده',
            'deleted'   => '❌ حذف شده',
            'unknown'   => '❓ ناشناخته',
        ];

        $timeLimit    = $data['time_limit'] ?? 0;
        $hasTimeLimit = $timeLimit > 0;
        $isExpired    = $hasTimeLimit && $this->isExpired($timeLimit);
        $userStatus   = $data['status'] ?? 'unknown';

        // وضعیت نهایی
        $status = match (true) {
            $userStatus === 'suspended' => $statusMap['suspended'],
            $userStatus === 'canceled'  => $statusMap['canceled'],
            $userStatus === 'deleted'   => $statusMap['deleted'],
            ! $hasTimeLimit             => $statusMap['active'],
            $isExpired                  => $statusMap['expired'],
            default                     => $statusMap['active'],
        };

        // نام پلن و حجم‌ها
        $planName     = get_clean_name($data['name']) ?? 'نامشخص';
        $uploadGB     = bytes_to_gb($data['upload'] ?? 0);
        $downloadGB   = bytes_to_gb($data['download'] ?? 0);
        $totalGBVal   = $data['totalGB'] ?? 0;
        $totalGB      = $totalGBVal > 0 ? bytes_to_gb($totalGBVal) . ' گیگ' : 'نامحدود';
        $usagePercent = number_format($data['usage'] ?? 0, 2);

        // تاریخ انقضا
        if ($hasTimeLimit) {
            $expiryDate      = toShamsi((int) ($timeLimit / 1000));
            $timeLeftDetails = calculate_time_left($timeLimit);
            $timeLeft        = $isExpired ? '' : sprintf(
                "(%d روز و %d ساعت و %d دقیقه دیگر باقی مانده)\n\n",
                $timeLeftDetails['days'],
                $timeLeftDetails['hours'],
                $timeLeftDetails['minutes']
            );

            if ($timeLeftDetails['days'] <= 3 && $timeLeftDetails['minutes'] >= 1) {
                $status .= " - ⚠️ در حال انقضا";
            }
        } else {
            $expiryDate = 'نامحدود';
            $timeLeft   = "\n\n";
        }

        // لینک اشتراک فقط اگر وضعیت غیرفعال یا لغو شده یا حذف شده نباشه
        if ($data['status']) {
            $panelBase = sprintf(
                "%s://%s:%s",
                env('XUI_SSL_ACTIVE') ? 'https' : 'http',
                env('XUI_SUB_DOMAIN'),
                env('XUI_SUB_PORT')
            );

            $subscriptionId = $data['subscription'] ?? '';
            $subUrl  = "{$panelBase}/" . env('XUI_SUB_PATH') . "/{$subscriptionId}";
            $jsonUrl = "{$panelBase}/" . env('XUI_SUB_JSON_PATH') . "/{$subscriptionId}";
        } else {
            $subUrl  = 'لینک اشتراک در دسترس نیست';
            $jsonUrl = 'لینک اشتراک در دسترس نیست';
        }

        return <<<INFO
━━━━━━━━━━━━━━━━━━━━
🔹 *کد اشتراک: {$subId}*
📛 *وضعیت*: {$status}
📌 *نام اشتراک*: {$planName}
📊 *مصرف*: {$usagePercent}% (آپلود: {$uploadGB} گیگ / دانلود: {$downloadGB} گیگ)
🧮 *حجم کل*: {$totalGB}
⏳ *تاریخ انقضا*: {$expiryDate}
{$timeLeft}🔗 *لینک اشتراک*:
`{$subUrl}`

INFO;
    }

    /**
     * Get all user subscriptions as formatted string.
     *
     * @param string $tgId
     * @return string
     */
    public function getUserSubscriptions(string $tgId): string
    {
        $result        = '';
        $subscriptions = $this->getUserXuiData($tgId, 'active');

        $statusMap = [
            'active'    => '✅',
            'expired'   => '❌',
            'pending'   => '⏳',
            'suspended' => '⛔',
            'canceled'  => '❌',
            'deleted'   => '❌',
            'unknown'   => '❓',
        ];

        foreach ($subscriptions as $subId => $data) {
            $planName = get_clean_name($data['name']) ?? 'نامشخص';

            $panelBase = sprintf(
                "%s://%s:%s",
                env('XUI_SSL_ACTIVE') ? 'https' : 'http',
                env('XUI_SUB_DOMAIN'),
                env('XUI_SUB_PORT')
            );

            $subscriptionId = $data['subscription'] ?? '';
            $subUrl         = "{$panelBase}/" . env('XUI_SUB_PATH') . "/{$subscriptionId}";
            $jsonUrl        = "{$panelBase}/" . env('XUI_SUB_JSON_PATH') . "/{$subscriptionId}";

            $timeLimit    = $data['time_limit'] ?? 0;
            $hasTimeLimit = $timeLimit > 0;
            $isExpired    = $hasTimeLimit && $this->isExpired($timeLimit);
            $userStatus   = $data['status'] ?? null;

            $status = match (true) {
                $userStatus === 'suspended' => $statusMap['suspended'],
                $userStatus === 'canceled' => $statusMap['canceled'],
                $userStatus === 'deleted' => $statusMap['deleted'],
                ! $hasTimeLimit => $statusMap['active'],
                $isExpired => $statusMap['expired'],
                default => $statusMap['active'],
            };

            $result .= "{$status} {$planName}:\n🔗 *لینک معمولی* (`{$subUrl}`)\n🔗 *لینک حرفه‌ای* (`{$jsonUrl}`)\n\n";
        }

        return $result;
    }
}
