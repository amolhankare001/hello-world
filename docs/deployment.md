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
8. Confirm HTTPS, login, and an authorized dashboard before opening access to users.

## Required production configuration

- `APP_ENV=production`, `APP_DEBUG=false`, and a generated application key.
- `APP_URL` set to the final HTTPS domain.
- MySQL credentials with access only to the portal database.
- `SESSION_DRIVER=database`, `CACHE_STORE=database`, and `QUEUE_CONNECTION=sync`.
- Secure, HTTP-only session cookies and `SESSION_SECURE_COOKIE=true`.
- Private uploads stored outside the public web root.
- A daily hosting backup with a documented restore test.
- A once-per-minute cron entry for `php artisan schedule:run` only when scheduled notifications are enabled.

Production seed data must not contain development accounts. Rotate any initial administrator password immediately.
