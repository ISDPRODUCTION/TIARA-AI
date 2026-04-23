#!/bin/sh

# Default port to 8080 if not set
export PORT=${PORT:-8080}

# Replace $PORT placeholder in Nginx config
envsubst '${PORT}' < /etc/nginx/sites-available/default > /etc/nginx/sites-available/default.tmp && mv /etc/nginx/sites-available/default.tmp /etc/nginx/sites-available/default

# Generate .env from Railway environment variables
cat > /var/www/html/.env << EOF
APP_NAME="${APP_NAME:-Tiara AI}"
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
SESSION_SECURE_COOKIE="${SESSION_SECURE_COOKIE:-false}"

CACHE_STORE="${CACHE_STORE:-file}"
QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"
FILESYSTEM_DISK="${FILESYSTEM_DISK:-local}"

AI_PROVIDER="${AI_PROVIDER:-}"
MISTRAL_API_KEY="${MISTRAL_API_KEY:-}"
MISTRAL_MODEL="${MISTRAL_MODEL:-mistral-small-latest}"
GEMINI_API_KEY="${GEMINI_API_KEY:-}"

GOOGLE_CLIENT_ID="${GOOGLE_CLIENT_ID:-}"
GOOGLE_CLIENT_SECRET="${GOOGLE_CLIENT_SECRET:-}"
GOOGLE_REDIRECT_URI="${GOOGLE_REDIRECT_URI:-}"
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

# Run migrations, ignore errors if table already exists
php artisan migrate --force 2>&1 || echo "Migration had errors (possibly already migrated), continuing..."

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
echo "Starting Nginx on port $PORT..."
nginx -g "daemon off;"