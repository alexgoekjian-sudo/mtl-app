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

# Composer install: try multiple strategies (composer, local composer.phar)
if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader --no-interaction || echo "composer install returned non-zero";
elif [ -f "$REPO_ROOT/composer.phar" ]; then
  php "$REPO_ROOT/composer.phar" install --no-dev --optimize-autoloader --no-interaction || echo "composer.phar install returned non-zero";
elif [ -f "$REPO_ROOT/.composer/composer.phar" ]; then
  php "$REPO_ROOT/.composer/composer.phar" install --no-dev --optimize-autoloader --no-interaction || echo "composer.phar install returned non-zero";
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
  php artisan key:generate --force || true
  php artisan migrate --force || true
  php artisan config:cache || true
  php artisan route:cache || true
fi

# Permissions
if [ -d storage ] && [ -d bootstrap/cache ]; then
  chown -R $(whoami):$(whoami) storage bootstrap/cache || true
  chmod -R 775 storage bootstrap/cache || true
fi

echo "Plesk deploy script completed"
