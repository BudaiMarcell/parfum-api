# syntax=docker/dockerfile:1.7

# ─── Stage 1 — vendor ─────────────────────────────────────────────────────────
# All composer work happens in this stage so the runtime image stays free of
# composer + its build artifacts. We do it in two steps:
#
#   1. `composer install --no-scripts` against composer.{json,lock} only.
#      Splitting this into its own layer means dependency-only changes
#      invalidate ONLY this step, not the application copy below.
#   2. Copy the full app, then `composer dump-autoload` with classmap
#      authority. This is when post-autoload-dump (artisan package:discover)
#      runs — and it needs the full app to succeed.
FROM composer:2 AS vendor

WORKDIR /app

# Copy only the manifests first so the dependency layer caches across
# code-only changes.
COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --prefer-dist \
        --no-autoloader

# Now bring in the rest of the source and regenerate the autoloader with
# the full classmap. `package:discover` runs here as the post-autoload-dump
# hook; with the app present, it succeeds.
COPY . .

RUN composer dump-autoload \
        --no-dev \
        --optimize \
        --classmap-authoritative

# ─── Stage 2 — runtime ────────────────────────────────────────────────────────
# Alpine keeps the image small (~120 MB compressed). PHP 8.3 matches the
# composer.json `^8.2` constraint with headroom for a year+ of patches.
FROM php:8.3-fpm-alpine AS runtime

# Build deps for the PHP extensions we install. They're all present at
# `pecl install` time but the bare runtime libs are pulled in via the
# `--virtual` mechanism so we can drop them at the end of the layer.
RUN set -eux; \
    apk add --no-cache \
        bash \
        icu-libs \
        libpng \
        libzip \
        oniguruma \
        netcat-openbsd \
        tzdata; \
    apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        autoconf \
        gcc \
        g++ \
        make \
        icu-dev \
        libpng-dev \
        libzip-dev \
        oniguruma-dev; \
    docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        bcmath \
        intl \
        opcache \
        gd \
        zip; \
    pecl install redis; \
    docker-php-ext-enable redis; \
    apk del --no-network .build-deps; \
    rm -rf /tmp/* /var/cache/apk/*

# OPcache settings tuned for a long-running php-fpm worker. opcache.validate_timestamps=0
# is safe because the codebase is baked into the image; if it changes, you're
# building a new image anyway.
COPY <<'EOF' /usr/local/etc/php/conf.d/opcache.ini
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
EOF

COPY <<'EOF' /usr/local/etc/php/conf.d/app.ini
expose_php=Off
max_execution_time=30
memory_limit=256M
upload_max_filesize=10M
post_max_size=10M
date.timezone=Europe/Budapest
EOF

WORKDIR /var/www

# Copy the fully prepared app + vendor tree from the build stage. The
# autoloader has already been generated with classmap authority there, so
# the runtime image needs no composer binary at all.
COPY --from=vendor /app /var/www

# Laravel writes to bootstrap/cache and storage/* — those have to be owned
# by the php-fpm runtime user. The base image's www-data uid:gid is 82:82
# on alpine, which we keep so the container is unprivileged by default.
RUN set -eux; \
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache; \
    chmod -R ug+rwx /var/www/storage /var/www/bootstrap/cache

# Entrypoint waits for MySQL, runs migrations, caches config, then exec's php-fpm.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Healthcheck: artisan returns non-zero if the app can't bootstrap. We don't
# hit /api/products because that requires the nginx sidecar — `artisan about`
# is the one in-process probe that exercises config loading and DB resolution.
HEALTHCHECK --interval=30s --timeout=10s --start-period=20s --retries=3 \
    CMD php artisan about > /dev/null 2>&1 || exit 1

USER www-data

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
