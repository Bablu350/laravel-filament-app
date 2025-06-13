FROM php:8.2-fpm

# Install PHP dependencies
RUN apt-get update && apt-get install -y \
    nginx curl git unzip zip libzip-dev libonig-dev libxml2-dev \
    libpq-dev libicu-dev sqlite3 libsqlite3-dev default-mysql-client supervisor \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pdo_sqlite mbstring exif pcntl bcmath zip intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# ✅ Copy the full Laravel app first (so artisan is available)
COPY . .

# ✅ Then install Composer dependencies (now artisan exists)
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www \
  && chmod -R 755 /var/www

# Copy Nginx config
COPY docker/default.conf /etc/nginx/sites-available/default

# Copy supervisord config
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose port
EXPOSE 80

# Start supervisor (nginx + php-fpm)
CMD ["/usr/bin/supervisord"]
