<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Process email chains every 5 minutes
        $schedule->command('email:process-chains')->everyFiveMinutes();

        // Expire old coupons daily at midnight
        $schedule->command('coupons:expire-old')->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
