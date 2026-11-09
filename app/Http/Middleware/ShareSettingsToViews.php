<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Models\Setting;
use Symfony\Component\HttpFoundation\Response;

class ShareSettingsToViews
{
    /**
     * Handle an incoming request.
     * Bagikan variabel nama instansi dan pengaturan aplikasi secara global ke seluruh Blade views.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = Setting::getAllSettings();

        View::share('appTitle', $settings['app_title']);
        View::share('institutionName', $settings['institution_name']);
        View::share('institutionShort', $settings['institution_short']);
        View::share('appDescription', $settings['app_description']);
        View::share('appLogo', $settings['app_logo'] ?? null);
        View::share('appTheme', $settings['app_theme'] ?? 'blue');
        View::share('sambaServerHost', Setting::getSambaHost());
        View::share('appSettings', $settings);

        return $next($request);
    }
}
