#!/bin/sh
# POSIX-compatible deploy script for Plesk chrooted environment
set -eu

# Plesk post-deploy script for Laravel app
# Assumes this script is placed in the repo at .plesk/deploy.sh and Plesk is configured to run it from the repo root

echo "Running Plesk deploy script: $(date)"

# Move to repo root (Plesk may call from different cwd)
REPO_ROOT="$(cd "$(dirname "$0")"/.. && pwd)"
cd "$REPO_ROOT"

echo "Repository root: $REPO_ROOT"

# Detect PHP binary (DirectAdmin examples include /opt/alt/php81/usr/bin/php)
if command -v php >/dev/null 2>&1; then
  PHPBIN=$(command -v php)
elif [ -x /opt/alt/php81/usr/bin/php ]; then
  PHPBIN=/opt/alt/php81/usr/bin/php
elif [ -x /usr/local/bin/php ]; then
  PHPBIN=/usr/local/bin/php
elif [ -x /usr/bin/php ]; then
  PHPBIN=/usr/bin/php
else
  PHPBIN=php
fi
echo "Using PHP binary: $PHPBIN"

# Persistent deploy log: capture all output to a repo-local log for later inspection
LOGFILE="$REPO_ROOT/.plesk/deploy.log"
# Ensure the .plesk directory exists before writing the logfile. In some Plesk
# invocations the computed REPO_ROOT may point to a parent directory that
# doesn't contain a .plesk subdir yet.
mkdir -p "$(dirname "$LOGFILE")" || true
echo "=== Deploy started: $(date) ===" >> "$LOGFILE"
# Save stdout/stderr and redirect remaining output to logfile (keeps earlier messages visible in Plesk UI)
exec 3>&1 4>&2
exec 1>>"$LOGFILE" 2>&1

# Ensure Laravel runtime directories exist (storage, logs, bootstrap cache)
mkdir -p storage/framework/{sessions,views,cache,data} storage/logs bootstrap/cache || true
touch storage/logs/laravel.log || true
chmod -R 775 storage bootstrap/cache || true

# Composer install: try multiple strategies (composer, local composer.phar)
if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader --no-interaction || echo "composer install returned non-zero";
elif [ -f "$REPO_ROOT/composer.phar" ]; then
  "$PHPBIN" "$REPO_ROOT/composer.phar" install --no-dev --optimize-autoloader --no-interaction || echo "composer.phar install returned non-zero";
elif [ -f "$REPO_ROOT/.composer/composer.phar" ]; then
  "$PHPBIN" "$REPO_ROOT/.composer/composer.phar" install --no-dev --optimize-autoloader --no-interaction || echo "composer.phar install returned non-zero";
else
  echo "composer not found inside chroot; skipping composer install. To enable dependency install add composer to PATH or upload vendor/ from local machine."
fi

# Ensure .env exists
if [ ! -f .env ]; then
  if [ -f .env.example ]; then
    cp .env.example .env
    echo "Copied .env.example -> .env; please edit .env with production credentials"
  else
    echo "No .env or .env.example found. Create .env before running."
  fi
fi

# Laravel specific steps (guard if artisan present)
if [ -f artisan ]; then
  # Only generate APP_KEY if it's missing or empty in .env. This avoids
  # rotating a manually-set production APP_KEY during deploy.
  APP_KEY_VAL=""
  if [ -f .env ]; then
    APP_KEY_VAL=$(grep -m1 '^APP_KEY=' .env | cut -d'=' -f2- | tr -d '\r\n' || true)
  fi

  if [ -z "$APP_KEY_VAL" ]; then
    echo "APP_KEY not found in .env — generating a new key"
    "$PHPBIN" artisan key:generate --force || true
  else
    echo "APP_KEY present in .env — skipping key:generate"
  fi

  # Run migrations and cache steps (safe to keep as best-effort)
  "$PHPBIN" artisan migrate --force || true
  "$PHPBIN" artisan config:cache || true
  "$PHPBIN" artisan route:cache || true
fi

# Permissions
if [ -d storage ] && [ -d bootstrap/cache ]; then
  chown -R $(whoami):$(whoami) storage bootstrap/cache || true
  chmod -R 775 storage bootstrap/cache || true
fi

echo "Plesk deploy script completed"
