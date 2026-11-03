@extends('layouts.app')

@section('title', 'Berkas Mahasiswa ' . $student->name . ' (' . $student->nim . ') – Maki Samba')

@section('content')
<div x-data="{
    // Modal Pratinjau (Preview) Interaktif Read-Only
    showPreviewModal: false,
    previewLoading: false,
    previewData: {
        name: '',
        path: '',
        size: '',
        lastModified: '',
        ext: '',
        url: '',
        downloadUrl: '',
        type: 'other',
        textContent: ''
    },

    openPreview(file) {
        const ext = (file.extension || '').toLowerCase();
        let type = 'other';
        
        if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'].includes(ext)) {
            type = 'image';
        } else if (ext === 'pdf') {
            type = 'pdf';
        } else if (['txt', 'log', 'csv', 'sql', 'json', 'xml', 'md', 'html', 'css', 'js', 'php', 'py', 'sh', 'c', 'cpp', 'java'].includes(ext)) {
            type = 'text';
        } else if (['mp4', 'webm', 'ogg'].includes(ext)) {
            type = 'video';
        } else if (['mp3', 'wav', 'ogg'].includes(ext)) {
            type = 'audio';
        } else if (['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(ext)) {
            type = 'office';
        }

        const previewUrl = '{{ route('dosen.mahasiswa.preview', ['nim' => $student->nim]) }}?file=' + encodeURIComponent(file.path);
        const downloadUrl = '{{ route('dosen.mahasiswa.download', ['nim' => $student->nim]) }}?file=' + encodeURIComponent(file.path);

        this.previewData = {
            name: file.name,
            path: file.path,
            size: file.size,
            lastModified: file.last_modified,
            ext: ext.toUpperCase(),
            url: previewUrl,
            downloadUrl: downloadUrl,
            type: type,
            textContent: ''
        };

        this.showPreviewModal = true;

        if (type === 'text') {
            this.previewLoading = true;
            fetch(previewUrl)
                .then(res => res.text())
                .then(text => {
                    this.previewData.textContent = text;
                    this.previewLoading = false;
                })
                .catch(() => {
                    this.previewData.textContent = 'Gagal memuat isi file teks.';
                    this.previewLoading = false;
                });
        }
    }
}" class="space-y-4">

    {{-- ═══ 1. HEADER INFO MAHASISWA & STATUS READ-ONLY ═══ --}}
    <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        
        <div class="flex items-center gap-3.5">
            <a href="{{ route('dosen.mahasiswa.index') }}"
               class="w-10 h-10 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition shrink-0"
               title="Kembali ke Direktori Mahasiswa">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-base font-extrabold text-slate-800">{{ $student->name }}</h1>
                    <span class="bg-blue-100 text-blue-800 text-[10px] font-bold px-2 py-0.5 rounded-full border border-blue-200 uppercase tracking-wider">
                        Semester {{ $student->semester ?: 1 }}
                    </span>
                    @if($student->kelas)
                    <span class="bg-slate-100 text-slate-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-slate-200 uppercase tracking-wider">
                        Kelas {{ $student->kelas }}
                    </span>
                    @endif
                    <span class="bg-amber-100 text-amber-800 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-amber-200 uppercase tracking-wider">
                        🔒 Mode Koreksi (Hanya Baca)
                    </span>
                </div>
                <p class="text-xs font-mono text-slate-500 mt-0.5">
                    NIM: <strong class="text-slate-700">{{ $student->nim }}</strong> &bull; Kuota: {{ $quotaUsedFormatted }} / {{ $quotaLimitFormatted }} ({{ $quotaPercent }}%)
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('dosen.mahasiswa.download_student_zip', ['nim' => $student->nim, 'path' => $current_path]) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs"
               title="Unduh seluruh file dalam folder ini sebagai file ZIP">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>{{ empty($current_path) ? 'Unduh Semua (.zip)' : 'Unduh Folder Ini (.zip)' }}</span>
            </a>
            <a href="{{ route('dosen.mahasiswa.index') }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                <span>← Kembali</span>
            </a>
        </div>
    </div>

    {{-- ═══ 2. BREADCRUMBS NAVIGASI FOLDER ═══ --}}
    <nav class="flex items-center gap-1.5 text-xs text-slate-500 bg-white border border-slate-200 rounded-2xl px-4 py-2.5 shadow-xs overflow-x-auto whitespace-nowrap">
        <a href="{{ route('dosen.mahasiswa.show', ['nim' => $student->nim]) }}"
           class="flex items-center gap-1 font-bold hover:text-blue-600 transition {{ empty($current_path) ? 'text-blue-700 bg-blue-50 px-2 py-1 rounded-lg' : 'text-slate-600' }}">
            <span>🏠</span>
            <span>Root ({{ $student->nim }})</span>
        </a>

        @foreach($breadcrumbs as $b)
            @if(!$loop->first)
                <span class="text-slate-300">/</span>
                @if($loop->last)
                    <span class="font-extrabold text-blue-900 bg-blue-50 px-2 py-1 rounded-lg">{{ $b['title'] ?? $b['name'] ?? '' }}</span>
                @else
                    <a href="{{ route('dosen.mahasiswa.show', ['nim' => $student->nim, 'path' => $b['path']]) }}"
                       class="hover:text-blue-600 transition text-slate-600 font-medium px-1 py-0.5">
                        {{ $b['title'] ?? $b['name'] ?? '' }}
                    </a>
                @endif
            @endif
        @endforeach
    </nav>

    {{-- ═══ 3. TABEL DAFTAR FILE & FOLDER MAHASISWA ═══ --}}
    <div class="bg-white border border-slate-200 rounded-3xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Nama File / Folder</th>
                        <th class="py-3 px-4 w-28">Ukuran</th>
                        <th class="py-3 px-4 w-44">Waktu Terakhir Diubah</th>
                        <th class="py-3 px-4 text-right w-36">Aksi Berkas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">

                    {{-- Link Naik 1 Level (Parent Directory) --}}
                    @if(!empty($current_path))
                    @php
                        $parentPath = dirname($current_path) === '.' ? '' : dirname($current_path);
                    @endphp
                    <tr class="hover:bg-slate-50/70 transition">
                        <td colspan="4" class="py-2.5 px-4">
                            <a href="{{ route('dosen.mahasiswa.show', ['nim' => $student->nim, 'path' => $parentPath]) }}"
                               class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 font-bold">
                                <span>⤴️</span>
                                <span>.. (Kembali ke folder sebelumnya)</span>
                            </a>
                        </td>
                    </tr>
                    @endif

                    {{-- Daftar Folder --}}
                    @foreach($folders as $folder)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-2.5 px-4 font-semibold text-slate-800">
                            <a href="{{ route('dosen.mahasiswa.show', ['nim' => $student->nim, 'path' => $folder['path']]) }}"
                               class="inline-flex items-center gap-2.5 hover:text-blue-600 transition">
                                <span class="text-base text-amber-500">📁</span>
                                <span>{{ $folder['name'] }}</span>
                            </a>
                        </td>
                        <td class="py-2.5 px-4 text-slate-400 font-mono text-[11px]">—</td>
                        <td class="py-2.5 px-4 text-slate-500">{{ $folder['last_modified'] }}</td>
                        <td class="py-2.5 px-4 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('dosen.mahasiswa.download_student_zip', ['nim' => $student->nim, 'path' => $folder['path']]) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition"
                                   title="Unduh folder {{ $folder['name'] }} sebagai file ZIP">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    <span>ZIP</span>
                                </a>
                                <a href="{{ route('dosen.mahasiswa.show', ['nim' => $student->nim, 'path' => $folder['path']]) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-bold text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                    <span>Buka Folder →</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach

                    {{-- Daftar File --}}
                    @foreach($files as $file)
                    @php
                        $ext = strtolower($file['extension'] ?? '');
                        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                    @endphp
                    <tr class="hover:bg-slate-50/70 transition group">
                        <td class="py-2.5 px-4 font-medium text-slate-800">
                            <div class="flex items-center gap-2.5">
                                @if($isImage)
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 overflow-hidden border border-slate-200 shrink-0 flex items-center justify-center">
                                        <img src="{{ route('dosen.mahasiswa.preview', ['nim' => $student->nim, 'file' => $file['path']]) }}"
                                             alt="{{ $file['name'] }}"
                                             loading="lazy"
                                             class="w-full h-full object-cover">
                                    </div>
                                @else
                                    <span class="w-7 h-7 rounded-lg {{ $file['icon_color'] ?? 'bg-slate-100 text-slate-600' }} flex items-center justify-center font-bold text-[10px] shrink-0 font-mono">
                                        {{ $file['badge'] ?? strtoupper(substr($ext, 0, 3)) }}
                                    </span>
                                @endif

                                <span class="truncate max-w-md font-semibold text-slate-800" title="{{ $file['name'] }}">
                                    {{ $file['name'] }}
                                </span>
                            </div>
                        </td>
                        <td class="py-2.5 px-4 text-slate-600 font-mono text-[11.5px] whitespace-nowrap">
                            {{ $file['size'] }}
                        </td>
                        <td class="py-2.5 px-4 text-slate-500 whitespace-nowrap">
                            {{ $file['last_modified'] }}
                        </td>
                        <td class="py-2.5 px-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-1">
                                {{-- Tombol Pratinjau (Preview) --}}
                                <button type="button"
                                        x-on:click="openPreview({{ json_encode($file) }})"
                                        class="p-1.5 text-slate-500 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition"
                                        title="Pratinjau File (Preview)">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>

                                {{-- Tombol Unduh (Download) --}}
                                <a href="{{ route('dosen.mahasiswa.download', ['nim' => $student->nim, 'file' => $file['path']]) }}"
                                   class="p-1.5 text-slate-500 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition"
                                   title="Unduh File">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach

                    {{-- Empty State jika folder kosong --}}
                    @if(empty($folders) && empty($files))
                    <tr>
                        <td colspan="4" class="py-12 text-center text-slate-400">
                            <div class="max-w-xs mx-auto space-y-2">
                                <span class="text-3xl">📭</span>
                                <p class="text-xs font-bold text-slate-600">Folder ini masih kosong</p>
                                <p class="text-[11px] text-slate-400">Mahasiswa belum mengunggah file di dalam direktori ini.</p>
                            </div>
                        </td>
                    </tr>
                    @endif

                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ 4. MODAL PRATINJAU FILE (INTERACTIVE PREVIEW MODAL) ═══ --}}
    <div x-cloak x-show="showPreviewModal"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-6">
        
        <div x-on:click.outside="showPreviewModal = false"
             class="bg-white rounded-3xl max-w-4xl w-full flex flex-col shadow-2xl border border-slate-200 overflow-hidden max-h-[90vh]">
            
            {{-- Modal Header --}}
            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/80 gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="text-lg">👁️</span>
                    <div class="truncate">
                        <h3 class="font-extrabold text-slate-800 text-xs sm:text-sm truncate" x-text="previewData.name"></h3>
                        <p class="text-[10.5px] text-slate-400 font-mono">
                            <span x-text="previewData.size"></span> &bull; <span x-text="previewData.lastModified"></span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <a x-bind:href="previewData.downloadUrl"
                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                        <span>⬇️</span>
                        <span class="hidden sm:inline">Unduh</span>
                    </a>
                    <button x-on:click="showPreviewModal = false"
                            class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition">
                        ✕
                    </button>
                </div>
            </div>

            {{-- Modal Body: Render berdasarkan tipe file --}}
            <div class="flex-1 overflow-auto p-4 flex items-center justify-center bg-slate-900/5 min-h-[300px]">
                
                {{-- 1. Gambar --}}
                <template x-if="previewData.type === 'image'">
                    <img x-bind:src="previewData.url"
                         class="max-h-[70vh] max-w-full rounded-2xl shadow-xs object-contain"
                         alt="Preview Gambar">
                </template>

                {{-- 2. PDF Viewer --}}
                <template x-if="previewData.type === 'pdf'">
                    <iframe x-bind:src="previewData.url"
                            class="w-full h-[70vh] rounded-2xl border border-slate-200 bg-white"></iframe>
                </template>

                {{-- 3. File Teks / Code --}}
                <template x-if="previewData.type === 'text'">
                    <div class="w-full h-full max-h-[70vh] overflow-auto">
                        <div x-show="previewLoading" class="text-center py-8 text-xs text-slate-400">
                            Memuat isi file...
                        </div>
                        <pre x-show="!previewLoading"
                             class="bg-slate-900 text-slate-100 p-4 rounded-2xl text-[11px] font-mono whitespace-pre-wrap select-text leading-relaxed"
                             x-text="previewData.textContent"></pre>
                    </div>
                </template>

                {{-- 4. Video Player --}}
                <template x-if="previewData.type === 'video'">
                    <video controls class="max-h-[70vh] max-w-full rounded-2xl shadow-xs">
                        <source x-bind:src="previewData.url">
                        Browser Anda tidak mendukung pemutaran video.
                    </video>
                </template>

                {{-- 5. Audio Player --}}
                <template x-if="previewData.type === 'audio'">
                    <div class="p-6 bg-white rounded-2xl shadow-xs border border-slate-200 text-center">
                        <audio controls class="w-full max-w-md">
                            <source x-bind:src="previewData.url">
                        </audio>
                    </div>
                </template>

                {{-- 6. Dokumen / Tipe Lain --}}
                <template x-if="previewData.type === 'other' || previewData.type === 'office'">
                    <div class="text-center p-8 bg-white rounded-3xl border border-slate-200 max-w-md space-y-3 shadow-xs">
                        <span class="text-4xl block">📄</span>
                        <h4 class="font-extrabold text-slate-800 text-sm">Pratinjau Langsung Tidak Didukung</h4>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            File dengan ekstensi <code class="font-bold text-slate-700" x-text="previewData.ext"></code> dapat diperiksa dengan mengunduh berkas terlebih dahulu.
                        </p>
                        <a x-bind:href="previewData.downloadUrl"
                           class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-xs transition">
                            <span>⬇️ Unduh Berkas untuk Diperiksa</span>
                        </a>
                    </div>
                </template>

            </div>

        </div>
    </div>

</div>
@endsection
