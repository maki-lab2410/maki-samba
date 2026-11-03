@extends('layouts.app')

@section('title', 'Web File Manager – ' . ($appTitle ?? 'Maki Samba') . ' (' . ($institutionShort ?? 'Lab RMIK') . ')')

@section('content')
<div x-data="{
    search: '',
    showNewFolderModal: false,
    showUploadModal: false,
    showDeleteModal: false,
    deleteData: { path: '', name: '', type: 'file' },
    isDragging: false,
    copied: false,
    selectedFiles: [],

    showPreviewModal: false,
    previewData: {
        name: '',
        path: '',
        size: '',
        lastModified: '',
        ext: '',
        url: '',
        downloadUrl: '',
        type: 'other',
        textContent: '',
        loadingText: false
    },

    openPreview(file) {
        const ext = file.extension ? file.extension.toLowerCase() : '';
        const imgExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        const textExts = ['txt', 'md', 'csv', 'json', 'log', 'sql', 'js', 'html', 'css', 'php', 'py', 'sh', 'xml'];
        const videoExts = ['mp4', 'webm', 'ogg', 'mov'];
        const audioExts = ['mp3', 'wav', 'ogg'];

        let type = 'other';
        if (imgExts.includes(ext)) type = 'image';
        else if (ext === 'pdf') type = 'pdf';
        else if (textExts.includes(ext)) type = 'text';
        else if (videoExts.includes(ext)) type = 'video';
        else if (audioExts.includes(ext)) type = 'audio';

        const previewUrl = '{{ route('filemanager.preview') }}?path=' + encodeURIComponent(file.path);
        const downloadUrl = '{{ route('filemanager.download') }}?path=' + encodeURIComponent(file.path);

        this.previewData = {
            name: file.name,
            path: file.path,
            size: file.size,
            lastModified: file.last_modified,
            ext: ext.toUpperCase(),
            url: previewUrl,
            downloadUrl: downloadUrl,
            type: type,
            textContent: '',
            loadingText: false
        };

        if (type === 'text') {
            this.previewData.loadingText = true;
            fetch(previewUrl)
                .then(res => res.text())
                .then(text => {
                    this.previewData.textContent = text;
                    this.previewData.loadingText = false;
                })
                .catch(() => {
                    this.previewData.textContent = 'Gagal memuat isi file teks.';
                    this.previewData.loadingText = false;
                });
        }

        this.showPreviewModal = true;
    },
    
    copySmb(text) {
        navigator.clipboard.writeText(text);
        this.copied = true;
        setTimeout(() => this.copied = false, 2000);
    },
    
    confirmDelete(path, name, type) {
        this.deleteData = { path, name, type };
        this.showDeleteModal = true;
    },

    formatBytes(bytes) {
        if (!bytes || bytes === 0) return '0 B';
        if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return bytes + ' B';
    },

    handleFileSelect(fileList) {
        if (!fileList || fileList.length === 0) return;
        this.selectedFiles = Array.from(fileList).map(file => ({
            name: file.name,
            size: this.formatBytes(file.size),
            rawSize: file.size,
            ext: file.name.split('.').pop().toLowerCase()
        }));
    },

    removeSelectedFile(index) {
        this.selectedFiles.splice(index, 1);
        const dt = new DataTransfer();
        const input = this.$refs.fileInput;
        if (input && input.files) {
            Array.from(input.files).forEach((file, i) => {
                if (i !== index) dt.items.add(file);
            });
            input.files = dt.files;
        }
    },

    clearSelectedFiles() {
        this.selectedFiles = [];
        if (this.$refs.fileInput) {
            this.$refs.fileInput.value = '';
        }
    }
}">

    {{-- ═══ 1. BREADCRUMBS & ACTION HEADER ═══ --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-4 mb-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        {{-- Breadcrumbs Navigation --}}
        <div class="flex items-center flex-wrap gap-1.5 text-xs font-semibold text-slate-600">
            <span class="text-slate-400 mr-1 flex items-center">
                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                </svg>
            </span>

            @foreach($breadcrumbs as $index => $crumb)
                @if(!$loop->first)
                    <span class="text-slate-300 font-normal">/</span>
                @endif

                @if($loop->last)
                    <span class="text-blue-900 font-bold px-2 py-1 bg-blue-50 rounded-lg">
                        {{ $crumb['title'] }}
                    </span>
                @else
                    <a href="{{ route('filemanager.index', ['path' => $crumb['path']]) }}"
                       class="hover:text-blue-600 hover:bg-slate-50 px-2 py-1 rounded-lg transition">
                        {{ $crumb['title'] }}
                    </a>
                @endif
            @endforeach
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2">
            <button x-on:click="showNewFolderModal = true" type="button"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl shadow-xs transition">
                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                </svg>
                <span>Folder Baru</span>
            </button>

            <button x-on:click="showUploadModal = true; clearSelectedFiles()" type="button"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-sm shadow-blue-500/25 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                <span>Upload File</span>
            </button>
        </div>

    </div>

    {{-- ═══ 2. SERVER & DIRECTORY INFO WIDGET (Dinamis: Mahasiswa -> \NIM, Laboran -> \Operasional_Lab) ═══ --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
        
        {{-- Root Linux Path --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-3.5 shadow-xs flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
            </div>
            <div class="overflow-hidden min-w-0">
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Root Linux Path</p>
                <p class="text-xs font-mono font-semibold text-slate-800 truncate" title="{{ $user_root }}">
                    {{ $user_root }}
                </p>
            </div>
        </div>

        {{-- Akses Samba LAN Dinamis --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-3.5 shadow-xs flex items-center justify-between gap-2">
            <div class="flex items-center gap-3 overflow-hidden min-w-0">
                <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0a8 8 0 00-.001 0"/>
                    </svg>
                </div>
                <div class="overflow-hidden min-w-0">
                    <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Akses Samba LAN</p>
                    <p class="text-xs font-mono font-semibold text-blue-700 truncate" title="\\{{ $sambaHost }}\{{ $sambaShareName }}">
                        \\{{ $sambaHost }}\{{ $sambaShareName }}
                    </p>
                </div>
            </div>
            <button x-on:click="copySmb('\\\\{{ $sambaHost }}\\{{ $sambaShareName }}')" type="button"
                    class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition shrink-0">
                <span x-text="copied ? 'Tersalin!' : 'Salin'"></span>
            </button>
        </div>

        {{-- Statistics Counter --}}
        <div class="bg-white border border-slate-200 rounded-2xl p-3.5 shadow-xs flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Isi Direktori Ini</p>
                <p class="text-xs font-bold text-slate-800">
                    <span class="text-blue-600">{{ count($folders) }}</span> Folder &bull;
                    <span class="text-emerald-600">{{ count($files) }}</span> File
                </p>
            </div>
        </div>

    </div>

    {{-- ═══ 3. INTEGRASI WIDGET KUOTA (Progress Bar Dinamis / Unlimited untuk Admin) ═══ --}}
    @php
        $isAdminRole = ($role === 'admin') || (auth()->user() && auth()->user()->isAdmin());
        $percent = $quotaPercent ?? 0;
        if ($isAdminRole) {
            $barColor = 'bg-gradient-to-r from-blue-500 to-indigo-600';
            $badgeBg = 'bg-blue-50 text-blue-700 border-blue-200';
            $statusText = 'Akses Penuh / Unlimited';
        } elseif ($percent < 75) {
            $barColor = 'bg-emerald-500';
            $badgeBg = 'bg-emerald-50 text-emerald-700 border-emerald-200';
            $statusText = 'Kapasitas Normal';
        } elseif ($percent <= 90) {
            $barColor = 'bg-amber-500';
            $badgeBg = 'bg-amber-50 text-amber-700 border-amber-200';
            $statusText = 'Mendekati Batas';
        } else {
            $barColor = 'bg-rose-500';
            $badgeBg = 'bg-rose-50 text-rose-700 border-rose-200';
            $statusText = 'Kritis / Penuh';
        }
    @endphp
    <div class="bg-white border border-slate-200 rounded-2xl p-4 mb-4 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
            <div class="flex items-center gap-2">
                <span class="text-sm">💾</span>
                <span class="text-xs font-extrabold text-slate-800">
                    @if($isAdminRole)
                        Kapasitas Penyimpanan Administrator (Samba Operasional)
                    @elseif($role === 'laboran')
                        Kapasitas Penyimpanan Laboran (Samba Operasional)
                    @elseif($role === 'dosen')
                        Kapasitas Penyimpanan Dosen (Samba Operasional - Kuota {{ $quotaLimitFormatted }})
                    @else
                        Kapasitas Penyimpanan Mahasiswa
                    @endif
                </span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border {{ $badgeBg }}">
                    {{ $statusText }}
                </span>
            </div>
            <div class="text-xs font-mono font-bold text-slate-600">
                <span class="text-slate-800">{{ $quotaUsedFormatted }}</span>
                <span class="text-slate-400 font-normal"> / {{ $quotaLimitFormatted }}</span>
                @if(!$isAdminRole)
                    <span class="text-blue-600 ml-1">({{ $percent }}%)</span>
                @else
                    <span class="text-blue-600 ml-1 font-extrabold">(Tanpa Batas)</span>
                @endif
            </div>
        </div>

        {{-- Progress Bar Dinamis --}}
        <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden p-0.5 border border-slate-200/80">
            <div class="h-full rounded-full transition-all duration-500 {{ $barColor }}"
                 style="width: {{ $isAdminRole ? 100 : $percent }}%;"></div>
        </div>
    </div>

    {{-- ═══ 4. DAFTAR FILE & FOLDER (Tabel Modern) ═══ --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
        
        {{-- Search & Filter Toolbar --}}
        <div class="p-3.5 border-b border-slate-100 flex items-center justify-between gap-3 bg-slate-50/50">
            <div class="relative max-w-xs w-full">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </span>
                <input type="text" x-model="search" placeholder="Cari file atau folder..."
                       class="w-full pl-9 pr-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-medium outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
            </div>

            @if(!empty($current_path))
                @php
                    $parentPath = dirname($current_path);
                    if ($parentPath === '.' || $parentPath === '/') $parentPath = '';
                @endphp
                <a href="{{ route('filemanager.index', ['path' => $parentPath]) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 text-xs font-semibold rounded-xl border border-slate-200 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                    </svg>
                    <span>Ke Atas</span>
                </a>
            @endif
        </div>

        {{-- Table Container --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Nama</th>
                        <th class="py-3 px-4 w-32">Ukuran</th>
                        <th class="py-3 px-4 w-48">Tanggal Modifikasi</th>
                        <th class="py-3 px-4 w-28 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    
                    {{-- ── A. DAFTAR FOLDER ── --}}
                    @foreach($folders as $folder)
                    <tr class="hover:bg-blue-50/40 transition group"
                        x-show="!search || '{{ strtolower($folder['name']) }}'.includes(search.toLowerCase())">
                        <td class="py-2.5 px-4 font-semibold text-slate-800">
                            <a href="{{ route('filemanager.index', ['path' => $folder['path']]) }}"
                               class="flex items-center gap-3 group-hover:text-blue-600 transition">
                                <span class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-200 text-amber-500 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                                    </svg>
                                </span>
                                <span class="truncate">{{ $folder['name'] }}</span>
                            </a>
                        </td>
                        <td class="py-2.5 px-4 text-slate-400 font-mono text-[11px]">—</td>
                        <td class="py-2.5 px-4 text-slate-500">{{ $folder['last_modified'] }}</td>
                        <td class="py-2.5 px-4 text-right">
                            <button x-on:click="confirmDelete('{{ $folder['path'] }}', '{{ $folder['name'] }}', 'folder')"
                                    type="button"
                                    class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition"
                                    title="Hapus Folder">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @endforeach

                    {{-- ── B. DAFTAR FILE ── --}}
                    @foreach($files as $file)
                    @php
                        $isImg = in_array(strtolower($file['extension'] ?? ''), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                        $filePreviewUrl = route('filemanager.preview', ['path' => $file['path']]);
                    @endphp
                    <tr class="hover:bg-slate-50/70 transition group"
                        x-show="!search || '{{ strtolower($file['name']) }}'.includes(search.toLowerCase())">
                        <td class="py-2.5 px-4 font-medium text-slate-800">
                            <div class="flex items-center gap-3">
                                @if($isImg)
                                    <div class="w-8 h-8 rounded-lg overflow-hidden border border-slate-200 bg-slate-100 flex items-center justify-center shrink-0 cursor-pointer shadow-2xs group-hover:scale-105 transition"
                                         x-on:click="openPreview({{ json_encode($file) }})"
                                         title="Klik untuk pratinjau">
                                        <img src="{{ $filePreviewUrl }}" alt="{{ $file['name'] }}" loading="lazy" class="w-full h-full object-cover">
                                    </div>
                                @else
                                    <span class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-[10px] border shrink-0 cursor-pointer shadow-2xs group-hover:scale-105 transition {{ $file['icon_data']['color'] ?? ($file['icon_color'] ?? 'bg-slate-100 text-slate-700 border-slate-200') }}"
                                          x-on:click="openPreview({{ json_encode($file) }})"
                                          title="Klik untuk pratinjau">
                                        {{ $file['icon_data']['badge'] ?? ($file['badge'] ?? strtoupper($file['extension'] ?? 'FILE')) }}
                                    </span>
                                @endif

                                <button type="button"
                                        x-on:click="openPreview({{ json_encode($file) }})"
                                        class="text-left font-medium text-slate-800 hover:text-blue-600 truncate max-w-md transition flex items-center gap-1.5"
                                        title="Klik untuk melihat pratinjau: {{ $file['name'] }}">
                                    <span class="truncate">{{ $file['name'] }}</span>
                                </button>
                            </div>
                        </td>
                        <td class="py-2.5 px-4 text-slate-600 font-mono text-[11.5px] whitespace-nowrap">
                            {{ $file['size'] }}
                        </td>
                        <td class="py-2.5 px-4 text-slate-500 whitespace-nowrap">
                            {{ $file['last_modified'] }}
                        </td>
                        <td class="py-2.5 px-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                {{-- Tombol Pratinjau (Preview) --}}
                                <button type="button"
                                        x-on:click="openPreview({{ json_encode($file) }})"
                                        class="p-1.5 text-slate-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition"
                                        title="Pratinjau / Preview File">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>

                                {{-- Tombol Unduh (Download) --}}
                                <a href="{{ route('filemanager.download', ['path' => $file['path']]) }}"
                                   class="p-1.5 text-slate-400 hover:text-emerald-600 rounded-lg hover:bg-emerald-50 transition"
                                   title="Unduh File">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </a>

                                {{-- Tombol Hapus --}}
                                <button x-on:click="confirmDelete('{{ $file['path'] }}', '{{ $file['name'] }}', 'file')"
                                        type="button"
                                        class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition"
                                        title="Hapus File">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach

                    {{-- Empty State --}}
                    @if(empty($folders) && empty($files))
                    <tr>
                        <td colspan="4" class="py-12 text-center">
                            <div class="max-w-xs mx-auto text-slate-400">
                                <svg class="w-12 h-12 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="text-xs font-semibold text-slate-600 mb-1">Folder ini masih kosong</p>
                                <p class="text-[11px] text-slate-400">Gunakan tombol di atas untuk membuat subfolder atau mengunggah file baru.</p>
                            </div>
                        </td>
                    </tr>
                    @endif

                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ 5. MODAL BUAT FOLDER BARU ═══ --}}
    <div x-cloak x-show="showNewFolderModal"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div x-on:click.outside="showNewFolderModal = false"
             class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-200">
            <h3 class="font-bold text-slate-800 text-base mb-1">Buat Folder Baru</h3>
            <p class="text-xs text-slate-500 mb-4">Folder akan dibuat di path saat ini.</p>

            <form action="{{ route('filemanager.folder.create') }}" method="POST">
                @csrf
                <input type="hidden" name="current_path" value="{{ $current_path }}">
                
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-600 mb-1">Nama Folder</label>
                    <input type="text" name="folder_name" required autofocus placeholder="Contoh: Tugas_Praktikum"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button x-on:click="showNewFolderModal = false" type="button"
                            class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                        Simpan Folder
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ 6. MODAL UPLOAD FILE DENGAN JS PREVIEW ═══ --}}
    <div x-cloak x-show="showUploadModal"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div x-on:click.outside="showUploadModal = false"
             class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                    </span>
                    <h3 class="font-bold text-slate-800 text-base">Unggah File ke Samba</h3>
                </div>
                <button x-on:click="showUploadModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>

            <form action="{{ route('filemanager.upload') }}" method="POST" enctype="multipart/form-data"
                  onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit]').innerText = 'Sedang mengunggah...';">
                @csrf
                <input type="hidden" name="current_path" value="{{ $current_path }}">

                {{-- Drag & Drop Upload Zone --}}
                <div class="border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-2xl p-6 text-center cursor-pointer transition bg-slate-50/50 mb-4"
                     x-on:dragover.prevent="isDragging = true"
                     x-on:dragleave.prevent="isDragging = false"
                     x-on:drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change')); handleFileSelect($event.dataTransfer.files)"
                     :class="{ 'border-blue-500 bg-blue-50/50': isDragging }">
                    <input x-ref="fileInput" type="file" name="files[]" multiple required class="hidden" id="fileUploadInput"
                           x-on:change="handleFileSelect($event.target.files)">
                    
                    <label for="fileUploadInput" class="cursor-pointer block">
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                        </div>
                        <p class="text-xs font-bold text-slate-700 mb-1">Tarik & Jatuhkan file di sini, atau <span class="text-blue-600 underline">Pilih File</span></p>
                        <p class="text-[11px] text-slate-400">Mendukung multi-file upload sekaligus.</p>
                    </label>
                </div>

                {{-- ═══ LIVE JS FILE PREVIEW LIST ═══ --}}
                <div x-show="selectedFiles.length > 0" class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-bold text-slate-700">
                            File Terpilih (<span x-text="selectedFiles.length" class="text-blue-600 font-black"></span>):
                        </p>
                        <button type="button" x-on:click="clearSelectedFiles()"
                                class="text-[11px] text-rose-600 hover:underline font-semibold">
                            Hapus Semua
                        </button>
                    </div>

                    <div class="max-h-40 overflow-y-auto space-y-1.5 p-2 bg-slate-50 rounded-xl border border-slate-200 divide-y divide-slate-100">
                        <template x-for="(file, index) in selectedFiles" :key="index">
                            <div class="flex items-center justify-between gap-2 py-1 px-1.5 text-xs">
                                <div class="flex items-center gap-2 overflow-hidden min-w-0">
                                    <span class="w-5 h-5 rounded bg-blue-100 text-blue-700 font-bold text-[9px] flex items-center justify-center shrink-0 uppercase"
                                          x-text="file.ext.slice(0, 3)"></span>
                                    <span class="font-medium text-slate-700 truncate" x-text="file.name"></span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-[11px] text-slate-400 font-mono" x-text="file.size"></span>
                                    <button type="button" x-on:click="removeSelectedFile(index)"
                                            class="text-slate-400 hover:text-rose-600 p-0.5 rounded transition"
                                            title="Batalkan file ini">
                                        &times;
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Catatan Khusus File Besar --}}
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-5">
                    <p class="text-[11.5px] font-bold text-amber-800 mb-0.5">⚠️ Catatan Batas Kuota & File Besar:</p>
                    <p class="text-[11px] text-amber-700 leading-relaxed">
                        Maksimal kuota per mahasiswa adalah <strong>1 GB</strong>. Untuk file berukuran sangat besar, disarankan memindahkan file langsung melalui koneksi Samba LAN.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button x-on:click="showUploadModal = false" type="button"
                            class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                        Mulai Unggah
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ 7. MODAL KONFIRMASI HAPUS ═══ --}}
    <div x-cloak x-show="showDeleteModal"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div x-on:click.outside="showDeleteModal = false"
             class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-200">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            
            <h3 class="font-bold text-slate-800 text-base mb-1">Konfirmasi Penghapusan</h3>
            <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                Apakah Anda yakin ingin menghapus <span class="font-bold text-slate-800" x-text="deleteData.type === 'folder' ? 'folder' : 'file'"></span>:
                <br><code class="font-mono text-rose-600 font-bold break-all" x-text="deleteData.name"></code>?
                <br><span class="text-rose-500 text-[11px]">Tindakan ini tidak dapat dibatalkan.</span>
            </p>

            <form action="{{ route('filemanager.destroy') }}" method="POST">
                @csrf
                @method('DELETE')
                <input type="hidden" name="current_path" value="{{ $current_path }}">
                <input type="hidden" name="path" :value="deleteData.path">
                <input type="hidden" name="type" :value="deleteData.type">

                <div class="flex items-center justify-end gap-2">
                    <button x-on:click="showDeleteModal = false" type="button"
                            class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-sm transition">
                        Ya, Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ 8. MODAL PRATINJAU FILE (FILE PREVIEW LIGHTBOX/MODAL) ═══ --}}
    <div x-cloak x-show="showPreviewModal"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-5">
        <div x-on:click.outside="showPreviewModal = false"
             class="bg-white rounded-3xl max-w-4xl w-full max-h-[92vh] flex flex-col shadow-2xl border border-slate-100 overflow-hidden transition-all">
            
            {{-- Modal Header --}}
            <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between gap-3 bg-slate-50/80 shrink-0">
                <div class="flex items-center gap-2.5 overflow-hidden min-w-0">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-blue-100 text-blue-700 shrink-0"
                          x-text="previewData.ext || 'FILE'"></span>
                    <div class="overflow-hidden min-w-0">
                        <h3 class="font-extrabold text-slate-800 text-sm truncate" x-text="previewData.name"></h3>
                        <p class="text-[11px] text-slate-400 font-medium truncate">
                            Ukuran: <span class="font-mono text-slate-600 font-semibold" x-text="previewData.size"></span> &bull; 
                            Dimodifikasi: <span x-text="previewData.lastModified"></span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 shrink-0">
                    {{-- Open in New Tab Button --}}
                    <a :href="previewData.url" target="_blank"
                       class="px-2.5 py-1.5 bg-white hover:bg-slate-100 text-slate-600 hover:text-blue-600 text-xs font-bold rounded-xl border border-slate-200 shadow-2xs transition inline-flex items-center gap-1"
                       title="Buka di Tab Baru">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        <span class="hidden sm:inline">Tab Baru</span>
                    </a>

                    {{-- Download Button --}}
                    <a :href="previewData.downloadUrl"
                       class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-xs transition inline-flex items-center gap-1"
                       title="Unduh File">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span class="hidden sm:inline">Unduh</span>
                    </a>

                    {{-- Close Button --}}
                    <button x-on:click="showPreviewModal = false" type="button"
                            class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center font-bold text-lg transition">
                        &times;
                    </button>
                </div>
            </div>

            {{-- Modal Body (Tampilan Dinamis Sesuai Jenis File) --}}
            <div class="p-4 sm:p-6 overflow-y-auto flex-1 flex items-center justify-center bg-slate-100/60 min-h-[320px]">
                
                {{-- 1. Pratinjau Gambar (JPG, PNG, GIF, WEBP, SVG) --}}
                <template x-if="previewData.type === 'image'">
                    <div class="flex items-center justify-center w-full h-full">
                        <img :src="previewData.url" :alt="previewData.name"
                             class="max-h-[72vh] max-w-full object-contain rounded-2xl shadow-md border border-slate-200/80 bg-white">
                    </div>
                </template>

                {{-- 2. Pratinjau Dokumen PDF --}}
                <template x-if="previewData.type === 'pdf'">
                    <div class="w-full h-[72vh] rounded-2xl overflow-hidden border border-slate-200 shadow-sm bg-white">
                        <iframe :src="previewData.url" class="w-full h-full border-0"></iframe>
                    </div>
                </template>

                {{-- 3. Pratinjau Video (MP4, WebM, OGG) --}}
                <template x-if="previewData.type === 'video'">
                    <div class="w-full max-h-[72vh] flex items-center justify-center">
                        <video :src="previewData.url" controls autoplay class="max-h-[72vh] max-w-full rounded-2xl shadow-lg bg-black"></video>
                    </div>
                </template>

                {{-- 4. Pratinjau Audio (MP3, WAV, OGG) --}}
                <template x-if="previewData.type === 'audio'">
                    <div class="w-full max-w-md p-7 bg-white rounded-3xl border border-slate-200 shadow-md text-center">
                        <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-3xl mx-auto mb-4 shadow-2xs">
                            🎵
                        </div>
                        <h4 class="font-bold text-slate-800 text-sm mb-1 truncate" x-text="previewData.name"></h4>
                        <p class="text-xs text-slate-400 mb-5" x-text="previewData.size"></p>
                        <audio :src="previewData.url" controls class="w-full"></audio>
                    </div>
                </template>

                {{-- 5. Pratinjau Dokumen Teks / Kode / Skrip (TXT, LOG, CSV, JSON, MD, SQL, CODE) --}}
                <template x-if="previewData.type === 'text'">
                    <div class="w-full h-full max-h-[72vh] flex flex-col">
                        <div x-show="previewData.loadingText" class="text-center py-12 text-slate-400">
                            <svg class="animate-spin h-8 w-8 mx-auto mb-2 text-blue-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span class="text-xs font-semibold">Memuat isi dokumen teks...</span>
                        </div>
                        <pre x-show="!previewData.loadingText"
                             class="w-full max-h-[70vh] overflow-auto p-4 bg-slate-900 text-emerald-400 rounded-2xl font-mono text-xs leading-relaxed border border-slate-800 shadow-inner select-text"
                             x-text="previewData.textContent"></pre>
                    </div>
                </template>

                {{-- 6. Dokumen Lainnya (Word, Excel, PPT, ZIP, RAR, dsb) --}}
                <template x-if="previewData.type === 'other'">
                    <div class="w-full max-w-sm p-7 bg-white rounded-3xl border border-slate-200 shadow-md text-center">
                        <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-3xl mx-auto mb-4 shadow-2xs">
                            📄
                        </div>
                        <h4 class="font-bold text-slate-800 text-sm mb-1 truncate" x-text="previewData.name"></h4>
                        <p class="text-xs text-slate-400 mb-3" x-text="previewData.size + ' • File ' + previewData.ext"></p>
                        <p class="text-xs text-slate-500 mb-6 leading-relaxed">
                            Format file ini tidak mendukung pratinjau langsung di dalam browser. Silakan unduh dokumen untuk membukanya di komputer Anda.
                        </p>
                        <a :href="previewData.downloadUrl"
                           class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-sm shadow-blue-500/25 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span>Unduh File Sekarang</span>
                        </a>
                    </div>
                </template>

            </div>

        </div>
    </div>

</div>
@endsection
