<?php

use App\Jobs\SyncCdekTrackingJob;
use App\Models\Shipment;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    Shipment::query()
        ->where('provider', 'cdek')
        ->whereIn('status', ['created', 'in_transit'])
        ->whereNotNull('external_id')
        ->select('id')
        ->each(fn (Shipment $shipment) => SyncCdekTrackingJob::dispatch($shipment->id));
})->name('sync-cdek-tracking')->everyTenMinutes()->withoutOverlapping();
