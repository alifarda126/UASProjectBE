#!/bin/bash
# =============================================================================
# clevercloud/deploy.sh
# Hook Post-Deployment untuk Clever Cloud (Laravel)
# File ini otomatis dieksekusi Clever Cloud setelah setiap git push.
#
# Cara aktifkan di Clever Cloud:
#   Environment Variable: CC_POST_BUILD_HOOK = bash clevercloud/deploy.sh
# =============================================================================

set -e

echo "🚀 [Clever Cloud Hook] Starting post-deployment tasks..."

# Install dependencies production
composer install --no-dev --optimize-autoloader --no-interaction

# Note: APP_KEY harus di-set melalui menu Environment Variables di Clever Cloud.
# Jangan gunakan php artisan key:generate di sini karena file .env tidak ada.

# Jalankan migrasi
echo "🗄️ Running migrations..."
php artisan migrate --force --no-interaction

# Jalankan seeder (hanya jika tabel users kosong)
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null || echo "0")
if [ "$USER_COUNT" = "0" ]; then
    echo "🌱 Running seeders (database kosong)..."
    php artisan db:seed --force --no-interaction
else
    echo "⏭️  Seeders dilewati (database sudah berisi $USER_COUNT user)."
fi

# Optimasi
echo "🔄 Caching config & routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Storage link
php artisan storage:link --force 2>/dev/null || true

echo "✅ [Clever Cloud Hook] Deployment tasks selesai!"
