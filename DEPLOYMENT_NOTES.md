# Deployment Notes

## Migration Issues on Production Server

### Problem
When running `php artisan migrate` with relative paths on the production server, migrations fail with:
```
PHP Fatal error: Cannot declare class [ClassName], because the name is already in use
```

This occurs even with:
- OPcache disabled
- Only one migration file existing
- Fresh class names
- Composer autoload regenerated

### Root Cause
Lumen's migration loader resolves relative paths (`--path=database/migrations/...`) multiple times, causing migration files to be loaded twice and triggering duplicate class declaration errors.

### Solution
**Always use ABSOLUTE paths when running migrations on the production server:**

```bash
# ❌ WRONG - Relative path causes duplicate loading
php artisan migrate --path=database/migrations/20251121_000001_example.php

# ✅ CORRECT - Absolute path works
/opt/alt/php82/usr/bin/php -d opcache.enable=0 -d opcache.enable_cli=0 artisan migrate --path=/home/u5021d9810/domains/mixtreelangdb.nl/mtl_app/database/migrations/20251121_000001_example.php
```

### Migration Command Template

```bash
# For individual migration
/opt/alt/php82/usr/bin/php -d opcache.enable=0 -d opcache.enable_cli=0 artisan migrate --path=/home/u5021d9810/domains/mixtreelangdb.nl/mtl_app/database/migrations/[FILENAME].php

# For all pending migrations (run one at a time)
/opt/alt/php82/usr/bin/php -d opcache.enable=0 -d opcache.enable_cli=0 artisan migrate --path=/home/u5021d9810/domains/mixtreelangdb.nl/mtl_app/database/migrations/20251118_000002_add_course_offering_identifiers.php
/opt/alt/php82/usr/bin/php -d opcache.enable=0 -d opcache.enable_cli=0 artisan migrate --path=/home/u5021d9810/domains/mixtreelangdb.nl/mtl_app/database/migrations/20251119_000001_add_historical_course_fields.php
# ... etc
```

### Server Configuration
- **Server**: u5021d9810@web0091
- **Path**: /home/u5021d9810/domains/mixtreelangdb.nl/mtl_app/
- **PHP Version**: 8.2 (use `/opt/alt/php82/usr/bin/php`)
- **Framework**: Lumen 9.1.6
- **OPcache**: Enabled (disable for migrations with `-d opcache.enable=0 -d opcache.enable_cli=0`)

### Additional Notes
- The issue does NOT occur on local development environments
- Running `php artisan migrate` without `--path` (for all migrations at once) also fails
- The bootstrap/app.php configuration is minimal and doesn't cause the issue
- No symlinks or duplicate migration files exist

### Verified Working Process (November 21, 2025)
1. Upload migration files to server
2. SSH into server: `ssh u5021d9810@web0091`
3. Navigate to app: `cd domains/mixtreelangdb.nl/mtl_app`
4. Run each migration individually with absolute path
5. Verify with: `php artisan migrate:status`

## PHP Version Compatibility
- Lumen 9.x officially supports PHP 8.0, 8.1, 8.2
- PHP 8.3 mostly works but may have warnings
- PHP 8.4 is NOT recommended (too new, potential compatibility issues)
- **Use PHP 8.2** for stability
