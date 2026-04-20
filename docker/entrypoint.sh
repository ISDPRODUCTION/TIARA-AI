#!/bin/sh

# Set the port for Nginx from Railway environment variable or default to 8080
PORT=${PORT:-8080}
sed -i "s/listen 8080;/listen ${PORT};/g" /etc/nginx/http.d/default.conf
sed -i "s/listen \[::\]:8080;/listen \[::\]:${PORT};/g" /etc/nginx/http.d/default.conf

# Create SQLite database if it doesn't exist
if [ ! -f /var/www/html/database/database.sqlite ]; then
    touch /var/www/html/database/database.sqlite
    chmod 666 /var/www/html/database/database.sqlite
fi

# Run migrations
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data /var/www/html

# Ensure PHP-FPM listens on 127.0.0.1:9000 (often defaults to socket in some setups)
find /usr/local/etc/php-fpm.d -name "*.conf" -exec sed -i 's|listen = .*|listen = 127.0.0.1:9000|g' {} +

php artisan migrate --force

# Optimize Laravel for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"
