<?php

// Pastikan zona waktu PHP default menggunakan Asia/Jakarta (WIB)
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Percayai header reverse proxy (X-Forwarded-Host/Port/Proto) dari 43.173.41.238:888
        $middleware->trustProxies(at: '*');

        // Bagikan data pengaturan instansi ke seluruh views
        $middleware->web(append: [
            \App\Http\Middleware\ShareSettingsToViews::class,
        ]);

        // Registrasi middleware alias untuk proteksi route Maki Samba
        $middleware->alias([
            'samba.auth' => \App\Http\Middleware\SambaAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Tangani TokenMismatchException (419 Page Expired) secara graceful tanpa layar error mentah
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->is('logout')) {
                return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
            }
            return redirect()->route('login')->with('warning', 'Sesi Anda telah berakhir. Silakan login kembali.');
        });
    })->create();
