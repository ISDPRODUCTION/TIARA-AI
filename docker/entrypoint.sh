#!/bin/bash

echo ">>> Starting TIARA AI Deployment..."

# Set the port for Nginx from Railway environment variable or default to 8080
PORT=${PORT:-8080}
echo ">>> Using PORT: ${PORT}"

# Clean up default Nginx config to avoid conflicts
rm -f /etc/nginx/http.d/default.conf

# Recreate our config from the one we copied
cp /var/www/html/docker/nginx.conf /etc/nginx/http.d/tiara.conf

# Replace port in the config
sed -i "s/listen 8080;/listen ${PORT};/g" /etc/nginx/http.d/tiara.conf
sed -i "s/listen \[::\]:8080;/listen \[::\]:${PORT};/g" /etc/nginx/http.d/tiara.conf

# Ensure permissions
echo ">>> Setting permissions..."
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html

# Ensure nginx user is in the correct group
addgroup -S www-data 2>/dev/null || true
adduser nginx www-data 2>/dev/null || true

# Force PHP-FPM to listen on 127.0.0.1:9000
echo ">>> Configuring PHP-FPM..."
find /usr/local/etc/php-fpm.d -name "*.conf" -exec sed -i 's|listen = .*|listen = 127.0.0.1:9000|g' {} +

# Create SQLite database if it doesn't exist
if [ ! -f /var/www/html/database/database.sqlite ]; then
    echo ">>> Creating SQLite database..."
    touch /var/www/html/database/database.sqlite
    chmod 666 /var/www/html/database/database.sqlite
fi

# Run migrations
echo ">>> Running migrations..."
php artisan migrate --force

# Optimize Laravel
echo ">>> Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start processes
echo ">>> Starting PHP-FPM..."
php-fpm -D

echo ">>> Starting Nginx..."
nginx -g "daemon off;"
