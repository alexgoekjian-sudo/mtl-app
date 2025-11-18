scripts/health-check.sh

What it is
- A tiny health-check script that requests /status and writes OK/FAIL lines to storage/logs/health-check.log.

Install on server
1) Make executable:
   chmod +x scripts/health-check.sh

2) Test:
   ./scripts/health-check.sh
   tail -n 50 storage/logs/health-check.log

3) Install cron (run as the site user):
   (crontab -l 2>/dev/null; echo "*/5 * * * * /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app/scripts/health-check.sh >/dev/null 2>&1") | crontab -

Troubleshooting
- If you see "No such file or directory" when running the script even though the file exists, convert line endings to Unix format:
  sed -i 's/\r$//' scripts/health-check.sh

- Make sure the shebang is present and the script is executable.

Notes
- The script uses curl -k to skip TLS verification. Remove -k if you want proper SSL checks.

--------------------------------------------------------------------------------

server_deploy.sh

What it is
- A safe server-side deploy helper intended for shared hosts. It performs a timestamped backup, optionally syncs with a git remote when available, and runs post-deploy tasks (vendor extraction/composer install and artisan migrate/seed). The script supports non-git deployments (SFTP) and a dry-run preview mode.

Quick usage
- SFTP upload flow (you upload files directly via SFTP and then run the script on the server):

```bash
cd /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app
chmod +x ./scripts/server_deploy.sh
# Run post-deploy tasks but skip composer and artisan (safe test):
NO_COMPOSER=1 SKIP_ARTISAN=1 ./scripts/server_deploy.sh /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app git@github.com:alexgoekjian-sudo/mtl-app.git main
```

- Release tarball flow (upload a release archive via SFTP then extract):

```bash
# Upload release.tar.gz to the repo dir, then:
RELEASE_TAR=/home/u5021d9810/domains/mixtreelangdb.nl/mtl_app/release.tar.gz NO_COMPOSER=1 SKIP_ARTISAN=1 ./scripts/server_deploy.sh /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app git@github.com:alexgoekjian-sudo/mtl-app.git main
```

Dry-run (preview) mode
- To preview what the script will do without making changes, run with `--dry-run` as the first argument or set `DRY_RUN=1` in the environment:

```bash
./scripts/server_deploy.sh --dry-run /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app git@github.com:alexgoekjian-sudo/mtl-app.git main
# or equivalently
DRY_RUN=1 ./scripts/server_deploy.sh /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app git@github.com:alexgoekjian-sudo/mtl-app.git main
```

What dry-run shows
- The backup command that would be executed
- Whether git would be checked/initialized and whether a release tarball would be extracted
- Which composer/vendor commands would run (or be skipped)
- Which artisan commands would run (or be skipped)

Verbose mode
- To stream command outputs (composer, artisan, git) during a run, add the `--verbose` flag as an argument (can be combined with `--dry-run`):

```bash
./scripts/server_deploy.sh --verbose /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app git@github.com:alexgoekjian-sudo/mtl-app.git main
```

When `--verbose` is set the script prints commands and lets their stdout/stderr stream to your terminal; when not set many internal commands are run quietly to keep logs tidy.

Environment flags
- NO_COMPOSER=1  -> skip composer/vendor steps (useful if you uploaded vendor/ manually)
- SKIP_ARTISAN=1 -> skip running `artisan migrate` and `artisan db:seed`
- VENDOR_TAR=path -> custom path to vendor tarball to extract when composer is unavailable
- RELEASE_TAR=path -> custom path to a release tarball (SFTP upload) to extract in non-git mode

Notes & troubleshooting
- The script is tolerant of shared-host limitations: if `git` is not available the script will operate in non-git mode and continue with post-deploy tasks.
- If `tar` is missing on the server, extraction/backup will fail; ask your host to install `tar` or contact me and I can add a `zip` fallback.
- Always run `--dry-run` first on a production server to preview changes.

If you'd like, I can add a `--verbose` flag to show more output, or add a `zip` fallback for systems without `tar`.

Post-deploy health-check
- The deploy script can verify the application after deployment by calling a health endpoint and failing the deploy if the endpoint does not return HTTP 200.

Environment variables
- `HEALTH_URL` — URL to hit for the health check. Default: `http://127.0.0.1/status`. Example: `HEALTH_URL=https://mixtreelangdb.nl/status`.
- `HEALTH_CHECK_INSECURE=1` — set to `1` to add `-k` to curl and allow insecure TLS (useful when self-signed certs are in use).

Example: run with a remote health URL and fail the deploy if unhealthy:

```bash
HEALTH_URL=https://mixtreelangdb.nl/status ./scripts/server_deploy.sh /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app git@github.com:alexgoekjian-sudo/mtl-app.git main
```

If you prefer the script to ignore TLS errors for the health check (not recommended for production), set:

```bash
HEALTH_URL=https://mixtreelangdb.nl/status HEALTH_CHECK_INSECURE=1 ./scripts/server_deploy.sh /home/..../mtl_app ...
```
