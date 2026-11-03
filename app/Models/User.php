<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'nim',
        'name',
        'password',
        'role', // 'admin', 'laboran', 'dosen', 'mahasiswa'
        'semester',
        'kelas',
        'quota_limit',
        'quota_used',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'semester' => 'integer',
            'quota_limit' => 'integer',
            'quota_used' => 'integer',
        ];
    }

    /**
     * Dapatkan tahun angkatan dari NIM (contoh: 202401001 -> 2024)
     */
    public function getAngkatanAttribute(): ?string
    {
        if (preg_match('/\.(\d{4})\./', $this->nim, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || strtolower($this->nim) === 'admin';
    }

    public function isLaboran(): bool
    {
        return $this->role === 'laboran' || ($this->role !== 'dosen' && strtolower($this->nim) === 'laboran');
    }

    public function isDosen(): bool
    {
        return $this->role === 'dosen';
    }

    public function isMahasiswa(): bool
    {
        return $this->role === 'mahasiswa';
    }

    public function isUnlimitedQuota(): bool
    {
        return $this->isAdmin() || $this->quota_limit === 0;
    }

    /**
     * Hitung persentase kuota terpakai (0 - 100%)
     */
    public function getQuotaPercentAttribute(): float
    {
        if ($this->isUnlimitedQuota()) {
            return 0.0;
        }

        $defaultLimit = 1073741824; // Default 1 GB Mahasiswa
        if ($this->isLaboran()) {
            $defaultLimit = 10737418240; // 10 GB Laboran
        } elseif ($this->isDosen()) {
            $defaultLimit = 5368709120; // 5 GB Dosen
        }

        $limit = $this->quota_limit > 0 ? $this->quota_limit : $defaultLimit;
        return (float) min(100, round(($this->quota_used / $limit) * 100, 1));
    }

    public function getQuotaLimitFormattedAttribute(): string
    {
        if ($this->isUnlimitedQuota()) {
            return 'Unlimited';
        }
        $limit = $this->quota_limit;
        if (!$limit || $limit <= 0) {
            if ($this->isLaboran()) {
                $limit = 10737418240; // 10 GB
            } elseif ($this->isDosen()) {
                $limit = 5368709120; // 5 GB
            } else {
                $limit = 1073741824; // 1 GB
            }
        }
        return $this->formatBytes($limit);
    }

    public function getQuotaUsedFormattedAttribute(): string
    {
        return $this->formatBytes($this->quota_used ?: 0);
    }

    /**
     * Nama share Samba LAN dinamis
     */
    public function getSambaSharePathAttribute(): string
    {
        if ($this->isLaboran() || $this->isDosen() || $this->isAdmin()) {
            return env('SAMBA_OPERASIONAL_SHARE', 'operasional');
        }
        return env('SAMBA_MAHASISWA_SHARE', 'mahasiswa');
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return number_format($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)       return number_format($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
