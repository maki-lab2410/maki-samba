<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $table = 'settings';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    /**
     * Cache runtime sementara agar tidak query berulang dalam 1 lifecycle request
     */
    protected static array $runtimeCache = [];

    /**
     * Ambil nilai setting berdasarkan key dengan fallback ke .env / config / default
     */
    public static function get(string $key, $default = null): ?string
    {
        if (array_key_exists($key, static::$runtimeCache)) {
            return static::$runtimeCache[$key];
        }

        try {
            if (Schema::hasTable('settings')) {
                $record = static::find($key);
                if ($record && $record->value !== null && $record->value !== '') {
                    static::$runtimeCache[$key] = $record->value;
                    return $record->value;
                }
            }
        } catch (\Throwable $e) {
            // Fail gracefully jika koneksi DB atau tabel belum siap
        }

        // Fallback ke .env menggunakan format APP_KEY
        $envKey = 'APP_' . strtoupper($key);
        $val = env($envKey, config("app.{$key}", $default));
        static::$runtimeCache[$key] = $val;
        return $val;
    }

    /**
     * Simpan / Perbarui nilai setting
     */
    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        static::$runtimeCache[$key] = $value;
    }

    /**
     * Ambil seluruh konfigurasi instansi & aplikasi dalam 1 array terstruktur
     */
    public static function getAllSettings(): array
    {
        return [
            'app_title'          => static::get('app_title', 'Maki Samba'),
            'institution_name'   => static::get('institution_name', 'Laboratorium Rekam Medis & Informasi Kesehatan (RMIK)'),
            'institution_short'  => static::get('institution_short', 'Lab RMIK'),
            'app_description'    => static::get('app_description', 'Portal File Manager & Otomatisasi Samba'),
            'app_logo'           => static::get('app_logo', null),
            'app_theme'          => static::get('app_theme', 'blue'),
            'samba_server_host'  => static::get('samba_server_host', null),
        ];
    }

    /**
     * Dapatkan IP atau hostname server Samba (LAN)
     * Prioritas:
     * 1. Nilai yang disetel admin di menu Pengaturan (database)
     * 2. Nilai SAMBA_SERVER_HOST di .env jika ada dan bukan placeholder default 192.168.1.100
     * 3. Host / IP yang sedang diakses saat ini dari browser pengguna (misal 192.168.1.100)
     * 4. SERVER_ADDR dari web server
     */
    public static function getSambaHost(): string
    {
        $dbHost = static::get('samba_server_host');
        if (!empty($dbHost)) {
            return trim($dbHost);
        }

        $envHost = env('SAMBA_SERVER_HOST');
        if (!empty($envHost) && $envHost !== '192.168.1.100') {
            return trim($envHost);
        }

        try {
            if (request()) {
                $reqHost = request()->getHost();
                if (!empty($reqHost) && !in_array($reqHost, ['localhost', '127.0.0.1'])) {
                    return $reqHost;
                }
            }
        } catch (\Throwable $e) {}

        $serverAddr = $_SERVER['SERVER_ADDR'] ?? null;
        if (!empty($serverAddr) && $serverAddr !== '127.0.0.1') {
            return $serverAddr;
        }

        return !empty($envHost) ? $envHost : '192.168.1.100';
    }
}
