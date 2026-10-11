#!/usr/bin/env bash
#
# Provisioning aplikasi absensi di server domanesia (shared hosting, SSH).
#
# Cara pakai:
#   1) Upload bundel aplikasi ke ~/absensi (lihat docs deployment).
#   2) Pastikan ~/absensi/.env sudah diisi (APP_URL + kredensial DB).
#   3) Jalankan:  bash ~/absensi/deploy/provision.sh
#
set -euo pipefail

APP_DIR="$HOME/absensi"
cd "$APP_DIR"
echo "== Direktori aplikasi: $APP_DIR"

# 0. Perbaiki izin hasil ekstrak: ZIP buatan Windows sering menyimpan direktori
#    tanpa bit execute (drw-r--r--) sehingga PHP menolak require vendor
#    ("Permission denied"). Ini wajib dijalankan sekali setelah extract.
echo "== Memperbaiki izin file (direktori 755, file 644)..."
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 775 storage bootstrap/cache

# 1. Validasi .env
if [ ! -f .env ]; then
    echo "!! File .env tidak ditemukan. Salin dari template lalu isi:"
    echo "   cp .env.production.example .env"
    echo "   nano .env"
    exit 1
fi
if grep -q "APP_KEY=$" .env; then
    echo "== Membuat APP_KEY..."
    php artisan key:generate --force
fi

# 2. Composer (vendor)
if [ ! -d vendor ]; then
    echo "== composer install --no-dev..."
    if command -v composer >/dev/null 2>&1; then
        composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
    else
        echo "!! composer tidak ada di PATH. Coba lokasi cPanel:"
        for c in "$HOME/composer.phar" /opt/cpanel/composer/composer /usr/local/bin/composer; do
            [ -x "$c" ] && { php "$c" install --no-dev --no-interaction --prefer-dist --optimize-autoloader; break; }
        done
    fi
fi

# 3. Migrasi database (baris ini dipakai sekali; aman diulang)
echo "== php artisan migrate --force..."
php artisan migrate --force

# 4. Tautan storage (gambar/logo milik publik). Aplikasi menyajikan berkas
#    unggahan lewat route /media, jadi kegagalan symlink di sini bukan lagi
#    masalah fatal — tetapi tetap dilaporkan supaya ketahuan.
echo "== php artisan storage:link..."
if php artisan storage:link >/dev/null 2>&1; then
    echo "   OK: public/storage tersedia."
else
    echo "   !! GAGAL membuat public/storage (hosting mungkin melarang symlink)."
    echo "      Tidak masalah: foto & bukti transaksi tetap disajikan lewat /media."
fi

# 5. Cache konfigurasi + view (route:cache dimatikan kalau ada closure)
echo "== Cache config & view..."
php artisan config:cache
php artisan view:cache
php artisan route:cache >/dev/null 2>&1 || echo "   (route:cache dilewati — ada penutup dinamis)"

# 6. Izin direktori yang ditulis PHP
echo "== chmod storage & bootstrap/cache..."
chmod -R 775 storage bootstrap/cache

echo "== Selesai. Uji: $APP_URL"
echo "   Jangan lupa atur izin SSL/HTTPS dan arahkan subdomain ke:"
echo "   $APP_DIR/public"