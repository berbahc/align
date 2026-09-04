<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ist das Semester vorbei, kommt zurück, was seine Kurse verdrängt hatten.
Schedule::command('habits:restore-displaced')->dailyAt('04:00');
