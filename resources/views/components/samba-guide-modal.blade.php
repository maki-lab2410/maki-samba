{{-- ═══ MODAL PANDUAN KONEKSI SAMBA LAN (Komponen Modular) ═══ --}}
@php
    $user = auth()->user();
    $isStaff = ($user && ($user->isLaboran() || $user->isAdmin())) || in_array(strtolower(session('samba_role', '')), ['laboran', 'admin']) || in_array(strtolower(session('samba_nim', '')), ['laboran', 'admin']);
    $shareName = $isStaff ? env('SAMBA_OPERASIONAL_SHARE', 'operasional') : env('SAMBA_MAHASISWA_SHARE', 'mahasiswa');
    $sambaUser = $user ? $user->nim : session('samba_nim', 'NIM_ANDA');
    $serverHost = $sambaServerHost ?? \App\Models\Setting::getSambaHost();
    $windowsPath = "\\\\{$serverHost}\\{$shareName}";
    $macPath = "smb://{$serverHost}/{$shareName}";
@endphp

<div x-cloak x-show="showSambaHelp"
     x-data="{
         guideTab: 'windows',
         copiedText: '',
         copyToClipboard(text, type) {
             const self = this;
             const successCallback = () => {
                 self.copiedText = type;
                 setTimeout(() => { self.copiedText = ''; }, 2500);
             };

             if (navigator.clipboard && window.isSecureContext) {
                 navigator.clipboard.writeText(text).then(successCallback).catch(() => {
                     self.fallbackCopy(text, successCallback);
                 });
             } else {
                 self.fallbackCopy(text, successCallback);
             }
         },
         fallbackCopy(str, cb) {
             try {
                 const ta = document.createElement('textarea');
                 ta.value = str;
                 ta.style.position = 'fixed';
                 ta.style.top = '0';
                 ta.style.left = '0';
                 ta.style.width = '2em';
                 ta.style.height = '2em';
                 ta.style.padding = '0';
                 ta.style.border = 'none';
                 ta.style.outline = 'none';
                 ta.style.boxShadow = 'none';
                 ta.style.background = 'transparent';
                 ta.style.opacity = '0';
                 document.body.appendChild(ta);
                 ta.focus();
                 ta.select();
                 document.execCommand('copy');
                 document.body.removeChild(ta);
                 if (typeof cb === 'function') cb();
             } catch (err) {
                 console.error('Copy fallback failed:', err);
             }
         }
     }"
     class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    
    <div x-on:click.outside="showSambaHelp = false"
         class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-7 shadow-2xl border border-slate-100 transition-all">
        
        {{-- Header Modal --}}
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center text-xl shadow-xs">
                    📁
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-800 text-base leading-tight">Panduan Akses Jaringan Samba LAN</h3>
                    <p class="text-xs text-slate-500 font-medium">{{ $institutionName ?? 'Laboratorium RMIK' }} &bull; Server AlmaLinux 10</p>
                </div>
            </div>
            <button x-on:click="showSambaHelp = false" type="button"
                    class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center font-bold text-lg transition">
                &times;
            </button>
        </div>

        {{-- Tab Switcher OS (Windows vs macOS) --}}
        <div class="mt-5 flex p-1 bg-slate-100 rounded-2xl border border-slate-200/80">
            <button type="button"
                    x-on:click="guideTab = 'windows'"
                    class="flex-1 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-2"
                    :class="guideTab === 'windows' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'">
                <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M0 3.449L9.75 2.1v9.451H0m10.949-9.602L24 0v11.4H10.949M0 12.6h9.75v9.451L0 20.699M10.949 12.6H24V24l-12.9-1.801"/>
                </svg>
                <span>Pengguna Windows</span>
            </button>
            <button type="button"
                    x-on:click="guideTab = 'macos'"
                    class="flex-1 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-2"
                    :class="guideTab === 'macos' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-600 hover:text-slate-900'">
                <svg class="w-4 h-4 text-slate-700" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.61-.75 1.04-1.8 1.01-2.87-.89.04-2.01.6-2.65 1.34-.56.65-.99 1.68-.93 2.7.99.08 2-.52 2.57-1.17z"/>
                </svg>
                <span>Pengguna macOS / Linux</span>
            </button>
        </div>

        {{-- Tab Content Windows --}}
        <div x-show="guideTab === 'windows'" class="mt-4 space-y-3.5 text-xs text-slate-600">
            <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-4">
                <ol class="space-y-2 list-decimal list-inside font-medium leading-relaxed text-slate-700">
                    <li>Nyalakan PC dan pastikan terhubung ke jaringan **Wi-Fi / LAN {{ $institutionShort ?? 'Lab RMIK' }}**.</li>
                    <li>Tekan kombinasi tombol <kbd class="px-2 py-0.5 bg-white border border-slate-300 rounded-md font-mono text-slate-800 shadow-2xs">Win + R</kbd> pada keyboard untuk membuka jendela *Run*.</li>
                    <li>Ketik atau salin alamat jaringan UNC berikut lalu tekan <kbd class="px-2 py-0.5 bg-white border border-slate-300 rounded-md font-mono text-slate-800 shadow-2xs">Enter</kbd>:</li>
                </ol>

                <div class="mt-3 flex items-center justify-between gap-2 p-2.5 bg-slate-900 rounded-xl font-mono text-emerald-400 text-xs border border-slate-800">
                    <span class="truncate select-all tracking-wide">{{ $windowsPath }}</span>
                    <button type="button" x-on:click="copyToClipboard('{{ addslashes($windowsPath) }}', 'win')"
                            class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-white text-[11px] font-bold rounded-lg shrink-0 transition flex items-center gap-1">
                        <span x-text="copiedText === 'win' ? '✅ Tersalin' : '📋 Salin'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Tab Content macOS / Linux --}}
        <div x-show="guideTab === 'macos'" class="mt-4 space-y-3.5 text-xs text-slate-600">
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4">
                <ol class="space-y-2 list-decimal list-inside font-medium leading-relaxed text-slate-700">
                    <li>Buka aplikasi **Finder** di Mac Anda.</li>
                    <li>Tekan kombinasi tombol <kbd class="px-2 py-0.5 bg-white border border-slate-300 rounded-md font-mono text-slate-800 shadow-2xs">Cmd + K</kbd> (*Connect to Server*).</li>
                    <li>Ketik atau salin alamat protokol Samba berikut lalu klik **Connect**:</li>
                </ol>

                <div class="mt-3 flex items-center justify-between gap-2 p-2.5 bg-slate-900 rounded-xl font-mono text-emerald-400 text-xs border border-slate-800">
                    <span class="truncate select-all tracking-wide">{{ $macPath }}</span>
                    <button type="button" x-on:click="copyToClipboard('{{ addslashes($macPath) }}', 'mac')"
                            class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-white text-[11px] font-bold rounded-lg shrink-0 transition flex items-center gap-1">
                        <span x-text="copiedText === 'mac' ? '✅ Tersalin' : '📋 Salin'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Petunjuk Kredensial Login --}}
        <div class="mt-4 p-3.5 bg-amber-50/80 border border-amber-200 rounded-2xl flex items-start gap-3">
            <span class="text-amber-600 text-lg">🔑</span>
            <div class="text-xs text-amber-900 leading-relaxed">
                <p class="font-bold">Kredensial Saat Diminta Otentikasi Samba:</p>
                <p class="mt-0.5 text-amber-800">
                    Gunakan <strong>Username = <span class="font-mono bg-amber-100 px-1 py-0.5 rounded">{{ $sambaUser }}</span></strong> dan <strong>Password</strong> yang didaftarkan pada portal ini.
                </p>
                @if($isStaff)
                <p class="mt-1 text-blue-700 font-semibold">
                    &bull; Akun Anda terhubung langsung ke direktori bersama Operasional Lab (<span class="font-mono">/samba/operasional</span>).
                </p>
                @else
                <p class="mt-1 text-slate-600">
                    &bull; Akun Anda terisolasi secara otomatis ke folder pribadi Anda (<span class="font-mono">/samba/mahasiswa/{{ $sambaUser }}</span>).
                </p>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <div class="mt-6 flex items-center justify-between pt-3 border-t border-slate-100">
            <span class="text-[11px] text-slate-400">Penyimpanan Terisolasi {{ $institutionShort ?? 'Lab RMIK' }}</span>
            <button x-on:click="showSambaHelp = false" type="button"
                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-sm shadow-blue-500/25 transition">
                Tutup Panduan
            </button>
        </div>

    </div>
</div>
