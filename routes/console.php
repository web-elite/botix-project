<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('users:sync')->hourly();
Schedule::command('calc:time')->daily();
Schedule::command('calc:bandwidth')->everyMinute();
