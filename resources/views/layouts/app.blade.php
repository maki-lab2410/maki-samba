<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', ($appTitle ?? 'Maki Samba') . ' – ' . ($appDescription ?? 'Portal File Manager & Otomatisasi Samba'))</title>
    
    {{-- Favicon Browser Tab --}}
    @php
        $themeFaviconHex = match($appTheme ?? 'blue') {
            'emerald' => '%23059669',
            'violet'  => '%237C3AED',
            'rose'    => '%23E11D48',
            default   => '%232563EB',
        };
    @endphp
    @if(!empty($appLogo) && file_exists(public_path($appLogo)))
        <link rel="icon" href="{{ asset($appLogo) }}">
        <link rel="apple-touch-icon" href="{{ asset($appLogo) }}">
    @else
        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='{{ $themeFaviconHex }}'><rect width='24' height='24' rx='6' fill='{{ $themeFaviconHex }}'/><path d='M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg>">
    @endif

    <!-- Tailwind CSS (CDN & Dynamic Pastel Theme) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        @if(($appTheme ?? 'blue') === 'emerald')
                        blue: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        },
                        @elseif(($appTheme ?? 'blue') === 'violet')
                        blue: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            200: '#ddd6fe',
                            300: '#c4b5fd',
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            800: '#5b21b6',
                            900: '#4c1d95',
                        },
                        @elseif(($appTheme ?? 'blue') === 'rose')
                        blue: {
                            50: '#fff1f2',
                            100: '#ffe4e6',
                            200: '#fecdd3',
                            300: '#fda4af',
                            400: '#fb7185',
                            500: '#f43f5e',
                            600: '#e11d48',
                            700: '#be123c',
                            800: '#9f1239',
                            900: '#881337',
                        },
                        @endif
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #F8FAFC;
        }
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { height: 6px; width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Normalisasi Select & Chevron Cross-Browser */
        select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: none;
        }
        select::-ms-expand {
            display: none;
        }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen flex flex-col"
      x-data="{ showSambaHelp: false, userDropdownOpen: false }">

    {{-- ═══ TOP NAVBAR DENGAN RBAC (Role-Based Access Control) ═══ --}}
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-2 sm:gap-4">
            
            {{-- Brand Logo & Info --}}
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                <a href="{{ route('filemanager.index') }}" class="flex items-center gap-2.5 sm:gap-3 group min-w-0">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-md shadow-blue-500/20 group-hover:scale-105 transition-transform overflow-hidden p-1.5 shrink-0">
                        @if(!empty($appLogo) && file_exists(public_path($appLogo)))
                            <img src="{{ asset($appLogo) }}" alt="Logo" class="w-full h-full object-contain rounded-xl">
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                            </svg>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            <span class="font-black text-blue-900 text-base sm:text-lg leading-tight tracking-tight truncate">{{ $appTitle ?? 'Maki Samba' }}</span>
                            <span class="bg-blue-100 text-blue-800 text-[9px] sm:text-[10px] font-bold px-1.5 sm:px-2 py-0.5 rounded-full uppercase tracking-wider shrink-0">{{ $institutionShort ?? 'Lab RMIK' }}</span>
                        </div>
                        <p class="text-[10px] sm:text-[11px] text-slate-500 font-medium truncate">{{ $appDescription ?? 'Portal File Manager & Otomatisasi Samba' }}</p>
                    </div>
                </a>
            </div>

            {{-- ═══ NAVIGATION MENU BERBASIS ROLE (RBAC) - SEGMENTED PILL CONTAINER ═══ --}}
            @auth
            <nav class="hidden md:flex items-center">
                <div class="bg-slate-100/90 p-1 rounded-2xl border border-slate-200/80 flex items-center gap-1 text-xs">
                    
                    {{-- 1. MENU MAHASISWA --}}
                    @if(auth()->user()->role === 'mahasiswa')
                        @php $activeFile = request()->routeIs('filemanager.*'); @endphp
                        <a href="{{ route('filemanager.index') }}"
                           class="px-3 py-1.5 rounded-xl transition flex items-center gap-2 {{ $activeFile ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                            <svg class="w-4 h-4 {{ $activeFile ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                            <span>File Manager</span>
                        </a>

                        @php $activeProf = request()->routeIs('profile.*'); @endphp
                        <a href="{{ route('profile.index') }}"
                           class="px-3 py-1.5 rounded-xl transition flex items-center gap-2 {{ $activeProf ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                            <svg class="w-4 h-4 {{ $activeProf ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span>Profil Saya</span>
                        </a>

                        <button type="button" x-on:click="showSambaHelp = true"
                                class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium transition flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Panduan Samba</span>
                        </button>
                    @endif

                    {{-- 2. MENU LABORAN & DOSEN --}}
                    @if(in_array(auth()->user()->role, ['laboran', 'dosen']))
                        @php $activeOp = request()->routeIs('filemanager.*'); @endphp
                        <a href="{{ route('filemanager.index') }}"
                           class="px-3 py-1.5 rounded-xl transition flex items-center gap-2 {{ $activeOp ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                            <svg class="w-4 h-4 {{ $activeOp ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                            <span>Folder Operasional</span>
                        </a>

                        @php $activeMhs = request()->routeIs('dosen.mahasiswa.*'); @endphp
                        <a href="{{ route('dosen.mahasiswa.index') }}"
                           class="px-3 py-1.5 rounded-xl transition flex items-center gap-2 {{ $activeMhs ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                            <svg class="w-4 h-4 {{ $activeMhs ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                            </svg>
                            <span>Direktori Mahasiswa</span>
                        </a>

                        @php $activeProf = request()->routeIs('profile.*'); @endphp
                        <a href="{{ route('profile.index') }}"
                           class="px-3 py-1.5 rounded-xl transition flex items-center gap-2 {{ $activeProf ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                            <svg class="w-4 h-4 {{ $activeProf ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span>Profil</span>
                        </a>

                        <button type="button" x-on:click="showSambaHelp = true"
                                class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium transition flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Panduan</span>
                        </button>
                    @endif

                    {{-- 3. MENU ADMIN --}}
                    @if(auth()->user()->role === 'admin')
                        @php $activeOp = request()->routeIs('filemanager.*'); @endphp
                        <a href="{{ route('filemanager.index') }}"
                           class="px-3 py-1.5 rounded-xl transition flex items-center gap-2 {{ $activeOp ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                            <svg class="w-4 h-4 {{ $activeOp ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                            <span>Operasional</span>
                        </a>

                        @php $activeMhs = request()->routeIs('dosen.mahasiswa.*'); @endphp
                        <a href="{{ route('dosen.mahasiswa.index') }}"
                           class="px-3 py-1.5 rounded-xl transition flex items-center gap-2 {{ $activeMhs ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                            <svg class="w-4 h-4 {{ $activeMhs ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                            </svg>
                            <span>Mahasiswa</span>
                        </a>

                        @php $activeAcc = request()->routeIs('admin.index') && !in_array(request('tab'), ['monitor', 'server', 'settings']); @endphp
                        <a href="{{ route('admin.index') }}"
                           class="px-3 py-1.5 rounded-xl transition flex items-center gap-2 {{ $activeAcc ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                            <svg class="w-4 h-4 {{ $activeAcc ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span>Akun</span>
                        </a>

                        @php $activeMon = request('tab') === 'monitor'; @endphp
                        <a href="{{ route('admin.index', ['tab' => 'monitor']) }}"
                           class="px-3 py-1.5 rounded-xl transition flex items-center gap-2 {{ $activeMon ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                            <svg class="w-4 h-4 {{ $activeMon ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <span>Monitoring</span>
                        </a>

                        @php $activeSrv = request('tab') === 'server'; @endphp
                        <a href="{{ route('admin.index', ['tab' => 'server']) }}"
                           class="px-3 py-1.5 rounded-xl transition flex items-center gap-2 {{ $activeSrv ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                            <svg class="w-4 h-4 {{ $activeSrv ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                            </svg>
                            <span>Server</span>
                        </a>

                        @php $activeSet = request('tab') === 'settings'; @endphp
                        <a href="{{ route('admin.index', ['tab' => 'settings']) }}"
                           class="px-3 py-1.5 rounded-xl transition flex items-center gap-2 {{ $activeSet ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                            <svg class="w-4 h-4 {{ $activeSet ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Pengaturan</span>
                        </a>

                        @php $activeProf = request()->routeIs('profile.*'); @endphp
                        <a href="{{ route('profile.index') }}"
                           class="px-3 py-1.5 rounded-xl transition flex items-center gap-2 {{ $activeProf ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                            <svg class="w-4 h-4 {{ $activeProf ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span>Profil</span>
                        </a>
                    @endif

                </div>
            </nav>
            @endauth

            {{-- ═══ USER DROPDOWN (Nama Lengkap & Role Badge) ═══ --}}
            <div class="flex items-center gap-3 shrink-0">
                @auth
                    <div class="relative" x-on:click.outside="userDropdownOpen = false">
                        <button type="button"
                                x-on:click="userDropdownOpen = !userDropdownOpen"
                                class="flex items-center gap-2.5 p-1.5 pl-3 bg-slate-50 hover:bg-slate-100 border border-slate-200/80 rounded-2xl transition">
                            
                            {{-- Info User --}}
                            <div class="text-right hidden sm:block">
                                <p class="text-xs font-extrabold text-slate-800 leading-tight">
                                    {{ auth()->user()->name }}
                                </p>
                                <p class="text-[10px] font-mono text-slate-400">
                                    {{ auth()->user()->nim }}
                                </p>
                            </div>

                            {{-- Role Badge Dinamis --}}
                            @php
                                $role = auth()->user()->role;
                                $badgeClass = match($role) {
                                    'admin' => 'bg-rose-100 text-rose-800 border-rose-200',
                                    'laboran' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                    'dosen' => 'bg-purple-100 text-purple-800 border-purple-200',
                                    default => 'bg-blue-100 text-blue-800 border-blue-200',
                                };
                            @endphp
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border uppercase tracking-wider {{ $badgeClass }}">
                                {{ $role }}
                            </span>

                            <svg class="w-3.5 h-3.5 text-slate-400 mr-1 transition-transform"
                                 :class="userDropdownOpen ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        {{-- Dropdown Menu --}}
                        <div x-cloak x-show="userDropdownOpen"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-52 bg-white rounded-2xl shadow-xl border border-slate-100 py-1.5 z-50 text-xs">
                            
                            <div class="px-4 py-2 border-b border-slate-100 sm:hidden">
                                <p class="font-bold text-slate-800">{{ auth()->user()->name }}</p>
                                <p class="text-[11px] font-mono text-slate-400">{{ auth()->user()->nim }}</p>
                            </div>

                            {{-- Menu Navigasi Cepat untuk Layar Mobile --}}
                            <div class="md:hidden border-b border-slate-100 pb-1 mb-1">
                                @if(auth()->user()->role === 'mahasiswa')
                                    <a href="{{ route('filemanager.index') }}" class="flex items-center gap-2 px-4 py-2 text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                        <span>📂</span>
                                        <span class="font-medium">File Manager</span>
                                    </a>
                                @elseif(in_array(auth()->user()->role, ['laboran', 'dosen']))
                                    <a href="{{ route('filemanager.index') }}" class="flex items-center gap-2 px-4 py-2 text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                        <span>📂</span>
                                        <span class="font-medium">Folder Operasional</span>
                                    </a>
                                    <a href="{{ route('dosen.mahasiswa.index') }}" class="flex items-center gap-2 px-4 py-2 text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                        <span>🎓</span>
                                        <span class="font-medium">Direktori Mahasiswa</span>
                                    </a>
                                @elseif(auth()->user()->role === 'admin')
                                    <a href="{{ route('filemanager.index') }}" class="flex items-center gap-2 px-4 py-2 text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                        <span>📂</span>
                                        <span class="font-medium">Operasional</span>
                                    </a>
                                    <a href="{{ route('dosen.mahasiswa.index') }}" class="flex items-center gap-2 px-4 py-2 text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                        <span>🎓</span>
                                        <span class="font-medium">Mahasiswa</span>
                                    </a>
                                    <a href="{{ route('admin.index') }}" class="flex items-center gap-2 px-4 py-2 text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                        <span>👥</span>
                                        <span class="font-medium">Akun</span>
                                    </a>
                                    <a href="{{ route('admin.index', ['tab' => 'monitor']) }}" class="flex items-center gap-2 px-4 py-2 text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                        <span>📊</span>
                                        <span class="font-medium">Monitoring</span>
                                    </a>
                                    <a href="{{ route('admin.index', ['tab' => 'server']) }}" class="flex items-center gap-2 px-4 py-2 text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                        <span>🖥️</span>
                                        <span class="font-medium">Server</span>
                                    </a>
                                    <a href="{{ route('admin.index', ['tab' => 'settings']) }}" class="flex items-center gap-2 px-4 py-2 text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                        <span>⚙️</span>
                                        <span class="font-medium">Pengaturan</span>
                                    </a>
                                @endif
                            </div>

                            <a href="{{ route('profile.index') }}"
                               class="flex items-center gap-2 px-4 py-2 text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                <span>👤</span>
                                <span class="font-medium">Profil & Keamanan</span>
                            </a>

                            <button type="button" x-on:click="showSambaHelp = true; userDropdownOpen = false"
                                    class="w-full flex items-center gap-2 px-4 py-2 text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition text-left">
                                <span>📁</span>
                                <span class="font-medium">Panduan Akses LAN</span>
                            </button>

                            <div class="border-t border-slate-100 my-1"></div>

                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-2 px-4 py-2 text-rose-600 hover:bg-rose-50 transition text-left font-bold">
                                    <span>🚪</span>
                                    <span>Keluar dari Portal</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="text-xs font-bold text-slate-600 hover:text-blue-600 transition">Login</a>
                    <a href="{{ route('register') }}" class="text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-2 rounded-xl transition shadow-xs">Daftar Akun</a>
                @endauth
            </div>

        </div>

        {{-- ═══ MOBILE NAVIGATION BAR RESPONSIF (Tampil di < 768px) ═══ --}}
        @auth
        <nav aria-label="Menu Mobile" class="md:hidden border-t border-slate-100 bg-slate-50/70 px-3 py-2 overflow-x-auto no-scrollbar">
            <div class="bg-slate-100/90 p-1 rounded-2xl border border-slate-200/80 inline-flex items-center gap-1 text-xs min-w-max">

                {{-- 1. MENU MAHASISWA (MOBILE) --}}
                @if(auth()->user()->role === 'mahasiswa')
                    @php
                        $activeFile = request()->routeIs('filemanager.*');
                        $activeProf = request()->routeIs('profile.*');
                    @endphp
                    <a href="{{ route('filemanager.index') }}"
                       class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap shrink-0 {{ $activeFile ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                        <svg class="w-4 h-4 {{ $activeFile ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        <span>File Manager</span>
                    </a>

                    <a href="{{ route('profile.index') }}"
                       class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap shrink-0 {{ $activeProf ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                        <svg class="w-4 h-4 {{ $activeProf ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Profil Saya</span>
                    </a>

                    <button type="button" x-on:click="showSambaHelp = true"
                            class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium transition flex items-center gap-1.5 whitespace-nowrap shrink-0">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Panduan Samba</span>
                    </button>
                @endif

                {{-- 2. MENU LABORAN & DOSEN (MOBILE) --}}
                @if(in_array(auth()->user()->role, ['laboran', 'dosen']))
                    @php
                        $activeOp   = request()->routeIs('filemanager.*');
                        $activeMhs  = request()->routeIs('dosen.mahasiswa.*');
                        $activeProf = request()->routeIs('profile.*');
                    @endphp
                    <a href="{{ route('filemanager.index') }}"
                       class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap shrink-0 {{ $activeOp ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                        <svg class="w-4 h-4 {{ $activeOp ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        <span>Folder Operasional</span>
                    </a>

                    <a href="{{ route('dosen.mahasiswa.index') }}"
                       class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap shrink-0 {{ $activeMhs ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                        <svg class="w-4 h-4 {{ $activeMhs ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                        </svg>
                        <span>Direktori Mahasiswa</span>
                    </a>

                    <a href="{{ route('profile.index') }}"
                       class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap shrink-0 {{ $activeProf ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                        <svg class="w-4 h-4 {{ $activeProf ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Profil</span>
                    </a>

                    <button type="button" x-on:click="showSambaHelp = true"
                            class="px-3 py-1.5 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium transition flex items-center gap-1.5 whitespace-nowrap shrink-0">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Panduan</span>
                    </button>
                @endif

                {{-- 3. MENU ADMIN (MOBILE) --}}
                @if(auth()->user()->role === 'admin')
                    @php
                        $activeOp   = request()->routeIs('filemanager.*');
                        $activeMhs  = request()->routeIs('dosen.mahasiswa.*');
                        $activeAcc  = request()->routeIs('admin.index') && !in_array(request('tab'), ['monitor', 'server', 'settings']);
                        $activeMon  = request('tab') === 'monitor';
                        $activeSrv  = request('tab') === 'server';
                        $activeSet  = request('tab') === 'settings';
                        $activeProf = request()->routeIs('profile.*');
                    @endphp
                    <a href="{{ route('filemanager.index') }}"
                       class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap shrink-0 {{ $activeOp ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                        <svg class="w-4 h-4 {{ $activeOp ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        <span>Operasional</span>
                    </a>

                    <a href="{{ route('dosen.mahasiswa.index') }}"
                       class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap shrink-0 {{ $activeMhs ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                        <svg class="w-4 h-4 {{ $activeMhs ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                        </svg>
                        <span>Mahasiswa</span>
                    </a>

                    <a href="{{ route('admin.index') }}"
                       class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap shrink-0 {{ $activeAcc ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                        <svg class="w-4 h-4 {{ $activeAcc ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span>Akun</span>
                    </a>

                    <a href="{{ route('admin.index', ['tab' => 'monitor']) }}"
                       class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap shrink-0 {{ $activeMon ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                        <svg class="w-4 h-4 {{ $activeMon ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Monitoring</span>
                    </a>

                    <a href="{{ route('admin.index', ['tab' => 'server']) }}"
                       class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap shrink-0 {{ $activeSrv ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                        <svg class="w-4 h-4 {{ $activeSrv ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                        </svg>
                        <span>Server</span>
                    </a>

                    <a href="{{ route('admin.index', ['tab' => 'settings']) }}"
                       class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap shrink-0 {{ $activeSet ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                        <svg class="w-4 h-4 {{ $activeSet ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Pengaturan</span>
                    </a>

                    <a href="{{ route('profile.index') }}"
                       class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap shrink-0 {{ $activeProf ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200/50 font-medium' }}">
                        <svg class="w-4 h-4 {{ $activeProf ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Profil</span>
                    </a>
                @endif

            </div>
        </nav>
        @endauth
    </header>

    {{-- ═══ NOTIFIKASI FLASH ALERT ═══ --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full mt-4">
        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-3.5 flex items-center gap-3 shadow-xs mb-3">
            <span class="text-emerald-600 text-lg">✅</span>
            <p class="text-xs font-semibold m-0 flex-1">{{ session('success') }}</p>
        </div>
        @endif

        @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3.5 flex items-center gap-3 shadow-xs mb-3">
            <span class="text-rose-600 text-lg">⚠️</span>
            <p class="text-xs font-semibold m-0 flex-1">{{ session('error') }}</p>
        </div>
        @endif

        @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3.5 flex items-start gap-3 shadow-xs mb-3">
            <span class="text-rose-600 text-lg">⚠️</span>
            <div class="text-xs font-semibold flex-1">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @if(session('warning'))
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl p-3.5 flex items-center gap-3 shadow-xs mb-3">
            <span class="text-amber-600 text-lg">💡</span>
            <p class="text-xs font-semibold m-0 flex-1">{{ session('warning') }}</p>
        </div>
        @endif
    </div>

    {{-- ═══ KONTEN UTAMA ═══ --}}
    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-4">
        @yield('content')
    </main>

    {{-- ═══ FOOTER ═══ --}}
    <footer class="bg-white border-t border-slate-200 py-4 mt-8">
        <div class="max-w-7xl mx-auto px-4 text-center text-xs text-slate-500">
            &copy; {{ date('Y') }} <strong>{{ $appTitle ?? 'Maki Samba' }}</strong> &bull; {{ $institutionName ?? 'Laboratorium Rekam Medis & Informasi Kesehatan (RMIK)' }}. Powered by AlmaLinux 10 & Samba.
        </div>
    </footer>

    {{-- ═══ KOMPONEN MODAL PANDUAN SAMBA (Modular) ═══ --}}
    @include('components.samba-guide-modal')

</body>
</html>
