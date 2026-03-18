# ============================================
# Magento 2.4.6-p4 All-in-One for Northflank
# ============================================

# ---------- Base: PHP extensions + system deps ----------
FROM php:8.2-fpm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx supervisor curl git unzip \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev \
    libicu-dev libxml2-dev libxslt1-dev libzip-dev libonig-dev \
    libsodium-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
       bcmath gd intl mbstring pdo_mysql soap sockets xsl zip sodium opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP config
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
    && echo "memory_limit=1536M" > /usr/local/etc/php/conf.d/99-magento.ini \
    && echo "max_execution_time=600" >> /usr/local/etc/php/conf.d/99-magento.ini \
    && echo "realpath_cache_size=10M" >> /usr/local/etc/php/conf.d/99-magento.ini \
    && echo "realpath_cache_ttl=7200" >> /usr/local/etc/php/conf.d/99-magento.ini \
    && echo "opcache.memory_consumption=512" >> /usr/local/etc/php/conf.d/99-magento.ini \
    && echo "opcache.max_accelerated_files=130987" >> /usr/local/etc/php/conf.d/99-magento.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/99-magento.ini

WORKDIR /var/www/html

# ---------- Build: Install + compile Magento ----------
FROM base AS build

ARG MAGENTO_PUBLIC_KEY
ARG MAGENTO_PRIVATE_KEY

RUN composer global config http-basic.repo.magento.com \
    "${MAGENTO_PUBLIC_KEY}" "${MAGENTO_PRIVATE_KEY}"

RUN find . -maxdepth 1 -not -name '.' -exec rm -rf {} + 2>/dev/null || true \
    && composer create-project --repository-url=https://repo.magento.com/ \
       magento/project-community-edition:2.4.6-p4 . --no-install --no-interaction

RUN composer config audit.block-insecure false \
    && composer update --no-dev --no-interaction --no-audit --optimize-autoloader

# Enable all modules without full install
RUN php bin/magento module:enable --all --force

RUN php -d memory_limit=2G bin/magento setup:di:compile

# Skip static-content:deploy - will be done at runtime after setup:install

RUN rm -rf var/cache/* var/page_cache/* var/generation/* var/di/* \
    && rm -rf /.composer/cache

# ---------- Production ----------
FROM base AS production

# Nginx config
RUN mkdir -p /etc/nginx/sites-enabled /var/log/nginx /run
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/magento.conf /etc/nginx/sites-enabled/default

# Supervisor config
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

# Copy pre-built Magento from build stage
COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html

# Writable dirs
RUN mkdir -p var/log var/cache var/page_cache var/session pub/static pub/media generated \
    && chmod -R 775 var generated pub/static pub/media \
    && chown -R www-data:www-data var generated pub/static pub/media

# Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=10s --start-period=120s --retries=3 \
    CMD curl -f http://localhost:8080/health_check.php || exit 1

ENTRYPOINT ["entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisord.conf"]
