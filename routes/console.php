<?php

use App\Console\Commands\BackupDatabase;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Backup database setiap hari pukul 02:00
Schedule::command(BackupDatabase::class)
    ->dailyAt('02:00')
    ->emailOutputOnFailure(config('mail.from.address'))
    ->withoutOverlapping()
    ->runInBackground();
