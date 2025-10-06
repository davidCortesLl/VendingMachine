FROM php:8.3-cli
WORKDIR /app
COPY . .
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libzip-dev \
    && docker-php-ext-install zip
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN php composer.phar install || true
RUN pecl install redis && docker-php-ext-enable redis
RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN composer dump-autoload
CMD ["php", "-S", "0.0.0.0:8080", "public/index.php"]
