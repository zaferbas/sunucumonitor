<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Saatlik metrik özetleme
Schedule::command('metrics:aggregate --period=hourly')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Günlük metrik özetleme
Schedule::command('metrics:aggregate --period=daily')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->runInBackground();

// Eski verileri temizleme
Schedule::command('metrics:prune')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground();
