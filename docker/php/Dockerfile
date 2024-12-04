FROM php:8.3-fpm AS php_base

ARG TIMEZONE

RUN apt-get update && apt-get install -y \
    gnupg g++ git procps openssl mc nano make acl \
    curl wget unzip gettext software-properties-common \
    zlib1g-dev libzip-dev libonig-dev libxslt1-dev \
    libicu-dev libpq-dev curl libcurl4-openssl-dev

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer --version

# Set timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone \
    && printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini \
    && "date"

RUN docker-php-ext-install -j$(nproc) curl
RUN docker-php-ext-install -j$(nproc) intl
RUN docker-php-ext-install -j$(nproc) bcmath
RUN docker-php-ext-install -j$(nproc) mbstring
RUN docker-php-ext-install -j$(nproc) pgsql
RUN docker-php-ext-install -j$(nproc) pdo_pgsql
RUN docker-php-ext-install -j$(nproc) exif

RUN pecl update-channels
RUN pecl install redis
RUN pecl install igbinary
RUN docker-php-ext-enable redis igbinary


RUN echo 'alias console="php bin/console"' >> ~/.bashrc

WORKDIR /var/www/app
