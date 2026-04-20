# Stage 1: Build Assets
FROM node:20-alpine AS assets-builder
WORKDIR /app
COPY . .
RUN npm install && npm run build

# Stage 2: PHP & Application
FROM php:8.3-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    nginx \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd bcmath zip pdo pdo_mysql intl opcache mbstring

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . .

# Copy built assets from builder stage
COPY --from=assets-builder /app/public/build ./public/build

# Copy Nginx configuration
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Setup entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port (Railway will use the $PORT env var, but we'll default to 8080 in the config)
EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
