#!/usr/bin/env bash
set -euo pipefail

# Local helper deploy script for manual runs (composer, migrate, cache)
# Usage: ./deploy.sh [--no-migrate]

NO_MIGRATE=0
for arg in "$@"; do
  case $arg in
    --no-migrate) NO_MIGRATE=1; shift;;
  esac
done

echo "Starting deploy helper"

if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader --no-interaction
else
  echo "composer not found locally; install composer or run on server"
fi

if [ ! -f .env ]; then
  echo ".env not found; copying .env.example -> .env"
  cp .env.example .env
  echo "Edit .env before continuing"
fi

if [ -f artisan ] && [ "$NO_MIGRATE" -eq 0 ]; then
  php artisan key:generate --force || true
  php artisan migrate --force
  php artisan config:cache || true
  php artisan route:cache || true
fi

echo "Deploy helper completed"
