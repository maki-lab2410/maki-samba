@extends('layouts.app')

@section('title', 'Direktori Berkas Mahasiswa – Maki Samba')

@section('content')
<div class="space-y-6" x-data="{
    filterSemester: '{{ $currentSemester }}',
    filterKelas: '{{ $currentKelas }}',
    allNims: {{ json_encode(array_map(fn($s) => $s['user']->nim, $students)) }},
    selectedNims: [],
    showBulkFolderModal: false,
    bulkTargetType: 'filter',
    get isAllSelected() {
        return this.allNims.length > 0 && this.selectedNims.length === this.allNims.length;
    },
    toggleSelectAll() {
        if (this.isAllSelected) {
            this.selectedNims = [];
        } else {
            this.selectedNims = [...this.allNims];
        }
    }
}">

    {{-- ═══ 1. HEADER HALAMAN DIREKTORI MAHASISWA ═══ --}}
    <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center text-white text-2xl shadow-md shadow-blue-500/20 shrink-0">
                🎓
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-lg font-black text-slate-800">Direktori Berkas Mahasiswa</h1>
                    <span class="bg-slate-100 text-slate-700 text-[10px] font-bold px-2.5 py-0.5 rounded-full border border-slate-200 uppercase tracking-wider">
                        Read-Only
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Pemeriksaan tugas & folder praktikum mahasiswa berdasarkan Semester, Kelas, dan Mata Kuliah.
                </p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2.5 self-start md:self-auto">
            {{-- Badge Total Mahasiswa --}}
            <div class="px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-700 flex items-center gap-2 shrink-0">
                <span>👥 Total:</span>
                <span class="text-blue-600 font-extrabold text-sm">{{ $totalStudents }} Mahasiswa</span>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                {{-- Tombol Buat Folder Massal di Akun Mahasiswa --}}
                <button x-on:click="showBulkFolderModal = true; bulkTargetType = (selectedNims.length > 0 ? 'selected' : 'filter')"
                        type="button"
                        class="inline-flex items-center gap-2 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-xs font-bold shadow-xs shadow-indigo-500/20 transition cursor-pointer shrink-0"
                        title="Buat folder praktikum atau tugas secara massal di akun mahasiswa">
                    <svg class="w-4 h-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    </svg>
                    <span>+ Buat Folder Massal</span>
                </button>

                {{-- Tombol Cepat Unduh ZIP Semua Mahasiswa Sesuai Filter --}}
                @if($totalStudents > 0)
                <form action="{{ route('dosen.mahasiswa.download_zip') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="folder" value="{{ $folderSearch }}">
                    <input type="hidden" name="semester" value="{{ $currentSemester }}">
                    <input type="hidden" name="kelas" value="{{ $currentKelas }}">
                    <input type="hidden" name="q" value="{{ $search }}">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-xs font-bold shadow-xs shadow-emerald-500/20 transition cursor-pointer shrink-0"
                            title="{{ !empty($folderSearch) ? 'Unduh ZIP untuk folder ' . $folderSearch : 'Unduh seluruh berkas mahasiswa sesuai filter' }}">
                        <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Unduh ZIP @if(!empty($folderSearch)) <span class="max-w-[130px] truncate inline-block font-extrabold align-bottom" title="{{ $folderSearch }}">"{{ $folderSearch }}"</span> @endif ({{ $totalStudents }})</span>
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══ 2. FILTER MULTI-LEVEL (SEMESTER, KELAS, SELECT FOLDER, CARI NAMA) ═══ --}}
    <div class="bg-white border border-slate-200 rounded-3xl p-4 shadow-xs">
        <form action="{{ route('dosen.mahasiswa.index') }}" method="GET" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                
                {{-- Filter 1: Semester (1 - 8) dengan Custom Chevron Rapi --}}
                <div>
                    <label class="block text-[10.5px] uppercase font-bold text-slate-400 tracking-wider mb-1.5 flex items-center gap-1.5">
                        <span>📚</span>
                        <span>Semester</span>
                    </label>
                    <div class="relative">
                        <select name="semester" onchange="this.form.submit()"
                                class="w-full appearance-none pl-3.5 pr-9 py-2 bg-slate-50 hover:bg-slate-100/70 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 transition cursor-pointer">
                            <option value="all" {{ $currentSemester === 'all' ? 'selected' : '' }}>Semua Semester</option>
                            @for($i = 1; $i <= 8; $i++)
                                <option value="{{ $i }}" {{ (string)$currentSemester === (string)$i ? 'selected' : '' }}>
                                    Semester {{ $i }}
                                </option>
                            @endfor
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                            <svg class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Filter 2: Kelas (A, B, TI.2A, dll) dengan Custom Chevron Rapi --}}
                <div>
                    <label class="block text-[10.5px] uppercase font-bold text-slate-400 tracking-wider mb-1.5 flex items-center gap-1.5">
                        <span>🏫</span>
                        <span>Kelas</span>
                    </label>
                    <div class="relative">
                        <select name="kelas" onchange="this.form.submit()"
                                class="w-full appearance-none pl-3.5 pr-9 py-2 bg-slate-50 hover:bg-slate-100/70 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 transition cursor-pointer">
                            <option value="all" {{ $currentKelas === 'all' ? 'selected' : '' }}>Semua Kelas</option>
                            @foreach($availableClasses as $c)
                                <option value="{{ $c }}" {{ $currentKelas === $c ? 'selected' : '' }}>
                                    Kelas {{ $c }}
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                            <svg class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Filter 3: Select Folder Mata Kuliah / Praktikum --}}
                <div>
                    <label class="block text-[10.5px] uppercase font-bold text-slate-400 tracking-wider mb-1.5 flex items-center gap-1.5">
                        <span>📁</span>
                        <span>Pilih Folder Praktikum / MK</span>
                    </label>
                    <div class="relative">
                        <select name="folder" onchange="this.form.submit()"
                                class="w-full appearance-none pl-3.5 pr-9 py-2 bg-slate-50 hover:bg-slate-100/70 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 transition cursor-pointer">
                            <option value="">Semua Folder Praktikum</option>
                            @foreach($availableFolders as $fName => $fCount)
                                <option value="{{ $fName }}" {{ $folderSearch === $fName ? 'selected' : '' }}>
                                    📁 {{ $fName }} ({{ $fCount }} Mahasiswa)
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                            <svg class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Filter 4: Cari NIM / Nama Mahasiswa --}}
                <div>
                    <label class="block text-[10.5px] uppercase font-bold text-slate-400 tracking-wider mb-1.5 flex items-center gap-1.5">
                        <span>🔍</span>
                        <span>Cari NIM / Nama</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </span>
                        <input type="text" name="q" value="{{ $search }}" placeholder="Ketik NIM atau nama..."
                               class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-700 outline-none focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 transition">
                    </div>
                </div>

            </div>

            {{-- Filter Quick Actions --}}
            <div class="flex items-center justify-between pt-2 border-t border-slate-100 flex-wrap gap-2">
                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span class="text-amber-500">💡</span>
                    <span>Pilih folder praktikum untuk memfilter tugas mahasiswa, lalu unduh berkas dalam bentuk ZIP sekaligus.</span>
                </div>
                <div class="flex items-center gap-2">
                    @if($currentSemester !== 'all' || $currentKelas !== 'all' || !empty($search) || !empty($folderSearch))
                    <a href="{{ route('dosen.mahasiswa.index') }}"
                       class="px-3 py-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition">
                        Reset Filter
                    </a>
                    @endif
                    <button type="submit"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ═══ 3. TABEL DAFTAR MAHASISWA & FOLDER PRAKTIKUM ═══ --}}
    <div class="bg-white border border-slate-200 rounded-3xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        {{-- Kolom Checkbox Select All --}}
                        <th class="py-3.5 pl-4 pr-1 w-10 text-center">
                            <input type="checkbox"
                                   :checked="isAllSelected"
                                   @change="toggleSelectAll()"
                                   class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer transition"
                                   title="Pilih Semua Mahasiswa">
                        </th>
                        <th class="py-3.5 px-4">Mahasiswa</th>
                        <th class="py-3.5 px-4 w-36">Semester & Kelas</th>
                        <th class="py-3.5 px-4">Folder Praktikum / Tugas</th>
                        <th class="py-3.5 px-4 w-40">Penggunaan Kuota</th>
                        <th class="py-3.5 px-5 text-right w-36">Aksi Berkas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($students as $item)
                    @php
                        $u = $item['user'];
                        $folders = $item['folders'];
                        $pct = $u->quota_percent;
                        $barBg = $pct < 75 ? 'bg-emerald-500' : ($pct <= 90 ? 'bg-amber-500' : 'bg-rose-500');
                    @endphp
                    <tr class="hover:bg-slate-50/70 transition" :class="selectedNims.includes('{{ $u->nim }}') ? 'bg-blue-50/40' : ''">
                        
                        {{-- Checkbox Select Mahasiswa --}}
                        <td class="py-3 pl-4 pr-1 text-center">
                            <input type="checkbox"
                                   value="{{ $u->nim }}"
                                   x-model="selectedNims"
                                   class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer transition">
                        </td>

                        {{-- Identitas Mahasiswa --}}
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black flex items-center justify-center shrink-0 shadow-xs">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-extrabold text-slate-800 leading-tight">{{ $u->name }}</p>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="font-mono text-[11px] text-slate-500">{{ $u->nim }}</span>
                                        @if($u->angkatan)
                                            <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.2 rounded font-mono">
                                                Angkatan '{{ substr($u->angkatan, 2) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Semester & Kelas Badge --}}
                        <td class="py-3 px-4">
                            <div class="space-y-1">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-extrabold bg-blue-50 text-blue-700 border border-blue-200">
                                    <span>Semester {{ $u->semester ?: 1 }}</span>
                                </span>
                                @if($u->kelas)
                                    <div>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                            <span>Kelas {{ $u->kelas }}</span>
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </td>

                        {{-- Folder Mata Kuliah / Praktikum yang dibuat mahasiswa atau dosen --}}
                        <td class="py-3 px-4">
                            @if(count($folders) > 0)
                                <div class="flex items-center gap-2 flex-wrap max-w-lg">
                                    @foreach(array_slice($folders, 0, 5) as $folderName)
                                        @php
                                            $isCurrentActiveFolder = (!empty($folderSearch) && strcasecmp($folderName, $folderSearch) === 0);
                                        @endphp
                                        <div class="inline-flex items-center rounded-xl border transition overflow-hidden text-[11px] {{ $isCurrentActiveFolder ? 'border-blue-400 bg-blue-50 text-blue-800 font-bold shadow-2xs' : 'border-slate-200 bg-slate-50 hover:border-slate-300 text-slate-700' }}">
                                            <a href="{{ route('dosen.mahasiswa.show', ['nim' => $u->nim, 'path' => $folderName]) }}"
                                               class="flex items-center gap-1.5 px-2.5 py-1 hover:text-blue-600 transition"
                                               title="Buka folder {{ $folderName }}">
                                                <span class="text-xs">📁</span>
                                                <span class="truncate max-w-[180px] sm:max-w-[220px]" title="{{ $folderName }}">{{ $folderName }}</span>
                                            </a>
                                            <a href="{{ route('dosen.mahasiswa.download_student_zip', ['nim' => $u->nim, 'path' => $folderName]) }}"
                                               class="px-2 py-1 text-slate-400 hover:text-emerald-700 hover:bg-emerald-100/80 border-l border-slate-200/80 transition"
                                               title="Unduh folder {{ $folderName }} sebagai ZIP">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                            </a>
                                        </div>
                                    @endforeach
                                    @if(count($folders) > 5)
                                        <span class="text-[10.5px] text-slate-400 font-bold">
                                            +{{ count($folders) - 5 }} folder lain
                                        </span>
                                    @endif
                                </div>
                            @else
                                <span class="text-slate-400 text-[11px] italic">
                                    Belum ada folder praktikum
                                </span>
                            @endif
                        </td>

                        {{-- Kuota Penyimpanan --}}
                        <td class="py-3 px-4">
                            <div>
                                <div class="flex justify-between text-[10.5px] font-mono text-slate-600 mb-1">
                                    <span>{{ $u->quota_used_formatted }}</span>
                                    <span class="text-slate-400">/ {{ $u->quota_limit_formatted }}</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="h-full rounded-full {{ $barBg }}" style="width: {{ $pct }}%;"></div>
                                </div>
                            </div>
                        </td>

                        {{-- Tombol Buka Berkas Mahasiswa (Colorbase Blue) --}}
                        <td class="py-3 px-5 text-right">
                            <a href="{{ route('dosen.mahasiswa.show', ['nim' => $u->nim]) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-xs transition shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/>
                                </svg>
                                <span>Periksa</span>
                            </a>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400">
                            <div class="max-w-xs mx-auto space-y-2">
                                <span class="text-3xl">🔍</span>
                                <p class="text-xs font-bold text-slate-600">Tidak ada mahasiswa yang cocok</p>
                                <p class="text-[11px] text-slate-400">Silakan ubah filter semester, kelas, atau folder pencarian Anda.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ 4. FLOATING BAR: MULTI-SELECT UNDUH ZIP TERPILIH ═══ --}}
    <div x-cloak x-show="selectedNims.length > 0"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-slate-900/95 text-white backdrop-blur-md px-5 py-3 rounded-2xl shadow-2xl border border-slate-700 flex items-center gap-4 text-xs">
        
        <div class="flex items-center gap-2.5">
            <span class="w-6 h-6 rounded-lg bg-blue-600 flex items-center justify-center font-bold text-xs" x-text="selectedNims.length"></span>
            <span class="font-bold text-slate-200">Mahasiswa Dipilih</span>
        </div>

        <div class="h-4 w-px bg-slate-700"></div>

        <form action="{{ route('dosen.mahasiswa.download_zip') }}" method="POST" class="flex items-center gap-2">
            @csrf
            <template x-for="nim in selectedNims" :key="nim">
                <input type="hidden" name="nims[]" :value="nim">
            </template>
            <input type="hidden" name="folder" value="{{ $folderSearch }}">

            <button x-on:click.prevent="showBulkFolderModal = true; bulkTargetType = 'selected'"
                    type="button"
                    class="inline-flex items-center gap-2 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold shadow-sm transition">
                <svg class="w-4 h-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                </svg>
                <span>Buat Folder (<span x-text="selectedNims.length"></span>)</span>
            </button>

            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-extrabold shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                @if(!empty($folderSearch))
                    <span>Unduh ZIP Folder "{{ $folderSearch }}" (<span x-text="selectedNims.length"></span>)</span>
                @else
                    <span>Unduh ZIP Mahasiswa Terpilih (<span x-text="selectedNims.length"></span>)</span>
                @endif
            </button>

            <button type="button" @click="selectedNims = []"
                    class="px-3 py-2 text-slate-400 hover:text-white font-medium rounded-xl hover:bg-slate-800 transition">
                Batal
            </button>
        </form>
    </div>

    {{-- ═══ 5. MODAL BUAT FOLDER MASSAL DI AKUN MAHASISWA ═══ --}}
    <template x-teleport="body">
        <div x-cloak x-show="showBulkFolderModal" class="relative z-50" role="dialog" aria-modal="true">
            {{-- Backdrop Overlay (100% Fullscreen Viewport) --}}
            <div x-show="showBulkFolderModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                 aria-hidden="true"></div>

            {{-- Dialog Container (Fixed Fullscreen Scrollable) --}}
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    {{-- Modal Card --}}
                    <div x-show="showBulkFolderModal"
                         x-on:click.outside="showBulkFolderModal = false"
                         x-transition:enter="ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 relative text-left my-8">
                        
                        {{-- Modal Header --}}
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center text-xl shrink-0 shadow-xs">
                                    📁
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-slate-800 text-base leading-tight">Buat Folder Massal Mahasiswa</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Otomatisasi pembuatan folder praktikum/tugas di akun mahasiswa.</p>
                                </div>
                            </div>
                            <button x-on:click="showBulkFolderModal = false" type="button"
                                    class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Form --}}
                        <form action="{{ route('dosen.mahasiswa.bulk_folder') }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="current_semester" value="{{ $currentSemester }}">
                            <input type="hidden" name="current_kelas" value="{{ $currentKelas }}">
                            <input type="hidden" name="current_q" value="{{ $search }}">

                            {{-- Hidden input selected nims if any --}}
                            <template x-for="nim in selectedNims" :key="'bulk-' + nim">
                                <input type="hidden" name="selected_nims[]" :value="nim">
                            </template>

                            {{-- 1. Input Nama Folder --}}
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                                    <span>Nama Folder Praktikum / Tugas <span class="text-rose-500">*</span></span>
                                    <span class="text-[10.5px] font-normal text-slate-400">Format: Nama Tugas / Pertemuan</span>
                                </label>
                                <input type="text" name="folder_name" required autofocus
                                       placeholder="Contoh: TUGAS 3 - DESAIN FORMULIR"
                                       class="w-full border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-800 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition">
                                <p class="text-[11px] text-slate-400 mt-1">
                                    Folder otomatis dibuat di penyimpanan mahasiswa <code class="font-mono text-indigo-600 bg-indigo-50 px-1 py-0.5 rounded text-[10.5px]">/samba/mahasiswa/{NIM}/[Nama Folder]</code>
                                </p>
                            </div>

                            {{-- 2. Pilihan Target Mahasiswa --}}
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-2">Target Penerima Folder:</label>
                                
                                <div class="space-y-2">
                                    {{-- Opsi 1: Mahasiswa yang dicentang di tabel --}}
                                    <label class="flex items-start gap-3 p-3 rounded-2xl border cursor-pointer transition"
                                           :class="bulkTargetType === 'selected' ? 'bg-indigo-50/60 border-indigo-300' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/50'">
                                        <input type="radio" name="target_type" value="selected" x-model="bulkTargetType"
                                               :checked="bulkTargetType === 'selected'"
                                               :disabled="selectedNims.length === 0"
                                               class="mt-0.5 text-indigo-600 focus:ring-indigo-500 border-slate-300">
                                        <div class="text-xs">
                                            <span class="font-bold text-slate-800">Hanya Mahasiswa yang Dicentang di Tabel</span>
                                            <p class="text-[11px] text-slate-500 mt-0.5">
                                                <span x-text="selectedNims.length" class="font-bold text-indigo-600"></span> mahasiswa terpilih saat ini.
                                                <span x-show="selectedNims.length === 0" class="text-rose-500 italic block">(Centang kotak pada baris tabel mahasiswa terlebih dahulu)</span>
                                            </p>
                                        </div>
                                    </label>

                                    {{-- Opsi 2: Sesuai filter halaman saat ini --}}
                                    <label class="flex items-start gap-3 p-3 rounded-2xl border cursor-pointer transition"
                                           :class="bulkTargetType === 'filter' ? 'bg-indigo-50/60 border-indigo-300' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/50'">
                                        <input type="radio" name="target_type" value="filter" x-model="bulkTargetType"
                                               :checked="bulkTargetType === 'filter'"
                                               class="mt-0.5 text-indigo-600 focus:ring-indigo-500 border-slate-300">
                                        <div class="text-xs">
                                            <span class="font-bold text-slate-800">Semua Mahasiswa Sesuai Filter Saat Ini</span>
                                            <p class="text-[11px] text-slate-500 mt-0.5">
                                                Target: <strong class="text-indigo-700">{{ $totalStudents }} Mahasiswa</strong>
                                                @if($currentSemester !== 'all') &bull; Semester {{ $currentSemester }} @endif
                                                @if($currentKelas !== 'all') &bull; Kelas {{ $currentKelas }} @endif
                                            </p>
                                        </div>
                                    </label>

                                    {{-- Opsi 3: Pilih Spesifik Semester & Kelas --}}
                                    <label class="flex items-start gap-3 p-3 rounded-2xl border cursor-pointer transition"
                                           :class="bulkTargetType === 'class' ? 'bg-indigo-50/60 border-indigo-300' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/50'">
                                        <input type="radio" name="target_type" value="class" x-model="bulkTargetType"
                                               :checked="bulkTargetType === 'class'"
                                               class="mt-0.5 text-indigo-600 focus:ring-indigo-500 border-slate-300">
                                        <div class="text-xs w-full">
                                            <span class="font-bold text-slate-800">Pilih Berdasarkan Semester &amp; Kelas</span>
                                            
                                            <div x-show="bulkTargetType === 'class'" class="grid grid-cols-2 gap-2 mt-2" x-transition>
                                                <div>
                                                    <label class="block text-[10px] text-slate-400 font-bold mb-1">Semester</label>
                                                    <select name="target_semester"
                                                            class="w-full text-xs font-semibold py-1.5 px-2 bg-white border border-slate-200 rounded-lg">
                                                        <option value="all">Semua Semester</option>
                                                        @for($i = 1; $i <= 8; $i++)
                                                            <option value="{{ $i }}">Semester {{ $i }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] text-slate-400 font-bold mb-1">Kelas</label>
                                                    <select name="target_kelas"
                                                            class="w-full text-xs font-semibold py-1.5 px-2 bg-white border border-slate-200 rounded-lg">
                                                        <option value="all">Semua Kelas</option>
                                                        @foreach($availableClasses as $kls)
                                                            <option value="{{ $kls }}">Kelas {{ $kls }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </label>

                                    {{-- Opsi 4: Seluruh Mahasiswa Terdaftar --}}
                                    <label class="flex items-start gap-3 p-3 rounded-2xl border cursor-pointer transition"
                                           :class="bulkTargetType === 'all' ? 'bg-indigo-50/60 border-indigo-300' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/50'">
                                        <input type="radio" name="target_type" value="all" x-model="bulkTargetType"
                                               :checked="bulkTargetType === 'all'"
                                               class="mt-0.5 text-indigo-600 focus:ring-indigo-500 border-slate-300">
                                        <div class="text-xs">
                                            <span class="font-bold text-slate-800">Seluruh Mahasiswa Terdaftar di Sistem</span>
                                            <p class="text-[11px] text-slate-500 mt-0.5">Membuat folder ke seluruh akun mahasiswa tanpa kecuali.</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                                <button x-on:click="showBulkFolderModal = false" type="button"
                                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer">
                                    Batal
                                </button>
                                <button type="submit"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md shadow-indigo-500/25 transition cursor-pointer">
                                    <svg class="w-4 h-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span>Buat Folder Sekarang</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection
