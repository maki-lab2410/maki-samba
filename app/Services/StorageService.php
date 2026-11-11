<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageService
{
    protected string $nim;
    protected string $role;
    protected string $diskName = 'samba_user';
    protected string $userRoot;

    public function __construct(string $nim, string $role = 'mahasiswa')
    {
        $this->nim = $nim;
        $this->role = $role;
        $this->initUserDisk();
    }

    /**
     * Konfigurasi Custom Disk 'samba_user' secara DINAMIS pada runtime.
     * Mahasiswa -> /samba/mahasiswa/{NIM}
     * Laboran   -> /samba/operasional
     * Admin     -> /samba/operasional
     */
    protected function initUserDisk(): void
    {
        $base = config('filesystems.disks.samba_base.root', env('SAMBA_BASE_PATH', '/samba/mahasiswa'));
        $operasionalPath = env('SAMBA_OPERASIONAL_PATH', '/samba/operasional');

        $isDosen   = ($this->role === 'dosen');
        $isLaboran = ($this->role === 'laboran') || (!$isDosen && (strtolower($this->nim) === 'laboran' || str_starts_with($this->nim, '0686.')));
        $isAdmin   = ($this->role === 'admin') || strtolower($this->nim) === 'admin';

        // Mahasiswa:       /samba/mahasiswa/{NIM}
        // Laboran & Dosen: /samba/operasional/{NIP/NIDN/Username}
        // Admin:           /samba/operasional (akses master)
        if ($isLaboran || $isDosen) {
            $this->userRoot = rtrim($operasionalPath, '/') . '/' . $this->nim;
            $this->cleanupAccidentalMahasiswaDir($this->nim, $this->userRoot);
        } elseif ($isAdmin) {
            $this->userRoot = $operasionalPath;
            $this->cleanupAccidentalMahasiswaDir('admin', $operasionalPath);
        } else {
            $this->userRoot = rtrim($base, '/') . '/' . $this->nim;
        }

        // Pastikan folder fisik user sudah ada (jika belum, buat otomatis dengan izin 777)
        if (!File::isDirectory($this->userRoot)) {
            @File::makeDirectory($this->userRoot, 0777, true, true);
        } else {
            @chmod($this->userRoot, 0777);
        }

        // 1. Tulis konfigurasi disk baru ke runtime configuration Laravel
        Config::set("filesystems.disks.{$this->diskName}", [
            'driver'      => 'local',
            'root'        => $this->userRoot,
            'throw'       => false,
            'permissions' => [
                'file' => [
                    'public'  => 0666,
                    'private' => 0600,
                ],
                'dir' => [
                    'public'  => 0777,
                    'private' => 0700,
                ],
            ],
        ]);

        // 2. Wajib purge instance disk yang mungkin pernah di-cache sebelumnya
        Storage::purge($this->diskName);
    }

    /**
     * Self-healing: Bersihkan folder jika staf/laboran/operasional sempat tersimpan salah di /samba/mahasiswa/
     */
    protected function cleanupAccidentalMahasiswaDir(string $username, string $targetDir): void
    {
        $base = config('filesystems.disks.samba_base.root', env('SAMBA_BASE_PATH', '/samba/mahasiswa'));
        $wrongFolders = [$username, 'Operasional_Lab', 'operasional', 'admin'];

        foreach ($wrongFolders as $folder) {
            $accidentalDir = rtrim($base, '/') . '/' . $folder;
            if (File::isDirectory($accidentalDir)) {
                try {
                    $files = File::allFiles($accidentalDir);
                    foreach ($files as $file) {
                        $relPath = $file->getRelativePathname();
                        $destination = rtrim($targetDir, '/') . '/' . $relPath;
                        File::ensureDirectoryExists(dirname($destination));
                        File::move($file->getRealPath(), $destination);
                    }
                    File::deleteDirectory($accidentalDir);
                } catch (\Throwable $e) {
                    @File::deleteDirectory($accidentalDir);
                }
            }
        }
    }

    /**
     * Mendapatkan instance disk user
     */
    public function disk()
    {
        return Storage::disk($this->diskName);
    }

    /**
     * Sanitasi Path Relatif untuk Mencegah Path Traversal Vulnerability (../../)
     */
    public function cleanPath(?string $path): string
    {
        if (empty($path)) {
            return '';
        }

        // Hapus null byte, backslash normalize ke slash
        $path = str_replace(["\0", '\\'], ['', '/'], $path);
        
        // Hapus double dot .. yang mencoba naik direktori
        $parts = explode('/', $path);
        $safeParts = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || $part === '.' || $part === '..') {
                continue;
            }
            // Hanya izinkan karakter nama file / folder yang wajar
            $safeParts[] = $part;
        }

        return implode('/', $safeParts);
    }

    /**
     * Mengambil daftar isi direktori (Folder dan File)
     */
    public function listDirectory(string $subPath = ''): array
    {
        $safeSubPath = $this->cleanPath($subPath);
        $disk = $this->disk();

        $directories = [];
        $files = [];

        try {
            $directories = $disk->directories($safeSubPath);
            $files = $disk->files($safeSubPath);
        } catch (\Throwable $e) {
            // Tangkap exception jika terjadi batasan izin sistem Linux
        }

        $folderList = [];
        foreach ($directories as $dir) {
            $name = basename($dir);

            // Sembunyikan folder sistem Linux & Samba (lost+found, recycle bin, folder tersembunyi)
            if (in_array($name, ['lost+found', '.Recycle_bin', '.recycle']) || str_starts_with($name, '.')) {
                continue;
            }

            $fullPhysicalPath = $this->userRoot . '/' . $dir;
            $modTime = @file_exists($fullPhysicalPath) ? @filemtime($fullPhysicalPath) : null;

            $folderList[] = [
                'name'         => $name,
                'path'         => $dir,
                'is_dir'       => true,
                'size'         => '-',
                'size_raw'     => 0,
                'last_modified'=> $modTime ? date('d M Y, H:i', $modTime) . ' WIB' : 'N/A',
                'extension'    => 'folder',
            ];
        }

        $fileList = [];
        foreach ($files as $file) {
            $name = basename($file);

            // Sembunyikan file tersembunyi (.DS_Store, .bashrc, dll)
            if (str_starts_with($name, '.')) {
                continue;
            }

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $iconData = $this->getFileIconData($ext);
            $sizeRaw = 0;
            $modTime = null;

            try {
                $sizeRaw = (int) $disk->size($file);
                $modTime = $disk->lastModified($file);
            } catch (\Throwable $e) {
                $fullPath = $this->userRoot . '/' . $file;
                $sizeRaw = @file_exists($fullPath) ? (int) @filesize($fullPath) : 0;
                $modTime = @file_exists($fullPath) ? @filemtime($fullPath) : null;
            }

            $fileList[] = [
                'name'         => $name,
                'path'         => $file,
                'is_dir'       => false,
                'size'         => $this->formatFileSize($sizeRaw),
                'size_raw'     => $sizeRaw,
                'last_modified'=> $modTime ? date('d M Y, H:i', $modTime) . ' WIB' : 'N/A',
                'extension'    => $ext,
                'icon_data'    => $iconData,
                'icon_color'   => $iconData['color'],
                'badge'        => $iconData['badge'],
            ];
        }

        // Urutkan Folder A-Z, File A-Z
        usort($folderList, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
        usort($fileList, fn($a, $b) => strnatcasecmp($a['name'], $b['name']));

        return [
            'folders'      => $folderList,
            'files'        => $fileList,
            'current_path' => $safeSubPath,
            'breadcrumbs'  => $this->buildBreadcrumbs($safeSubPath),
            'user_root'    => $this->userRoot,
        ];
    }

    /**
     * Membuat Folder Baru di dalam path aktif
     */
    public function createFolder(string $currentPath, string $folderName): bool
    {
        $safeCurrentPath = $this->cleanPath($currentPath);
        $safeFolderName = trim(str_replace(['/', '\\', "\0"], '', $folderName));

        if (empty($safeFolderName)) {
            return false;
        }

        $targetPath = $safeCurrentPath ? "{$safeCurrentPath}/{$safeFolderName}" : $safeFolderName;

        if ($this->disk()->exists($targetPath)) {
            return false;
        }

        $created = $this->disk()->makeDirectory($targetPath);
        if ($created) {
            @chmod($this->userRoot . '/' . $targetPath, 0777);
        }
        return $created;
    }

    /**
     * Upload File ke direktori aktif
     */
    public function uploadFile(string $currentPath, $uploadedFile): ?string
    {
        $currentPath = $this->cleanPath($currentPath);
        $originalName = $uploadedFile->getClientOriginalName();
        
        // Bersihkan nama file dari karakter berbahaya
        $safeFileName = preg_replace('/[^\w\.\-\_ ]+/u', '_', $originalName);

        // Jika file sudah ada, beri suffix timestamp agar tidak tertimpa tanpa sengaja
        $targetPath = $currentPath ? "{$currentPath}/{$safeFileName}" : $safeFileName;
        if ($this->disk()->exists($targetPath)) {
            $nameWithoutExt = pathinfo($safeFileName, PATHINFO_FILENAME);
            $ext = pathinfo($safeFileName, PATHINFO_EXTENSION);
            $safeFileName = $nameWithoutExt . '_' . time() . ($ext ? ".{$ext}" : '');
            $targetPath = $currentPath ? "{$currentPath}/{$safeFileName}" : $safeFileName;
        }

        // Simpan menggunakan disk samba_user
        $savedPath = $this->disk()->putFileAs($currentPath, $uploadedFile, $safeFileName);
        if ($savedPath) {
            @chmod($this->userRoot . '/' . $savedPath, 0666);
        }
        return $savedPath ?: null;
    }

    /**
     * Download file dengan penanganan nama aman
     */
    public function downloadFile(string $filePath): StreamedResponse
    {
        $safePath = $this->cleanPath($filePath);

        if (!$this->disk()->exists($safePath)) {
            abort(404, 'File tidak ditemukan di direktori Samba Anda.');
        }

        return $this->disk()->download($safePath, basename($safePath));
    }

    /**
     * Pratinjau (Preview / Stream) File secara inline di browser tanpa download
     */
    public function previewFile(string $filePath)
    {
        $safePath = $this->cleanPath($filePath);
        $fullPhysicalPath = $this->userRoot . '/' . $safePath;

        if (!File::exists($fullPhysicalPath) || File::isDirectory($fullPhysicalPath)) {
            abort(404, 'File tidak ditemukan di direktori Samba Anda.');
        }

        $mimeType = File::mimeType($fullPhysicalPath) ?: 'application/octet-stream';
        $fileName = basename($safePath);
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Format aman yang diizinkan untuk inline preview di browser
        $safeInlineExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf', 'txt'];
        $disposition = in_array($extension, $safeInlineExtensions) ? 'inline' : 'attachment';

        // Streaming efisien memory-safe bawaan Symfony BinaryFileResponse dengan proteksi CSP Sandbox
        return response()->file($fullPhysicalPath, [
            'Content-Type'              => $mimeType,
            'Content-Disposition'       => "{$disposition}; filename=\"" . addslashes($fileName) . "\"",
            'Content-Security-Policy'   => "sandbox allow-scripts=none; default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline';",
            'X-Content-Type-Options'    => 'nosniff',
            'X-Frame-Options'           => 'SAMEORIGIN',
            'Cache-Control'             => 'private, max-age=3600',
        ]);
    }

    /**
     * Hapus file atau folder
     */
    public function deleteItem(string $path, bool $isDir): bool
    {
        $safePath = $this->cleanPath($path);

        if (empty($safePath)) {
            return false; // Jangan pernah hapus root!
        }

        if ($isDir) {
            return $this->disk()->deleteDirectory($safePath);
        }

        return $this->disk()->delete($safePath);
    }

    /**
     * Hitung total byte penggunaan penyimpanan di direktori fisik user.
     * Strategi:
     *   1. `du -sb` (paling akurat, menghitung semua file termasuk yang dibuat Samba)
     *   2. Symfony Finder via File::allFiles() (bypass Laravel disk cache)
     *   3. Laravel disk()->allFiles() (terakhir, bisa miss karena permission)
     */
    public function getTotalStorageUsed(): int
    {
        if (!File::isDirectory($this->userRoot)) {
            return 0;
        }

        // ── Strategy 1: `du` command (paling reliable di Linux server) ──
        try {
            $escapedPath = escapeshellarg($this->userRoot);
            // du -sb = total bytes; du -sk = total kilobytes (macOS fallback)
            $output = @shell_exec("du -sb {$escapedPath} 2>/dev/null");
            if ($output) {
                $parts = preg_split('/\s+/', trim($output));
                $bytes = (int) ($parts[0] ?? 0);
                if ($bytes > 0) {
                    return $bytes;
                }
            }
            // macOS: du -sk (kilobytes)
            $output = @shell_exec("du -sk {$escapedPath} 2>/dev/null");
            if ($output) {
                $parts = preg_split('/\s+/', trim($output));
                $kb = (int) ($parts[0] ?? 0);
                if ($kb > 0) {
                    return $kb * 1024;
                }
            }
        } catch (\Throwable $e) {
            // Lanjut ke strategy berikutnya
        }

        // ── Strategy 2: Symfony Finder (langsung ke filesystem, tanpa Laravel disk) ──
        try {
            $totalBytes = 0;
            $allFiles = File::allFiles($this->userRoot);
            foreach ($allFiles as $file) {
                $totalBytes += (int) $file->getSize();
            }
            if ($totalBytes > 0) {
                return $totalBytes;
            }
        } catch (\Throwable $e) {
            // Lanjut ke strategy berikutnya
        }

        // ── Strategy 3: Laravel disk (fallback terakhir) ──
        try {
            $totalBytes = 0;
            $allFiles = $this->disk()->allFiles();
            foreach ($allFiles as $file) {
                $totalBytes += (int) $this->disk()->size($file);
            }
            return $totalBytes;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("StorageService: Gagal menghitung storage untuk {$this->userRoot}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Format byte ke ukuran yang mudah dibaca manusia (KB, MB, GB)
     */
    public function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Bangun navigasi Breadcrumb
     */
    protected function buildBreadcrumbs(string $path): array
    {
        $crumbs = [
            ['title' => 'Home', 'name' => 'Home', 'path' => '']
        ];

        if (empty($path)) {
            return $crumbs;
        }

        $parts = explode('/', $path);
        $accumulated = '';

        foreach ($parts as $part) {
            $accumulated = $accumulated ? "{$accumulated}/{$part}" : $part;
            $crumbs[] = [
                'title' => $part,
                'name'  => $part,
                'path'  => $accumulated,
            ];
        }

        return $crumbs;
    }

    /**
     * Menentukan visual icon & warna badge file berdasarkan ekstensi
     */
    protected function getFileIconData(string $ext): array
    {
        return match ($ext) {
            'pdf' => ['color' => 'bg-red-50 text-red-600 border-red-200', 'badge' => 'PDF'],
            'doc', 'docx' => ['color' => 'bg-blue-50 text-blue-600 border-blue-200', 'badge' => 'DOC'],
            'xls', 'xlsx', 'csv' => ['color' => 'bg-emerald-50 text-emerald-600 border-emerald-200', 'badge' => 'XLS'],
            'ppt', 'pptx' => ['color' => 'bg-orange-50 text-orange-600 border-orange-200', 'badge' => 'PPT'],
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' => ['color' => 'bg-purple-50 text-purple-600 border-purple-200', 'badge' => 'IMG'],
            'zip', 'rar', '7z', 'tar', 'gz' => ['color' => 'bg-amber-50 text-amber-700 border-amber-200', 'badge' => 'ZIP'],
            'mp4', 'mkv', 'avi', 'mov' => ['color' => 'bg-rose-50 text-rose-600 border-rose-200', 'badge' => 'VID'],
            'mp3', 'wav', 'ogg' => ['color' => 'bg-indigo-50 text-indigo-600 border-indigo-200', 'badge' => 'AUD'],
            'sql', 'db' => ['color' => 'bg-cyan-50 text-cyan-700 border-cyan-200', 'badge' => 'SQL'],
            'txt', 'md', 'log' => ['color' => 'bg-slate-100 text-slate-700 border-slate-300', 'badge' => 'TXT'],
            'php', 'js', 'py', 'html', 'css', 'json' => ['color' => 'bg-yellow-50 text-yellow-700 border-yellow-200', 'badge' => 'CODE'],
            default => ['color' => 'bg-gray-100 text-gray-600 border-gray-200', 'badge' => strtoupper($ext ?: 'FILE')],
        };
    }
}
