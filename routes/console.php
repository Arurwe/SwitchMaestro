<?php

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');



try {
    $settings = Setting::all()->pluck('value', 'key');
    
    $backupTime = $settings->get('backup_schedule_time', '03:00');
    $retentionDays = $settings->get('backup_retention_days', 30);
    $oldBackupTime = $settings->get('prune_old_backup_schedule_time', '04:00');

    //  Trigger Backups
    Schedule::command('backup:trigger')
        ->everyMinute() 
        ->when(function () use ($backupTime) {
            return Carbon::now()->format('H:i') === $backupTime;
        })
        ->withoutOverlapping()
        ->onOneServer();

    // Prune Backups

    Schedule::command("backup:prune --days={$retentionDays}")
        ->everyMinute() 
        ->when(function () use ($oldBackupTime) {
            return Carbon::now()->format('H:i') === $oldBackupTime;
        })
        ->withoutOverlapping()
        ->onOneServer();

} catch (\Exception $e) {
    Log::error("Nie można załadować dynamicznego harmonogramu z bazy danych: " . $e->getMessage());
    Schedule::command('backup:trigger')->dailyAt('03:00');
    Schedule::command('backup:prune')->dailyAt('04:00');
}