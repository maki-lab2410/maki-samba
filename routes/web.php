<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| Web Routes - Maki Samba (Portal Samba & Web File Manager Lab RMIK)
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard (if logged in) or login
Route::get('/', function () {
    return redirect()->route('login');
});

// ─── AUTHENTICATION ROUTES (Guest) ───────────────────────────────────────────
Route::middleware('guest')->group(function () {
    // Halaman Login
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.post');

    // Halaman Pendaftaran Akun Samba
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.post');
});

// ─── PROTECTED ROUTES (Membutuhkan Session / Auth Aktif) ─────────────────────
Route::middleware('samba.auth')->group(function () {
    // Logout (Wajib POST dengan proteksi CSRF untuk mencegah serangan CSRF force-logout)
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard Web File Manager
    Route::get('/dashboard', [FileManagerController::class, 'index'])->name('filemanager.index');

    // Operasi File & Folder
    Route::post('/filemanager/folder', [FileManagerController::class, 'createFolder'])->name('filemanager.folder.create');
    Route::post('/filemanager/upload', [FileManagerController::class, 'upload'])->name('filemanager.upload');
    Route::get('/filemanager/download', [FileManagerController::class, 'download'])->name('filemanager.download');
    Route::get('/filemanager/preview', [FileManagerController::class, 'preview'])->name('filemanager.preview');
    Route::delete('/filemanager/destroy', [FileManagerController::class, 'destroy'])->name('filemanager.destroy');

    // ─── PROFIL PENGGUNA & KEAMANAN KATA SANDI ──────────────────────────────
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // ─── DASHBOARD ADMIN & LABORAN (RBAC) ────────────────────────────────────
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::put('/admin/settings', [AdminController::class, 'updateSettings'])->name('admin.update-settings');
    Route::put('/admin/user/{id}', [AdminController::class, 'updateUser'])->name('admin.update-user');
    Route::put('/admin/user/{id}/reset-password', [AdminController::class, 'resetPassword'])->name('admin.reset-password');
    Route::delete('/admin/user/{id}', [AdminController::class, 'destroyUser'])->name('admin.destroy-user');
    Route::post('/admin/import', [AdminController::class, 'importUsers'])->name('admin.import');

    // ─── DIREKTORI & PEMERIKSAAN FILE MAHASISWA (DOSEN, LABORAN, ADMIN) ───────
    Route::get('/dosen/mahasiswa', [\App\Http\Controllers\DosenMahasiswaController::class, 'index'])->name('dosen.mahasiswa.index');
    Route::post('/dosen/mahasiswa/bulk-folder', [\App\Http\Controllers\DosenMahasiswaController::class, 'bulkCreateFolder'])->name('dosen.mahasiswa.bulk_folder');
    Route::match(['get', 'post'], '/dosen/mahasiswa/download-zip', [\App\Http\Controllers\DosenMahasiswaController::class, 'downloadBatchZip'])->name('dosen.mahasiswa.download_zip');
    Route::get('/dosen/mahasiswa/{nim}', [\App\Http\Controllers\DosenMahasiswaController::class, 'show'])->name('dosen.mahasiswa.show');
    Route::get('/dosen/mahasiswa/{nim}/download-zip', [\App\Http\Controllers\DosenMahasiswaController::class, 'downloadStudentZip'])->name('dosen.mahasiswa.download_student_zip');
    Route::get('/dosen/mahasiswa/{nim}/preview', [\App\Http\Controllers\DosenMahasiswaController::class, 'preview'])->name('dosen.mahasiswa.preview');
    Route::get('/dosen/mahasiswa/{nim}/download', [\App\Http\Controllers\DosenMahasiswaController::class, 'download'])->name('dosen.mahasiswa.download');
});
