#!/bin/bash
# ==============================================================================
# SCRIPT DEPLOY OTOMATIS DARI MAC KE SERVER ALMALINUX 10 (aaPanel)
# Jalankan di Terminal Mac:
# ./deploy.sh
# ==============================================================================

set -e # Hentikan eksekusi jika terjadi error

SERVER_IP="${SERVER_IP:-YOUR_SERVER_IP}"
SERVER_USER="${SERVER_USER:-root}"
REMOTE_DIR="${REMOTE_DIR:-/www/wwwroot/maki-samba}"

echo "=================================================================="
echo "🚀 MEMULAI DEPLOY OTOMATIS KE SERVER ($SERVER_USER@$SERVER_IP)"
echo "=================================================================="

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# 1. Pastikan folder tujuan ada di server & cek apakah rsync tersedia
echo "🔍 [1/3] Menyiapkan direktori tujuan di server..."
ssh -o StrictHostKeyChecking=no "${SERVER_USER}@${SERVER_IP}" "mkdir -p ${REMOTE_DIR} && (command -v rsync >/dev/null 2>&1 || dnf install -y rsync >/dev/null 2>&1 || yum install -y rsync >/dev/null 2>&1 || true)"

# 2. Transfer File (Gunakan rsync jika tersedia, atau fallback universal dengan tar)
echo "📦 [2/3] Mengirim file dan update sistem..."
if ssh -o StrictHostKeyChecking=no "${SERVER_USER}@${SERVER_IP}" "command -v rsync >/dev/null 2>&1"; then
  echo "   -> Menggunakan Rsync..."
  rsync -avz \
    --exclude="vendor" \
    --exclude=".git" \
    --exclude=".env" \
    --exclude="database/*.sqlite" \
    --exclude="storage/framework/cache/*" \
    --exclude="storage/framework/sessions/*" \
    --exclude="storage/framework/views/*" \
    --exclude="storage/logs/*" \
    "$SCRIPT_DIR/" "${SERVER_USER}@${SERVER_IP}:${REMOTE_DIR}/"
else
  echo "   -> Menggunakan Native TAR Stream..."
  tar --exclude="./vendor" \
      --exclude="./.git" \
      --exclude="./.env" \
      --exclude="./database/*.sqlite" \
      --exclude="./database/database.sqlite*" \
      --exclude="./storage/framework/cache/*" \
      --exclude="./storage/framework/sessions/*" \
      --exclude="./storage/framework/views/*" \
      --exclude="./storage/logs/*" \
      -czf - . | ssh -o StrictHostKeyChecking=no "${SERVER_USER}@${SERVER_IP}" "tar -xzf - -C ${REMOTE_DIR}"
fi

# 3. Eksekusi Script Pembaruan Otomatis di Server
echo "⚙️  [3/3] Menjalankan pembaruan sistem & Samba di server..."
ssh -o StrictHostKeyChecking=no "${SERVER_USER}@${SERVER_IP}" "chmod +x ${REMOTE_DIR}/scripts/*.sh && bash ${REMOTE_DIR}/scripts/update_server.sh"

echo "=================================================================="
echo "🎉 DEPLOYMENT SELESAI DAN BERHASIL 100%!"
echo "   Portal Web: http://${SERVER_IP}"
echo "   Samba Operasional: \\\\${SERVER_IP}\\operasional"
echo "   Samba Mahasiswa:   \\\\${SERVER_IP}\\mahasiswa"
echo "=================================================================="
