<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name & Environment
    |--------------------------------------------------------------------------
    */
    'name' => env('APP_NAME', 'Maki Samba'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Identitas Instansi & Aplikasi (Dapat Dikustomisasi untuk Open Source)
    |--------------------------------------------------------------------------
    */
    'institution' => env('APP_INSTITUTION', 'Laboratorium Rekam Medis & Informasi Kesehatan (RMIK)'),
    'institution_short' => env('APP_INSTITUTION_SHORT', 'Lab RMIK'),
    'description' => env('APP_DESCRIPTION', 'Portal File Manager & Otomatisasi Samba'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone & Locale (WIB - Waktu Indonesia Barat / Asia/Jakarta)
    |--------------------------------------------------------------------------
    */
    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),
    'locale' => env('APP_LOCALE', 'id'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'id_ID'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key & Cipher
    |--------------------------------------------------------------------------
    */
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    'maintenance' => [
        'driver' => 'file',
    ],

];
