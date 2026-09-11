# infrastructure/railway/Dockerfile
#
# Railway variant of infrastructure/php/Dockerfile.
# Difference from the original: this image also installs nginx + supervisor
# and runs BOTH nginx and php-fpm inside a single container via supervisord,
# because Railway builds one Dockerfile per service and does not support the
# shared bind-mount volume that docker-compose.yml uses to let a separate
# `nginx` container see the same `laravel` container's code.
#
# Build context must be the REPO ROOT (same as the original), e.g. on Railway:
#   Root Directory: .
#   Dockerfile Path: infrastructure/railway/Dockerfile

FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    nginx \
    supervisor \
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

# --- Railway-only additions below ---

# Alpine's nginx package ships its own default.conf; overwrite it.
COPY infrastructure/railway/nginx.conf /etc/nginx/http.d/default.conf
COPY infrastructure/railway/supervisord.conf /etc/supervisord.conf

COPY infrastructure/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# nginx listens on 8080 in this variant (see nginx.conf). Set this service's
# Railway "Networking" target port to 8080 (or set a PORT=8080 variable,
# whichever your Railway project version exposes) so public traffic reaches it.
HEALTHCHECK --interval=15s --timeout=5s --retries=5 \
    CMD curl -f http://localhost:8080/nginx-health || exit 1

EXPOSE 8080
ENTRYPOINT ["entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf", "-n"]