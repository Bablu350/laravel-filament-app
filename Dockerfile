FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
  git unzip curl libpng-dev libonig-dev libxml2-dev zip libzip-dev libpq-dev \
  && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring zip exif pcntl gd intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy app
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www \
  && chmod -R 755 /var/www

# Expose port
EXPOSE 9000

CMD ["php-fpm"]