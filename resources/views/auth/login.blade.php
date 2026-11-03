<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – {{ $appTitle ?? 'Maki Samba' }} ({{ $institutionShort ?? 'Lab RMIK' }})</title>
    
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

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        @if(($appTheme ?? 'blue') === 'emerald')
                        blue: {
                            50: '#ecfdf5', 100: '#d1fae5', 200: '#a7f3d0', 300: '#6ee7b7', 400: '#34d399',
                            500: '#10b981', 600: '#059669', 700: '#047857', 800: '#065f46', 900: '#064e3b',
                        },
                        @elseif(($appTheme ?? 'blue') === 'violet')
                        blue: {
                            50: '#f5f3ff', 100: '#ede9fe', 200: '#ddd6fe', 300: '#c4b5fd', 400: '#a78bfa',
                            500: '#8b5cf6', 600: '#7c3aed', 700: '#6d28d9', 800: '#5b21b6', 900: '#4c1d95',
                        },
                        @elseif(($appTheme ?? 'blue') === 'rose')
                        blue: {
                            50: '#fff1f2', 100: '#ffe4e6', 200: '#fecdd3', 300: '#fda4af', 400: '#fb7185',
                            500: '#f43f5e', 600: '#e11d48', 700: '#be123c', 800: '#9f1239', 900: '#881337',
                        },
                        @endif
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
    </style>
</head>

<body style="min-height:100vh; background: linear-gradient(135deg, #eff6ff 0%, #e0e7ff 100%); display:flex; align-items:center; justify-content:center; padding: 24px 16px;">

    <div style="width:100%; max-width:420px;">

        {{-- ═══ HEADER LOGO & JUDUL (Style Maki Space) ═══ --}}
        <div style="text-align:center; margin-bottom:28px;">
            <div style="width:58px; height:58px; background:#3b82f6; border-radius:18px; display:inline-flex; align-items:center; justify-content:center; margin-bottom:14px; box-shadow:0 10px 25px rgba(59,130,246,0.35); overflow:hidden; padding:6px;">
                @if(!empty($appLogo) && file_exists(public_path($appLogo)))
                    <img src="{{ asset($appLogo) }}" alt="Logo" style="width:100%; height:100%; object-fit:contain; border-radius:12px;">
                @else
                    <svg style="width:30px;height:30px;color:white;" fill="none" stroke="white" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z"/>
                    </svg>
                @endif
            </div>
            <h1 style="font-size:24px; font-weight:900; color:#1e3a8a; margin:0 0 4px; letter-spacing:-0.02em;">{{ $appTitle ?? 'Maki Samba' }}</h1>
            <p style="font-size:13px; color:#64748b; margin:0; font-weight:500;">{{ $appDescription ?? 'Portal File & Samba Server' }} &bull; {{ $institutionShort ?? 'Lab RMIK' }}</p>
        </div>

        {{-- ═══ KARTU FORM LOGIN UTAMA ═══ --}}
        <div style="background:white; border-radius:22px; padding:32px; box-shadow:0 20px 60px rgba(0,0,0,0.08); border:1px solid rgba(255,255,255,0.9);">

            @if(session('success'))
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:12px 14px; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
                <span style="font-size:16px;">✅</span>
                <p style="font-size:12.5px; color:#166534; font-weight:600; margin:0; line-height:1.4;">{{ session('success') }}</p>
            </div>
            @endif

            @if(session('error'))
            <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:12px 14px; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
                <span style="font-size:16px;">⚠️</span>
                <p style="font-size:12.5px; color:#dc2626; font-weight:600; margin:0; line-height:1.4;">{{ session('error') }}</p>
            </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf

                {{-- NIM / Username Input --}}
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:11.5px; font-weight:700; color:#64748b; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.06em;">
                        NIM / Username
                    </label>
                    <input type="text" name="nim" value="{{ old('nim') }}" required autofocus
                           placeholder="Contoh: A11.2024.12345 atau admin"
                           style="width:100%; border:1.5px solid #e2e8f0; border-radius:12px; padding:11px 14px; font-size:14px; outline:none; box-sizing:border-box; transition:all 0.2s; font-family:monospace;"
                           onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.15)'"
                           onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                    
                    @error('nim')
                        <p style="font-size:12px; color:#dc2626; margin:4px 0 0;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password Input with Hide/Unhide Toggle --}}
                <div x-data="{ showPass: false }" style="margin-bottom:24px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <label style="font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.06em; margin:0;">
                            Password Akun
                        </label>
                    </div>
                    <div style="position:relative;">
                        <input :type="showPass ? 'text' : 'password'" name="password" required
                               placeholder="Masukkan password akun Anda..."
                               style="width:100%; border:1.5px solid #e2e8f0; border-radius:12px; padding:11px 42px 11px 14px; font-size:14px; outline:none; box-sizing:border-box; transition:all 0.2s;"
                               onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.15)'"
                               onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                        <button type="button" @click="showPass = !showPass" tabindex="-1"
                                style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:#94a3b8; cursor:pointer; padding:4px; display:flex; align-items:center;"
                                :title="showPass ? 'Sembunyikan password' : 'Lihat password'">
                            <svg x-show="!showPass" style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPass" style="width:18px;height:18px;color:#2563eb;display:none;" :style="showPass ? 'display:block;' : 'display:none;'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                            </svg>
                        </button>
                    </div>
                    
                    @error('password')
                        <p style="font-size:12px; color:#dc2626; margin:4px 0 0;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <button type="submit"
                        style="width:100%; background:#3b82f6; color:white; border:none; border-radius:12px; padding:13px; font-size:14px; font-weight:700; cursor:pointer; transition:all 0.2s; box-shadow:0 6px 16px rgba(59,130,246,0.35); display:flex; align-items:center; justify-content:center; gap:8px;"
                        onmouseover="this.style.background='#2563eb'; this.style.transform='translateY(-1px)'"
                        onmouseout="this.style.background='#3b82f6'; this.style.transform='none'">
                    <span>Masuk ke Portal</span>
                    <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </form>

            <div style="margin-top:20px; padding:10px 12px; background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0; font-size:11.5px; color:#64748b; line-height:1.4;">
                <span style="font-weight:700; color:#1e3a8a;">💡 Akses Pengelola:</span> Masuk menggunakan akun <strong>admin</strong> atau <strong>laboran</strong> untuk membuka Dashboard Manajemen.
            </div>

            <div style="margin-top:24px; padding-top:20px; border-top:1px solid #f1f5f9; text-align:center;">
                <p style="font-size:12.5px; color:#64748b; margin:0 0 6px;">Belum memiliki akun penyimpanan Samba?</p>
                <a href="{{ route('register') }}"
                   style="color:#2563eb; font-weight:700; font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                    <span>Daftar Akun Baru Sekarang</span>
                    <span>&rarr;</span>
                </a>
            </div>

        </div>

        {{-- Help footer --}}
        <div style="margin-top:24px; text-align:center;">
            <p style="font-size:11.5px; color:#94a3b8; margin:0;">
                {{ $institutionName ?? 'Laboratorium Rekam Medis & Informasi Kesehatan (RMIK)' }}<br>AlmaLinux 10 &bull; Apache &bull; PHP 8.4
            </p>
        </div>

    </div>

</body>
</html>
