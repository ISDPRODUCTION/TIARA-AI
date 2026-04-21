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

# Force PHP-FPM to listen on unix socket
echo ">>> Configuring PHP-FPM to use unix socket..."
find /usr/local/etc/php-fpm.d -name "*.conf" -exec sed -i 's|listen = .*|listen = /var/run/php-fpm.sock|g' {} +
find /usr/local/etc/php-fpm.d -name "*.conf" -exec sed -i 's|;catch_workers_output = .*|catch_workers_output = yes|g' {} +
find /usr/local/etc/php-fpm.d -name "*.conf" -exec sed -i 's|listen.mode = 0666|listen.mode = 0666\nphp_admin_value[error_log] = /proc/self/fd/2\nphp_admin_flag[log_errors] = on|g' {} +

# Create SQLite database if it doesn't exist (only if DB_CONNECTION is sqlite)
if [ "$DB_CONNECTION" = "sqlite" ]; then
    if [ ! -f /var/www/html/database/database.sqlite ]; then
        echo ">>> Creating SQLite database..."
        touch /var/www/html/database/database.sqlite
        chmod 666 /var/www/html/database/database.sqlite
    fi
fi

# Wait for database connection (only for mysql/pgsql)
if [ "$DB_CONNECTION" != "sqlite" ]; then
    echo ">>> Waiting for database connection ($DB_HOST)..."
    for i in {1..30}; do
        php artisan db:monitor && break
        echo ">>> Database not ready, waiting..."
        sleep 2
    done
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
# We use a simple script to ensure both stay running or exit the container if one fails
php-fpm -D

echo ">>> Starting Nginx..."
nginx -g "daemon off;"
