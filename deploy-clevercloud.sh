#!/bin/bash
# =============================================================================
# deploy-clevercloud.sh
# Script deployment MoneFlo Backend ke Clever Cloud
# Jalankan sekali setelah push pertama, atau saat perlu reset database.
# =============================================================================

set -e  # Hentikan script jika ada error

echo "=============================================="
echo "  🚀 MoneFlo — Clever Cloud Deployment Script"
echo "=============================================="

# ── 1. Install Composer Dependencies ──────────────────────────────────────────
echo ""
echo "📦 [1/7] Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# ── 2. Copy .env jika belum ada ──────────────────────────────────────────────
echo ""
echo "⚙️  [2/7] Checking .env file..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "     .env dibuat dari .env.example"
    echo "     ⚠️  Pastikan variabel DB_*, APP_KEY, dll sudah dikonfigurasi!"
fi

# ── 3. Generate APP_KEY jika belum ada ───────────────────────────────────────
echo ""
echo "🔑 [3/7] Generating application key (jika belum ada)..."
php artisan key:generate --no-interaction --force

# ── 4. Jalankan Migrations ───────────────────────────────────────────────────
echo ""
echo "🗄️  [4/7] Running database migrations..."
php artisan migrate --force --no-interaction

# ── 5. Jalankan Seeders ──────────────────────────────────────────────────────
echo ""
echo "🌱 [5/7] Running database seeders..."
php artisan db:seed --force --no-interaction

# ── 6. Clear & Cache Config/Route/View ───────────────────────────────────────
echo ""
echo "🔄 [6/7] Optimizing application..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── 7. Set Permission Storage (jika diperlukan) ───────────────────────────────
echo ""
echo "📂 [7/7] Setting storage permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
php artisan storage:link --force 2>/dev/null || true

echo ""
echo "=============================================="
echo "  ✅ Deployment selesai!"
echo "=============================================="
echo ""
echo "  Akun Admin:"
echo "  Email    : moneflosupp@gmail.com"
echo "  Password : (password asli dari backup)"
echo ""
