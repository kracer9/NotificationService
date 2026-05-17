FROM php:8.4-fpm

RUN apt-get update && \
    apt-get install -y curl libpq-dev git unzip && \
    docker-php-ext-install pdo_pgsql pgsql sockets

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS https://getcomposer.org/installer | php -- \
    --filename=composer \
    --install-dir=/usr/local/bin
RUN composer config -g process-timeout 2000

WORKDIR /var/www