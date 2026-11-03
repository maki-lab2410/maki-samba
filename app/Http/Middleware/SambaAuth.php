<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class SambaAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Sinkronisasi Sesi & Auth Guard
        if (!Auth::check() && Session::has('samba_nim')) {
            $user = User::where('nim', Session::get('samba_nim'))->first();
            if ($user) {
                Auth::login($user);
            }
        }

        // 2. Proteksi Akses
        if (!Auth::check() && !Session::get('samba_logged_in')) {
            return redirect()->route('login')
                ->with('error', 'Silakan login terlebih dahulu untuk mengakses portal.');
        }

        return $next($request);
    }
}
