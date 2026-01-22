<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Infrastructure\Analytics\ClickAggregator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('clicks:flush', function (ClickAggregator $aggregator) {
    $count = $aggregator->flushDailyCounts();
    $this->info("Flushed {$count} clicks.");
})->purpose('Flush aggregated daily click counters to the database');
