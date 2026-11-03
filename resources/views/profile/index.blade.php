@extends('layouts.app')

@section('title', 'Profil Pengguna & Keamanan – Maki Samba')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- ═══ HEADER PROFIL ═══ --}}
    <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-black text-2xl shadow-md shadow-blue-500/20">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-lg font-black text-slate-800">{{ $user->name }}</h1>
                    @php
                        $role = $user->role;
                        $badgeClass = match($role) {
                            'admin' => 'bg-rose-100 text-rose-800 border-rose-200',
                            'laboran' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                            'dosen' => 'bg-purple-100 text-purple-800 border-purple-200',
                            default => 'bg-blue-100 text-blue-800 border-blue-200',
                        };
                    @endphp
                    <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border uppercase tracking-wider {{ $badgeClass }}">
                        {{ $role }}
                    </span>
                </div>
                <p class="text-xs font-mono text-slate-400 mt-0.5">NIM / Username: <strong class="text-slate-700">{{ $user->nim }}</strong></p>
            </div>
        </div>

        {{-- Info Kuota --}}
        <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-3 sm:text-right">
            <p class="text-[10.5px] uppercase font-bold text-slate-400 tracking-wider">Kapasitas Kuota</p>
            <p class="text-xs font-bold text-slate-800 mt-0.5">
                @if($user->role === 'admin' || (method_exists($user, 'isAdmin') && $user->isAdmin()) || (int) $user->quota_limit === 0)
                    <span class="text-slate-800">{{ $user->quota_used_formatted }}</span>
                    <span class="text-slate-400 font-normal">/</span>
                    <span class="text-blue-600 font-extrabold tracking-wide">Unlimited</span>
                @else
                    {{ $user->quota_used_formatted }} <span class="text-slate-400 font-normal">/</span> {{ $user->quota_limit_formatted }}
                @endif
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- ═══ FORM 1: UBAH PROFIL (NAMA LENGKAP) ═══ --}}
        <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2.5 mb-4 pb-3 border-b border-slate-100">
                    <span class="text-lg">👤</span>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-800">Ubah Data Profil</h2>
                        <p class="text-[11px] text-slate-400">Perbarui nama tampilan identitas Anda.</p>
                    </div>
                </div>

                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="space-y-4">
                        {{-- NIM (Read-Only) --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
                                NIM / Username
                            </label>
                            <input type="text" value="{{ $user->nim }}" disabled
                                   class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-mono text-slate-500 cursor-not-allowed">
                            <span class="text-[10.5px] text-slate-400 mt-1 block">NIM terikat permanen dengan akun sistem Linux Anda.</span>
                        </div>

                        {{-- Nama Lengkap --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                                Nama Lengkap
                            </label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                   class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-semibold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                            @error('name')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 text-right">
                        <button type="submit"
                                class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-sm shadow-blue-500/25 transition">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ═══ FORM 2: GANTI KATA SANDI DENGAN VALIDASI REAL-TIME ALPINE.JS ═══ --}}
        <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs flex flex-col justify-between"
             x-data="{
                 currentPassword: '',
                 newPassword: '',
                 confirmPassword: '',
                 showCurrent: false,
                 showNew: false,
                 showConfirm: false,
                 get isMatching() {
                     return this.newPassword.length >= 6 && this.newPassword === this.confirmPassword;
                 },
                 get validationText() {
                     if (!this.newPassword) return '';
                     if (this.newPassword.length < 6) return 'Minimal 6 karakter.';
                     if (!this.confirmPassword) return 'Ulangi kata sandi baru.';
                     if (this.newPassword === this.confirmPassword) return 'Kata sandi cocok ✅';
                     return 'Konfirmasi kata sandi tidak cocok ❌';
                 }
             }">
            <div>
                <div class="flex items-center gap-2.5 mb-4 pb-3 border-b border-slate-100">
                    <span class="text-lg">🔒</span>
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-800">Ganti Kata Sandi</h2>
                        <p class="text-[11px] text-slate-400">Sinkronisasi otomatis ke akun Samba LAN.</p>
                    </div>
                </div>

                <form action="{{ route('profile.password') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="space-y-3.5">
                        {{-- Kata Sandi Saat Ini --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                                Kata Sandi Saat Ini
                            </label>
                            <div class="relative">
                                <input :type="showCurrent ? 'text' : 'password'" name="current_password" x-model="currentPassword" required
                                       placeholder="Masukkan sandi saat ini..."
                                       class="w-full border border-slate-200 rounded-xl pl-3.5 pr-10 py-2.5 text-xs font-semibold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                                <button type="button" @click="showCurrent = !showCurrent" tabindex="-1"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1">
                                    <svg x-show="!showCurrent" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="showCurrent" class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Kata Sandi Baru --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                                Kata Sandi Baru
                            </label>
                            <div class="relative">
                                <input :type="showNew ? 'text' : 'password'" name="new_password" x-model="newPassword" required minlength="6"
                                       placeholder="Minimal 6 karakter..."
                                       class="w-full border border-slate-200 rounded-xl pl-3.5 pr-10 py-2.5 text-xs font-semibold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                                <button type="button" @click="showNew = !showNew" tabindex="-1"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1">
                                    <svg x-show="!showNew" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="showNew" class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Konfirmasi Kata Sandi Baru --}}
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                                Konfirmasi Kata Sandi Baru
                            </label>
                            <div class="relative">
                                <input :type="showConfirm ? 'text' : 'password'" name="new_password_confirmation" x-model="confirmPassword" required
                                       placeholder="Ketik ulang kata sandi baru..."
                                       class="w-full border border-slate-200 rounded-xl pl-3.5 pr-10 py-2.5 text-xs font-semibold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"
                                       :class="{ 'border-emerald-500 focus:border-emerald-500 focus:ring-emerald-100': isMatching && confirmPassword, 'border-rose-300 focus:border-rose-500 focus:ring-rose-100': !isMatching && confirmPassword }">
                                <button type="button" @click="showConfirm = !showConfirm" tabindex="-1"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1">
                                    <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="showConfirm" class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                    </svg>
                                </button>
                            </div>
                            
                            {{-- Validasi Real-Time Alpine.js --}}
                            <p class="text-[11px] font-bold mt-1.5 transition-colors"
                               :class="isMatching ? 'text-emerald-600' : 'text-rose-500'"
                               x-text="validationText"></p>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400">Update akun &amp; Samba</span>
                        <button type="submit"
                                :disabled="!isMatching || !currentPassword"
                                :class="isMatching && currentPassword ? 'bg-blue-600 hover:bg-blue-700 cursor-pointer shadow-blue-500/25' : 'bg-slate-300 cursor-not-allowed opacity-60 shadow-none'"
                                class="px-5 py-2.5 text-white font-bold text-xs rounded-xl shadow-sm transition-all duration-200">
                            Perbarui Kata Sandi
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

</div>
@endsection
