#!/bin/sh

# Default port to 8080 if not set
export PORT=${PORT:-8080}

# Replace $PORT placeholder in Nginx config
envsubst '${PORT}' < /etc/nginx/sites-available/default > /etc/nginx/sites-available/default.tmp && mv /etc/nginx/sites-available/default.tmp /etc/nginx/sites-available/default

# Generate .env file if it doesn't exist or update it from environment variables
# Note: In production, it's better to use actual environment variables.
# This part ensures standard Laravel variables are set.
cat > /var/www/html/.env << EOF
APP_NAME="${APP_NAME:-Laravel}"
APP_ENV="${APP_ENV:-production}"
APP_KEY="${APP_KEY}"
APP_DEBUG="${APP_DEBUG:-false}"
APP_URL="${APP_URL:-http://localhost}"

DB_CONNECTION="${DB_CONNECTION:-mysql}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE}"
DB_USERNAME="${DB_USERNAME}"
DB_PASSWORD="${DB_PASSWORD}"

SESSION_DRIVER="${SESSION_DRIVER:-file}"
SESSION_LIFETIME="${SESSION_LIFETIME:-120}"

FILESYSTEM_DISK="${FILESYSTEM_DISK:-local}"
EOF

# Ensure storage directories exist and are writable
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Run Laravel optimizations
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Run migrations (only in production/force)
if [ "$APP_ENV" = "production" ]; then
    php artisan migrate --force
fi

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
echo "Starting Nginx on port $PORT..."
nginx -g "daemon off;"