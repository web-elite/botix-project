<?php
namespace App\Services;

use App\Models\User;
use App\Services\UserSyncService;

class UserService
{
    /**
     * Retrieve synced XUI user data by Telegram ID.
     *
     * @param string $tgId
     * @return array
     */
    public function getUserXuiData(string $tgId): array
    {
        (new UserSyncService)->syncXuiUsers();

        $user = User::where('tg_id', $tgId)->first();

        return $user->meta['xui_data'] ?? [];
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
        $userStatus   = $data['status'] ?? null;

        // Determine subscription status
        $status = match (true) {
            $userStatus === 'suspended' => $statusMap['suspended'],
            $userStatus === 'canceled' => $statusMap['canceled'],
            $userStatus === 'deleted' => $statusMap['deleted'],
            ! $hasTimeLimit => $statusMap['active'],
            $isExpired => $statusMap['expired'],
            default => $statusMap['active'],
        };

        $planName     = $data['name'] ?? 'نامشخص';
        $uploadGB     = bytes_to_gb($data['upload'] ?? 0);
        $downloadGB   = bytes_to_gb($data['download'] ?? 0);
        $totalGBVal   = $data['totalGB'] ?? 0;
        $totalGB      = $totalGBVal > 0 ? bytes_to_gb($totalGBVal) . ' گیگ' : 'نامحدود';
        $usagePercent = number_format($data['usage'] ?? 0, 2);

        // Time left or expiry
        if ($hasTimeLimit) {
            $expiryDate = date("Y-m-d H:i:s", $timeLimit / 1000);

            $timeLeft = $isExpired
            ? ''
            : sprintf(
                "(%d روز و %d ساعت و %d دقیقه دیگر باقی مانده)\n\n",
                calculate_time_left($timeLimit)['days'],
                calculate_time_left($timeLimit)['hours'],
                calculate_time_left($timeLimit)['minutes']
            );
        } else {
            $expiryDate = 'نامحدود';
            $timeLeft   = "\n\n";
        }

        // Subscription links
        $panelBase = sprintf(
            "%s://%s:%s",
            env('XUI_SSL_ACTIVE') ? 'https' : 'http',
            env('XUI_SUB_DOMAIN'),
            env('XUI_SUB_PORT')
        );

        $subscriptionId = $data['subscription'] ?? '';
        $subUrl         = "{$panelBase}/" . env('XUI_SUB_PATH') . "/{$subscriptionId}";
        $jsonUrl        = "{$panelBase}/" . env('XUI_SUB_JSON_PATH') . "/{$subscriptionId}";

        return <<<INFO
━━━━━━━━━━━━━━━━━━━━
🔹 *کد اشتراک: {$subId}*
📛 *وضعیت*: {$status}
📌 *نام اشتراک*: {$planName}
📊 *مصرف*: {$usagePercent}% (آپلود: {$uploadGB} گیگ / دانلود: {$downloadGB} گیگ)
🧮 *حجم کل*: {$totalGB}
⏳ *تاریخ انقضا*: {$expiryDate}
{$timeLeft}🔗 *لینک معمولی*:
`{$subUrl}`
🔗 *لینک حرفه‌ای*:
`{$jsonUrl}`

INFO;
    }
}
