# Lensmania Labs Store + License Backend (Scaffold)

## What this scaffold contains
- Laravel 11 app skeleton tailored to the Lensmania specification.
- Domain models + migrations for all requested tables.
- Quote -> PayPal create -> return/webhook fulfillment flow placeholders.
- License activation/validation/deactivation endpoints with table-driven grace state machine.
- Private build/download token flow placeholder.
- App structure for Filament resources and scheduled PayPal reconciliation command.

## Quick bootstrap on local machine / Hostinger
1. Install PHP 8.2+ and Composer on your machine.
2. Copy `.env.example` to `.env` and fill secrets.
3. Run:
   - `composer install`
   - `php artisan key:generate`
   - `php artisan storage:link`
   - `php artisan migrate --seed`
4. Configure queue worker (database driver):
   - `php artisan queue:work --sleep=3 --tries=3`
5. Configure cron (Hostinger CRON):
   - `* * * * * /usr/bin/php /home/<user>/<path>/artisan schedule:run >> /home/<user>/storage/logs/laravel-schedule.log 2>&1`
   - PayPal reconciliation command already scheduled every 15 minutes:
     `php artisan orders:reconcile-paypal --stale-minutes=20`

## Environment keys
See `.env.example`.
- `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`
- `DB_*` MySQL 8 settings
- `PAYPAL_ENV`, `PAYPAL_CLIENT_ID`, `PAYPAL_SECRET`, `PAYPAL_WEBHOOK_ID`
- `MAIL_*` SMTP values (Brevo recommended)
- `LICENSE_PRIVATE_KEY` and `LICENSE_PUBLIC_KEY`
- `MAX_OFFLINE_GRACE_SECONDS`

## Scope markers
- This repository currently contains scaffold code only in this environment (PHP tooling is unavailable here).
- Run Composer from your normal build environment to install dependencies.
