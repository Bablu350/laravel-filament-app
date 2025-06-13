FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    nginx curl git unzip zip libzip-dev libonig-dev libxml2-dev \
    libpq-dev libicu-dev sqlite3 libsqlite3-dev default-mysql-client supervisor \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pdo_sqlite mbstring exif pcntl bcmath zip intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Setup working directory
WORKDIR /var/www

# Copy Laravel app files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www \
  && chmod -R 755 /var/www

# Copy nginx config
COPY docker/default.conf /etc/nginx/sites-available/default

# Copy supervisor config
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose port 80
EXPOSE 80

# Run both services
CMD ["/usr/bin/supervisord"]
