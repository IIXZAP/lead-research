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
        composer dump-autoload --optimize; \
    fi

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

HEALTHCHECK --interval=15s --timeout=5s --retries=5 \
    CMD php artisan tinker --execute="echo 'ok';" > /dev/null 2>&1 || exit 1

EXPOSE 9000
CMD ["php-fpm"]
