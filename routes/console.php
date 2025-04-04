<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('users:sync', function () {
    $this->comment('Syncing users...');
})->purpose('Sync users with Telegram and Other Panels every 6 hours')->hourly();
