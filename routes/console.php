<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// T5: daily anchor of the audit hash chain
Illuminate\Support\Facades\Schedule::command('audit:anchor')->dailyAt('23:55');
