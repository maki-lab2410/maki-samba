#!/bin/bash
# ==============================================================================
# SCRIPT RECOVERY SAMBA - MAKI SAMBA
# Membuat ulang semua akun Linux + Samba + folder dari database SQLite.
# ==============================================================================

# Jangan gunakan set -e agar loop tidak berhenti di tengah jalan
echo "=================================================================="
echo "MAKI SAMBA - RECOVERY MODE"
echo "   Memulai pemulihan akun & folder Samba dari database..."
echo "=================================================================="

# Pastikan root
if [ "$EUID" -ne 0 ]; then
  echo "Error: Harus dijalankan sebagai root." >&2
  exit 1
fi

PROJECT_DIR="/www/wwwroot/maki-samba"
DB="$PROJECT_DIR/database/database.sqlite"
BUAT_AKUN="$PROJECT_DIR/scripts/buat_akun_samba.sh"
TMPFILE="/tmp/maki_recovery_users.txt"

# Cek database ada
if [ ! -f "$DB" ]; then
  echo "ERROR: Database tidak ditemukan: $DB"
  exit 1
fi

# Pastikan sqlite3 tersedia
if ! command -v sqlite3 >/dev/null 2>&1; then
  echo "Menginstall sqlite3..."
  dnf install -y sqlite 2>/dev/null || yum install -y sqlite 2>/dev/null || apt-get install -y sqlite3 2>/dev/null || true
fi

# Cek ulang sqlite3
if ! command -v sqlite3 >/dev/null 2>&1; then
  echo "ERROR: sqlite3 tidak tersedia. Install manual: dnf install -y sqlite"
  exit 1
fi

echo "[DEBUG] sqlite3 ditemukan: $(which sqlite3)"
echo "[DEBUG] Database size: $(ls -lh $DB | awk '{print $5}')"
echo "[DEBUG] Tabel di database:"
sqlite3 "$DB" ".tables" 2>&1 || echo "Gagal membaca tabel"

echo ""
echo "[DEBUG] Jumlah user di database:"
sqlite3 "$DB" "SELECT role, COUNT(*) FROM users GROUP BY role;" 2>&1 || echo "Gagal query"

# Pastikan zip & unzip tersedia
command -v zip >/dev/null 2>&1 || dnf install -y zip unzip 2>/dev/null || yum install -y zip unzip 2>/dev/null || true

# Update script buat_akun ke sistem
echo ""
echo "[1/6] Menyalin script buat_akun_samba.sh..."
if [ -f "$BUAT_AKUN" ]; then
  cp "$BUAT_AKUN" /usr/local/bin/buat_akun_samba.sh
  chmod 750 /usr/local/bin/buat_akun_samba.sh
  chown root:root /usr/local/bin/buat_akun_samba.sh
  echo "   OK: buat_akun_samba.sh disalin ke /usr/local/bin/"
else
  echo "   WARN: buat_akun_samba.sh tidak ditemukan, dilewati."
fi

# Buat folder induk
echo "[2/6] Membuat ulang folder induk Samba..."
mkdir -p /samba/mahasiswa
mkdir -p /samba/operasional
chmod 755 /samba/mahasiswa
chmod 755 /samba/operasional
timedatectl set-timezone Asia/Jakarta 2>/dev/null || true
echo "   OK: /samba/mahasiswa dan /samba/operasional siap."

# Ekspor user ke file temporer
echo "[3/6] Membaca daftar user dari database SQLite..."
sqlite3 "$DB" "SELECT username, role FROM users WHERE role IN ('mahasiswa','dosen','laboran') ORDER BY role, username;" > "$TMPFILE" 2>&1

TOTAL_LINES=$(wc -l < "$TMPFILE" | tr -d ' ')
echo "   Ditemukan $TOTAL_LINES baris user."

if [ "$TOTAL_LINES" -eq 0 ]; then
  echo "   WARN: Tidak ada user mahasiswa/dosen/laboran di database."
  cat "$TMPFILE"
fi

# Buat ulang akun satu per satu
echo "[4/6] Membuat ulang akun Linux + Samba..."
echo "--------------------------------------------------------------"

COUNT=0
FAIL=0

while IFS='|' read -r USERNAME ROLE; do
  # Skip baris kosong
  [ -z "$USERNAME" ] && continue

  # Password sementara = username
  SAMBA_PASS="$USERNAME"

  # Tentukan direktori
  if [ "$ROLE" = "mahasiswa" ]; then
    TARGET_DIR="/samba/mahasiswa/${USERNAME}"
  else
    TARGET_DIR="/samba/operasional/${USERNAME}"
  fi

  echo -n "   ${ROLE}/${USERNAME} ... "

  # Buat / update user Linux
  if id "$USERNAME" >/dev/null 2>&1; then
    usermod -c "${ROLE} ${USERNAME}" "$USERNAME" 2>/dev/null || true
  else
    useradd -M -s /sbin/nologin -c "${ROLE} ${USERNAME}" "$USERNAME" 2>/dev/null
    if [ $? -ne 0 ]; then
      echo "GAGAL buat user Linux"
      FAIL=$((FAIL + 1))
      continue
    fi
  fi

  # Set password Linux
  echo "${USERNAME}:${SAMBA_PASS}" | chpasswd 2>/dev/null || true

  # Daftarkan ke Samba
  (printf "%s\n%s\n" "$SAMBA_PASS" "$SAMBA_PASS") | smbpasswd -s -a "$USERNAME" 2>/dev/null || true
  smbpasswd -e "$USERNAME" >/dev/null 2>&1 || true

  # Buat folder
  mkdir -p "$TARGET_DIR"
  chown -R "${USERNAME}:${USERNAME}" "$TARGET_DIR" 2>/dev/null || true
  chmod -R 750 "$TARGET_DIR"

  # ACL
  if command -v setfacl >/dev/null 2>&1; then
    setfacl -R -m u:www:rwx "$TARGET_DIR" 2>/dev/null || true
    setfacl -R -d -m u:www:rwx "$TARGET_DIR" 2>/dev/null || true
    if id "apache" >/dev/null 2>&1; then
      setfacl -R -m u:apache:rwx "$TARGET_DIR" 2>/dev/null || true
    fi
  fi

  # SELinux
  if command -v getenforce >/dev/null 2>&1 && [ "$(getenforce)" != "Disabled" ]; then
    chcon -R -t samba_share_t "$TARGET_DIR" 2>/dev/null || true
  fi

  echo "OK -> $TARGET_DIR"
  COUNT=$((COUNT + 1))

done < "$TMPFILE"

rm -f "$TMPFILE"

echo "--------------------------------------------------------------"
echo "   Selesai: $COUNT berhasil, $FAIL gagal."
echo ""

# Akun laboran default sistem
echo "[5/6] Memastikan akun sistem laboran..."
LABORAN_PASS="${LABORAN_DEFAULT_PASS:-laboran123}"
if ! id "laboran" >/dev/null 2>&1; then
  useradd -M -s /sbin/nologin -c "Laboran RMIK" laboran
  echo "laboran:${LABORAN_PASS}" | chpasswd
  (printf "%s\n%s\n" "${LABORAN_PASS}" "${LABORAN_PASS}") | smbpasswd -s -a laboran 2>/dev/null || true
  smbpasswd -e laboran >/dev/null 2>&1 || true
  echo "   OK: akun laboran dibuat."
else
  echo "   OK: akun laboran sudah ada."
fi
mkdir -p /samba/operasional/laboran
chown -R laboran:laboran /samba/operasional/laboran 2>/dev/null || true
chmod -R 750 /samba/operasional/laboran

# Verifikasi & perbaiki smb.conf
echo "[6/6] Memeriksa konfigurasi Samba..."

if ! grep -q "\[mahasiswa\]" /etc/samba/smb.conf 2>/dev/null; then
  echo "   -> Menambahkan blok [mahasiswa]..."
  cat >> /etc/samba/smb.conf << 'SAMBACFG'

[mahasiswa]
    comment = Folder Mahasiswa
    path = /samba/mahasiswa/%U
    browseable = no
    read only = no
    writable = yes
    valid users = %U
    create mask = 0770
    directory mask = 0770
    force create mode = 0770
    force directory mode = 0770
    invalid users = laboran, dosen, admin
SAMBACFG
fi

if ! grep -q "\[operasional\]" /etc/samba/smb.conf 2>/dev/null; then
  echo "   -> Menambahkan blok [operasional]..."
  cat >> /etc/samba/smb.conf << 'SAMBACFG'

[operasional]
    comment = Folder Operasional Lab
    path = /samba/operasional/%U
    browseable = yes
    read only = no
    writable = yes
    valid users = %U
    create mask = 0770
    directory mask = 0770
    force create mode = 0770
    force directory mode = 0770
SAMBACFG
fi

# SELinux seluruh /samba
if command -v getenforce >/dev/null 2>&1 && [ "$(getenforce)" != "Disabled" ]; then
  chcon -R -t samba_share_t /samba 2>/dev/null || true
fi

# Firewall
if command -v firewall-cmd >/dev/null 2>&1; then
  firewall-cmd --permanent --add-service=samba 2>/dev/null || true
  firewall-cmd --reload 2>/dev/null || true
fi

# Restart Samba
systemctl enable smb nmb 2>/dev/null || true
systemctl restart smb nmb 2>/dev/null || true
sleep 1

echo ""
echo "=================================================================="
echo "RECOVERY SELESAI!"
echo ""
echo "   $COUNT akun berhasil dipulihkan dari database."
if [ "$FAIL" -gt 0 ]; then
echo "   WARN: $FAIL akun gagal dibuat (lihat log di atas)."
fi
echo ""
echo "   PENTING - Password Samba di-reset:"
echo "   Setiap user: password Samba = username mereka masing-masing"
echo "   Contoh: NIM 2023001 -> password Samba: 2023001"
echo "   Login web portal TIDAK berubah (pakai password lama)."
echo ""
echo "   Folder mahasiswa : $(ls /samba/mahasiswa/ 2>/dev/null | wc -l | tr -d ' ') folder"
echo "   Folder operasional: $(ls /samba/operasional/ 2>/dev/null | wc -l | tr -d ' ') folder"
echo ""
echo "   Status Samba:"
systemctl status smb --no-pager | head -4
echo "=================================================================="
