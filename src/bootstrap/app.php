<?php

use App\Http\Middleware\VerifyLineLiffToken;
use App\Http\Middleware\VerifyLineMessagingSignature;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('spotrem:option-reminder')->dailyAt('09:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // Render 等のリバースプロキシ背後で https / 署名付き URL（X-Forwarded-*）を正しく扱う
        $middleware->trustProxies(at: '*');

        // 管理画面のセッション認証でゲストに誘導する（当面は admin ログインのみ）
        $middleware->redirectGuestsTo(fn () => route('admin.login'));

        $middleware->validateCsrfTokens(except: [
            'line/webhook',
        ]);

        $middleware->alias([
            'line.liff' => VerifyLineLiffToken::class,
            'line.messaging.signature' => VerifyLineMessagingSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
