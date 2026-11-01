<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes & Scheduler
|--------------------------------------------------------------------------
|
| يتم فحص وإرسال التنبيهات الفورية للإقامات (<=45 يوم) والعقود (<=90 يوم)
| دورياً كل يوم عند الساعة 8:00 صباحاً بتوقيت مكة المكرمة.
|
*/

Schedule::command('alerts:send')
    ->dailyAt('08:00')
    ->timezone('Asia/Riyadh')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/alerts_cron.log'));
