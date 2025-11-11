# Lumen scaffold (minimal)

This repository contains a minimal Lumen scaffold so you can deploy a PHP backend on Cloud86 / Plesk.

Next steps (on server or locally):

1. Install composer dependencies:

```bash
composer install
```

2. Create `.env` from `.env.example` and edit DB and service credentials.

3. Generate app key and run migrations (after composer install):

```bash
php artisan key:generate
php artisan migrate
```

4. Point Plesk document root to `public/` or configure to use `public/index.php` as the webroot.

Note: This is a minimal scaffold; you should expand controllers, middleware, services and tests as needed.
