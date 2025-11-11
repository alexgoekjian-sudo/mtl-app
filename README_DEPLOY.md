# Deploy guide (Plesk / Cloud86)

This file documents the recommended deploy steps for Cloud86 (Plesk) shared hosting. Use the `.plesk/deploy.sh` script as the post-deploy hook in Plesk Git deployment.

Quick steps:

1. Create subdomain in Plesk (e.g. `app.yourschool.tld`) and note the document root.
2. Create a MySQL database and user for the app in Plesk.
3. Add a Git repository in Plesk or push your repo via SSH. Configure automatic deployment to the site.
4. Configure Plesk to run `.plesk/deploy.sh` after each deploy (post-deploy hook).
5. Upload `.env` (edit values) and ensure `APP_URL` and DB credentials are correct.
6. Use Plesk Scheduled Tasks for Laravel scheduler: run `php /path/to/artisan schedule:run` every minute.

If composer is not available on the server, run `composer install` locally and upload the `vendor/` directory (not recommended for long-term).

See `deploy.sh` and `.plesk/deploy.sh` for automation helpers.
