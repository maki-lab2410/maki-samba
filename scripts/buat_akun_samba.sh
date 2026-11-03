#!/bin/bash
# ==============================================================================
# SCRIPT OTOMATISASI PEMBUATAN AKUN LINUX & SAMBA - LAB RMIK
# File Path Target di Server: /usr/local/bin/buat_akun_samba.sh
# Sistem Operasi: AlmaLinux 10 / RHEL 10
# ==============================================================================

set -e # Hentikan eksekusi jika terjadi error tak terduga

# Pastikan script dieksekusi dengan hak akses root (via sudo)
if [ "$EUID" -ne 0 ]; then
  echo "Error: Script ini wajib dijalankan dengan hak akses root (sudo)." >&2
  exit 1
fi

NIM="$1"
ROLE="${3:-mahasiswa}"
WEB_USER="www" # User web server aaPanel (atau 'apache' pada instalasi standar)

# 1. Validasi input argumen
if [ -z "$NIM" ]; then
  echo "Usage: $0 <NIM> [PASSWORD|-] [ROLE]" >&2
  exit 1
fi

# Jika argumen 2 adalah '-' atau kosong, baca password secara aman dari stdin
if [ "$2" = "-" ] || [ -z "$2" ]; then
  read -r PASSWORD
else
  PASSWORD="$2"
fi

if [ -z "$PASSWORD" ]; then
  echo "Error: Password tidak boleh kosong." >&2
  exit 1
fi

# Validasi keamanan: hanya izinkan karakter alfanumerik, titik, strip, underscore
if ! [[ "$NIM" =~ ^[a-zA-Z0-9._-]+$ ]]; then
  echo "Error: Format NIM tidak diizinkan. Hanya karakter alfanumerik, titik, strip, dan underscore." >&2
  exit 2
fi

# Tentukan direktori target berdasarkan NIM/NIP & Role
# Dosen     -> /samba/operasional/{NIDN/NIP} (role dosen)
# Laboran   -> /samba/operasional/{NIP} (role laboran)
# Admin     -> /samba/operasional
# Mahasiswa -> /samba/mahasiswa/{NIM}
if [ "$ROLE" = "dosen" ] || [ "$NIM" = "dosen" ]; then
  TARGET_ROLE="dosen"
  BASE_DIR="/samba/operasional"
  TARGET_DIR="${BASE_DIR}/${NIM}"
  USER_COMMENT="Dosen RMIK $NIM"
elif [ "$ROLE" = "admin" ] || [ "$NIM" = "admin" ]; then
  TARGET_ROLE="admin"
  BASE_DIR="/samba/operasional"
  TARGET_DIR="${BASE_DIR}"
  USER_COMMENT="Administrator Lab RMIK"
elif [ "$ROLE" = "laboran" ] || [ "$NIM" = "laboran" ] || [[ "$NIM" =~ ^0686\. ]]; then
  TARGET_ROLE="laboran"
  BASE_DIR="/samba/operasional"
  TARGET_DIR="${BASE_DIR}/${NIM}"
  USER_COMMENT="Laboran RMIK $NIM"
else
  TARGET_ROLE="mahasiswa"
  BASE_DIR="/samba/mahasiswa"
  TARGET_DIR="${BASE_DIR}/${NIM}"
  USER_COMMENT="Mahasiswa RMIK $NIM"
fi

# Pastikan direktori induk sudah ada
if [ ! -d "$BASE_DIR" ]; then
  mkdir -p "$BASE_DIR"
  chmod 755 "$BASE_DIR"
fi

# 2. Cek dan buat User Sistem Linux
if id "$NIM" &>/dev/null; then
  EXISTING_UID=$(id -u "$NIM" 2>/dev/null || echo -1)
  if [ "$EXISTING_UID" -ge 0 ] && [ "$EXISTING_UID" -lt 1000 ]; then
    echo "Error: Akun '$NIM' (UID: $EXISTING_UID) adalah akun sistem terproteksi (UID < 1000). Ditolak demi keamanan." >&2
    exit 1
  fi
  echo "Info: User sistem Linux '$NIM' (UID: $EXISTING_UID) sudah ada. Memperbarui password & deskripsi..."
  usermod -c "$USER_COMMENT" "$NIM" 2>/dev/null || true
else
  useradd -M -s /sbin/nologin -c "$USER_COMMENT" "$NIM"
  echo "Sukses: User sistem Linux '$NIM' berhasil dibuat."
fi

# Update password user Linux
echo "${NIM}:${PASSWORD}" | chpasswd

# 3. Daftarkan / Update User di Database Samba (smbpasswd)
(echo "$PASSWORD"; echo "$PASSWORD") | smbpasswd -s -a "$NIM"
smbpasswd -e "$NIM" > /dev/null
echo "Sukses: Kredensial Samba untuk '$NIM' berhasil dikonfigurasi."

# 4. Siapkan Direktori Fisik Target
if [ ! -d "$TARGET_DIR" ]; then
  mkdir -p "$TARGET_DIR"
fi

# Set izin kepemilikan dan permission direktori yang aman (750)
chown -R "$NIM":"$NIM" "$TARGET_DIR" 2>/dev/null || true
chmod -R 750 "$TARGET_DIR"

# 5. INTEGRASI DENGAN WEB FILE MANAGER (aaPanel / Apache via POSIX ACL)
if command -v setfacl &> /dev/null; then
  setfacl -R -m u:"$WEB_USER":rwx "$TARGET_DIR" 2>/dev/null || true
  setfacl -R -d -m u:"$WEB_USER":rwx "$TARGET_DIR" 2>/dev/null || true
  
  if id "apache" &>/dev/null; then
    setfacl -R -m u:apache:rwx "$TARGET_DIR" 2>/dev/null || true
    setfacl -R -d -m u:apache:rwx "$TARGET_DIR" 2>/dev/null || true
  fi

  if [ "$TARGET_ROLE" = "laboran" ] || [ "$TARGET_ROLE" = "dosen" ]; then
    setfacl -R -m u:"$NIM":rwx "$TARGET_DIR" 2>/dev/null || true
    setfacl -R -d -m u:"$NIM":rwx "$TARGET_DIR" 2>/dev/null || true
  fi
else
  usermod -a -G "$NIM" "$WEB_USER" 2>/dev/null || true
fi

# 6. Konfigurasi Keamanan SELinux (AlmaLinux 10)
if command -v getenforce &> /dev/null && [ "$(getenforce)" != "Disabled" ]; then
  chcon -R -t samba_share_t "$TARGET_DIR" 2>/dev/null || true
fi

echo "Sukses: Akun '$NIM' ($TARGET_ROLE) dan folder $TARGET_DIR berhasil disiapkan."
exit 0
