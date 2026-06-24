#!/bin/sh
set -e

# Ensure the SQLite database file exists (bundled, ephemeral demo DB)
DB_FILE="${DB_DATABASE:-/app/database/database.sqlite}"
if [ ! -f "$DB_FILE" ]; then
    echo "Creating SQLite database at $DB_FILE"
    mkdir -p "$(dirname "$DB_FILE")"
    touch "$DB_FILE"
fi

# Generate an app key if one wasn't provided via env
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Regenerate Laravel's package manifest (skipped at build time because the DB
# didn't exist yet). Now that the DB and env are present, this succeeds.
php artisan package:discover --ansi

# Storage symlink (ignore error if it already exists)
php artisan storage:link || true

# Fresh demo data on every boot — clean, always-seeded portfolio demo
php artisan migrate:fresh --seed --force

# Cache config for production performance.
# NOTE: route:cache and view:cache are intentionally skipped. The app has a
# duplicate route name (teacher.announcements.reply) and a few dead views with
# broken component refs (e.g. welcome.blade.php) that break EAGER caching.
# Routes/views still compile fine on-demand at runtime — only the unused ones
# would error, and those are never rendered. config:cache is the one that
# actually matters for perf, and it succeeds.
php artisan config:cache

# Serve on the port Render provides
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
