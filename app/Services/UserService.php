<?php
namespace App\Services;

use App\Models\User;

class UserService
{
    /**
     * Get user data from the database.
     *
     * @param string $tgId
     * @return array
     */
    public function getUserXuiData($tgId)
    {
        $sync = new UserSyncService;
        $sync->syncXuiUsers();
        $user = User::where('tg_id', $tgId)->first();
        if (! $user) {
            return [];
        }
        $xuiUser = $user->meta['xui_data'] ?? null;
        return $xuiUser;
    }

    private function is_expired($timeLimit): bool
    {
        return intval($timeLimit / 1000) <= time();
    }

    public function formatUserSubInfo($subId, $data)
    {
        // محاسبه درصد مصرف
        $usagePercent = number_format(($data['usage'] ?? 0), 2);

        // محاسبه زمان باقی‌مانده
        $calcTimeLeft = calculate_time_left($data['time_limit']);
        $nowTimestamp = time() * 1000;

        $hasTimeLimit = isset($data['time_limit']) && $data['time_limit'] > 0;
        $isExpired    = $hasTimeLimit && $data['time_limit'] < $nowTimestamp;

        // وضعیت اشتراک
        $status   = $this->is_expired($data['time_limit'] ?? 0) ? "❌ منقضی" : "✅ فعال";
        $planName = $data['name'];

        // محاسبه ترافیک
        $uploadGB   = bytes_to_gb($data['upload'] ?? 0);
        $downloadGB = bytes_to_gb($data['download'] ?? 0);
        $totalGB    = bytes_to_gb($data['totalGB'] ?? 0);
        $totalGB    = $totalGB > 0 ? $totalGB . ' گیگ' : "نامحدود";

        // درصد مصرف
        $usagePercent = number_format(($data['usage'] ?? 0), 2);

        // محاسبه زمان باقی‌مانده
        if ($hasTimeLimit) {
            if (! $isExpired) {
                $calcTimeLeft = calculate_time_left($data['time_limit']);

                $timeLeft   = "({$calcTimeLeft['days']} روز و {$calcTimeLeft['hours']} ساعت و {$calcTimeLeft['minutes']} دقیقه دیگر باقی مانده)\n\n";
                $expiryDate = date("Y-m-d H:i:s", $data['time_limit'] / 1000);
            } else {
                $timeLeft   = "\n";
                $expiryDate = "⛔ منقضی شده";
            }
        } else {
            $timeLeft   = "\n";
            $expiryDate = "نامحدود";
        }

        // ساخت لینک‌های مربوط به اشتراک
        $panelBase = (env('XUI_SSL_ACTIVE') ? 'https://' : 'http://') . env('XUI_SUB_DOMAIN') . ':' . env('XUI_SUB_PORT');
        $subUrl    = $panelBase . '/' . env('XUI_SUB_PATH') . '/' . ($data['subscription'] ?? '');
        $jsonUrl   = $panelBase . '/' . env('XUI_SUB_JSON_PATH') . '/' . ($data['subscription'] ?? '');

        return "━━━━━━━━━━━━━━━━━━━━\n" .
            "🔹 *کد اشتراک: " . $subId . "*\n" .
            "📛 *وضعیت*: $status\n" .
            "📌 *نام اشتراک*: $planName\n" .
            "📊 *مصرف*: $usagePercent% (آپلود: $uploadGB گیگ / دانلود: $downloadGB گیگ)\n" .
            "🧮 *حجم کل*: $totalGB\n" .
            "⏳ *تاریخ انقضا*: $expiryDate\n$timeLeft" .
            "🔗 *لینک معمولی*:\n`$subUrl`\n" .
            "🔗 *لینک حرفه‌ای*:\n`$jsonUrl`\n\n";
    }
}
