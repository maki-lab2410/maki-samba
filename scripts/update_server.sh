#!/bin/bash
# ==============================================================================
# SCRIPT ALL-IN-ONE UPDATE SERVER MAKI-SAMBA
# Lokasi di Server: /www/wwwroot/maki-samba/scripts/update_server.sh
# Jalankan di Terminal aaPanel dengan perintah:
# bash /www/wwwroot/maki-samba/scripts/update_server.sh
# ==============================================================================

set -e

echo "🚀 [1/5] Memulai pembaruan otomatis Maki Samba..."

# 1. Pastikan dieksekusi dengan hak akses root & timezone WIB
if [ "$EUID" -ne 0 ]; then
  echo "❌ Error: Script ini wajib dijalankan sebagai root (atau via sudo)." >&2
  exit 1
fi

# Pastikan timezone sistem server Linux adalah Asia/Jakarta (WIB / UTC+7)
timedatectl set-timezone Asia/Jakarta 2>/dev/null || ln -sf /usr/share/zoneinfo/Asia/Jakarta /etc/localtime 2>/dev/null || true

# Pastikan utility zip dan unzip tersedia
(command -v zip >/dev/null 2>&1 && command -v unzip >/dev/null 2>&1) || dnf install -y zip unzip 2>/dev/null || yum install -y zip unzip 2>/dev/null || true

PROJECT_DIR="/www/wwwroot/maki-samba"
cd "$PROJECT_DIR" || exit 1

# PROTEKSI PRODUCTION: Pastikan .env server tidak pernah ditimpa oleh deploy
# File .env di server berisi APP_KEY dan konfigurasi production yang unik
if [ ! -f "$PROJECT_DIR/.env" ]; then
  if [ -f "$PROJECT_DIR/.env.example" ]; then
    cp "$PROJECT_DIR/.env.example" "$PROJECT_DIR/.env"
    echo "⚠️  PERHATIAN: .env tidak ditemukan di server, dibuat dari .env.example."
    echo "   -> Silakan generate APP_KEY dengan: php artisan key:generate"
  else
    echo "❌ Error: File .env tidak ditemukan dan .env.example tidak tersedia." >&2
    exit 1
  fi
fi

# 2. Update Script Otomatisasi Samba ke sistem Linux
echo "📦 [2/5] Memperbarui script otomatisasi Samba di /usr/local/bin/..."
cp "$PROJECT_DIR/scripts/buat_akun_samba.sh" /usr/local/bin/buat_akun_samba.sh
chmod 750 /usr/local/bin/buat_akun_samba.sh
chown root:root /usr/local/bin/buat_akun_samba.sh

# Update sudoers jika ada
if [ -f "$PROJECT_DIR/scripts/sudoers_rmik_samba" ]; then
  cp "$PROJECT_DIR/scripts/sudoers_rmik_samba" /etc/sudoers.d/rmik_samba
  chmod 0440 /etc/sudoers.d/rmik_samba
fi

# 3. Registrasi User & Penyiapan Folder Akun Laboran
echo "📂 [3/5] Mempersiapkan akun dan folder operasional lab..."
mkdir -p /samba/operasional
mkdir -p /samba/mahasiswa

# Buat user Linux & Samba 'laboran' jika belum terdaftar
if ! id "laboran" &>/dev/null; then
  echo "   -> Membuat user sistem Linux 'laboran'..."
  useradd -M -s /sbin/nologin -c "Laboran RMIK laboran" laboran
  # Password default - WAJIB diganti melalui menu Profil setelah login pertama
  LABORAN_PASS="${LABORAN_DEFAULT_PASS:-laboran123}"
  echo "laboran:${LABORAN_PASS}" | chpasswd
  (echo "${LABORAN_PASS}"; echo "${LABORAN_PASS}") | smbpasswd -s -a laboran
  smbpasswd -e laboran > /dev/null
  echo "   -> Kredensial Linux & Samba 'laboran' berhasil disiapkan!"
fi

# Buat folder akun laboran di dalam /samba/operasional/laboran
mkdir -p /samba/operasional/laboran
chown -R laboran:laboran /samba/operasional/laboran 2>/dev/null || true
chmod -R 750 /samba/operasional/laboran

# Pindahkan semua akun staf (NIP diawali 0686.*) dari /samba/mahasiswa ke /samba/operasional secara aman tanpa menumpuk folder
for NIP_DIR in /samba/mahasiswa/0686.*; do
  if [ -d "$NIP_DIR" ]; then
    NIP_NAME=$(basename "$NIP_DIR")
    echo "   -> Memindahkan isi folder staf ($NIP_NAME) ke /samba/operasional/$NIP_NAME/..."
    mkdir -p "/samba/operasional/$NIP_NAME"
    cp -a "$NIP_DIR/." "/samba/operasional/$NIP_NAME/" 2>/dev/null || true
    rm -rf "$NIP_DIR"
  fi
done

# Periksa & ratakan folder bersarang (nested) jika ada folder akun di dalam folder akun sendiri
for USER_DIR in /samba/operasional/*; do
  if [ -d "$USER_DIR" ]; then
    UNAME=$(basename "$USER_DIR")
    if [ -d "$USER_DIR/$UNAME" ]; then
      echo "   -> Memulihkan file dari folder bersarang $USER_DIR/$UNAME ke $USER_DIR/..."
      cp -a "$USER_DIR/$UNAME/." "$USER_DIR/" 2>/dev/null || true
      rm -rf "$USER_DIR/$UNAME"
    fi
  fi
done

chmod -R 777 /samba/operasional
chmod -R 777 /samba/mahasiswa
chown -R root:www /samba/operasional /samba/mahasiswa 2>/dev/null || true

# Sinkronkan kuota default mahasiswa ke 1 GB (1073741824 bytes)
if [ -f "$PROJECT_DIR/database/database.sqlite" ]; then
  sqlite3 "$PROJECT_DIR/database/database.sqlite" "UPDATE users SET quota_limit=1073741824 WHERE role='mahasiswa' AND (quota_limit=209715200 OR quota_limit IS NULL OR quota_limit=0);" 2>/dev/null || true
  sqlite3 "$PROJECT_DIR/database/database.sqlite" "UPDATE users SET quota_limit=10737418240 WHERE (role='laboran' OR username LIKE '0686.%') AND (quota_limit=1073741824 OR quota_limit IS NULL OR quota_limit=0);" 2>/dev/null || true
  sqlite3 "$PROJECT_DIR/database/database.sqlite" "ALTER TABLE users ADD COLUMN semester INTEGER DEFAULT 1;" 2>/dev/null || true
  sqlite3 "$PROJECT_DIR/database/database.sqlite" "ALTER TABLE users ADD COLUMN kelas TEXT DEFAULT NULL;" 2>/dev/null || true
fi

# ACL untuk Web Server & Samba (Dua arah: Web & Samba dapat membaca dan menulis)
if command -v setfacl &> /dev/null; then
  setfacl -R -m u:www:rwx -m d:u:www:rwx /samba/operasional 2>/dev/null || true
  setfacl -R -m g:www:rwx -m d:g:www:rwx /samba/operasional 2>/dev/null || true
  setfacl -R -m u:www:rwx -m d:u:www:rwx /samba/mahasiswa 2>/dev/null || true
  setfacl -R -m g:www:rwx -m d:g:www:rwx /samba/mahasiswa 2>/dev/null || true
fi

# SELinux contexts
if command -v getenforce &> /dev/null && [ "$(getenforce)" != "Disabled" ]; then
  chcon -R -t samba_share_t /samba/operasional 2>/dev/null || true
  chcon -R -t samba_share_t /samba/mahasiswa 2>/dev/null || true
fi

# 4. Sinkronisasi Konfigurasi Samba (/etc/samba/smb.conf)
echo "⚙️  [4/5] Memeriksa konfigurasi Samba (/etc/samba/smb.conf)..."

# Sesuaikan share [operasional] agar menggunakan macro %U (per akun laboran & dosen)
if grep -q "\[operasional\]" /etc/samba/smb.conf; then
  # Perbarui path [operasional] agar menjadi /samba/operasional/%U jika belum
  sed -i 's|path = /samba/operasional$|path = /samba/operasional/%U|g' /etc/samba/smb.conf 2>/dev/null || true
  sed -i 's|valid users = laboran, admin|valid users = %U|g' /etc/samba/smb.conf 2>/dev/null || true
  # Pastikan permission 0777 dan group www agar sinkron 100% dengan Web File Manager
  sed -i 's|create mask = 0770|create mask = 0777|g' /etc/samba/smb.conf 2>/dev/null || true
  sed -i 's|directory mask = 0770|directory mask = 0777|g' /etc/samba/smb.conf 2>/dev/null || true
  sed -i 's|force create mode = 0770|force create mode = 0777|g' /etc/samba/smb.conf 2>/dev/null || true
  sed -i 's|force directory mode = 0770|force directory mode = 0777|g' /etc/samba/smb.conf 2>/dev/null || true
  if ! grep -q "force group = www" /etc/samba/smb.conf; then
    sed -i '/\[operasional\]/a \    force group = www' /etc/samba/smb.conf 2>/dev/null || true
    sed -i '/\[mahasiswa\]/a \    force group = www' /etc/samba/smb.conf 2>/dev/null || true
  fi
else
  echo "   -> Menambahkan blok [operasional] dinamis ke /etc/samba/smb.conf..."
  cat << 'EOF' >> /etc/samba/smb.conf

# Share Ruang Operasional Lab per Akun Laboran & Dosen
[operasional]
    comment = Folder Akun Operasional Laboratorium RMIK
    path = /samba/operasional/%U
    browseable = yes
    read only = no
    writable = yes
    valid users = %U
    force user = %U
    force group = www
    create mask = 0777
    directory mask = 0777
    force create mode = 0777
    force directory mode = 0777
EOF
fi

# Tambahkan share master operasional khusus Administrator jika belum ada
if ! grep -q "\[admin_operasional\]" /etc/samba/smb.conf; then
  cat << 'EOF' >> /etc/samba/smb.conf

# Share Master Operasional (Khusus Administrator)
[admin_operasional]
    comment = Master Direktori Operasional Lab RMIK (Administrator)
    path = /samba/operasional
    browseable = yes
    read only = no
    writable = yes
    valid users = admin
    force group = www
    create mask = 0777
    directory mask = 0777
    force create mode = 0777
    force directory mode = 0777
EOF
fi

# Pastikan [mahasiswa] melarang laboran, dosen & admin
if grep -q "\[mahasiswa\]" /etc/samba/smb.conf; then
  if grep -q "invalid users.*laboran" /etc/samba/smb.conf; then
    sed -i 's|invalid users = laboran, admin|invalid users = laboran, dosen, admin|g' /etc/samba/smb.conf 2>/dev/null || true
  else
    sed -i '/\[mahasiswa\]/a \    invalid users = laboran, dosen, admin' /etc/samba/smb.conf 2>/dev/null || true
  fi
  sed -i 's|create mask = 0770|create mask = 0777|g' /etc/samba/smb.conf 2>/dev/null || true
  sed -i 's|directory mask = 0770|directory mask = 0777|g' /etc/samba/smb.conf 2>/dev/null || true
  sed -i 's|force create mode = 0770|force create mode = 0777|g' /etc/samba/smb.conf 2>/dev/null || true
  sed -i 's|force directory mode = 0770|force directory mode = 0777|g' /etc/samba/smb.conf 2>/dev/null || true
fi

# Restart daemon Samba
systemctl restart smb nmb 2>/dev/null || true

# Optimasi Limit Upload Nginx & PHP di aaPanel (Mendukung upload video hingga 2GB)
echo "⚡ Mengoptimasi batas ukuran upload PHP & Nginx (hingga 2 GB)..."
for PHP_INI in /www/server/php/*/etc/php.ini /etc/php.ini /etc/php/*/fpm/php.ini; do
  if [ -f "$PHP_INI" ]; then
    sed -i 's/^upload_max_filesize.*/upload_max_filesize = 2048M/' "$PHP_INI" 2>/dev/null || true
    sed -i 's/^post_max_size.*/post_max_size = 2048M/' "$PHP_INI" 2>/dev/null || true
    sed -i 's/^max_execution_time.*/max_execution_time = 600/' "$PHP_INI" 2>/dev/null || true
    sed -i 's/^max_input_time.*/max_input_time = 600/' "$PHP_INI" 2>/dev/null || true
    sed -i 's/^memory_limit.*/memory_limit = 512M/' "$PHP_INI" 2>/dev/null || true
  fi
done

# Restart layanan PHP-FPM jika ada
for PHP_SVC in /etc/init.d/php-fpm* /www/server/php/*/etc/init.d/php-fpm; do
  if [ -x "$PHP_SVC" ]; then
    "$PHP_SVC" reload >/dev/null 2>&1 || true
  fi
done

# Pastikan Nginx mengizinkan upload besar
for NGINX_CONF in /www/server/nginx/conf/nginx.conf /etc/nginx/nginx.conf; do
  if [ -f "$NGINX_CONF" ]; then
    if grep -q "client_max_body_size" "$NGINX_CONF"; then
      sed -i 's/client_max_body_size.*/client_max_body_size 2048M;/' "$NGINX_CONF" 2>/dev/null || true
    else
      sed -i '/http {/a \    client_max_body_size 2048M;' "$NGINX_CONF" 2>/dev/null || true
    fi
  fi
done
systemctl reload nginx 2>/dev/null || /etc/init.d/nginx reload 2>/dev/null || true

# 5. Atur Hak Akses Web Server & Clear Cache Laravel
echo "🧹 [5/5] Membersihkan cache aplikasi & mengatur izin file..."
mkdir -p "$PROJECT_DIR/public/uploads/logo" 2>/dev/null || true
chown -R www:www "$PROJECT_DIR" 2>/dev/null || chown -R apache:apache "$PROJECT_DIR" 2>/dev/null || true
chmod -R 775 "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache" "$PROJECT_DIR/public/uploads" 2>/dev/null || true
chmod 666 "$PROJECT_DIR/database/database.sqlite" 2>/dev/null || true

# Jalankan migrasi database otomatis jika ada tabel baru
php artisan migrate --force 2>/dev/null || true

php artisan optimize:clear 2>/dev/null || true

echo "✅ SELESAI! Seluruh sistem Maki Samba dan Samba Server telah berhasil diperbarui."

# 6. Jalankan Recovery Samba (buat ulang semua akun & folder dari database)
echo ""
echo "🔄 [RECOVERY] Memulai pemulihan akun & folder Samba dari database..."
if [ -f "$PROJECT_DIR/scripts/recovery_samba.sh" ]; then
  chmod +x "$PROJECT_DIR/scripts/recovery_samba.sh"
  bash "$PROJECT_DIR/scripts/recovery_samba.sh"
else
  echo "⚠️  Script recovery_samba.sh tidak ditemukan, dilewati."
fi
