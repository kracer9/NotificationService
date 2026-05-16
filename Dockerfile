FROM php:8.4-fpm

RUN apt update && \
    apt install -y curl libpq-dev && \
    docker-php-ext-install pdo_pgsql pgsql

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS https://getcomposer.org/installer | php -- \
    --filename=composer \
    --install-dir=/usr/local/bin

WORKDIR /var/www