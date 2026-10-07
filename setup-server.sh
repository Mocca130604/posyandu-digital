#!/bin/bash
set -e

# =====================================================================
#       SETUP OTOMATIS POSYANDU DIGITAL PADA SERVER LINUX / VPS
# =====================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "=== 1. Memeriksa file .env ==="
if [ ! -f ".env" ]; then
    cp .env.example .env
    echo "✓ .env berhasil dibuat dari .env.example"
else
    echo "✓ .env sudah ada"
fi

echo "=== 2. Memeriksa PHP & Ekstensi ==="
if ! command -v php &> /dev/null; then
    echo "PHP belum terinstall. Silakan install php8.2 atau php8.3 beserta ekstensi sqlite3, mbstring, curl."
    exit 1
fi

echo "=== 3. Memasang Dependensi Composer & NPM jika diperlukan ==="
if [ ! -d "vendor" ]; then
    if command -v composer &> /dev/null; then
        echo "Menjalankan composer install..."
        composer install --no-dev --optimize-autoloader
    else
        echo "Peringatan: Direktori vendor tidak ada dan composer belum terpasang."
    fi
fi

if [ ! -d "node_modules" ]; then
    if command -v npm &> /dev/null; then
        echo "Menjalankan npm install & npm run build..."
        npm install
        npm run build
    fi
fi

echo "=== 4. Menyiapkan APP_KEY ==="
if ! grep -q "APP_KEY=base64:" .env; then
    php artisan key:generate --force
fi

echo "=== 5. Mengembalikan Data Lengkap (Restore Database) ==="
php scripts/restore_database.php

echo "=== 6. Memastikan Skema Tabel & Link Storage ==="
php artisan migrate --force
php artisan storage:link || true
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "=== 7. Mengatur Permissions Storage & Cache ==="
chmod -R 775 storage bootstrap/cache database || true

echo ""
echo "====================================================================="
echo " SETUP SELESAI! Seluruh data Posyandu siap digunakan."
echo "====================================================================="
echo "Jalankan server dengan: php artisan serve --host=0.0.0.0 --port=8000"
echo "Atau jalankan tunnel dengan: bash tunnel.sh"
echo "====================================================================="
