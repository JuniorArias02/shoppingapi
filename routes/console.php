<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar limpieza de pedidos expirados cada 5 minutos
Schedule::command('pedidos:limpiar-expirados')->everyFiveMinutes();

// Programar recordatorio de carritos abandonados (12:00 PM)
Schedule::command('emails:send-abandoned-cart-reminders')->dailyAt('12:00');
