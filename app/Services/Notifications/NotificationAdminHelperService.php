<?php
namespace App\Services\Notifications;

class NotificationAdminHelperService
{

    /**
     * @var NotificationCoreService
     */
    protected NotificationCoreService $notif;

    /**
     * NotificationAdminHelperService constructor.
     *
     * @param NotificationCoreService $notif
     */
    public function __construct(NotificationCoreService $notif)
    {
        $this->notif = $notif;
    }

    /**
     * Send a notification to all admins.
     *
     * @param string $message
     * @return void
     */

    public function sendTelegramNotification(string $message)
    {
        $finalMsg = "🔔 اعلان جدید:\n\n" . $message;

        $adminIds = get_admin_ids();
        collect($adminIds)->each(function ($adminId) use ($finalMsg) {
            resolve(NotificationCoreService::class)->sendTelegramNotification($adminId, $finalMsg);
        });
    }
}
