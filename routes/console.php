<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Odoo Auto-Sync Schedule
// Uses a dynamic Schedule::call() that checks DB settings at runtime each minute.
// This means enabling/changing auto-sync from the UI takes effect within 1 minute
// without needing to restart the scheduler container.
Schedule::call(function () {
    try {
        $odooSetting = \App\Models\OdooSetting::first();

        if (!$odooSetting || !$odooSetting->enable_auto_sync) {
            return; // Auto-sync disabled, skip
        }

        $interval = $odooSetting->sync_interval ?: 'daily';
        $lastSync = $odooSetting->last_sync ? Carbon::parse($odooSetting->last_sync) : null;
        $now = Carbon::now();

        // Determine if enough time has passed since the last sync
        $shouldRun = match ($interval) {
            'hourly'        => !$lastSync || $lastSync->diffInMinutes($now) >= 60,
            'every_2_hours' => !$lastSync || $lastSync->diffInMinutes($now) >= 120,
            'every_4_hours' => !$lastSync || $lastSync->diffInMinutes($now) >= 240,
            'every_6_hours' => !$lastSync || $lastSync->diffInMinutes($now) >= 360,
            'every_12_hours'=> !$lastSync || $lastSync->diffInMinutes($now) >= 720,
            'daily'         => !$lastSync || $lastSync->diffInHours($now) >= 24,
            default         => !$lastSync || $lastSync->diffInHours($now) >= 24,
        };

        if ($shouldRun) {
            \Illuminate\Support\Facades\Log::info('Triggering Scheduled Odoo Syncs...');
            
            // 1. Sync Job Orders and Bills (The one configured in the Maintenance UI)
            Artisan::call('maintenance:sync-odoo');
            
            // 2. Sync Inventory Data (Legacy/Other)
            Artisan::call('odoo:sync', ['--force' => true]);

            // 3. Sync Vehicle Rental Starts & KM
            Artisan::call('maintenance:sync-rental-starts');
        }
    } catch (\Exception $e) {
        // Database not available or other error - skip silently
        \Illuminate\Support\Facades\Log::warning('Odoo scheduler check failed: ' . $e->getMessage());
    }
})->everyMinute()->name('odoo-auto-sync-check');
