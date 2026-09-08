# Shared-hosting deployment foundation

## Target topology

```text
HTTPS
  -> cPanel/Plesk document root pointed at Laravel public/
  -> hosting-provider PHP 8.3+
  -> hosting-provider MySQL 8
```

The application uses ordinary synchronous Laravel web requests, Blade pages, database sessions, and MySQL. It does not require Redis, WebSockets, a process supervisor, or a continuously running queue worker.

## Application release

1. Build a versioned application artifact after Composer, PHPUnit, Pint, and Vite checks pass.
2. Run `composer install --no-dev --classmap-authoritative` and `npm ci && npm run build` before upload when the hosting account does not provide Composer or Node.js.
3. Upload the application outside `public_html` and point the domain document root at its `public` directory. If the host cannot change the document root, place only the contents of `public` in `public_html` and update `index.php` paths to the application directory.
4. Create a MySQL 8 database and a least-privilege application user through the hosting control panel.
5. Configure `.env` on the server and never upload the local environment file.
6. Run `php artisan migrate --force` from SSH or the hosting terminal.
7. Run `php artisan optimize`; ensure `storage` and `bootstrap/cache` are writable by the PHP process.
8. Add the optional scheduler cron below if proactive notification delivery is wanted.
9. Confirm HTTPS, login, reports, a private portfolio download, and an authorized dashboard before opening access to users.

## Required production configuration

- `APP_ENV=production`, `APP_DEBUG=false`, and a generated application key.
- `APP_URL` set to the final HTTPS domain.
- MySQL credentials with access only to the portal database.
- `SESSION_DRIVER=database`, `CACHE_STORE=database`, and `QUEUE_CONNECTION=sync`.
- Secure, HTTP-only session cookies and `SESSION_SECURE_COOKIE=true`.
- `PORTFOLIO_DISK=local` keeps private student files under `storage/app/private`; never move that directory under `public_html`.
- Private uploads stored outside the public web root.
- A daily hosting backup with a documented restore test.
- A once-per-minute cron entry for `php artisan schedule:run` only when proactive notification synchronization is enabled.

Production seed data must not contain development accounts. Rotate any initial administrator password immediately.

## cPanel or Plesk cron

The portal creates notifications synchronously whenever a student or mentor opens a dashboard. For proactive hourly synchronization, add one cron job and replace both paths with values from the hosting account:

```sh
* * * * * /usr/local/bin/php /home/account/learning-portal/artisan schedule:run >> /dev/null 2>&1
```

The scheduled command is overlap-protected and deduplicates every notification. A queue worker, Redis, Supervisor, and WebSockets are not needed.

## Hosting without a configurable document root

The preferred layout keeps the whole Laravel application outside `public_html` and points the domain at `learning-portal/public`. If the provider cannot change the document root:

1. Keep the application in `/home/account/learning-portal`.
2. Copy only the contents of `learning-portal/public` into `/home/account/public_html`.
3. In `public_html/index.php`, change the maintenance, Composer autoload, and bootstrap paths from `../...` to `/home/account/learning-portal/...`.
4. Keep `.env`, `vendor`, `storage`, source code, and portfolio files outside `public_html`.
5. Copy each new production build from `learning-portal/public/build` to `public_html/build`.

Do not copy the full application into the web root and do not create a public storage symlink for portfolios.

## Release commands

Run these in the application directory for each release:

```sh
composer install --no-dev --prefer-dist --classmap-authoritative
php artisan migrate --force
php artisan optimize
php artisan notifications:sync
```

Compile frontend assets before upload if Node.js is unavailable on the hosting account:

```sh
npm ci
npm run build
```

After changing `.env`, run `php artisan optimize:clear` followed by `php artisan optimize`.
