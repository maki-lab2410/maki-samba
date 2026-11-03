<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StorageService;
use Illuminate\Support\Facades\Session;
use App\Models\User;

class FileManagerController extends Controller
{
    /**
     * Helper untuk menginisialisasi StorageService berbasis identitas & role pengguna
     */
    protected function getStorageService(): StorageService
    {
        $user = auth()->user();
        $nim = $user ? $user->nim : (string) Session::get('samba_nim', '');
        $role = $user ? $user->role : (string) Session::get('samba_role', 'mahasiswa');

        // Pastikan laboran, dosen, dan admin selalu diidentifikasi secara tepat
        $nimLower = strtolower($nim);
        if ($user && $user->role === 'dosen') {
            $role = 'dosen';
        } elseif ($user && $user->role === 'laboran') {
            $role = 'laboran';
        } elseif ($nimLower === 'admin') {
            $role = 'admin';
        } elseif ($nimLower === 'laboran') {
            $role = 'laboran';
        }

        if (empty($nim)) {
            abort(403, 'Akses ditolak: Sesi pengguna tidak ditemukan.');
        }

        return new StorageService($nim, $role);
    }

    /**
     * 1. Menampilkan Halaman Dashboard Web File Manager
     */
    public function index(Request $request)
    {
        $storage = $this->getStorageService();
        
        // Memaksa cast ke string agar nilai null dari query berubah menjadi ''
        $currentPath = (string) $request->query('path', '');
        
        $data = $storage->listDirectory($currentPath);
        
        $user = auth()->user();
        $nim = $user ? $user->nim : (string) Session::get('samba_nim', '');
        $role = $user ? $user->role : (string) Session::get('samba_role', 'mahasiswa');

        $nimLower = strtolower($nim);
        if ($user && $user->role === 'dosen') {
            $role = 'dosen';
        } elseif ($user && $user->role === 'laboran') {
            $role = 'laboran';
        } elseif ($nimLower === 'admin') {
            $role = 'admin';
        } elseif ($nimLower === 'laboran') {
            $role = 'laboran';
        }

        // Hitung kuota penggunaan (default 1 GB = 1073741824 bytes)
        $storageUsed = $storage->getTotalStorageUsed();
        $quotaLimit = 1073741824; // 1 GB
        $isAdmin = ($role === 'admin') || ($user && $user->isAdmin());

        if ($user) {
            // Pastikan laboran selalu mendapatkan alokasi kuota 10 GB
            if ($user->role === 'laboran' && $user->quota_limit < 10737418240) {
                $user->update(['quota_limit' => 10737418240]);
            }

            // Pastikan dosen selalu mendapatkan alokasi kuota 5 GB
            if ($user->role === 'dosen' && $user->quota_limit < 5368709120) {
                $user->update(['quota_limit' => 5368709120]);
            }

            // Pastikan mahasiswa selalu mendapatkan alokasi kuota minimal 1 GB jika sebelumnya 200 MB
            if ($user->role === 'mahasiswa' && $user->quota_limit < 1073741824) {
                $user->update(['quota_limit' => 1073741824]);
            }

            // Pastikan admin selalu unlimited (0)
            if ($isAdmin && $user->quota_limit !== 0) {
                $user->update(['quota_limit' => 0]);
            }

            $defaultQuota = 1073741824;
            if ($role === 'laboran') {
                $defaultQuota = 10737418240;
            } elseif ($role === 'dosen') {
                $defaultQuota = 5368709120;
            }

            $quotaLimit = $user->quota_limit ?: $defaultQuota;
            // Update database jika nilainya berbeda
            if ($user->quota_used !== $storageUsed) {
                $user->update(['quota_used' => $storageUsed]);
            }
        }

        if ($isAdmin) {
            $quotaLimit = 0;
            $quotaPercent = 0.0;
            $quotaUsedFormatted = $storage->formatFileSize($storageUsed);
            $quotaLimitFormatted = 'Unlimited';
        } else {
            $quotaPercent = (float) min(100, round(($storageUsed / max(1, $quotaLimit)) * 100, 1));
            $quotaUsedFormatted = $storage->formatFileSize($storageUsed);
            $quotaLimitFormatted = $storage->formatFileSize($quotaLimit);
        }

        // Path dinamis Samba LAN:
        // Mahasiswa         -> \\SERVER\mahasiswa
        // Staf/Dosen/Admin  -> \\SERVER\operasional
        $isStaff = in_array(strtolower($role), ['laboran', 'dosen', 'admin']) || in_array($nimLower, ['laboran', 'admin']);
        $sambaShareName = $isStaff ? env('SAMBA_OPERASIONAL_SHARE', 'operasional') : env('SAMBA_MAHASISWA_SHARE', 'mahasiswa');

        return view('filemanager.index', array_merge($data, [
            'nim'                 => $nim,
            'role'                => $role,
            'serverIp'            => (string) ($request->server('SERVER_ADDR') ?: gethostbyname(gethostname())),
            'sambaHost'           => (string) env('SAMBA_SERVER_HOST', '192.168.1.100'),
            'sambaShareName'      => $sambaShareName,
            'quotaUsed'           => $storageUsed,
            'quotaLimit'          => $quotaLimit,
            'quotaPercent'        => $quotaPercent,
            'quotaUsedFormatted'  => $quotaUsedFormatted,
            'quotaLimitFormatted' => $quotaLimitFormatted,
        ]));
    }

    /**
     * 2. Membuat Folder Baru di dalam path aktif
     */
    public function createFolder(Request $request)
    {
        $request->validate([
            'folder_name'  => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_\-\s.]+$/'],
            'current_path' => ['nullable', 'string'],
        ], [
            'folder_name.required' => 'Nama folder tidak boleh kosong.',
            'folder_name.regex'    => 'Nama folder hanya boleh mengandung huruf, angka, spasi, titik, atau strip.',
        ]);

        $storage = $this->getStorageService();
        $currentPath = (string) $request->input('current_path', '');
        $folderName = trim((string) $request->input('folder_name', ''));

        $success = $storage->createFolder($currentPath, $folderName);

        if ($success) {
            return redirect()->route('filemanager.index', ['path' => $currentPath])
                ->with('success', "Folder \"{$folderName}\" berhasil dibuat.");
        }

        return redirect()->route('filemanager.index', ['path' => $currentPath])
            ->with('error', 'Gagal membuat folder baru. Periksa izin direktori di server.');
    }

    /**
     * 3. Mengunggah (Upload) File ke folder aktif
     */
    public function upload(Request $request)
    {
        $request->validate([
            'files'        => ['required', 'array'],
            'files.*'      => ['required', 'file'],
            'current_path' => ['nullable', 'string'],
        ], [
            'files.required' => 'Pilih setidaknya satu file untuk diunggah.',
        ]);

        $storage = $this->getStorageService();
        $currentPath = (string) $request->input('current_path', '');
        $uploadedFiles = $request->file('files');

        // Validasi ekstensi berbahaya untuk mencegah web shell atau eksekusi skrip server
        $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'phar', 'cgi', 'pl', 'sh', 'bash', 'exe'];
        foreach ($uploadedFiles as $f) {
            $ext = strtolower($f->getClientOriginalExtension());
            if (in_array($ext, $dangerousExtensions)) {
                return redirect()->route('filemanager.index', ['path' => $currentPath])
                    ->with('error', "File dengan ekstensi '.{$ext}' tidak diizinkan demi keamanan server.");
            }
        }

        // Cek kuota sebelum upload (Admin memiliki kuota Unlimited)
        $user = auth()->user();
        $isAdmin = $user && ($user->isAdmin() || $user->role === 'admin' || $user->quota_limit === 0);
        $quotaLimit = $user ? ($user->quota_limit ?: 1073741824) : 1073741824;
        $currentUsed = $storage->getTotalStorageUsed();

        $totalUploadSize = 0;
        foreach ($uploadedFiles as $f) {
            $totalUploadSize += $f->getSize();
        }

        if (!$isAdmin && ($currentUsed + $totalUploadSize) > $quotaLimit) {
            $limitFormatted = $storage->formatFileSize($quotaLimit);
            return redirect()->route('filemanager.index', ['path' => $currentPath])
                ->with('error', "Gagal upload: Ukuran file melebihi batas kuota penyimpanan ({$limitFormatted}) Anda.");
        }

        $uploadedCount = 0;
        $failedFiles = [];

        foreach ($uploadedFiles as $file) {
            $saved = $storage->uploadFile($currentPath, $file);
            if ($saved) {
                $uploadedCount++;
            } else {
                $failedFiles[] = $file->getClientOriginalName();
            }
        }

        // Perbarui kuota setelah upload
        if ($user) {
            $user->update(['quota_used' => $storage->getTotalStorageUsed()]);
        }

        if ($uploadedCount > 0 && empty($failedFiles)) {
            return redirect()->route('filemanager.index', ['path' => $currentPath])
                ->with('success', "{$uploadedCount} file berhasil diunggah.");
        } elseif ($uploadedCount > 0 && !empty($failedFiles)) {
            $failList = implode(', ', $failedFiles);
            return redirect()->route('filemanager.index', ['path' => $currentPath])
                ->with('warning', "{$uploadedCount} file berhasil diunggah, namun file berikut gagal: {$failList}");
        }

        return redirect()->route('filemanager.index', ['path' => $currentPath])
            ->with('error', 'Gagal mengunggah file.');
    }

    /**
     * 4. Mengunduh (Download) File
     */
    public function download(Request $request)
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        $storage = $this->getStorageService();
        $path = (string) $request->input('path', '');

        return $storage->downloadFile($path);
    }

    /**
     * 5. Pratinjau (Preview / Stream) File di Browser
     */
    public function preview(Request $request)
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        $storage = $this->getStorageService();
        $path = (string) $request->input('path', '');

        return $storage->previewFile($path);
    }

    /**
     * 5. Menghapus File atau Folder
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'path'         => ['required', 'string'],
            'type'         => ['required', 'in:file,folder'],
            'current_path' => ['nullable', 'string'],
        ]);

        $storage = $this->getStorageService();
        $targetPath = (string) $request->input('path', '');
        $isDir = ((string) $request->input('type') === 'folder');
        $currentPath = (string) $request->input('current_path', '');

        $itemName = basename($targetPath);
        $success = $storage->deleteItem($targetPath, $isDir);

        if ($success) {
            // Update kuota terpakai
            if ($user = auth()->user()) {
                $user->update(['quota_used' => $storage->getTotalStorageUsed()]);
            }

            $typeLabel = $isDir ? 'Folder' : 'File';
            return redirect()->route('filemanager.index', ['path' => $currentPath])
                ->with('success', "{$typeLabel} \"{$itemName}\" berhasil dihapus.");
        }

        return redirect()->route('filemanager.index', ['path' => $currentPath])
            ->with('error', "Gagal menghapus \"{$itemName}\". Pastikan Anda memiliki izin akses.");
    }
}
