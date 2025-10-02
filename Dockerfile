FROM php:8.2-cli
WORKDIR /app
COPY . .
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php
RUN php composer.phar install || true
RUN pecl install redis && docker-php-ext-enable redis
RUN php composer.phar dump-autoload
CMD ["php", "-S", "0.0.0.0:8080", "src/Api/index.php"]