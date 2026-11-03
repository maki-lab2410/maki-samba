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
            'app_title'        => static::get('app_title', 'Maki Samba'),
            'institution_name' => static::get('institution_name', 'Laboratorium Rekam Medis & Informasi Kesehatan (RMIK)'),
            'institution_short'=> static::get('institution_short', 'Lab RMIK'),
            'app_description'  => static::get('app_description', 'Portal File Manager & Otomatisasi Samba'),
            'app_logo'         => static::get('app_logo', null),
            'app_theme'        => static::get('app_theme', 'blue'),
        ];
    }
}
