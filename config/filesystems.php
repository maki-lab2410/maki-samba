<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Disk default aplikasi Laravel.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Konfigurasi disk storage. Untuk aplikasi Maki Samba Lab RMIK, kita menambahkan
    | custom disk 'samba_base' dan 'samba_user' yang memetakan storage Laravel
    | langsung ke direktori fisik Linux /samba/mahasiswa/{NIM}.
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app/private'),
            'serve'  => true,
            'throw'  => false,
            'report' => false,
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw'      => false,
            'report'     => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Custom Disk: Samba Base Path
        |--------------------------------------------------------------------------
        | Root direktori utama untuk seluruh mahasiswa di server Linux AlmaLinux.
        | Default: /samba/mahasiswa
        */
        'samba_base' => [
            'driver'     => 'local',
            'root'       => env('SAMBA_BASE_PATH', '/samba/mahasiswa'),
            'throw'      => false,
            'permissions'=> [
                'file' => [
                    'public'  => 0666,
                    'private' => 0600,
                ],
                'dir' => [
                    'public'  => 0777,
                    'private' => 0700,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Custom Disk: Samba User Disk (Dikonfigurasi Dinamis pada Runtime)
        |--------------------------------------------------------------------------
        | Disk ini merepresentasikan folder mahasiswa yang sedang aktif login.
        | Root path-nya diubah secara on-the-fly di StorageService ke:
        | /samba/mahasiswa/{NIM}
        | Setiap file operation (list, makeDirectory, upload, delete, download)
        | akan terisolasi (chrooted/jailed) di direktori mahasiswa tersebut.
        */
        'samba_user' => [
            'driver'     => 'local',
            'root'       => env('SAMBA_BASE_PATH', '/samba/mahasiswa'),
            'throw'      => false,
            'permissions'=> [
                'file' => [
                    'public'  => 0666,
                    'private' => 0600,
                ],
                'dir' => [
                    'public'  => 0777,
                    'private' => 0700,
                ],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
