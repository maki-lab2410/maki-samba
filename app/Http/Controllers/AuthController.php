<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SambaService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    protected SambaService $sambaService;

    public function __construct(SambaService $sambaService)
    {
        $this->sambaService = $sambaService;
    }

    /**
     * Tampilkan Halaman Login
     */
    public function showLogin()
    {
        if (Auth::check() || Session::get('samba_logged_in')) {
            $user = Auth::user();
            if ($user && $user->role === 'admin') {
                return redirect()->route('admin.index');
            }
            return redirect()->route('filemanager.index');
        }

        return view('auth.login');
    }

    /**
     * Proses Login Pengguna (Mahasiswa, Laboran, Admin)
     */
    public function login(Request $request)
    {
        $request->validate([
            'nim'      => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'nim.required'      => 'NIM / Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $nim = trim((string) $request->input('nim'));
        $password = (string) $request->input('password');
        $nimLower = strtolower($nim);

        // ════ 1. INISIALISASI AKUN ADMIN & LABORAN (Hanya jika belum ada di database) ════
        // Password default dapat dikonfigurasi via .env (ADMIN_DEFAULT_PASS / LABORAN_DEFAULT_PASS)
        if ($nimLower === 'admin' && !User::where('nim', 'admin')->exists()) {
            User::create([
                'nim'         => 'admin',
                'name'        => 'Administrator ' . (\App\Models\Setting::get('institution_short', 'Lab')),
                'password'    => Hash::make(env('ADMIN_DEFAULT_PASS', 'makisamba2026')),
                'role'        => 'admin',
                'quota_limit' => 0, // 0 = Unlimited
                'quota_used'  => 0,
            ]);
        }

        if ($nimLower === 'laboran' && !User::where('nim', 'laboran')->exists()) {
            User::create([
                'nim'         => 'laboran',
                'name'        => 'Laboran ' . (\App\Models\Setting::get('institution_short', 'Lab')),
                'password'    => Hash::make(env('LABORAN_DEFAULT_PASS', 'laboran123')),
                'role'        => 'laboran',
                'quota_limit' => 10737418240, // 10 GB
                'quota_used'  => 0,
            ]);
        }

        // ════ 2. CEK USER YANG TERDAFTAR DI DATABASE SQLITE ════
        $user = User::where('nim', $nim)->orWhere('nim', $nimLower)->first();

        if ($user) {
            $validPassword = Hash::check($password, $user->password);

            if ($validPassword) {
                // KOREKSI OTOMATIS: Pastikan kuota dan integritas role akun staf terjaga
                if ($user->role === 'dosen') {
                    if ($user->quota_limit < 5368709120) {
                        $user->quota_limit = 5368709120;
                        $user->save();
                    }
                } elseif ($user->nim === 'admin' || $nimLower === 'admin') {
                    $user->role = 'admin';
                    $user->quota_limit = 0;
                    $user->save();
                } elseif ($user->nim === 'laboran' || $nimLower === 'laboran') {
                    $user->role = 'laboran';
                    if ($user->quota_limit < 10737418240) {
                        $user->quota_limit = 10737418240;
                        $user->save();
                    }
                } elseif ($user->role === 'laboran') {
                    if ($user->quota_limit < 10737418240) {
                        $user->quota_limit = 10737418240;
                        $user->save();
                    }
                } elseif ($user->role === 'mahasiswa') {
                    if ($user->quota_limit < 1073741824) {
                        $user->quota_limit = 1073741824;
                        $user->save();
                    }
                }

                Auth::login($user, true);
                $request->session()->regenerate();
                Session::put('samba_logged_in', true);
                Session::put('samba_nim', $user->nim);
                Session::put('samba_role', $user->role);

                if ($user->role === 'admin') {
                    return redirect()->route('admin.index')
                        ->with('success', "Selamat datang, {$user->name}!");
                }

                return redirect()->route('filemanager.index')
                    ->with('success', "Selamat datang, {$user->name}!");
            }
            return back()->withInput()->with('error', 'Password yang Anda masukkan salah.');
        }

        // ════ 3. FALLBACK VALIDASI FISIK SERVER UNTUK MAHASISWA ════
        if ($nimLower !== 'admin' && $nimLower !== 'laboran' && $this->sambaService->studentDirectoryExists($nim)) {
            $user = User::create([
                'nim'         => $nim,
                'name'        => 'Mahasiswa ' . $nim,
                'password'    => Hash::make($password),
                'role'        => 'mahasiswa',
                'quota_limit' => 1073741824, // 1 GB
                'quota_used'  => 0,
            ]);

            Auth::login($user);
            $request->session()->regenerate();
            Session::put('samba_logged_in', true);
            Session::put('samba_nim', $nim);
            Session::put('samba_role', 'mahasiswa');

            $instShort = \App\Models\Setting::get('institution_short', '');
            $welcomeTitle = \App\Models\Setting::get('app_title', 'Maki Samba');
            $welcomeSuffix = !empty($instShort) ? " {$instShort}" : '';

            return redirect()->route('filemanager.index')
                ->with('success', "Selamat datang di Portal {$welcomeTitle}{$welcomeSuffix}, {$user->name}!");
        }

        return back()->withInput()->with('error', "NIM / Username '{$nim}' belum terdaftar. Silakan lakukan pendaftaran akun terlebih dahulu.");
    }

    /**
     * Tampilkan Halaman Registrasi Akun Samba
     */
    public function showRegister()
    {
        if (Auth::check() || Session::get('samba_logged_in')) {
            return redirect()->route('filemanager.index');
        }

        return view('auth.register');
    }

    /**
     * Proses Registrasi Akun Samba (Khusus Mahasiswa)
     */
    public function register(Request $request)
    {
        $request->validate([
            'nim'                   => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'password'              => ['required', 'string', 'min:6'],
            'password_confirmation' => ['required', 'same:password'],
        ], [
            'nim.required'          => 'NIM wajib diisi.',
            'nim.regex'             => 'Format NIM hanya boleh berupa huruf, angka, titik, atau strip tanpa spasi.',
            'password.required'     => 'Password wajib diisi.',
            'password.min'          => 'Password minimal harus 6 karakter.',
            'password_confirmation.same' => 'Konfirmasi password tidak cocok.',
        ]);

        $nim      = trim((string) $request->input('nim'));
        $nimLower = strtolower($nim);
        $password = (string) $request->input('password');

        // Larang registrasi akun sistem & service daemons melalui form publik mahasiswa
        $blockedSystemAccounts = [
            'admin', 'laboran', 'dosen', 'root', 'administrator', 'operasional',
            'apache', 'nginx', 'www', 'www-data', 'nobody', 'daemon', 'bin', 'sys',
            'sync', 'games', 'man', 'lp', 'mail', 'news', 'uucp', 'proxy', 'postgres',
            'mysql', 'sshd', 'ftp', 'systemd', 'messagebus', 'syslog', 'systemd-resolve'
        ];
        if (in_array($nimLower, $blockedSystemAccounts)) {
            return back()->withInput()->with('error', "Username '{$nim}' merupakan akun sistem dan tidak dapat didaftarkan melalui form registrasi mahasiswa.");
        }

        if (User::where('nim', $nim)->exists() || $this->sambaService->studentDirectoryExists($nim)) {
            return back()->withInput()->with('error', "NIM {$nim} sudah terdaftar di sistem. Silakan langsung login.");
        }

        // 1. Eksekusi script bash otomatisasi Linux (role: mahasiswa)
        $result = $this->sambaService->createSambaAccount($nim, $password, 'mahasiswa');

        if ($result['success']) {
            // 2. Simpan user ke database SQLite
            User::create([
                'nim'         => $nim,
                'name'        => 'Mahasiswa ' . $nim,
                'password'    => Hash::make($password),
                'role'        => 'mahasiswa',
                'quota_limit' => 1073741824, // 1 GB
                'quota_used'  => 0,
            ]);

            return redirect()->route('login')
                ->with('success', "Akun Samba untuk NIM {$nim} berhasil didaftarkan! Silakan login.");
        }

        return back()->withInput()->with('error', $result['message']);
    }

    /**
     * Proses Logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        Session::flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}
