#!/bin/sh
# Laravel container entrypoint. Runs every time the api / queue / scheduler
# container starts. Three responsibilities:
#
#   1. Wait for MySQL to accept TCP — without this the migrate step below
#      crashes the container and docker keeps restarting until MySQL is up,
#      which is noisy and slow.
#   2. Cache config + routes so php-fpm doesn't re-parse them on every
#      request. Safe in prod because the codebase is baked into the image.
#   3. Apply pending migrations exactly once per boot. --force suppresses
#      the production confirmation prompt; we're a single-replica setup so
#      a brief migration window is fine. Switch this to a dedicated job if
#      you ever scale to N replicas.
#
# Finally, exec's the original CMD (php-fpm by default, but compose overrides
# this for the queue + scheduler services so they share the same image).

set -e

DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"

echo "[entrypoint] Waiting for ${DB_HOST}:${DB_PORT}..."
# Cap the wait at ~60s so a misconfigured stack fails fast instead of
# silently looping. nc is from netcat-openbsd (installed in the Dockerfile).
attempts=0
until nc -z "${DB_HOST}" "${DB_PORT}" 2>/dev/null; do
    attempts=$((attempts + 1))
    if [ "${attempts}" -ge 60 ]; then
        echo "[entrypoint] FATAL: ${DB_HOST}:${DB_PORT} never became reachable after 60s." >&2
        exit 1
    fi
    sleep 1
done
echo "[entrypoint] ${DB_HOST}:${DB_PORT} is up."

# Only the primary api service should run migrations. queue / scheduler
# containers share the image and would otherwise race against each other.
# Set SKIP_MIGRATIONS=1 on those services in docker-compose.yml.
if [ "${SKIP_MIGRATIONS:-0}" != "1" ]; then
    echo "[entrypoint] Caching config + routes..."
    php artisan config:cache
    php artisan route:cache

    echo "[entrypoint] Running migrations..."
    php artisan migrate --force
fi

echo "[entrypoint] Starting: $*"
exec "$@"
