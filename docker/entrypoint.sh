#!/bin/sh
set -e

echo "=== Sunucu Monitor Başlatılıyor ==="

# SQLite database kontrolü
if [ ! -f /var/www/html/database/database.sqlite ]; then
    echo "SQLite veritabanı oluşturuluyor..."
    touch /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
fi

# Migrations
echo "Migrations çalıştırılıyor..."
php artisan migrate --force

# Cache
echo "Konfigürasyon cache'leniyor..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permissions
echo "İzinler ayarlanıyor..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/database

echo "=== Uygulama hazır ==="

# Start supervisor
exec "$@"
