FROM php:8.4-fpm AS base

RUN apt-get update && \
    apt-get install -y curl libpq-dev git unzip && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    docker-php-ext-install pdo_pgsql pgsql sockets

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS https://getcomposer.org/installer | php -- \
    --filename=composer \
    --install-dir=/usr/local/bin
RUN composer config -g process-timeout 2000

FROM base AS dev
WORKDIR /var/www

FROM base AS build
COPY ./project /var/www
WORKDIR /var/www