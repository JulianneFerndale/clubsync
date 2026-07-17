<?php

use App\Jobs\DetectClubViolationsJob;
use App\Jobs\SemesterPresenceReminderJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SemesterPresenceReminderJob)->dailyAt('08:00');
Schedule::job(new DetectClubViolationsJob)->dailyAt('08:00');

// Prune transient data and alert admins when the database nears its cap.
Schedule::command('retention:check')->dailyAt('02:00');
