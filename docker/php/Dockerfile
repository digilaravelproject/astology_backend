# Multi-purpose lightweight PHP 8.2 Alpine image for Laravel 12 (App, Worker, Reverb)
FROM php:8.2-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP build dependencies
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    supervisor \
    icu-dev \
    linux-headers \
    $PHPIZE_DEPS

# Configure and install PHP extensions (including posix & pcntl for Reverb & Supervisor)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        posix \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Install Redis extension via PECL
RUN pecl install redis \
    && docker-php-ext-enable redis

# Clean up build dependencies to keep image small (<100MB layer)
RUN apk del $PHPIZE_DEPS \
    && rm -rf /tmp/pear

# Get latest Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy custom PHP & OPcache config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Ensure www-data permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
