FROM php:8.3-fpm-alpine
RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip \
    gd \
    bcmath \
    intl \
    opcache
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY apps/web/composer.json apps/web/composer.lock* ./
RUN if [ -f composer.lock ]; then \
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist; \
    fi
COPY apps/web/ ./
RUN if [ -f composer.lock ]; then \
    composer dump-autoload --optimize --no-scripts; \
    fi
COPY infrastructure/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Git doesn't track empty directories, so storage/* and bootstrap/cache may
# not exist at all in a fresh clone (no .gitkeep placeholders in this repo).
# Create them before chown so the build doesn't fail on "No such file".
RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/framework/testing \
    storage/logs \
    bootstrap/cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY infrastructure/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

HEALTHCHECK --interval=15s --timeout=5s --retries=5 \
    CMD php artisan tinker --execute="echo 'ok';" > /dev/null 2>&1 || exit 1
EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]