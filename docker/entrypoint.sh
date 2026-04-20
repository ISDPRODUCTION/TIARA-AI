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
php artisan migrate --force

# Optimize Laravel for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"
