<?php

use App\Http\Middleware\Peran;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Codespaces (dan reverse proxy lain) mengirim header
        // X-Forwarded-Proto/Host. Tanpa trustProxies, asset()/route()
        // membentuk URL pakai host lokal sehingga CSS/JS tidak ke-load
        // saat diakses lewat URL *.app.github.dev.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'peran' => Peran::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
