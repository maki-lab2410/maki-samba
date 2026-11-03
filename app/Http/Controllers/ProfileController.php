<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\SambaService;

class ProfileController extends Controller
{
    protected SambaService $sambaService;

    public function __construct(SambaService $sambaService)
    {
        $this->sambaService = $sambaService;
    }

    /**
     * Tampilkan Halaman Profil Pengguna
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Pastikan kuota admin unlimited (0) dan laboran 10 GB
        if ($user->role === 'admin' && $user->quota_limit !== 0) {
            $user->update(['quota_limit' => 0]);
        } elseif ($user->role === 'laboran' && $user->quota_limit < 10737418240) {
            $user->update(['quota_limit' => 10737418240]);
        }

        return view('profile.index', [
            'user' => $user,
        ]);
    }

    /**
     * Update Nama Lengkap Profil
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
        ]);

        $user = Auth::user();
        $user->update([
            'name' => trim($request->input('name')),
        ]);

        return back()->with('success', 'Profil nama lengkap berhasil diperbarui.');
    }

    /**
     * Update Password & Sinkronisasi ke Akun Samba Linux
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password'          => ['required', 'string'],
            'new_password'              => ['required', 'string', 'min:6'],
            'new_password_confirmation' => ['required', 'same:new_password'],
        ], [
            'current_password.required'          => 'Kata sandi saat ini wajib diisi.',
            'new_password.required'              => 'Kata sandi baru wajib diisi.',
            'new_password.min'                   => 'Kata sandi baru minimal 6 karakter.',
            'new_password_confirmation.same'     => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user = Auth::user();

        // 1. Verifikasi Password Saat Ini
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return back()->with('error', 'Kata sandi saat ini yang Anda masukkan salah.');
        }

        $newPassword = $request->input('new_password');

        // 2. Perbarui Password di SQLite Database
        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        // 3. Sinkronkan Kata Sandi ke Samba Server Linux
        if ($this->sambaService->isValidNim($user->nim)) {
            $this->sambaService->createSambaAccount($user->nim, $newPassword);
        }

        return back()->with('success', 'Kata sandi berhasil diperbarui dan disinkronkan ke Samba server!');
    }
}
