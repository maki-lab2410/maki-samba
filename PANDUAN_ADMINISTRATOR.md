# 📘 Buku Panduan Administrator: Maki Samba
**Sistem Manajemen Storage, File Manager Web, & Otomatisasi Samba Lab RMIK**  
*Lisensi: MIT License &bull; Hak Cipta &copy; 2026 Maki*

---

## 📑 Daftar Isi
1. [Ringkasan Arsitektur & Peran Pengguna (RBAC)](#1-ringkasan-arsitektur--peran-pengguna-rbac)
2. [Spesifikasi Server & Persyaratan Sistem](#2-spesifikasi-server--persyaratan-sistem)
3. [Panduan Deployment Sistem](#3-panduan-deployment-sistem)
   - [Metode 1: Deploy Otomatis via Mac / PC (Rekomendasi)](#metode-1-deploy-otomatis-via-mac--pc-rekomendasi)
   - [Metode 2: Deploy Manual di Server Linux](#metode-2-deploy-manual-di-server-linux)
4. [Konfigurasi Server & Lingkungan Produksi](#4-konfigurasi-server--lingkungan-produksi)
   - [Konfigurasi PHP 8.4 (aaPanel)](#konfigurasi-php-84-aapanel)
   - [Konfigurasi Samba (/etc/samba/smb.conf)](#konfigurasi-samba-etcsambasmbconf)
   - [Konfigurasi Sudoers & Keamanan Linux](#konfigurasi-sudoers--keamanan-linux)
   - [Pengaturan Zona Waktu (WIB)](#pengaturan-zona-waktu-wib)
5. [Panduan Operasional & Penggunaan Administrator](#5-panduan-operasional--penggunaan-administrator)
   - [Akses Dashboard Admin](#akses-dashboard-admin)
   - [Manajemen Akun Pengguna](#manajemen-akun-pengguna)
   - [Mengubah Semester & Kelas Mahasiswa (Satuan & Massal)](#mengubah-semester--kelas-mahasiswa-satuan--massal)
   - [Impor Akun Massal Berbasis CSV](#impor-akun-massal-berbasis-csv)
   - [Pemeriksaan Direktori & Berkas Mahasiswa](#pemeriksaan-direktori--berkas-mahasiswa)
   - [Fitur Select Folder & Unduh ZIP Tugas Praktikum](#fitur-select-folder--unduh-zip-tugas-praktikum)
   - [Monitoring Sesi Samba & Utilisasi Server](#monitoring-sesi-samba--utilisasi-server)
6. [Pemeliharaan, Backup, & Pemecahan Masalah (Troubleshooting)](#6-pemeliharaan-backup--pemecahan-masalah-troubleshooting)
7. [Lisensi Penggunaan](#7-lisensi-penggunaan)

---

## 1. Ringkasan Arsitektur & Peran Pengguna (RBAC)

Maki Samba adalah portal web internal yang menggabungkan:
1. **Web File Manager**: Antarmuka berbasis peramban (browser) untuk unggah, unduh, pratinjau (preview), dan pengorganisasian berkas.
2. **Samba File Server**: Protokol SMB/CIFS (`\\IP_SERVER\share`) untuk akses langsung dari sistem operasi Windows di PC Lab tanpa login web berulang.

### Skema Role-Based Access Control (RBAC):

| Role | Kuota Standar | Folder Samba (LAN) | Path Fisik di Server | Akses Fitur Utama |
| :--- | :---: | :--- | :--- | :--- |
| **Mahasiswa** | **1 GB** | `\\IP\mahasiswa` | `/samba/mahasiswa/{NIM}` | File Manager pribadi, unggah/unduh tugas, buat folder praktikum. |
| **Dosen** | **5 GB** | `\\IP\operasional` | `/samba/operasional/{NIDN}` | File Manager operasional pribadi + **Direktori Mahasiswa** (baca, preview, unduh ZIP per kelas/semester). |
| **Laboran** | **10 GB** | `\\IP\operasional` | `/samba/operasional/{NIP}` | File Manager operasional pribadi + **Direktori Mahasiswa** + Monitoring sesi Samba. |
| **Administrator**| **Unlimited**| `\\IP\operasional` | `/samba/operasional` (Master) | **Hak penuh**: manajemen akun, reset password, impor CSV, server tools, & unduh ZIP massal. |

---

## 2. Spesifikasi Server & Persyaratan Sistem

- **Sistem Operasi**: AlmaLinux 10 / RHEL 10 / Rocky Linux 9/10
- **Panel Manajemen**: aaPanel (Linux)
- **Web Server**: Apache 2.4+
- **PHP**: Versi 8.4 (ekstensi wajib: `php-zip`, `php-sqlite3`, `php-fileinfo`, `php-mbstring`, `php-xml`)
- **Database**: SQLite 3 (tersimpan di `database/database.sqlite`)
- **File Server Service**: Samba (`smb`, `nmb`)
- **Utilitas Sistem Linux**: `rsync`, `zip`, `unzip`, `sudo`, `tar`

---

## 3. Panduan Deployment Sistem

### Metode 1: Deploy Otomatis via Mac / PC (Rekomendasi)

Proyek ini telah dilengkapi dengan script otomatisasi `./deploy.sh` yang melakukan sinkronisasi file via `rsync` / `tar` dan langsung menjalankan pembaruan di server.

1. Buka terminal di komputer Anda, lalu masuk ke direktori proyek:
   ```bash
   cd /path/ke/maki-samba
   ```
2. Pastikan file `deploy.sh` memiliki izin eksekusi:
   ```bash
   chmod +x deploy.sh
   ```
3. Jalankan script deploy:
   ```bash
   ./deploy.sh
   ```
   *Catatan:* Jika server menggunakan IP atau user non-default, jalankan dengan parameter:
   ```bash
   SERVER_IP=<SERVER_IP> SERVER_USER=root ./deploy.sh
   ```
4. Script akan secara otomatis:
   - Mengunggah file ke `/www/wwwroot/maki-samba` (mengecualikan file cache & database).
   - Menjalankan `scripts/update_server.sh` di server.
   - Mengatur izin file (chmod 777), timezone WIB, script otomatisasi Samba, dan membersihkan cache Laravel.

---

### Metode 2: Deploy Manual di Server Linux

Jika melakukan setup awal dari server langsung:

1. **Unduh atau Ekstrak File Proyek**:
   Letakkan file proyek di `/www/wwwroot/maki-samba`.
2. **Pasang Script Otomatisasi Samba**:
   ```bash
   cp /www/wwwroot/maki-samba/scripts/buat_akun_samba.sh /usr/local/bin/buat_akun_samba.sh
   chmod 750 /usr/local/bin/buat_akun_samba.sh
   chown root:root /usr/local/bin/buat_akun_samba.sh
   ```
3. **Konfigurasi Hak Akses Sudoers**:
   ```bash
   cp /www/wwwroot/maki-samba/scripts/sudoers_rmik_samba /etc/sudoers.d/rmik_samba
   chmod 0440 /etc/sudoers.d/rmik_samba
   ```
4. **Jalankan Script Pembaruan Sistem**:
   ```bash
   chmod +x /www/wwwroot/maki-samba/scripts/*.sh
   bash /www/wwwroot/maki-samba/scripts/update_server.sh
   ```

---

## 4. Konfigurasi Server & Lingkungan Produksi

### Konfigurasi PHP 8.4 (aaPanel)
Untuk mendukung unggah berkas besar (video, file database medis, dll), buka menu **PHP 8.4 > Configuration (php.ini)** di aaPanel dan pastikan parameter berikut terpasang:

```ini
; Batas maksimal unggah berkas
upload_max_filesize = 2048M
post_max_size = 2048M

; Alokasi memori script untuk proses kompresi ZIP berkas besar
memory_limit = 512M

; Batas waktu eksekusi proses upload dan zip
max_execution_time = 600
max_input_time = 600

; Zona waktu sistem
date.timezone = Asia/Jakarta
```

> **Catatan Apache:** Pada konfigurasi Apache aaPanel (`httpd.conf`), pastikan direktif:
> `LimitRequestBody 0`

---

### Konfigurasi Samba (/etc/samba/smb.conf)
Konfigurasi share wajib terdaftar di `/etc/samba/smb.conf`:

```ini
[global]
    workgroup = SAMBA
    security = user
    passdb backend = tdbsam
    map to guest = Bad User
    ntlm auth = yes
    unix extensions = no

# Share Mahasiswa (Terisolasi per Akun)
[mahasiswa]
    comment = Direktori Pribadi Mahasiswa Lab RMIK
    path = /samba/mahasiswa/%U
    browseable = yes
    read only = no
    writable = yes
    valid users = %U
    invalid users = laboran, dosen, admin
    create mask = 0777
    directory mask = 0777
    force create mode = 0777
    force directory mode = 0777

# Share Operasional (Dosen, Laboran, & Admin)
[operasional]
    comment = Ruang Operasional Lab RMIK
    path = /samba/operasional
    browseable = yes
    read only = no
    writable = yes
    valid users = @laboran, @dosen, laboran, admin, %U
    create mask = 0777
    directory mask = 0777
    force create mode = 0777
    force directory mode = 0777
```

Setelah mengubah konfigurasi Samba, restart service:
```bash
systemctl restart smb nmb
```

---

### Pengaturan Zona Waktu (WIB)
Seluruh pencatatan waktu upload, log aktivitas, dan timestamp file di Maki Samba menggunakan **WIB (Waktu Indonesia Barat / UTC+7)**.
Pastikan timezone Linux server disetel ke `Asia/Jakarta`:
```bash
timedatectl set-timezone Asia/Jakarta
```

---

## 5. Panduan Operasional & Penggunaan Administrator

### Akses Dashboard Admin
1. Buka peramban dan tuju URL: `http://IP_SERVER/login`
2. Masukkan akun Administrator (Default: username `admin`).
3. Pada navigasi atas, terdapat menu utama:
   - **Operasional**: Akses ke ruang file manager root operasional.
   - **Mahasiswa**: Masuk ke menu Direktori Berkas Mahasiswa (pemeriksaan berkas & unduh ZIP).
   - **Akun**: Panel manajemen seluruh akun pengguna (Mahasiswa, Dosen, Laboran, Admin).
   - **Monitoring**: Pantauan PC client yang sedang aktif terhubung ke Samba.
   - **Server**: Informasi status resource sistem, PHP, dan disk storage.

---

### Manajemen Akun Pengguna
Pada menu **Akun** (`/admin`):
- **Statistik Cepat**: Melihat jumlah total akun terdaftar, akun mahasiswa, dosen, laboran, admin, dan kuota terpakai.
- **Pencarian & Filter**: Filter akun berdasarkan Role atau cari berdasarkan NIM/Nama.
- **Reset Password (🔑)**:
  - Klik ikon kunci di kolom Aksi.
  - Masukkan password baru (minimal 6 karakter).
  - Password otomatis tersinkronisasi ke database web dan kredensial Samba Linux (`smbpasswd`).
- **Hapus Akun (🗑️)**:
  - Menghapus akun dari database SQLite serta membersihkan folder Linux user bersangkutan.

---

### Mengubah Semester & Kelas Mahasiswa (Satuan & Massal)

Ada dua metode yang dapat digunakan administrator untuk memperbarui data Semester dan Kelas mahasiswa:

#### Metode A: Edit Langsung dari Web Admin (Satuan / Cepat)
Gunakan metode ini jika ada mahasiswa yang pindah kelas atau koreksi data perorangan:
1. Buka menu **Akun** (`/admin`).
2. Di tabel pengguna, cari mahasiswa yang bersangkutan. Status **`Sem. X`** dan **`Kls. Y`** ditampilkan di bawah kolom Role.
3. Klik tombol **Edit (✏️)** (ikon pensil) di baris mahasiswa tersebut.
4. Pada modal **Edit Data Pengguna**:
   - Pilih **Semester** (Semester 1 s.d. 8).
   - Ketik **Kelas** (contoh: `A`, `B`, `TI.2A`).
5. Klik **Simpan Perubahan**.

#### Metode B: Pembaruan Massal via Import CSV (Pergantian Semester Baru)
Gunakan metode ini ketika seluruh angkatan naik semester atau awal tahun ajaran baru:
1. Siapkan file CSV dengan susunan 6 kolom (lihat bagian [Impor Akun Massal](#impor-akun-massal-berbasis-csv)).
2. Masukkan nilai Semester dan Kelas yang baru.
3. Klik tombol **`📥 Import CSV`** di pojok kanan atas halaman Akun.
4. Pilih file dan klik **Mulai Import Akun**.
5. Sistem akan otomatis mendeteksi NIM yang ada dan memperbarui Semester & Kelasnya.  
   *(Semua berkas tugas mahasiswa yang sudah tersimpan di Samba tetap aman dan tidak akan terhapus).*

---

### Impor Akun Massal Berbasis CSV
Format baris CSV menggunakan pemisah koma (`,`):
```csv
NIM/Username,Nama,Password,Role,Semester,Kelas
202401001,Budi Santoso,Rahasia123,mahasiswa,2,A
202302045,Siti Aminah,Rahasia123,mahasiswa,4,TI.2A
198501012010,Dr. Hendra (Dosen),DosenPass123,dosen
199002022018,Staf Laboran,LaboranPass123,laboran
```

**Ketentuan Kolom:**
1. **NIM/Username**: Wajib (unik).
2. **Nama**: Nama lengkap pengguna.
3. **Password**: Password awal (minimal 6 karakter).
4. **Role**: Pilihan `mahasiswa`, `dosen`, `laboran`, atau `admin`.
5. **Semester**: Angka 1 s.d. 8 (khusus mahasiswa).
6. **Kelas**: Kode kelas (contoh: `A`, `B`, `TI.2A`).

---

### Pemeriksaan Direktori & Berkas Mahasiswa
Dosen, Laboran, dan Administrator dapat memeriksa berkas tugas mahasiswa melalui menu **Direktori Berkas Mahasiswa** (`/dosen/mahasiswa`):
- **Filter Multi-Level**: Filter berdasarkan Semester (1-8), Kelas, Nama Folder Praktikum, dan NIM/Nama Mahasiswa.
- **Mode Read-Only**: Staf dapat memeriksa tanpa risiko berkas mahasiswa terhapus atau tertimpa secara tidak sengaja.
- **Pratinjau Interaktif (Preview)**:
  - Gambar (`.jpg`, `.png`, `.webp`, `.svg`)
  - Dokumen PDF (`.pdf`)
  - File Teks & Source Code (`.txt`, `.sql`, `.csv`, `.json`, `.py`, `.php`, `.html`, `.css`, dll)
  - Audio (`.mp3`, `.wav`) & Video (`.mp4`, `.webm`)

---

### Fitur Select Folder & Unduh ZIP Tugas Praktikum
Fitur ini mempermudah pengumpulan dan pengunduhan tugas praktikum mahasiswa dalam format `.zip`:

1. **Pilih (Select) Folder Mata Kuliah**:
   - Pada dropdown **Pilih Folder Praktikum / MK**, sistem otomatis mendeteksi dan menampilkan seluruh nama folder yang dibuat oleh mahasiswa (misal: `📁 Coba Tugas (2 Mahasiswa)`).
   - Memilih salah satu folder akan langsung menyaring daftar mahasiswa yang memiliki folder tugas tersebut.
2. **Unduh ZIP Sekaligus (Header)**:
   - Klik tombol hijau **`📦 Unduh ZIP "[Nama Folder]" (X Mahasiswa)`** di header halaman.
   - Sistem akan mengompres folder tugas tersebut dari seluruh mahasiswa yang cocok menjadi 1 file ZIP.
3. **Unduh ZIP Mahasiswa Pilihan (Checkbox Multi-Select)**:
   - Centang checkbox di samping nama mahasiswa yang ingin diunduh.
   - Floating Action Bar akan muncul di bawah layar:
     `[✓ X Mahasiswa Dipilih] [ 📦 Unduh ZIP (X) ] [ Batal ]`
4. **Unduh ZIP Cepat per Mahasiswa**:
   - Di kolom *Folder Praktikum / MK*, klik ikon unduh `⬇️` di samping badge folder mahasiswa.
5. **Struktur Berkas di dalam ZIP**:
   ```text
   Tugas_Coba_Tugas_20261028_1540.zip
   ├── 202401001_Budi_Santoso/
   │   └── Coba Tugas/
   │       ├── tugas1.pdf
   │       └── laporan.docx
   └── 202302045_Siti_Aminah/
       └── Coba Tugas/
           └── laporan_praktikum.xlsx
   ```

---

### Monitoring Sesi Samba & Utilisasi Server
Pada menu **Monitoring** (`/admin?tab=monitor`):
- Memantau PC klien yang sedang aktif membuka share folder via jaringan LAN.
- Mengidentifikasi IP komputer lab, nama pengguna, dan folder yang sedang diakses secara *real-time* berbasis perintah `smbstatus`.

---

### Pengaturan Identitas Instansi, Logo, & Tema Warna
Aplikasi Maki Samba dirancang agar mudah disesuaikan (whitelabel) untuk berbagai instansi, sekolah, dan perguruan tinggi.

Administrator dapat mengatur identitas dan tampilan portal secara visual melalui **Menu Web Pengaturan (`/admin?tab=settings`)**:
1. **Identitas & Teks Portal**:
   - **Nama Lengkap Instansi**: Ditampilkan di footer portal, halaman login, modal bantuan jaringan, dan dokumen sistem.
   - **Label Singkat / Badge**: Tampil pada badge di navbar atas (contoh: `Lab Komputer`, `FTI`, `Lab RMIK`).
   - **Nama Aplikasi**: Nama branding portal utama (default: `Maki Samba`).
   - **Deskripsi / Slogan**: Keterangan di bawah logo portal.
2. **Kustomisasi Logo & Favicon**:
   - Administrator dapat mengunggah file logo instansi (PNG/JPEG/SVG/WEBP).
   - Logo yang diunggah otomatis tersimpan di folder `public/uploads/logo/` dan langsung berlaku untuk logo navbar utama serta ikon tab browser (*favicon*).
3. **Pilihan Tema Warna Pastel**:
   - Tersedia pilihan tema warna pastel yang lembut dan nyaman di mata (seperti Pastel Blue, Pastel Sage Green, Pastel Rose/Lavender) untuk menyesuaikan dengan warna identitas institusi Anda.
   Perubahan yang disimpan melalui antarmuka web langsung berlaku seketika di seluruh halaman tanpa perlu me-restart server Apache/PHP.

Administrator juga dapat mengonfigurasi nilai default awal melalui file environment (`.env`):
```env
APP_TITLE="Maki Samba"
APP_INSTITUTION="Nama Universitas / Sekolah / Laboratorium Anda"
APP_INSTITUTION_SHORT="Lab Komputer"
APP_DESCRIPTION="Portal File Manager & Otomatisasi Samba"
```

---

### Panduan Singkat untuk Pengguna Klien (Mahasiswa & Dosen)

#### 1. Akses File via Peramban Web (File Manager)
- Buka alamat portal di peramban web: `http://<IP_SERVER>`
- Masuk menggunakan **NIM / NIDN** dan kata sandi yang telah didaftarkan.
- Pengguna dapat membuat folder, mengunggah dokumen/tugas, melihat preview berkas, dan mengunduh berkas dengan kuota disk yang telah ditentukan.

#### 2. Akses File via Windows Explorer (Network Share LAN)
- Tekan kombinasi tombol `Win + R` pada keyboard di PC Lab.
- Ketikkan alamat share:
  - **Mahasiswa**: `\\<IP_SERVER>\mahasiswa`
  - **Dosen / Laboran**: `\\<IP_SERVER>\operasional`
- Masukkan username (NIM/NIDN/NIP) dan kata sandi yang sama dengan akun portal web.
- Berkas dapat langsung dibuka, diedit, dan disimpan layaknya drive lokal di komputer laboratorium.

---

## 6. Pemeliharaan, Backup, & Pemecahan Masalah (Troubleshooting)

### Backup Rutin Database & Konfigurasi
Database sistem menggunakan SQLite yang sangat portabel. Untuk backup:
```bash
# Backup database SQLite
cp /www/wwwroot/maki-samba/database/database.sqlite /backup/database_$(date +%F).sqlite

# Backup konfigurasi Samba
cp /etc/samba/smb.conf /backup/smb_$(date +%F).conf
```

### Pemecahan Masalah Umum (Troubleshooting)

#### 1. Pesan "Akses Ditolak" saat Akses Samba dari Windows Klien
- Pastikan akun pengguna sudah dibuat di Samba:
  ```bash
  pdbedit -L -v -u [NIM/NIP]
  ```
- Jika belum terdaftar, lakukan reset password via web admin atau jalankan:
  ```bash
  sudo /usr/local/bin/buat_akun_samba.sh [NIM/NIP] [password] [role]
  ```

#### 2. Unduh ZIP Gagal atau Terhenti di Tengah Jalan
- Pastikan ekstensi `php-zip` aktif di PHP 8.4:
  ```bash
  php -m | grep zip
  ```
- Pastikan nilai `memory_limit` di `php.ini` minimal `512M` dan `max_execution_time` minimal `600`.

#### 3. Error Izin Folder (Permission Denied) saat Mahasiswa Unggah File
- Jalankan reset izin direktori Samba:
  ```bash
  chmod -R 777 /samba/mahasiswa
  chmod -R 777 /samba/operasional
  ```

#### 4. Service Samba Tidak Berjalan
- Cek log error Samba:
  ```bash
  testparm
  systemctl status smb nmb
  cat /var/log/samba/log.smbd | tail -n 30
  ```

---

## 7. Lisensi Penggunaan

Perangkat lunak ini dilisensikan di bawah **Lisensi MIT**.  
Anda bebas menggunakan, memodifikasi, mendistribusikan, dan mengadaptasi perangkat lunak ini untuk keperluan institusi maupun komersial dengan tetap menyertakan pemberitahuan hak cipta asli.

Lihat berkas [LICENSE](LICENSE) untuk teks lengkap lisensi.
