<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Models\User;
use App\Services\StorageService;

class DosenMahasiswaController extends Controller
{
    /**
     * Pastikan hanya Staf (Dosen, Laboran, Admin) yang dapat mengakses
     */
    protected function authorizeStaff(): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Akses ditolak: Anda belum terautentikasi.');
        }

        $allowedRoles = ['dosen', 'laboran', 'admin'];
        if (!in_array($user->role, $allowedRoles) && !$user->isAdmin()) {
            abort(403, 'Akses terbatas: Menu ini hanya dapat diakses oleh Dosen, Laboran, dan Administrator.');
        }
    }

    /**
     * 1. Menampilkan Direktori Daftar Mahasiswa dengan Filter Semester, Kelas, dan Folder Mata Kuliah
     */
    public function index(Request $request)
    {
        $this->authorizeStaff();

        $semesterFilter = $request->query('semester');
        $kelasFilter    = $request->query('kelas');
        $search         = trim((string) $request->query('q', ''));
        $folderSearch   = trim((string) $request->query('folder', ''));

        // Query Mahasiswa
        $query = User::where('role', 'mahasiswa');

        if (!empty($semesterFilter) && $semesterFilter !== 'all') {
            $query->where('semester', (int) $semesterFilter);
        }

        if (!empty($kelasFilter) && $kelasFilter !== 'all') {
            $query->where('kelas', $kelasFilter);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Urutkan berdasarkan Semester asc, Kelas asc, NIM asc
        $query->orderByRaw('CASE WHEN semester IS NULL THEN 99 ELSE semester END ASC')
              ->orderBy('kelas', 'asc')
              ->orderBy('nim', 'asc');

        $students = $query->get();

        // Path direktori mahasiswa di server
        $sambaBasePath = config('filesystems.disks.samba_base.root', env('SAMBA_BASE_PATH', '/samba/mahasiswa'));

        // Ambil folder-folder praktikum / mata kuliah yang dibuat mahasiswa
        $allUniqueFolders = [];
        $studentFoldersMap = [];

        foreach ($students as $student) {
            $studentRoot = rtrim($sambaBasePath, '/') . '/' . $student->nim;
            $folders = [];

            if (File::isDirectory($studentRoot)) {
                try {
                    $dirs = File::directories($studentRoot);
                    foreach ($dirs as $dir) {
                        $baseName = basename($dir);
                        if (!str_starts_with($baseName, '.') && !in_array($baseName, ['lost+found', '.Recycle_bin', '.recycle'])) {
                            $folders[] = $baseName;
                            $allUniqueFolders[$baseName] = ($allUniqueFolders[$baseName] ?? 0) + 1;
                        }
                    }
                } catch (\Throwable $e) {
                    // Abaikan kendala permission direktori
                }
            }
            $studentFoldersMap[$student->id] = $folders;
        }

        ksort($allUniqueFolders);

        $studentList = [];
        foreach ($students as $student) {
            $folders = $studentFoldersMap[$student->id] ?? [];

            // Jika ada filter folder mata kuliah tertentu
            if (!empty($folderSearch)) {
                $matched = false;
                foreach ($folders as $f) {
                    if (stripos($f, $folderSearch) !== false) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    continue; // Lewati mahasiswa yang tidak memiliki folder yang cocok
                }
            }

            $studentList[] = [
                'user'          => $student,
                'folders'       => $folders,
                'total_folders' => count($folders),
            ];
        }

        // Daftar unik Semester & Kelas yang ada di database untuk opsi dropdown
        $availableSemesters = User::where('role', 'mahasiswa')
            ->whereNotNull('semester')
            ->distinct()
            ->orderBy('semester', 'asc')
            ->pluck('semester');

        $availableClasses = User::where('role', 'mahasiswa')
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->distinct()
            ->orderBy('kelas', 'asc')
            ->pluck('kelas');

        return view('dosen.mahasiswa.index', [
            'students'           => $studentList,
            'totalStudents'      => count($studentList),
            'availableSemesters' => $availableSemesters,
            'availableClasses'   => $availableClasses,
            'availableFolders'   => $allUniqueFolders,
            'currentSemester'    => $semesterFilter ?: 'all',
            'currentKelas'       => $kelasFilter ?: 'all',
            'search'             => $search,
            'folderSearch'       => $folderSearch,
        ]);
    }

    /**
     * 2. Unduh Batch / Multi-Mahasiswa Folder Praktikum dalam format ZIP
     */
    public function downloadBatchZip(Request $request)
    {
        $this->authorizeStaff();

        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $folder   = trim((string) $request->input('folder', ''));
        $semester = $request->input('semester');
        $kelas    = $request->input('kelas');
        $search   = trim((string) $request->input('q', ''));

        // Handle daftar NIM mahasiswa (bisa via array POST atau comma-separated string)
        $nims = $request->input('nims', []);
        if (is_string($nims)) {
            $nims = array_filter(array_map('trim', explode(',', $nims)));
        }

        $query = User::where('role', 'mahasiswa');
        if (!empty($nims)) {
            $query->whereIn('nim', $nims);
        } else {
            if (!empty($semester) && $semester !== 'all') {
                $query->where('semester', (int) $semester);
            }
            if (!empty($kelas) && $kelas !== 'all') {
                $query->where('kelas', $kelas);
            }
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('nim', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
                });
            }
        }

        $students = $query->orderBy('nim', 'asc')->get();

        if ($students->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data mahasiswa yang dipilih untuk diunduh.');
        }

        if (!class_exists('ZipArchive')) {
            return redirect()->back()->with('error', 'Ekstensi PHP ZipArchive belum terpasang di server.');
        }

        $sambaBasePath = config('filesystems.disks.samba_base.root', env('SAMBA_BASE_PATH', '/samba/mahasiswa'));
        $tempZipFile = tempnam(sys_get_temp_dir(), 'maki_zip_') . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($tempZipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'Gagal membuat file arsip ZIP di server.');
        }

        $totalFilesAdded = 0;

        foreach ($students as $student) {
            $studentRoot = rtrim($sambaBasePath, '/') . '/' . $student->nim;
            if (!is_dir($studentRoot)) {
                continue;
            }

            $safeStudentName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $student->name);
            $studentFolderPrefix = "{$student->nim}_{$safeStudentName}";

            if (!empty($folder) && $folder !== 'all' && $folder !== '__all__') {
                $cleanFolder = trim(str_replace(['..', '\\'], '', $folder), '/');
                $targetDir = $studentRoot . '/' . $cleanFolder;

                if (is_dir($targetDir)) {
                    $zipSubDir = "{$studentFolderPrefix}/{$cleanFolder}";
                    $totalFilesAdded += $this->addFolderToZip($zip, $targetDir, $zipSubDir);
                }
            } else {
                $totalFilesAdded += $this->addFolderToZip($zip, $studentRoot, $studentFolderPrefix);
            }
        }

        $zip->close();

        if ($totalFilesAdded === 0) {
            @unlink($tempZipFile);
            $msg = !empty($folder)
                ? "Tidak ditemukan file pada folder '{$folder}' untuk mahasiswa yang dipilih."
                : "Tidak ditemukan file pada berkas mahasiswa yang dipilih.";
            return redirect()->back()->with('warning', $msg);
        }

        $folderSlug = (!empty($folder) && $folder !== 'all') ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $folder) : 'Semua_Berkas';
        $dateStamp = date('Ymd_His');
        $downloadFileName = "Tugas_{$folderSlug}_{$dateStamp}.zip";

        return response()->download($tempZipFile, $downloadFileName)->deleteFileAfterSend(true);
    }

    /**
     * 3. Unduh Folder Mahasiswa Spesifik dalam format ZIP
     */
    public function downloadStudentZip(string $nim, Request $request)
    {
        $this->authorizeStaff();

        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $student = User::where('nim', $nim)->firstOrFail();
        $folder = trim((string) $request->query('path', ''));

        $sambaBasePath = config('filesystems.disks.samba_base.root', env('SAMBA_BASE_PATH', '/samba/mahasiswa'));
        $studentRoot = rtrim($sambaBasePath, '/') . '/' . $student->nim;

        if (!is_dir($studentRoot)) {
            return redirect()->back()->with('error', 'Direktori berkas mahasiswa tidak ditemukan di server.');
        }

        $cleanFolder = trim(str_replace(['..', '\\'], '', $folder), '/');
        $targetDir = empty($cleanFolder) ? $studentRoot : $studentRoot . '/' . $cleanFolder;

        if (!is_dir($targetDir)) {
            return redirect()->back()->with('error', 'Folder ' . $cleanFolder . ' tidak ditemukan.');
        }

        if (!class_exists('ZipArchive')) {
            return redirect()->back()->with('error', 'Ekstensi PHP ZipArchive belum terpasang di server.');
        }

        $tempZipFile = tempnam(sys_get_temp_dir(), 'maki_zip_') . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($tempZipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'Gagal menginisialisasi file ZIP di server.');
        }

        $folderBaseName = empty($cleanFolder) ? 'Semua_Berkas' : basename($cleanFolder);
        $totalFilesAdded = $this->addFolderToZip($zip, $targetDir, $folderBaseName);
        $zip->close();

        if ($totalFilesAdded === 0) {
            @unlink($tempZipFile);
            return redirect()->back()->with('warning', "Folder '{$folderBaseName}' belum berisi file apapun.");
        }

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $student->name);
        $downloadFileName = "{$student->nim}_{$safeName}_{$folderBaseName}.zip";

        return response()->download($tempZipFile, $downloadFileName)->deleteFileAfterSend(true);
    }

    /**
     * Helper rekursif untuk menambahkan seluruh isi folder ke dalam ZipArchive
     */
    protected function addFolderToZip(\ZipArchive $zip, string $dirPath, string $zipSubDir = ''): int
    {
        $count = 0;
        if (!is_dir($dirPath)) {
            return 0;
        }

        $items = @scandir($dirPath);
        if ($items === false) {
            return 0;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || str_starts_with($item, '.') || in_array($item, ['lost+found', '.Recycle_bin', '.recycle'])) {
                continue;
            }

            $fullPath = $dirPath . '/' . $item;
            $localZipPath = ltrim(($zipSubDir !== '' ? $zipSubDir . '/' : '') . $item, '/');

            if (is_dir($fullPath)) {
                $zip->addEmptyDir($localZipPath);
                $count += $this->addFolderToZip($zip, $fullPath, $localZipPath);
            } elseif (is_file($fullPath) && is_readable($fullPath)) {
                $zip->addFile($fullPath, $localZipPath);
                $count++;
            }
        }

        return $count;
    }

    /**
     * 4. Membuka dan Memeriksa Isi Folder Mahasiswa Tertentu (Mode Read-Only)
     */
    public function show(string $nim, Request $request)
    {
        $this->authorizeStaff();

        $student = User::where('nim', $nim)->firstOrFail();
        $currentPath = (string) $request->query('path', '');

        // Inisialisasi storage mahasiswa dalam mode baca
        $storage = new StorageService($student->nim, 'mahasiswa');
        $data = $storage->listDirectory($currentPath);

        // Ambil kuota terpakai mahasiswa
        $storageUsed = $storage->getTotalStorageUsed();
        $quotaUsedFormatted = $storage->formatFileSize($storageUsed);
        $quotaLimitFormatted = $student->quota_limit_formatted;
        $quotaPercent = $student->quota_percent;

        return view('dosen.mahasiswa.show', array_merge($data, [
            'student'             => $student,
            'quotaUsedFormatted'  => $quotaUsedFormatted,
            'quotaLimitFormatted' => $quotaLimitFormatted,
            'quotaPercent'        => $quotaPercent,
        ]));
    }

    /**
     * 5. Pratinjau File Mahasiswa (Preview Gambar, PDF, Teks, Dokumen, Video, Audio)
     */
    public function preview(string $nim, Request $request)
    {
        $this->authorizeStaff();

        $student = User::where('nim', $nim)->firstOrFail();
        $filePath = (string) $request->query('file', '');

        $storage = new StorageService($student->nim, 'mahasiswa');
        return $storage->previewFile($filePath);
    }

    /**
     * 6. Unduh File Satuan Mahasiswa
     */
    public function download(string $nim, Request $request)
    {
        $this->authorizeStaff();

        $student = User::where('nim', $nim)->firstOrFail();
        $filePath = (string) $request->query('file', '');

        $storage = new StorageService($student->nim, 'mahasiswa');
        return $storage->downloadFile($filePath);
    }
}
