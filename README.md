# 📦 Maki Samba – Portal Samba & Web File Manager Laboratorium

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Platform](https://img.shields.io/badge/Platform-AlmaLinux%2010%20%7C%20aaPanel-orange.svg)]()
[![PHP Version](https://img.shields.io/badge/PHP-8.4-777bb4.svg)]()
[![Laravel](https://img.shields.io/badge/Laravel-12.x-ff2d20.svg)]()
[![Security Hardened](https://img.shields.io/badge/Security-Hardened%20(No%20PlainText%20Shell)-brightgreen.svg)]()

**Maki Samba** adalah solusi portal web modern berbasis **Laravel 12 (PHP 8.4)** yang dirancang untuk mengotomatisasi pembuatan akun **Samba File Server (SMB/CIFS)** dan menyediakan **Web File Manager** (Google Drive lokal) berkinerja tinggi. Aplikasi ini dioptimalkan khusus untuk lingkungan laboratorium komputer kampus, institusi pendidikan, dan ruang praktikum dengan sistem operasi **AlmaLinux 10 / RHEL**, **Apache/Nginx**, dan panel hosting **aaPanel**.

---

## 📑 Daftar Isi

- [Fitur Utama](#-fitur-utama)
- [Peran Pengguna & Skema Hak Akses (RBAC)](#-peran-pengguna--skema-hak-akses-rbac)
- [Arsitektur & Keamanan](#-arsitektur--keamanan)
- [Persyaratan Sistem](#-persyaratan-sistem)
- [Struktur Direktori Proyek](#-struktur-direktori-proyek)
- [Panduan Instalasi & Deployment Cepat](#-panduan-instalasi--deployment-cepat)
  - [Metode 1: Deploy Otomatis (Rekomendasi)](#metode-1-deploy-otomatis-rekomendasi)
  - [Metode 2: Setup Manual di Server Linux](#metode-2-setup-manual-di-server-linux)
- [Variabel Lingkungan (.env)](#-variabel-lingkungan-env)
- [Buku Manual & Panduan Lengkap](#-buku-manual--panduan-lengkap)
- [Kontribusi & Lisensi](#-kontribusi--lisensi)

---

## 🌟 Fitur Utama

- 👥 **Otomatisasi Akun Linux & Samba (Zero-Terminal)**:
  - Registrasi mahasiswa dan pembuatan akun dari panel admin otomatis memicu pembuatan akun Linux OS (`useradd`) dan password Samba (`smbpasswd`) secara *real-time*.
  - Pembaruan kata sandi dari profil pengguna langsung disinkronkan ke share Windows LAN.
- 📁 **Web File Manager Modern (Local Cloud Drive)**:
  - Antarmuka drag-and-drop untuk unggah/unduh berkas dengan kuota disk dinamis.
  - Pengorganisasian folder, multi-select, pencarian instan, dan pratinjau berkas terisolasi (*sandbox*).
- 🎓 **Direktori Berkas Mahasiswa (Panel Dosen & Laboran)**:
  - Navigasi tugas praktikum dengan filter multi-level: **Semester (1 - 8)**, **Kelas**, **Folder Mata Kuliah**, dan **Pencarian Nama/NIM**.
  - **Dynamic Folder Scanner**: Mendeteksi folder tugas yang dibuat mandiri oleh mahasiswa.
  - **Bulk Download ZIP**: Mengunduh seluruh berkas praktikum per kelas/semester ke dalam satu file `.zip` rapi dalam satu klik.
- 🎨 **Kustomisasi Identitas Instansi & Tema Pastel**:
  - Ganti nama instansi, logo kustom (berlaku untuk favicon browser & navbar), slogan portal, dan pilihan palet tema pastel melalui dashboard admin.
- 📊 **Monitoring Server Real-Time**:
  - Pemantauan status service Samba (`smbd`), koneksi aktif klien Windows (`smbstatus`), penggunaan storage disk, dan konsumsi memori server.
- 📥 **Impor Akun Massal (CSV)**:
  - Fasilitas impor puluhan hingga ratusan mahasiswa sekaligus beserta pembagian semester dan kelas.
- 🔒 **Keamanan Berlapis (Enterprise Hardened)**:
  - Proteksi injeksi perintah shell (`proc_open` via STDIN).
  - Isolasi pratinjau file dengan Content Security Policy (CSP).
  - Proteksi akun sistem OS (UID < 1000 blacklist).
  - Hak akses file Linux ketat (`chmod 750/755`).

---

## 👥 Peran Pengguna & Skema Hak Akses (RBAC)

| Role | Kuota Standar | Windows Share (LAN) | Path Server Fisik | Hak Akses Utama |
| :--- | :---: | :--- | :--- | :--- |
| **Mahasiswa** | **1 GB** | `\\IP\mahasiswa` | `/samba/mahasiswa/{NIM}` | File Manager pribadi, unggah tugas praktikum, akses folder share via Windows Explorer. |
| **Dosen** | **5 GB** | `\\IP\operasional` | `/samba/operasional/{NIDN}` | Ruang operasional pribadi + **Direktori Berkas Mahasiswa** (Read-Only: preview & unduh ZIP tugas per kelas/semester). |
| **Laboran** | **10 GB** | `\\IP\operasional` | `/samba/operasional/{NIP}` | Ruang operasional pribadi + **Direktori Berkas Mahasiswa** + Monitoring koneksi sesi Samba klien. |
| **Administrator**| **Unlimited**| `\\IP\operasional` | `/samba/operasional` | **Akses Penuh**: Pengaturan identitas, manajemen akun, reset sandi, impor CSV, server tools, & unduh ZIP massal. |

---

## 🛡️ Arsitektur & Keamanan

Sistem mengadopsi model *Security by Design*:

1. **Komunikasi Shell Tanpa Plaintext**:
   Interaksi PHP ke script bash sistem Linux (`/usr/local/bin/buat_akun_samba.sh`) tidak mengirimkan password via argumen CLI (mencegah kebocoran di `ps aux`). Password dialirkan aman lewat pipe descriptor `proc_open` ke `STDIN`.
2. **Proteksi Sistem Akun Daemon**:
   Pencegahan pengambilalihan akun root/system service (`root`, `bin`, `daemon`, `www`, `nobody`, dll.) dengan validasi ganda di layer PHP dan validasi `id -u < 1000` di layer Bash.
3. **Safe File Preview (Anti Stored-XSS)**:
   File yang diunggah pengguna dipratinjau dengan header `Content-Security-Policy: sandbox allow-scripts` dan skrip executable server (`.php`, `.sh`, `.phtml`, dll.) dipaksa menjadi `Content-Disposition: attachment`.
4. **Isolasi Folder Samba**:
   Hak akses direktori mahasiswa dan operasional menggunakan mode `chmod 750` dengan kepemilikan user masing-masing, mencegah akses silang antar pengguna di tingkat filesystem.

---

## 💻 Persyaratan Sistem

- **Sistem Operasi**: AlmaLinux 9/10, RHEL 9/10, Rocky Linux 9/10, atau Ubuntu Server 22.04/24.04
- **Web Server**: Apache 2.4+ atau Nginx 1.20+
- **PHP**: Versi 8.2 – 8.4
  - Ekstensi PHP wajib: `php-zip`, `php-sqlite3`, `php-fileinfo`, `php-mbstring`, `php-xml`, `php-curl`
- **Database**: SQLite 3 (bawaan, tanpa perlu setup MySQL server)
- **Paket Linux Tambahan**: `samba`, `samba-client`, `rsync`, `zip`, `unzip`, `sudo`

---

## 📂 Struktur Direktori Proyek

```text
maki-samba/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AdminController.php           # Panel manajemen admin, kuota, identitas, CSV
│   │   │   ├── AuthController.php            # Autentikasi login, register, logout
│   │   │   ├── DosenMahasiswaController.php  # Direktori mahasiswa, filter kelas, unduh ZIP
│   │   │   ├── FileManagerController.php     # Web file manager personal & upload handler
│   │   │   └── ProfileController.php         # Profil pengguna & ubah kata sandi mandiri
│   │   └── Middleware/                       # Proteksi session & otorisasi RBAC
│   ├── Models/
│   │   ├── Setting.php                       # Model kustomisasi branding & tema
│   │   └── User.php                          # Model Eloquent pengguna, semester, kelas
│   └── Services/
│       ├── SambaService.php                  # Handler eksekusi sistem Linux (proc_open)
│       └── StorageService.php                # Storage disk dinamis & utilitas berkas
├── config/                                   # Konfigurasi aplikasi & filesystems
├── database/
│   └── migrations/                           # Skema tabel users, semester, dan settings
├── public/                                   # Document root web server
│   ├── uploads/logo/                         # Penyimpanan logo kustom instansi
│   └── index.php                             # Front controller Laravel
├── resources/
│   └── views/
│       ├── admin/index.blade.php             # Dashboard admin (Akun, Monitoring, Server, Pengaturan)
│       ├── dosen/mahasiswa/                  # Direktori tugas mahasiswa & preview modal
│       ├── filemanager/                      # Antarmuka file manager personal
│       └── layouts/app.blade.php             # Layout utama dengan navigasi dinamis & tema
├── routes/
│   └── web.php                               # Definisi rute web & API internal
├── scripts/
│   ├── buat_akun_samba.sh                    # Automation script pembuatan user Linux/Samba
│   ├── recovery_samba.sh                     # Script perbaikan massal permission & Samba share
│   ├── sudoers_rmik_samba                    # Aturan hak akses sudoer untuk web server (www)
│   ├── update_server.sh                      # Script pembaruan dependensi & migrasi di server
│   └── smb.conf.example                      # Template referensi konfigurasi Samba
├── deploy.sh                                 # Script one-click deployment ke server
├── PANDUAN_ADMINISTRATOR.md                  # Buku manual operasional administrator lengkap
├── LICENSE                                   # Lisensi resmi MIT
└── README.md                                 # Dokumentasi ini
```

---

## 🚀 Panduan Instalasi & Deployment Cepat

### Metode 1: Deploy Otomatis (Rekomendasi)

Jika Anda memiliki akses SSH ke server target:

```bash
# Clone repository
git clone https://github.com/maki-lab2410/maki-samba.git
cd maki-samba

# Jalankan deploy ke server target (sesuaikan SERVER_IP)
SERVER_IP="192.168.1.100" SERVER_USER="root" ./deploy.sh
```

Script akan otomatis:
1. Menyalin seluruh source code ke server target tanpa menimpa database `.sqlite` maupun konfigurasi `.env`.
2. Memasang script otomatisasi ke `/usr/local/bin/buat_akun_samba.sh`.
3. Memasang izin sudoers di `/etc/sudoers.d/rmik_samba`.
4. Memastikan direktori `/samba/mahasiswa` dan `/samba/operasional` memiliki hak akses yang benar.
5. Menjalankan migrasi database dan membersihkan cache aplikasi.

---

### Metode 2: Setup Manual di Server Linux

1. **Clone dan Pasang Dependensi**:
   ```bash
   cd /www/wwwroot/maki-samba
   git clone https://github.com/maki-lab2410/maki-samba.git .
   cp .env.example .env
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   ```

2. **Inisialisasi Database SQLite**:
   ```bash
   touch database/database.sqlite
   php artisan migrate --force
   ```

3. **Pasang Script Otomatisasi Samba**:
   ```bash
   cp scripts/buat_akun_samba.sh /usr/local/bin/buat_akun_samba.sh
   chmod 755 /usr/local/bin/buat_akun_samba.sh
   chown root:root /usr/local/bin/buat_akun_samba.sh

   cp scripts/sudoers_rmik_samba /etc/sudoers.d/rmik_samba
   chmod 440 /etc/sudoers.d/rmik_samba
   ```

4. **Siapkan Direktori Samba**:
   ```bash
   mkdir -p /samba/mahasiswa /samba/operasional
   chmod 755 /samba/mahasiswa /samba/operasional
   ```

5. **Sesuaikan Kepemilikan Web**:
   ```bash
   chown -R www:www /www/wwwroot/maki-samba
   chmod -R 775 /www/wwwroot/maki-samba/storage /www/wwwroot/maki-samba/bootstrap/cache /www/wwwroot/maki-samba/database
   ```

---

## ⚙️ Variabel Lingkungan (.env)

Konfigurasi penting pada file `.env` di server:

```ini
APP_NAME="Maki Samba"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.1.100

# Konfigurasi Path Samba di Linux
SAMBA_BASE_PATH=/samba/mahasiswa
SAMBA_OPERASIONAL_PATH=/samba/operasional
SAMBA_SCRIPT_PATH=/usr/local/bin/buat_akun_samba.sh
SAMBA_SERVER_HOST=192.168.1.100

# Kredensial Awal Akun Sistem (Ubah saat inisialisasi pertama)
ADMIN_DEFAULT_PASS=makisamba2026
LABORAN_DEFAULT_PASS=laboran123
```

---

## 📖 Buku Manual & Panduan Lengkap

Panduan operasional harian, panduan konfigurasi Samba, manajemen akun massal, troubleshooting, dan panduan untuk dosen serta mahasiswa tersedia secara mendalam pada:

👉 **[Buku Manual Administrator & Panduan Operasional (PANDUAN_ADMINISTRATOR.md)](PANDUAN_ADMINISTRATOR.md)**

---

## 📄 Kontribusi & Lisensi

Proyek ini bersifat open-source dan dilisensikan di bawah **[MIT License](LICENSE)** &copy; 2026 **Maki**.
Bebas digunakan, dimodifikasi, dan didistribusikan untuk keperluan akademik maupun institusional.
