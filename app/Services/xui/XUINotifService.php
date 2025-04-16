<?php
namespace App\Services\xui;

use App\Models\User;
use App\Services\UserSyncService;

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
        if (empty($user->tg_id)) {
            return [];
        }

        $xuiData = $user->xui_data ?? [];

        $expiringSubscriptions = collect($xuiData)->filter(function ($subscription) {
            $hasTimeLimit = isset($subscription['time_limit'])
            && is_numeric($subscription['time_limit'])
            && $subscription['time_limit'] > 0;

            if (! $hasTimeLimit) {
                return false;
            }

            $expiryTimestamp = (int) ($subscription['time_limit'] / 1000);
            $threeDaysLater  = now()->addDays(3)->timestamp;
            // $expiryTimestamp <= time() -------- for expire user
            return $expiryTimestamp <= $threeDaysLater;
        });

        if ($expiringSubscriptions->isEmpty()) {
            return [];
        }

        $message = $expiringSubscriptions->map(function ($subscription, $subId) {
            $expiryDate = date('Y-m-d H:i', (int) ($subscription['time_limit'] / 1000));
            $subName    = get_clean_name($subscription['name']) ?? $subId;

            return "⚠️ اشتراک \"{$subName}\" تا {$expiryDate} منقضی می‌شود\n"
                . "📊 مصرف: {$this->formatUsage($subscription)}";
        })->implode("\n\n");

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

}
