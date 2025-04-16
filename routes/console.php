<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('users:sync', function () {
    $this->comment('Syncing users...');
})->purpose('Sync users with Telegram and Other Panels every 6 hours')->hourly();

Artisan::command('notify:expiring-subscriptions', function () {
    $this->comment('Notifying users about expiring subscriptions...');
})->purpose('Notifying users daily')->daily();
