FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libicu-dev \
    libxml2-dev \
    libxslt1-dev \
    libzip-dev \
    libonig-dev \
    libsodium-dev \
    libwebp-dev \
    default-mysql-client \
    git \
    unzip \
    cron \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    bcmath \
    gd \
    intl \
    mbstring \
    pdo_mysql \
    soap \
    sockets \
    xsl \
    zip \
    sodium \
    opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP configuration
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini
COPY php-custom.ini /usr/local/etc/php/conf.d/php-custom.ini

WORKDIR /var/www/html

# Set proper permissions
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

EXPOSE 9000
CMD ["php-fpm"]
