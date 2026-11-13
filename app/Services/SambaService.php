<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class SambaService
{
    /**
     * Absolute base path untuk home directory Samba mahasiswa di AlmaLinux
     */
    protected string $baseStoragePath;

    /**
     * Path script bash automation
     */
    protected string $scriptPath;

    public function __construct()
    {
        $this->baseStoragePath = env('SAMBA_BASE_PATH', '/samba/mahasiswa');
        $this->scriptPath = env('SAMBA_SCRIPT_PATH', '/usr/local/bin/buat_akun_samba.sh');
    }

    /**
     * Validasi format NIM / Username untuk keamanan berlapis sebelum shell_exec
     */
    public function isValidNim(string $nim): bool
    {
        // Hanya memperbolehkan alfanumerik, titik, underscore, dan dash (Contoh: A11.2024.12345, rmik202401, laboran, admin)
        return (bool) preg_match('/^[a-zA-Z0-9._-]{3,30}$/', $nim);
    }

    /**
     * Cek apakah direktori user sudah ada di filesystem Linux
     */
    public function studentDirectoryExists(string $nim): bool
    {
        if (!$this->isValidNim($nim)) {
            return false;
        }

        if (strtolower($nim) === 'laboran' || strtolower($nim) === 'admin') {
            $path = env('SAMBA_OPERASIONAL_PATH', '/samba/operasional');
            return File::isDirectory($path) || is_dir($path);
        }

        $studentPath = rtrim($this->baseStoragePath, '/') . '/' . $nim;
        return File::isDirectory($studentPath) || is_dir($studentPath);
    }

    /**
     * Eksekusi shell command untuk pembuatan akun Linux & Samba
     * 
     * Command: sudo /usr/local/bin/buat_akun_samba.sh <NIM> <PASSWORD> [ROLE]
     * 
     * @param string $nim
     * @param string $password
     * @param string $role (mahasiswa, laboran, admin)
     * @return array ['success' => bool, 'message' => string, 'output' => string]
     */
    public function createSambaAccount(string $nim, string $password, string $role = 'mahasiswa'): array
    {
        // 1. Validasi NIM format ketat untuk mencegah payload injection
        if (!$this->isValidNim($nim)) {
            return [
                'success' => false,
                'message' => 'Format NIM/Username tidak valid. Gunakan huruf, angka, titik, atau strip.',
                'output'  => '',
            ];
        }

        // Tentukan role otomatis jika username adalah laboran atau admin
        if (strtolower($nim) === 'laboran') {
            $role = 'laboran';
        } elseif (strtolower($nim) === 'admin') {
            $role = 'admin';
        }

        // Pastikan folder fisik user tersedia dengan izin 0777 di server
        $operasionalPath = rtrim(env('SAMBA_OPERASIONAL_PATH', '/samba/operasional'), '/');
        if ($role === 'admin') {
            $physicalDir = $operasionalPath;
        } elseif (in_array($role, ['laboran', 'dosen'], true)) {
            $physicalDir = $operasionalPath . '/' . $nim;
        } else {
            $physicalDir = rtrim($this->baseStoragePath, '/') . '/' . $nim;
        }
        if (!File::isDirectory($physicalDir)) {
            @File::makeDirectory($physicalDir, 0777, true, true);
        }
        @chmod($physicalDir, 0777);

        // 2. Mode Simulasi / Development Fallback:
        // Jika script bash belum dipasang di server lokal/dev atau SAMBA_MOCK_SHELL=true atau proc_open dinonaktifkan
        $mockMode = env('SAMBA_MOCK_SHELL', false);
        if ($mockMode || !function_exists('proc_open') || (!file_exists($this->scriptPath) && !windows_os() && !is_executable($this->scriptPath))) {
            // Hapus folder salah di mahasiswa jika mensimulasikan laboran
            if ($role === 'laboran') {
                $wrongDir = rtrim($this->baseStoragePath, '/') . '/laboran';
                if (File::isDirectory($wrongDir)) {
                    @File::deleteDirectory($wrongDir);
                }
            }

            Log::info("Samba Mock/PHP Mode: Akun {$nim} ({$role}) dipastikan di {$physicalDir}");
            return [
                'success' => true,
                'message' => "Akun Samba untuk {$nim} ({$role}) berhasil disiapkan.",
                'output'  => "Folder {$physicalDir} ready.",
            ];
        }

        // 3. KEAMANAN KRITIS: Sanitasi parameter dengan escapeshellarg()
        $safeNim  = escapeshellarg($nim);
        $safeRole = escapeshellarg($role);

        // Password dipassing secara aman melalui STDIN (bukan argumen command line) untuk mencegah terlihat di tabel proses (ps/proc)
        $command = "sudo {$this->scriptPath} {$safeNim} - {$safeRole}";

        Log::info("Menjalankan Bash Automation Samba: sudo {$this->scriptPath} {$safeNim} - {$safeRole}");

        // 4. Eksekusi perintah Linux OS menggunakan proc_open via standard input
        $descriptorSpec = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $outputString = '';
        $returnStatus = -1;

        try {
            $process = proc_open($command, $descriptorSpec, $pipes);

            if (is_resource($process)) {
                // Tulis password ke STDIN
                fwrite($pipes[0], $password . "\n");
                fclose($pipes[0]);

                $stdout = stream_get_contents($pipes[1]);
                fclose($pipes[1]);

                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[2]);

                $returnStatus = proc_close($process);
                $outputString = trim($stdout . ($stderr ? "\n" . $stderr : ''));
            } else {
                $outputString = 'Gagal menginisialisasi subproses OS.';
            }
        } catch (\Throwable $e) {
            $outputString = 'Eksekusi subproses tidak tersedia: ' . $e->getMessage();
            $returnStatus = -1;
        }

        if ($returnStatus === 0) {
            Log::info("Samba Automation Sukses [{$nim} - {$role}]: {$outputString}");
            return [
                'success' => true,
                'message' => "Akun Samba untuk {$nim} ({$role}) berhasil dibuat dan folder siap digunakan.",
                'output'  => $outputString,
            ];
        }

        // Error handling berdasarkan output bash script
        Log::error("Samba Automation Gagal [{$nim} - {$role}, ExitCode: {$returnStatus}]: {$outputString}");
        
        return [
            'success' => false,
            'message' => !empty($outputString) ? $outputString : 'Terjadi kegagalan saat membuat akun di server Linux.',
            'output'  => $outputString,
        ];
    }
}
