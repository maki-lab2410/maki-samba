<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Setting;
use App\Services\SambaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

class AdminController extends Controller
{
    protected SambaService $sambaService;

    public function __construct(SambaService $sambaService)
    {
        $this->sambaService = $sambaService;
    }

    /**
     * Dashboard Manajemen Akun & Live Monitoring LAN
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        if (!$currentUser || $currentUser->role !== 'admin') {
            abort(403, 'Akses ditolak: Halaman manajemen Samba hanya dapat diakses oleh Administrator.');
        }

        $tab = $request->query('tab', 'accounts');
        $search = $request->query('q', '');
        $roleFilter = $request->query('role', 'all');

        // 1. Ambil Data Mahasiswa & Akun
        $query = User::query();
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if (in_array($roleFilter, ['mahasiswa', 'laboran', 'dosen', 'admin'])) {
            $query->where('role', $roleFilter);
        }

        $users = $query->orderByRaw("CASE WHEN role = 'admin' THEN 1 WHEN role = 'laboran' THEN 2 WHEN role = 'dosen' THEN 3 ELSE 4 END")
                       ->orderBy('nim')
                       ->paginate(15)
                       ->appends(['tab' => $tab, 'q' => $search, 'role' => $roleFilter]);

        // 2. Ambil Data Live Monitoring Samba LAN
        $liveSessions = $this->getLiveSambaSessions();

        // 3. Statistik Ringkasan
        $stats = [
            'total_users'     => User::count(),
            'total_mahasiswa' => User::where('role', 'mahasiswa')->count(),
            'total_laboran'   => User::where('role', 'laboran')->count(),
            'total_dosen'     => User::where('role', 'dosen')->count(),
            'active_samba'    => count($liveSessions),
            'server_host'     => Setting::getSambaHost(),
        ];

        return view('admin.index', [
            'users'        => $users,
            'stats'        => $stats,
            'activeTab'    => $tab,
            'roleFilter'   => $roleFilter,
            'liveSessions' => $liveSessions,
            'search'       => $search,
            'currentUser'  => $currentUser,
            'settings'     => Setting::getAllSettings(),
        ]);
    }

    /**
     * Simpan pembaruan identitas instansi & aplikasi
     */
    public function updateSettings(Request $request)
    {
        $currentUser = Auth::user();
        if (!$currentUser || $currentUser->role !== 'admin') {
            abort(403, 'Hanya Administrator yang memiliki wewenang mengubah pengaturan instansi.');
        }

        $validated = $request->validate([
            'app_title'          => ['required', 'string', 'max:100'],
            'institution_name'   => ['required', 'string', 'max:255'],
            'institution_short'  => ['required', 'string', 'max:50'],
            'app_description'    => ['nullable', 'string', 'max:255'],
            'samba_server_host'  => ['nullable', 'string', 'max:100'],
            'app_logo'           => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,ico,webp', 'max:2048'],
            'app_theme'          => ['nullable', 'string', 'in:blue,emerald,violet,rose'],
        ], [
            'app_title.required'        => 'Nama aplikasi tidak boleh kosong.',
            'institution_name.required' => 'Nama lengkap instansi tidak boleh kosong.',
            'institution_short.required'=> 'Label singkat instansi tidak boleh kosong.',
            'app_logo.mimes'            => 'Format logo harus berupa PNG, JPG, JPEG, SVG, ICO, atau WEBP.',
            'app_logo.max'              => 'Ukuran file logo maksimal 2 MB.',
        ]);

        Setting::set('app_title', trim($validated['app_title']));
        Setting::set('institution_name', trim($validated['institution_name']));
        Setting::set('institution_short', trim($validated['institution_short']));
        Setting::set('app_description', trim($validated['app_description'] ?? ''));
        Setting::set('app_theme', $request->input('app_theme', 'blue'));
        Setting::set('samba_server_host', trim((string) $request->input('samba_server_host', '')));

        // Handle Upload Logo Kustom
        if ($request->hasFile('app_logo')) {
            $file = $request->file('app_logo');
            $ext = strtolower($file->getClientOriginalExtension());
            $filename = 'logo_' . time() . '.' . $ext;
            $uploadDir = public_path('uploads/logo');

            if (!File::isDirectory($uploadDir)) {
                File::makeDirectory($uploadDir, 0755, true, true);
            }

            // Hapus logo kustom lama jika ada
            $oldLogo = Setting::get('app_logo');
            if ($oldLogo && File::exists(public_path($oldLogo))) {
                @File::delete(public_path($oldLogo));
            }

            $file->move($uploadDir, $filename);
            Setting::set('app_logo', 'uploads/logo/' . $filename);
        } elseif ($request->boolean('remove_logo')) {
            $oldLogo = Setting::get('app_logo');
            if ($oldLogo && File::exists(public_path($oldLogo))) {
                @File::delete(public_path($oldLogo));
            }
            Setting::set('app_logo', null);
        }

        return redirect()->route('admin.index', ['tab' => 'settings'])
            ->with('success', 'Identitas instansi & pengaturan aplikasi berhasil disimpan.');
    }

    /**
     * Update data akun pengguna (Nama, Role, Semester, Kelas)
     */
    public function updateUser(Request $request, $id)
    {
        $currentUser = Auth::user();
        if (!$currentUser || $currentUser->role !== 'admin') {
            abort(403, 'Hanya Administrator yang memiliki wewenang mengubah data akun.');
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'role'     => ['required', 'in:mahasiswa,dosen,laboran,admin'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:8'],
            'kelas'    => ['nullable', 'string', 'max:50'],
        ], [
            'name.required' => 'Nama pengguna tidak boleh kosong.',
            'role.required' => 'Pilih role akun.',
        ]);

        $user->name = trim($validated['name']);
        $oldRole    = $user->role;
        $newRole    = $validated['role'];
        $user->role = $newRole;

        if ($newRole === 'mahasiswa') {
            $user->semester = !empty($validated['semester']) ? (int) $validated['semester'] : 1;
            $user->kelas    = !empty($validated['kelas']) ? strtoupper(trim($validated['kelas'])) : null;
        } else {
            $user->semester = null;
            $user->kelas    = null;
        }

        // Sesuaikan kuota jika role akun berubah
        if ($oldRole !== $newRole) {
            if ($newRole === 'admin') {
                $user->quota_limit = 0;
            } elseif ($newRole === 'laboran') {
                $user->quota_limit = 10737418240; // 10 GB
            } elseif ($newRole === 'dosen') {
                $user->quota_limit = 5368709120;  // 5 GB
            } else {
                $user->quota_limit = 1073741824;  // 1 GB
            }
        }

        $user->save();

        return redirect()->back()->with('success', "Data akun {$user->name} ({$user->nim}) berhasil diperbarui.");
    }

    /**
     * Reset Password Akun & Sinkronisasi ke Samba
     */
    public function resetPassword(Request $request, $id)
    {
        $currentUser = Auth::user();
        if (!$currentUser || $currentUser->role !== 'admin') {
            abort(403, 'Hanya Administrator yang memiliki wewenang mereset kata sandi.');
        }

        $request->validate([
            'new_password' => ['required', 'string', 'min:6'],
        ], [
            'new_password.required' => 'Kata sandi baru tidak boleh kosong.',
            'new_password.min'      => 'Kata sandi baru minimal 6 karakter.',
        ]);

        $user = User::findOrFail($id);
        $newPassword = $request->input('new_password');

        // 1. Update di SQLite
        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        // 2. Update di Samba Server Linux
        if ($this->sambaService->isValidNim($user->nim)) {
            $this->sambaService->createSambaAccount($user->nim, $newPassword, $user->role);
        }

        return back()->with('success', "Kata sandi untuk pengguna {$user->name} ({$user->nim}) berhasil direset!");
    }

    /**
     * Hapus Akun Pengguna & Direktori Samba
     */
    public function destroyUser(Request $request, $id)
    {
        $currentUser = Auth::user();
        if (!$currentUser || $currentUser->role !== 'admin') {
            abort(403, 'Hanya Administrator yang dapat menghapus akun pengguna.');
        }

        $user = User::findOrFail($id);

        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $nim = $user->nim;
        $name = $user->name;

        // Hapus dari database SQLite
        $user->delete();

        // Bersihkan folder akun jika ada
        if ($user->role === 'mahasiswa') {
            $studentFolder = rtrim(env('SAMBA_BASE_PATH', '/samba/mahasiswa'), '/') . '/' . $nim;
            if (File::isDirectory($studentFolder)) {
                try {
                    File::deleteDirectory($studentFolder);
                } catch (\Throwable $e) {}
            }
        } elseif (in_array($user->role, ['laboran', 'dosen'])) {
            $operasionalFolder = rtrim(env('SAMBA_OPERASIONAL_PATH', '/samba/operasional'), '/') . '/' . $nim;
            if (File::isDirectory($operasionalFolder)) {
                try {
                    File::deleteDirectory($operasionalFolder);
                } catch (\Throwable $e) {}
            }
        }

        // Hapus user samba dari Linux
        $safeNim = escapeshellarg($nim);
        @exec("sudo smbpasswd -x {$safeNim} 2>&1");
        @exec("sudo userdel {$safeNim} 2>&1");

        return back()->with('success', "Akun {$name} ({$nim}) dan kredensial Samba berhasil dihapus.");
    }

    /**
     * Import Massal Pengguna via CSV
     */
    public function importUsers(Request $request)
    {
        $currentUser = Auth::user();
        if (!$currentUser || $currentUser->role !== 'admin') {
            abort(403, 'Hanya Administrator yang dapat mengimpor data akun.');
        }

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ], [
            'csv_file.required' => 'Pilih file CSV untuk diimpor.',
            'csv_file.mimes'    => 'Format file harus berupa CSV atau teks (.csv, .txt).',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        $imported = 0;
        $skipped = 0;
        $row = 0;

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $row++;
            // Lewati header jika baris pertama adalah nama kolom
            if ($row === 1 && (strtolower($data[0] ?? '') === 'nim' || strtolower($data[0] ?? '') === 'username')) {
                continue;
            }

            // Format baris: NIM, Nama, Password, Role (opsional), Semester (opsional), Kelas (opsional)
            $nim       = trim($data[0] ?? '');
            $name      = trim($data[1] ?? '');
            $password  = trim($data[2] ?? '');
            $inputRole = strtolower(trim($data[3] ?? ''));
            $semester  = !empty($data[4]) ? (int) trim($data[4]) : null;
            $kelas     = !empty($data[5]) ? strtoupper(trim($data[5])) : null;

            if ($semester !== null && ($semester < 1 || $semester > 8)) {
                $semester = 1;
            }

            if (empty($nim) || empty($password)) {
                $skipped++;
                continue;
            }

            // Tentukan role: prioritaskan nilai eksplisit dari CSV jika ada
            if (!empty($inputRole) && in_array($inputRole, ['admin', 'laboran', 'dosen', 'mahasiswa'])) {
                $role = $inputRole;
            } elseif (str_starts_with($nim, '0686.')) {
                // Default jika kolom role kosong tapi format NIP staf
                $role = 'laboran';
            } else {
                $role = 'mahasiswa';
            }

            if (empty($name)) {
                if ($role === 'laboran') {
                    $name = "Laboran {$nim}";
                } elseif ($role === 'dosen') {
                    $name = "Dosen {$nim}";
                } else {
                    $name = "Mahasiswa {$nim}";
                }
            }

            // Kuota default: Laboran 10 GB, Dosen 5 GB, Admin Unlimited (0), Mahasiswa 1 GB
            if ($role === 'admin') {
                $quotaLimit = 0;
            } elseif ($role === 'laboran') {
                $quotaLimit = 10737418240; // 10 GB
            } elseif ($role === 'dosen') {
                $quotaLimit = 5368709120;  // 5 GB
            } else {
                $quotaLimit = 1073741824;  // 1 GB
            }

            // Buat akun di Linux & Samba dengan menyertakan role
            $this->sambaService->createSambaAccount($nim, $password, $role);

            // Simpan / update di SQLite
            $userPayload = [
                'name'        => $name,
                'password'    => Hash::make($password),
                'role'        => $role,
                'quota_limit' => $quotaLimit,
            ];

            if ($role === 'mahasiswa') {
                if ($semester !== null) {
                    $userPayload['semester'] = $semester;
                }
                if ($kelas !== null) {
                    $userPayload['kelas'] = $kelas;
                }
            }

            User::updateOrCreate(
                ['nim' => $nim],
                $userPayload
            );

            $imported++;
        }

        fclose($handle);

        return back()->with('success', "Import massal selesai! {$imported} akun berhasil dibuat/diperbarui, {$skipped} baris dilewati.");
    }

    /**
     * Membaca status koneksi Samba LAN secara real-time
     */
    protected function getLiveSambaSessions(): array
    {
        $sessions = [];
        $output = [];
        $status = null;

        // Jalankan perintah Linux 'smbstatus -b' (brief session overview)
        @exec("sudo smbstatus -b 2>&1", $output, $status);

        if (!empty($output)) {
            foreach ($output as $line) {
                $line = trim($line);
                // Abaikan baris header smbstatus
                if (empty($line) || str_starts_with($line, 'PID') || str_starts_with($line, '---') || str_starts_with($line, 'Samba')) {
                    continue;
                }
                
                // Parse kolom PID, Username, Group, Machine/IP
                $cols = preg_split('/\s+/', $line, 5);
                if (count($cols) >= 4) {
                    $sessions[] = [
                        'pid'         => $cols[0],
                        'username'    => $cols[1],
                        'machine_ip'  => $cols[3] ?? '127.0.0.1',
                        'time'        => now()->format('H:i:s'),
                        'status'      => 'Terkoneksi',
                    ];
                }
            }
        }

        // Jika smbstatus tidak ada atau di environment development lokal, sediakan sample visual
        if (empty($sessions) && (env('SAMBA_MOCK_SHELL', false) || windows_os() || !file_exists('/usr/bin/smbstatus'))) {
            $sessions = [
                ['pid' => '1042', 'username' => '202401001', 'machine_ip' => '192.168.1.105 (PC-LAB-01)', 'time' => now()->subMinutes(12)->format('H:i:s'), 'status' => 'Aktif (Read/Write)'],
                ['pid' => '1088', 'username' => '198801012015', 'machine_ip' => '192.168.1.112 (PC-LAB-08)', 'time' => now()->subMinutes(3)->format('H:i:s'), 'status' => 'Aktif (Read)'],
                ['pid' => '1102', 'username' => 'laboran',       'machine_ip' => '192.168.1.50 (LAPTOP-DOSEN)',  'time' => now()->subMinutes(35)->format('H:i:s'), 'status' => 'Aktif (Idle)'],
            ];
        }

        return $sessions;
    }
}
