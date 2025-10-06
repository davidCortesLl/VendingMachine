FROM php:8.3-cli
WORKDIR /app

# Install system dependencies first
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libzip-dev \
    && docker-php-ext-install zip

# Install Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Copy all source code
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --no-scripts

# Install PHP extensions
RUN pecl install redis && docker-php-ext-enable redis
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Generate autoload files
RUN composer dump-autoload

CMD ["php", "-S", "0.0.0.0:8080", "public/index.php"]