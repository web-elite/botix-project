<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

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
        $xuiUser = $user->meta['xui_data'] ?? null;
        return $xuiUser;
    }

    public function formatUserSubInfo($subId, $data)
    {
        // وضعیت اشتراک
        $status = $data['status'] ? "✅ فعال" : "❌ غیرفعال";
        $planName = $data['name'];

        // محاسبه ترافیک (تبدیل به گیگابایت)
        $uploadGB = bytes_to_gb($data['upload'] ?? 0);
        $downloadGB = bytes_to_gb($data['download'] ?? 0);
        $totalGB = bytes_to_gb($data['totalGB'] ?? 0);

        // محاسبه زمان باقی‌مانده
        $timeLeft = calculate_time_left($data['time_limit']);

        // محاسبه درصد مصرف
        $usagePercent = number_format(($data['usage'] ?? 0), 2);

        // تاریخ انقضا
        $expiryDate = date("Y-m-d H:i:s", ($data['time_limit'] ?? 0) / 1000);

        // ساخت لینک‌های مربوط به اشتراک
        $panelBase = (env('XUI_SSL_ACTIVE') ? 'https://' : 'http://') . env('XUI_SUB_DOMAIN') . ':' . env('XUI_SUB_PORT');
        $subUrl = $panelBase . '/' . env('XUI_SUB_PATH') . '/' . ($data['subscription'] ?? '');
        $jsonUrl = $panelBase . '/' . env('XUI_SUB_JSON_PATH') . '/' . ($data['subscription'] ?? '');

        return "━━━━━━━━━━━━━━━━━━━━\n" .
            "🔹 *کد اشتراک: " . $subId . "*\n" .
            "📛 *وضعیت*: $status\n" .
            "📌 *نام اشتراک*: $planName\n" .
            "📊 *مصرف*: $usagePercent% (آپلود: $uploadGB گیگ / دانلود: $downloadGB گیگ)\n" .
            "🧮 *حجم کل*: $totalGB گیگ\n" .
            "⏳ *تاریخ انقضا*: $expiryDate\n({$timeLeft['days']} روز و {$timeLeft['hours']} ساعت و {$timeLeft['minutes']} دقیقه دیگر باقی مانده)\n\n" .
            "🔗 *لینک معمولی*:\n`$subUrl`\n" .
            "🔗 *لینک حرفه‌ای*:\n`$jsonUrl`\n\n";
    }
}
