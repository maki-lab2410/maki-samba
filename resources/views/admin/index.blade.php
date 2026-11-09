@extends('layouts.app')

@section('title', 'Admin Panel & Monitoring – Maki Samba')

@section('content')
<div x-data="{
    activeTab: '{{ $activeTab }}',
    showResetModal: false,
    showDeleteModal: false,
    showImportModal: false,
    showEditModal: false,
    selectedUser: { id: null, nim: '', name: '' },
    editUser: { id: null, nim: '', name: '', role: 'mahasiswa', semester: 1, kelas: '' },
    openReset(user) {
        this.selectedUser = user;
        this.showResetModal = true;
    },
    openDelete(user) {
        this.selectedUser = user;
        this.showDeleteModal = true;
    },
    openEdit(user) {
        this.editUser = { ...user };
        this.showEditModal = true;
    }
}" class="space-y-6">

    {{-- ═══ 1. HEADER STATS OVERVIEW (6 CARDS) ═══ --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        {{-- Total Akun --}}
        <div class="bg-white border border-slate-200 rounded-3xl p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shrink-0">
                👥
            </div>
            <div>
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total Akun</p>
                <p class="text-base font-black text-slate-800 leading-tight">{{ $stats['total_users'] }}</p>
                <p class="text-[10px] text-slate-400">{{ $stats['total_mahasiswa'] + $stats['total_laboran'] + ($stats['total_dosen'] ?? 0) }} Pengguna</p>
            </div>
        </div>

        {{-- Mahasiswa RMIK --}}
        <div class="bg-white border border-slate-200 rounded-3xl p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shrink-0">
                🎓
            </div>
            <div>
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Mahasiswa</p>
                <p class="text-base font-black text-slate-800 leading-tight">{{ $stats['total_mahasiswa'] }}</p>
                <p class="text-[10px] text-slate-400">Kuota: 1 GB/User</p>
            </div>
        </div>

        {{-- Dosen RMIK --}}
        <div class="bg-white border border-slate-200 rounded-3xl p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg shrink-0">
                👨‍🏫
            </div>
            <div>
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Dosen</p>
                <p class="text-base font-black text-slate-800 leading-tight">{{ $stats['total_dosen'] ?? 0 }}</p>
                <p class="text-[10px] text-slate-400">Kuota: 5 GB/User</p>
            </div>
        </div>

        {{-- Laboran RMIK --}}
        <div class="bg-white border border-slate-200 rounded-3xl p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shrink-0">
                🔬
            </div>
            <div>
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Laboran</p>
                <p class="text-base font-black text-slate-800 leading-tight">{{ $stats['total_laboran'] }}</p>
                <p class="text-[10px] text-slate-400">Kuota: 10 GB/User</p>
            </div>
        </div>

        {{-- Live Samba Connection --}}
        <div class="bg-white border border-slate-200 rounded-3xl p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0 relative">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping absolute top-2 right-2"></span>
                <span>📶</span>
            </div>
            <div>
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Koneksi Aktif LAN</p>
                <p class="text-base font-black text-emerald-600 leading-tight">{{ $stats['active_samba'] }} User</p>
                <p class="text-[10px] text-slate-400">Real-time smbstatus</p>
            </div>
        </div>

        {{-- IP Server --}}
        <div class="bg-white border border-slate-200 rounded-3xl p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-slate-100 text-slate-600 flex items-center justify-center text-lg shrink-0">
                🖥️
            </div>
            <div class="overflow-hidden min-w-0">
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Server Samba</p>
                <p class="text-xs font-mono font-bold text-slate-800 truncate" title="{{ $stats['server_host'] }}">{{ $stats['server_host'] }}</p>
                <p class="text-[10px] text-emerald-600 font-bold">● AlmaLinux 10</p>
            </div>
        </div>
    </div>

    {{-- ═══ 2. KONTEN TAB: MANAJEMEN AKUN (CRUD) ═══ --}}
    @if($activeTab === 'accounts' || empty($activeTab))
    <div class="bg-white border border-slate-200 rounded-3xl shadow-xs overflow-hidden">
        
        {{-- Toolbar: Filter Role Pills, Search & Import Action --}}
        <div class="p-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-slate-50/50">
            
            {{-- Filter Pills: Semua, Mahasiswa, Dosen, Laboran --}}
            <div class="flex items-center gap-1.5 flex-wrap">
                <a href="{{ route('admin.index', ['tab' => 'accounts', 'q' => $search, 'role' => 'all']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ ($roleFilter ?? 'all') === 'all' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100' }}">
                    <span>Semua</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ ($roleFilter ?? 'all') === 'all' ? 'bg-blue-700 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $stats['total_users'] }}</span>
                </a>
                <a href="{{ route('admin.index', ['tab' => 'accounts', 'q' => $search, 'role' => 'mahasiswa']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ ($roleFilter ?? 'all') === 'mahasiswa' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100' }}">
                    <span>🎓 Mahasiswa</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ ($roleFilter ?? 'all') === 'mahasiswa' ? 'bg-blue-700 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $stats['total_mahasiswa'] }}</span>
                </a>
                <a href="{{ route('admin.index', ['tab' => 'accounts', 'q' => $search, 'role' => 'dosen']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ ($roleFilter ?? 'all') === 'dosen' ? 'bg-purple-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100' }}">
                    <span>👨‍🏫 Dosen</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ ($roleFilter ?? 'all') === 'dosen' ? 'bg-purple-700 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $stats['total_dosen'] ?? 0 }}</span>
                </a>
                <a href="{{ route('admin.index', ['tab' => 'accounts', 'q' => $search, 'role' => 'laboran']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ ($roleFilter ?? 'all') === 'laboran' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100' }}">
                    <span>🔬 Laboran</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ ($roleFilter ?? 'all') === 'laboran' ? 'bg-indigo-700 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $stats['total_laboran'] }}</span>
                </a>
            </div>

            {{-- Right Actions: Search Box & Import CSV --}}
            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap justify-between sm:justify-end">
                <form action="{{ route('admin.index') }}" method="GET" class="relative max-w-xs w-full sm:w-64">
                    <input type="hidden" name="tab" value="accounts">
                    <input type="hidden" name="role" value="{{ $roleFilter ?? 'all' }}">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input type="text" name="q" value="{{ $search }}" placeholder="Cari NIM atau nama..."
                           class="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-medium outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                </form>

                <button type="button" x-on:click="showImportModal = true"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl shadow-xs transition shrink-0">
                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <span>Import CSV</span>
                </button>
            </div>

        </div>

        {{-- Tabel Akun --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-5">Pengguna</th>
                        <th class="py-3.5 px-4 w-32">Role</th>
                        <th class="py-3.5 px-4 w-52">Kapasitas Kuota Terpakai</th>
                        <th class="py-3.5 px-4 w-36">Folder Samba</th>
                        <th class="py-3.5 px-5 text-right w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $u)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3 px-5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 font-bold flex items-center justify-center shrink-0">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-extrabold text-slate-800 leading-tight">{{ $u->name }}</p>
                                    <p class="font-mono text-[11px] text-slate-400 mt-0.5">{{ $u->nim }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            @php
                                $roleClass = match($u->role) {
                                    'admin' => 'bg-rose-100 text-rose-800 border-rose-200',
                                    'laboran' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                    'dosen' => 'bg-purple-100 text-purple-800 border-purple-200',
                                    default => 'bg-blue-100 text-blue-800 border-blue-200',
                                };
                            @endphp
                            <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full border uppercase tracking-wider {{ $roleClass }}">
                                {{ $u->role }}
                            </span>

                            @if($u->role === 'mahasiswa')
                                <div class="flex items-center gap-1 mt-1">
                                    <span class="text-[9.5px] font-bold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                                        Sem. {{ $u->semester ?: 1 }}
                                    </span>
                                    @if($u->kelas)
                                        <span class="text-[9.5px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                                            Kls. {{ $u->kelas }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @if($u->role === 'admin' || $u->quota_limit === 0)
                                <div>
                                    <div class="flex justify-between text-[11px] font-mono text-slate-600 mb-1">
                                        <span>{{ $u->quota_used_formatted }}</span>
                                        <span class="font-extrabold text-blue-600">Unlimited</span>
                                    </div>
                                    <div class="w-full bg-blue-100/70 rounded-full h-2 overflow-hidden">
                                        <div class="h-full rounded-full bg-blue-500" style="width: 100%;"></div>
                                    </div>
                                </div>
                            @else
                                @php
                                    $pct = $u->quota_percent;
                                    $barBg = $pct < 75 ? 'bg-emerald-500' : ($pct <= 90 ? 'bg-amber-500' : 'bg-rose-500');
                                @endphp
                                <div>
                                    <div class="flex justify-between text-[11px] font-mono text-slate-600 mb-1">
                                        <span>{{ $u->quota_used_formatted }}</span>
                                        <span class="font-bold">{{ $pct }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                        <div class="h-full rounded-full {{ $barBg }}" style="width: {{ $pct }}%;"></div>
                                    </div>
                                </div>
                            @endif
                        </td>
                        <td class="py-3 px-4 font-mono text-[11.5px] text-slate-600">
                            \{{ $u->samba_share_path }}
                        </td>
                        <td class="py-3 px-5 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                {{-- Tombol Edit Akun (Nama, Semester, Kelas, Role) --}}
                                <button type="button"
                                        x-on:click="openEdit({ id: {{ $u->id }}, nim: '{{ $u->nim }}', name: '{{ addslashes($u->name) }}', role: '{{ $u->role }}', semester: {{ $u->semester ?: 1 }}, kelas: '{{ addslashes($u->kelas ?? '') }}' })"
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition"
                                        title="Edit Data Akun (Semester & Kelas)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>

                                {{-- Tombol Reset Password --}}
                                <button type="button"
                                        x-on:click="openReset({ id: {{ $u->id }}, nim: '{{ $u->nim }}', name: '{{ addslashes($u->name) }}' })"
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition"
                                        title="Reset Password">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                </button>

                                {{-- Tombol Hapus Akun (Hanya Admin & tidak menghapus diri sendiri) --}}
                                @if(auth()->user()->role === 'admin' && $u->id !== auth()->id())
                                <button type="button"
                                        x-on:click="openDelete({ id: {{ $u->id }}, nim: '{{ $u->nim }}', name: '{{ addslashes($u->name) }}' })"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                        title="Hapus Akun">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-400">
                            Tidak ada data pengguna yang cocok dengan pencarian.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
        <div class="p-4 border-t border-slate-100 flex justify-end">
            {{ $users->links() }}
        </div>
        @endif
    </div>
    @endif

    {{-- ═══ 4. KONTEN TAB: LIVE MONITORING LAN ═══ --}}
    @if($activeTab === 'monitor')
    <div class="bg-white border border-slate-200 rounded-3xl shadow-xs overflow-hidden p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h2 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Monitoring Real-Time Koneksi Samba LAN</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Mendeteksi perangkat yang sedang aktif membuka folder share laboratorium.</p>
            </div>
            <button type="button" onclick="location.reload()"
                    class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>Segarkan</span>
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">PID</th>
                        <th class="py-3 px-4">Pengguna (NIM)</th>
                        <th class="py-3 px-4">Alamat Perangkat / IP</th>
                        <th class="py-3 px-4">Waktu Terkoneksi</th>
                        <th class="py-3 px-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($liveSessions as $session)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3 px-4 font-mono text-slate-400">{{ $session['pid'] }}</td>
                        <td class="py-3 px-4 font-extrabold text-slate-800">
                            <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-lg font-mono">
                                {{ $session['username'] }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-600">{{ $session['machine_ip'] }}</td>
                        <td class="py-3 px-4 text-slate-500">{{ $session['time'] }}</td>
                        <td class="py-3 px-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                {{ $session['status'] }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-10 text-center text-slate-400">
                            Belum ada perangkat yang aktif mengakses folder Samba di LAN saat ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ═══ 5. KONTEN TAB: STATUS SERVER & LINUX ═══ --}}
    @if($activeTab === 'server' && auth()->user()->role === 'admin')
    <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
        <h2 class="text-sm font-extrabold text-slate-800 pb-3 border-b border-slate-100">
            ⚙️ Parameter Lingkungan &amp; Integrasi Linux
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
                <p class="font-bold text-slate-800">Status Server &amp; PHP</p>
                <div class="space-y-1 font-mono text-[11px] text-slate-600">
                    <p>Sistem Operasi : <span class="text-slate-800 font-bold">AlmaLinux 10 / RHEL 10</span></p>
                    <p>Web Server    : <span class="text-slate-800 font-bold">Apache (aaPanel)</span></p>
                    <p>PHP Version   : <span class="text-emerald-600 font-bold">PHP {{ phpversion() }}</span></p>
                    <p>Database      : <span class="text-slate-800 font-bold">SQLite (/database.sqlite)</span></p>
                    <p>Cache &amp; Session: <span class="text-blue-600 font-bold">FILE-BASED (Active)</span></p>
                </div>
            </div>

            <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
                <p class="font-bold text-slate-800">Integrasi Storage &amp; Keamanan</p>
                <div class="space-y-1 font-mono text-[11px] text-slate-600">
                    <p>Root Mahasiswa : <span class="text-blue-700 font-bold">/samba/mahasiswa/{NIM}</span></p>
                    <p>Folder Izin    : <span class="text-emerald-600 font-bold">0777 (No Permission Denied)</span></p>
                    <p>Script Path    : <span class="text-slate-800">/usr/local/bin/buat_akun_samba.sh</span></p>
                    <p>Sanitasi Shell : <span class="text-emerald-600 font-bold">escapeshellarg() ACTIVE</span></p>
                    <p>open_basedir   : <span class="text-emerald-600 font-bold">/samba/ Allowed</span></p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══ 5. TAB PENGATURAN IDENTITAS INSTANSI ═══ --}}
    @if($activeTab === 'settings' && auth()->user()->role === 'admin')
    <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xs">
        
        {{-- Header Tab --}}
        <div class="flex items-center gap-3.5 pb-6 border-b border-slate-100 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl shadow-xs">
                ⚙️
            </div>
            <div>
                <h2 class="text-base font-extrabold text-slate-800">Pengaturan Identitas Instansi &amp; Aplikasi</h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Sesuaikan nama lembaga, label singkatan, dan judul sistem untuk kebutuhan institusi Anda.
                </p>
            </div>
        </div>

        {{-- Grid 2 Kolom: Kiri Form Pengaturan, Kanan Lisensi & Info Sistem --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            {{-- Kolom Kiri: Form Pengaturan (Lebar 7 dari 12 kolom) --}}
            <div class="lg:col-span-7">
                <form action="{{ route('admin.update-settings') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="space-y-4">
                        {{-- Upload Logo Kustom Aplikasi / Tab Bar --}}
                        <div class="bg-slate-50/80 border border-slate-200 rounded-2xl p-4">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <span>🖼️</span>
                                    <span>Logo Aplikasi &amp; Favicon Tab Bar</span>
                                </span>
                                @if(!empty($settings['app_logo']) && file_exists(public_path($settings['app_logo'])))
                                    <span class="text-[10.5px] text-blue-600 font-semibold bg-blue-50 px-2 py-0.5 rounded-md border border-blue-200">Kustom Aktif</span>
                                @else
                                    <span class="text-[10.5px] text-slate-400 font-normal">Default SVG Icon</span>
                                @endif
                            </label>

                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                                {{-- Preview Logo Saat Ini --}}
                                <div class="w-16 h-16 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center justify-center overflow-hidden shrink-0 p-2">
                                    @if(!empty($settings['app_logo']) && file_exists(public_path($settings['app_logo'])))
                                        <img src="{{ asset($settings['app_logo']) }}" alt="Logo Saat Ini" class="w-full h-full object-contain">
                                    @else
                                        <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                {{-- Input File & Opsi Hapus --}}
                                <div class="flex-1 space-y-2 w-full">
                                    <input type="file" name="app_logo" accept=".png,.jpg,.jpeg,.svg,.ico,.webp"
                                           class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-slate-200 rounded-xl p-1.5 bg-white">
                                    <div class="flex items-center justify-between flex-wrap gap-2 text-[11px] text-slate-400">
                                        <span>Format: PNG, SVG, JPG, WEBP, ICO (Maks. 2MB, Rasio 1:1)</span>
                                        @if(!empty($settings['app_logo']) && file_exists(public_path($settings['app_logo'])))
                                            <label class="flex items-center gap-1.5 text-rose-600 hover:text-rose-700 font-semibold cursor-pointer">
                                                <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-400">
                                                <span>Reset ke Logo Bawaan</span>
                                            </label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Pemilihan Tema Warna Pastel --}}
                        <div class="bg-slate-50/80 border border-slate-200 rounded-2xl p-4"
                             x-data="{ selectedTheme: '{{ old('app_theme', $settings['app_theme'] ?? 'blue') }}' }">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1 flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <span>🎨</span>
                                    <span>Pilihan Tema Warna Pastel</span>
                                </span>
                                <span class="text-[11px] font-semibold text-blue-600 capitalize" x-text="selectedTheme === 'blue' ? 'Ocean Sky (Biru)' : (selectedTheme === 'emerald' ? 'Mint Sage (Hijau)' : 'Lavender Lilac (Ungu)')"></span>
                            </label>
                            <p class="text-[11px] text-slate-400 mb-3">Pilih nuansa warna aksen utama yang akan diterapkan pada navbar, tombol, badge, dan seluruh antarmuka portal.</p>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                {{-- 1. Ocean Sky (Biru Pastel) --}}
                                <label class="relative flex flex-col p-3 rounded-2xl border-2 cursor-pointer transition-all bg-white"
                                       :class="selectedTheme === 'blue' ? 'border-blue-500 ring-2 ring-blue-100 shadow-xs' : 'border-slate-200 hover:border-slate-300'">
                                    <input type="radio" name="app_theme" value="blue" x-model="selectedTheme" class="sr-only">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-4 h-4 rounded-full bg-blue-500 inline-block shadow-xs"></span>
                                            <span class="w-3 h-3 rounded-full bg-blue-100 inline-block"></span>
                                        </div>
                                        <span x-show="selectedTheme === 'blue'" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded-md">Aktif</span>
                                    </div>
                                    <span class="text-xs font-extrabold text-slate-800">Ocean Sky</span>
                                    <span class="text-[10.5px] text-slate-400 mt-0.5">Biru Pastel Lembut</span>
                                </label>

                                {{-- 2. Mint Sage (Hijau Pastel) --}}
                                <label class="relative flex flex-col p-3 rounded-2xl border-2 cursor-pointer transition-all bg-white"
                                       :class="selectedTheme === 'emerald' ? 'border-emerald-500 ring-2 ring-emerald-100 shadow-xs' : 'border-slate-200 hover:border-slate-300'">
                                    <input type="radio" name="app_theme" value="emerald" x-model="selectedTheme" class="sr-only">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-4 h-4 rounded-full bg-emerald-500 inline-block shadow-xs"></span>
                                            <span class="w-3 h-3 rounded-full bg-emerald-100 inline-block"></span>
                                        </div>
                                        <span x-show="selectedTheme === 'emerald'" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-md">Aktif</span>
                                    </div>
                                    <span class="text-xs font-extrabold text-slate-800">Mint Sage</span>
                                    <span class="text-[10.5px] text-slate-400 mt-0.5">Hijau Mint Pastel</span>
                                </label>

                                {{-- 3. Lavender Lilac (Ungu Pastel) --}}
                                <label class="relative flex flex-col p-3 rounded-2xl border-2 cursor-pointer transition-all bg-white"
                                       :class="selectedTheme === 'violet' ? 'border-violet-500 ring-2 ring-violet-100 shadow-xs' : 'border-slate-200 hover:border-slate-300'">
                                    <input type="radio" name="app_theme" value="violet" x-model="selectedTheme" class="sr-only">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-4 h-4 rounded-full bg-violet-500 inline-block shadow-xs"></span>
                                            <span class="w-3 h-3 rounded-full bg-violet-100 inline-block"></span>
                                        </div>
                                        <span x-show="selectedTheme === 'violet'" class="text-[10px] font-bold text-violet-600 bg-violet-50 px-1.5 py-0.5 rounded-md">Aktif</span>
                                    </div>
                                    <span class="text-xs font-extrabold text-slate-800">Lavender Lilac</span>
                                    <span class="text-[10.5px] text-slate-400 mt-0.5">Ungu Lilac Pastel</span>
                                </label>
                            </div>
                        </div>

                        {{-- Nama Lengkap Lembaga / Instansi --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                <span>🏛️</span>
                                <span>Nama Lengkap Instansi / Lembaga</span>
                            </label>
                            <input type="text" name="institution_name" required
                                   value="{{ old('institution_name', $settings['institution_name'] ?? $institutionName ?? 'Laboratorium Rekam Medis & Informasi Kesehatan (RMIK)') }}"
                                   placeholder="Contoh: Laboratorium Komputer Terpadu Universitas ABC"
                                   class="w-full border border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-semibold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                            <p class="text-[11px] text-slate-400 mt-1">Ditampilkan di footer sistem, halaman login, modal panduan jaringan, dan dokumen resmi sistem.</p>
                        </div>

                        {{-- Nama Singkat / Badge Instansi & Nama Aplikasi --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                    <span>🏷️</span>
                                    <span>Label Singkat / Badge Instansi</span>
                                </label>
                                <input type="text" name="institution_short" required
                                       value="{{ old('institution_short', $settings['institution_short'] ?? $institutionShort ?? 'Lab RMIK') }}"
                                       placeholder="Contoh: Lab Komputer / FTI / RMIK"
                                       class="w-full border border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-semibold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                                <p class="text-[11px] text-slate-400 mt-1">Ditampilkan pada badge biru di navbar dan subtitle login.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                    <span>🚀</span>
                                    <span>Nama Aplikasi</span>
                                </label>
                                <input type="text" name="app_title" required
                                       value="{{ old('app_title', $settings['app_title'] ?? $appTitle ?? 'Maki Samba') }}"
                                       placeholder="Contoh: Maki Samba"
                                       class="w-full border border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-semibold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                                <p class="text-[11px] text-slate-400 mt-1">Nama branding portal utama.</p>
                            </div>
                        </div>

                        {{-- Deskripsi / Subtitle Aplikasi --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                <span>📝</span>
                                <span>Deskripsi Singkat / Slogan Portal</span>
                            </label>
                            <input type="text" name="app_description"
                                   value="{{ old('app_description', $settings['app_description'] ?? $appDescription ?? 'Portal File Manager & Otomatisasi Samba') }}"
                                   placeholder="Contoh: Portal File Manager & Otomatisasi Samba Server"
                                   class="w-full border border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-semibold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                            <p class="text-[11px] text-slate-400 mt-1">Keterangan yang tampil di bawah logo utama pada navbar dan header.</p>
                        </div>

                        {{-- Host / IP Server Samba --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                <span>🌐</span>
                                <span>Host / IP Server Samba (LAN)</span>
                            </label>
                            <input type="text" name="samba_server_host"
                                   value="{{ old('samba_server_host', $settings['samba_server_host'] ?? '') }}"
                                   placeholder="Contoh: {{ request()->getHost() }} (Kosongkan untuk otomatis menggunakan host saat ini)"
                                   class="w-full border border-slate-200 rounded-2xl px-4 py-2.5 text-xs font-semibold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                            <p class="text-[11px] text-slate-400 mt-1">IP atau hostname yang digunakan pengguna saat melakukan koneksi network share (<code class="font-mono">\\IP\share</code> atau <code class="font-mono">smb://IP/share</code>). Jika dikosongkan, sistem otomatis mendeteksi IP/Domain server yang sedang diakses saat ini (<strong class="text-blue-600 font-mono">{{ request()->getHost() }}</strong>).</p>
                        </div>
                    </div>

                    {{-- Kotak Info Konfigurasi .env --}}
                    <div class="bg-blue-50/60 border border-blue-200/80 rounded-2xl p-4 text-xs text-blue-900 space-y-1.5">
                        <div class="flex items-center gap-2 font-bold text-blue-800">
                            <span>💡</span>
                            <span>Konfigurasi via File .env</span>
                        </div>
                        <p class="text-[11.5px] text-blue-700 leading-relaxed">
                            Pengaturan ini disimpan secara permanen di database lokal SQLite. Nilai default juga dapat didefinisikan langsung pada file <code class="font-mono bg-white/70 px-1.5 py-0.5 rounded border border-blue-200">.env</code>:
                        </p>
                        <div class="font-mono text-[11px] bg-slate-900 text-emerald-400 p-2.5 rounded-xl select-all">
                            APP_TITLE="Maki Samba"<br>
                            APP_INSTITUTION="Nama Instansi / Universitas Anda"<br>
                            APP_INSTITUTION_SHORT="Lab Komputer"<br>
                            APP_DESCRIPTION="Portal File Manager & Otomatisasi Samba"<br>
                            APP_THEME="blue" <span class="text-slate-400"># Opsi: blue, emerald, violet</span><br>
                            SAMBA_SERVER_HOST="192.168.67.225" <span class="text-slate-400"># IP Server Samba LAN</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="submit"
                                class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-2">
                            <span>💾</span>
                            <span>Simpan Pengaturan Instansi</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Kolom Kanan: Lisensi & Info Sistem (Lebar 5 dari 12 kolom) --}}
            <div class="lg:col-span-5 space-y-4">
                {{-- Card Lisensi MIT (Clean Light Style Konsisten) --}}
                <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-bold shadow-xs">
                                ⚖️
                            </div>
                            <div>
                                <h3 class="text-xs font-extrabold tracking-wider uppercase text-slate-800">Lisensi Perangkat Lunak</h3>
                                <p class="text-[11px] text-blue-600 font-semibold">MIT License</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold tracking-wide uppercase border border-emerald-200">
                            Resmi
                        </span>
                    </div>

                    <div class="mt-4 space-y-3 text-xs">
                        <p class="font-mono text-xs text-slate-700">
                            Copyright &copy; 2026 <strong class="text-slate-900 font-extrabold">Maki</strong>
                        </p>
                        <p class="text-slate-500 text-[11.5px] leading-relaxed">
                            Hak Cipta dilindungi di bawah ketentuan Lisensi MIT. Perangkat lunak ini bebas digunakan, dimodifikasi, dan didistribusikan untuk keperluan akademik maupun institusional.
                        </p>

                        <div class="bg-slate-50 rounded-2xl p-3 border border-slate-200 font-mono text-[10.5px] text-slate-600 leading-relaxed max-h-36 overflow-y-auto">
                            Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files, to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software.
                        </div>
                    </div>
                </div>

                {{-- Card Info Sistem & Versi --}}
                <div class="bg-slate-50 border border-slate-200 rounded-3xl p-5 space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-base">📦</span>
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Informasi Lingkungan Sistem</h4>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-200/60">
                            <span class="text-slate-500">Nama Aplikasi</span>
                            <span class="font-bold text-slate-800">{{ $settings['app_title'] ?? $appTitle ?? 'Maki Samba' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-200/60">
                            <span class="text-slate-500">Versi Portal</span>
                            <span class="px-2 py-0.5 rounded-lg bg-blue-100 text-blue-700 font-mono font-bold text-[11px]">v1.2.0</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-200/60">
                            <span class="text-slate-500">Tema Warna</span>
                            <span class="font-bold text-blue-600 capitalize">
                                {{ ($settings['app_theme'] ?? $appTheme ?? 'blue') === 'emerald' ? 'Mint Sage' : (($settings['app_theme'] ?? $appTheme ?? 'blue') === 'violet' ? 'Lavender Lilac' : 'Ocean Sky') }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-200/60">
                            <span class="text-slate-500">Framework Web</span>
                            <span class="font-semibold text-slate-700">Laravel {{ app()->version() }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-200/60">
                            <span class="text-slate-500">Versi PHP</span>
                            <span class="font-mono font-bold text-slate-700">{{ PHP_VERSION }}</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5 border-b border-slate-200/60">
                            <span class="text-slate-500">Basis Data</span>
                            <span class="font-semibold text-slate-700">SQLite 3</span>
                        </div>
                        <div class="flex items-center justify-between py-1.5">
                            <span class="text-slate-500">Protokol File</span>
                            <span class="font-semibold text-slate-700">SMB / CIFS (Samba 4)</span>
                        </div>
                    </div>
                </div>

                {{-- Card Pengembang --}}
                <div class="bg-blue-50/70 border border-blue-200/70 rounded-3xl p-4 text-center">
                    <p class="text-xs text-slate-600 font-medium">Dikembangkan untuk Manajemen Laboratorium</p>
                    <p class="text-xs font-extrabold text-blue-700 mt-0.5">Maki Samba Platform</p>
                </div>
            </div>

        </div>
    </div>
    @endif

    {{-- ═══ 5.5. MODAL EDIT AKUN (NAMA, SEMESTER, KELAS, ROLE) ═══ --}}
    <div x-cloak x-show="showEditModal"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div x-on:click.outside="showEditModal = false"
             class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                        ✏️
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-800 text-sm">Edit Data Pengguna</h3>
                        <p class="text-[11px] font-mono text-slate-400" x-text="editUser.nim"></p>
                    </div>
                </div>
                <button x-on:click="showEditModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">&times;</button>
            </div>

            <form x-bind:action="'{{ url('/admin/user') }}/' + editUser.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Nama Lengkap</label>
                    <input type="text" name="name" x-model="editUser.name" required
                           class="w-full border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-semibold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1 flex items-center gap-1">
                        <span>🏷️</span>
                        <span>Role Akun</span>
                    </label>
                    <div class="relative">
                        <select name="role" x-model="editUser.role"
                                class="w-full appearance-none pl-3.5 pr-9 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition cursor-pointer">
                            <option value="mahasiswa">Mahasiswa (Kuota 1 GB)</option>
                            <option value="dosen">Dosen (Kuota 5 GB)</option>
                            <option value="laboran">Laboran (Kuota 10 GB)</option>
                            <option value="admin">Administrator (Unlimited)</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Bidang Khusus Mahasiswa: Semester & Kelas --}}
                <div x-show="editUser.role === 'mahasiswa'" class="grid grid-cols-2 gap-3 p-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-600 uppercase tracking-wider mb-1 flex items-center gap-1">
                            <span>📚</span>
                            <span>Semester</span>
                        </label>
                        <div class="relative">
                            <select name="semester" x-model="editUser.semester"
                                    class="w-full appearance-none pl-3 pr-8 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition cursor-pointer">
                                @for($i = 1; $i <= 8; $i++)
                                    <option value="{{ $i }}">Semester {{ $i }}</option>
                                @endfor
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10.5px] font-bold text-slate-600 uppercase tracking-wider mb-1 flex items-center gap-1">
                            <span>🏫</span>
                            <span>Kelas</span>
                        </label>
                        <input type="text" name="kelas" x-model="editUser.kelas" placeholder="Misal: A, B, TI.2A"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold bg-white outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button x-on:click="showEditModal = false" type="button"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ 6. MODAL RESET PASSWORD (Alpine.js) ═══ --}}
    <div x-cloak x-show="showResetModal"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div x-on:click.outside="showResetModal = false"
             class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200">
            <h3 class="font-extrabold text-slate-800 text-base mb-1">Reset Kata Sandi</h3>
            <p class="text-xs text-slate-500 mb-4">
                Atur kata sandi baru untuk <strong class="text-blue-700 font-mono" x-text="selectedUser.nim"></strong> (<span x-text="selectedUser.name"></span>).
            </p>

            <form x-bind:action="'{{ url('/admin/user') }}/' + selectedUser.id + '/reset-password'" method="POST"
                  x-data="{ showResetPass: false }">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Kata Sandi Baru</label>
                    <div class="relative">
                        <input :type="showResetPass ? 'text' : 'password'" name="new_password" required minlength="6" placeholder="Minimal 6 karakter..."
                               class="w-full border border-slate-200 rounded-xl pl-3.5 pr-10 py-2.5 text-xs font-semibold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        <button type="button" @click="showResetPass = !showResetPass" tabindex="-1"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1">
                            <svg x-show="!showResetPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showResetPass" class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button x-on:click="showResetModal = false" type="button"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                        Reset Sandi
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ 7. MODAL HAPUS AKUN ═══ --}}
    <div x-cloak x-show="showDeleteModal"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div x-on:click.outside="showDeleteModal = false"
             class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>

            <h3 class="font-extrabold text-slate-800 text-base mb-1">Hapus Akun Pengguna</h3>
            <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                Yakin ingin menghapus akun <span class="font-bold text-slate-800" x-text="selectedUser.name"></span> (<code class="text-rose-600 font-bold" x-text="selectedUser.nim"></code>)? Seluruh folder di Linux Samba juga akan dibersihkan.
            </p>

            <form x-bind:action="'{{ url('/admin/user') }}/' + selectedUser.id" method="POST">
                @csrf
                @method('DELETE')

                <div class="flex items-center justify-end gap-2">
                    <button x-on:click="showDeleteModal = false" type="button"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                        Ya, Hapus Akun
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ 8. MODAL IMPORT CSV MASSAL ═══ --}}
    <div x-cloak x-show="showImportModal"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div x-on:click.outside="showImportModal = false"
             class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-xl">📥</span>
                    <h3 class="font-extrabold text-slate-800 text-base">Import Akun Massal (CSV)</h3>
                </div>
                <button x-on:click="showImportModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">&times;</button>
            </div>

            <form action="{{ route('admin.import') }}" method="POST" enctype="multipart/form-data"
                  onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit]').innerText = 'Mengimpor akun ke server...';">
                @csrf

                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Pilih File CSV Pengguna (Mahasiswa / Dosen / Laboran)</label>
                    <input type="file" name="csv_file" accept=".csv,.txt" required
                           class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-slate-200 rounded-2xl p-2">
                </div>

                {{-- Petunjuk Format CSV --}}
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 mb-5 text-xs text-slate-600 space-y-1.5">
                    <p class="font-bold text-slate-800">Format Kolom CSV (Pemisah Koma):</p>
                    <div class="font-mono text-[11px] bg-slate-900 text-emerald-400 p-2.5 rounded-xl select-all">
                        NIM/Username,Nama,Password,Role,Semester,Kelas<br>
                        202401001,Budi Santoso,Rahasia123,mahasiswa,1,A<br>
                        202302045,Siti Aminah,Rahasia123,mahasiswa,3,TI.2A<br>
                        198501012010,Dr. Hendra (Dosen),DosenPass123,dosen
                    </div>
                    <p class="text-[10.5px] text-slate-400 leading-relaxed">
                        Kolom <strong>Role</strong>, <strong>Semester</strong> (1-8), dan <strong>Kelas</strong> bersifat opsional. Mahasiswa otomatis mendapatkan kuota 1 GB, Dosen 5 GB, dan Laboran 10 GB.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button x-on:click="showImportModal = false" type="button"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                        Mulai Import Akun
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
