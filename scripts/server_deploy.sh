#!/bin/sh
# Safe server-side deploy helper
# Usage:
#   ./scripts/server_deploy.sh [repo_dir] [remote_git_url] [branch]
# Example:
#   ./scripts/server_deploy.sh /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app git@github.com:alexgoekjian-sudo/mtl-app.git main
#
# Behavior:
# - Creates a backup tarball of the current working tree (excluding .git) under .deploy_backups/
# - Initializes a git repo if missing
# - Adds remote 'origin' if missing (using provided remote_git_url)
# - If the remote branch exists, attempts a safe `git pull --rebase origin <branch>`
# - If no remote branch exists, pushes the local branch to origin
# - Exits on conflicts or if local working tree is dirty to avoid accidental overwrites

set -eu

# Parse optional flags (--dry-run, --verbose) before positional args so REPO_DIR/REMOTE/BRANCH are correct.
DRY_RUN=0
VERBOSE=0
while [ "${1:-}" != "" ]; do
  case "$1" in
    --dry-run)
      DRY_RUN=1
      echo "DRY-RUN: enabled (no actions will be executed)"
      shift
      ;;
    --verbose)
      VERBOSE=1
      echo "VERBOSE: enabled (commands will stream output)"
      shift
      ;;
    *)
      break
      ;;
  esac
done

# Positional args after optional flags
REPO_DIR=${1:-$(pwd)}
REMOTE=${2:-git@github.com:alexgoekjian-sudo/mtl-app.git}
BRANCH=${3:-main}

echo "Server deploy helper"
echo "Repo dir: $REPO_DIR"
echo "Remote: $REMOTE"
echo "Branch: $BRANCH"

cd "$REPO_DIR"

# Logging helpers
info(){ printf '%s\n' "INFO: $*"; }
warn(){ printf '%s\n' "WARN: $*" >&2; }
err(){ printf '%s\n' "ERROR: $*" >&2; }

# run_cmd: runs commands according to DRY_RUN/VERBOSE settings
# Usage: run_cmd cmd arg1 arg2 ...
run_cmd(){
  if [ "${DRY_RUN:-0}" -eq 1 ]; then
    info "[DRY-RUN] would run: $*"
    return 0
  fi
  if [ "${VERBOSE:-0}" -eq 1 ]; then
    info "Running: $*"
    "$@"
    return $?
  else
    "$@" >/dev/null 2>&1
    return $?
  fi
}

# Check git availability; don't hard-fail if git isn't present — we support non-git mode
GIT_AVAILABLE=1
if ! command -v git >/dev/null 2>&1; then
  warn "git not found; will operate in non-git mode"
  GIT_AVAILABLE=0
fi

# create backup
BACKUP_DIR="$REPO_DIR/.deploy_backups"
TIMESTAMP=$(date +%Y%m%dT%H%M%S)
mkdir -p "$BACKUP_DIR"
info "Creating backup to $BACKUP_DIR/backup-$TIMESTAMP.tar.gz (excluding .git and .deploy_backups) ..."
# Exclude .git and the backup dir itself to avoid 'file changed as we read it' race when tar writes into the tree.
if ! run_cmd tar --exclude='.git' --exclude='.deploy_backups' -czf "$BACKUP_DIR/backup-$TIMESTAMP.tar.gz" -C "$REPO_DIR" .; then
  warn "Backup tar failed (tar may not be installed or race condition occurred); continuing"
fi

# Try to ensure we have a git repo, but be tolerant if git init fails (shared hosts may restrict git or FS permissions).
# Determine whether we have a usable git repo; be conservative in dry-run mode.
GIT_OK=0
if [ "$GIT_AVAILABLE" -eq 1 ]; then
  if [ "$DRY_RUN" -eq 1 ]; then
    info "[DRY-RUN] would check/init git repository (skipping real git operations)"
    GIT_OK=0
  else
    if [ ! -d .git ]; then
      info "No .git found — attempting to initialize repository"
      if run_cmd git init; then
        info "git initialized"
        GIT_OK=1
      else
        warn "git init failed; continuing without git operations"
        GIT_OK=0
      fi
    else
      # we have a .git directory; verify it's a working tree
      if run_cmd git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
        GIT_OK=1
      else
        warn "Found .git but it's not a working tree; continuing in non-git mode"
        GIT_OK=0
      fi
    fi
  fi
else
  GIT_OK=0
fi

# If we have a working git repo, enforce clean working tree and perform remote sync; otherwise skip to post-deploy.
if [ "$GIT_OK" -eq 1 ]; then
  # Make sure there are no uncommitted changes to avoid accidental overwrites
  if [ -n "$(git status --porcelain)" ]; then
    err "Working tree is dirty. Commit or stash local changes before running this script."
    git status --porcelain
    exit 2
  fi

  if [ "$DRY_RUN" -eq 1 ]; then
    info "[DRY-RUN] would perform git fetch/pull/push operations (skipping)"
  else
    # Ensure local branch exists
    if ! run_cmd git show-ref --verify --quiet refs/heads/$BRANCH; then
      info "Local branch $BRANCH does not exist — creating from current HEAD"
      run_cmd git checkout -b "$BRANCH" || run_cmd git checkout -B "$BRANCH"
    fi

    # Configure remote if missing
    if run_cmd git remote get-url origin >/dev/null 2>&1; then
      EXISTING_REMOTE=$(git remote get-url origin)
      info "Existing origin: $EXISTING_REMOTE"
    else
      info "Adding origin remote: $REMOTE"
      run_cmd git remote add origin "$REMOTE" || warn "Failed to add origin remote; continuing"
    fi

  info "Fetching remote..."
  run_cmd git fetch origin --quiet || warn "git fetch returned non-zero; continuing"

    # Check if remote branch exists
    if run_cmd git ls-remote --exit-code --heads origin "$BRANCH" >/dev/null 2>&1; then
      info "Remote branch origin/$BRANCH exists — performing safe pull (rebase)"
      # attempt rebase pull
      set -o pipefail
      if run_cmd git pull --rebase origin "$BRANCH"; then
        info "Pulled and rebased successfully"
      else
        err "Pull failed (merge conflicts?). Rebase aborted. Inspect repo and resolve conflicts manually."
        exit 3
      fi
    else
      info "Remote branch origin/$BRANCH does not exist — pushing local branch to origin"
      run_cmd git push -u origin "$BRANCH" || warn "git push failed; continuing"
    fi
  fi
else
  info "Skipping git sync steps (non-git mode). Proceeding to post-deploy tasks."
fi

# When running in non-git mode (for SFTP uploads), allow deploying from a provided release tarball.
if [ "$GIT_OK" -eq 0 ]; then
  echo "Non-git mode: looking for RELEASE_TAR or release.tar.gz uploaded via SFTP"
  RELEASE_TAR_PATH="${RELEASE_TAR:-$REPO_DIR/release.tar.gz}"
  if [ -f "$RELEASE_TAR_PATH" ]; then
    info "Found release tarball at $RELEASE_TAR_PATH"
    if ! run_cmd tar -xzf "$RELEASE_TAR_PATH" -C "$REPO_DIR"; then
      warn "release extraction failed; continuing"
    else
      info "Release extracted"
    fi
  else
    info "No RELEASE_TAR found at $RELEASE_TAR_PATH — assuming files were uploaded directly via SFTP"
  fi
  echo "Skipped git sync steps (non-git mode). Proceeding to post-deploy tasks."
else
  echo "Deploy sync complete. Backup saved in $BACKUP_DIR/backup-$TIMESTAMP.tar.gz"
fi

# ------------------------
# Optional automated post-deploy tasks
# - vendor extraction or composer install (guarded by NO_COMPOSER)
# - run migrations and seeds (guarded by SKIP_ARTISAN)
# Environment flags (optional):
#   NO_COMPOSER=1    -> skip composer install and vendor extraction
#   SKIP_ARTISAN=1   -> skip running artisan migrate/seed
#   VENDOR_TAR=path  -> path to vendor tarball to extract if composer unavailable
# ------------------------

# detect PHP binary (prefer common hosting paths)
PHPBIN="$(command -v php || true)"
for p in /opt/alt/php81/usr/bin/php /opt/alt/php74/usr/bin/php /usr/bin/php /usr/local/bin/php; do
  if [ -x "$p" ]; then
    PHPBIN="$p"
    break
  fi
done

echo "Using PHP binary: ${PHPBIN:-php (none found)}"

# vendor handling
if [ -z "${NO_COMPOSER:-}" ]; then
  echo "NO_COMPOSER not set — attempting to ensure vendor is present"

  # If composer.phar is present in repo, use it
  if [ -f "$REPO_DIR/composer.phar" ]; then
    if [ -n "$PHPBIN" ]; then
      if ! run_cmd "$PHPBIN" "$REPO_DIR/composer.phar" install --no-dev --optimize-autoloader --no-interaction; then
        warn "composer.phar install failed"
      fi
    else
      warn "No PHP binary to run composer.phar; skipping composer.phar install"
    fi

  # If composer CLI available, use it
  elif command -v composer >/dev/null 2>&1; then
    if ! run_cmd composer install --no-dev --optimize-autoloader --no-interaction; then
      warn "composer install failed"
    fi

  # Fallback: if a vendor tarball exists (VENDOR_TAR env or vendor.tar.gz), extract it
  else
    VENDOR_TAR_PATH="${VENDOR_TAR:-$REPO_DIR/vendor.tar.gz}"
    if [ -f "$VENDOR_TAR_PATH" ]; then
      if ! run_cmd tar -xzf "$VENDOR_TAR_PATH" -C "$REPO_DIR"; then
        warn "vendor extraction failed"
      fi
    else
      echo "No composer available and no vendor tarball found at $VENDOR_TAR_PATH; continuing without vendor" >&2
    fi
  fi
else
  echo "NO_COMPOSER is set — skipping composer/vendor steps"
fi

# Artisan tasks (migrate/seed) — only run when SKIP_ARTISAN is not set
if [ -z "${SKIP_ARTISAN:-}" ]; then
  if [ -n "$PHPBIN" ] && [ -f "$REPO_DIR/artisan" ]; then
    if ! run_cmd "$PHPBIN" "$REPO_DIR/artisan" migrate --force; then
      warn "artisan migrate failed"
    fi
    # Seed admin user if seeder exists
    if ! run_cmd "$PHPBIN" "$REPO_DIR/artisan" db:seed --class=AdminUserSeeder --force; then
      warn "artisan db:seed (AdminUserSeeder) failed or not present"
    fi
  else
    warn "Skipping artisan tasks: artisan missing or no PHP binary available"
  fi
else
  echo "SKIP_ARTISAN is set — skipping artisan migrate/seed"
fi

# ------------------------
# Post-deploy health check
# - Checks $HEALTH_URL and fails the deploy if it does not return HTTP 200
# - Configure with HEALTH_URL and optional HEALTH_CHECK_INSECURE=1 to skip TLS verification
# ------------------------
HEALTH_URL=${HEALTH_URL:-http://127.0.0.1/status}
HEALTH_CHECK_INSECURE=${HEALTH_CHECK_INSECURE:-0}

info "Post-deploy: health check target: $HEALTH_URL"
if [ "${DRY_RUN:-0}" -eq 1 ]; then
  info "[DRY-RUN] would check health URL: $HEALTH_URL"
else
  CURL_OPTS="-s -S -m 10 -o /dev/null -w %{http_code}"
  if [ "${HEALTH_CHECK_INSECURE:-0}" -eq 1 ]; then
    # allow insecure TLS when requested
    HTTP_CODE=$(curl -k -s -S -m 10 -o /dev/null -w "%{http_code}" "$HEALTH_URL" || echo "000")
  else
    HTTP_CODE=$(curl -s -S -m 10 -o /dev/null -w "%{http_code}" "$HEALTH_URL" || echo "000")
  fi

  if [ "$HTTP_CODE" != "200" ]; then
    err "Health check failed: $HEALTH_URL returned HTTP $HTTP_CODE"
    err "Failing deploy to avoid exposing a broken site. Inspect app logs and retry after fixing issues."
    exit 4
  fi

  info "Health check passed (HTTP 200)"
fi

exit 0

