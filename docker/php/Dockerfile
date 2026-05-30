ARG PHP_VERSION=8.3

FROM php:${PHP_VERSION}-fpm-bookworm

ARG WWWUSER=1000
ARG WWWGROUP=1000

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        chromium \
        fontconfig \
        fonts-dejavu \
        fonts-liberation \
        ghostscript \
        git \
        imagemagick \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libmagickwand-dev \
        libonig-dev \
        libpng-dev \
        libwebp-dev \
        libxml2-dev \
        libxslt1-dev \
        libzip-dev \
        unzip \
        wkhtmltopdf \
        zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        soap \
        xsl \
        zip \
    && pecl install redis imagick \
    && docker-php-ext-enable redis imagick \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN groupmod -o -g "${WWWGROUP}" www-data \
    && usermod -o -u "${WWWUSER}" -g www-data www-data

USER www-data
