# Local development

## MySQL with Docker

The Compose service runs MySQL 8 on port 3306 and persists data in the `mysql-data` volume.

```sh
docker compose up -d mysql
docker compose ps
php artisan migrate:fresh --seed
```

The development credentials in `.env.example` are intentionally local-only. Never reuse them in a shared or production environment.

## SQLite for tests

PHPUnit uses an in-memory SQLite database. This keeps the automated test loop fast while migration tests ensure the same schema can be created from scratch. MySQL-specific behavior must also be checked before production releases.

## Seed data

`DatabaseSeeder` creates:

- Four system roles.
- One demonstration school and academic year.
- Standard 4, Division A.
- One school administrator and one mentor.
- Fifteen assigned and enrolled students.
- Marathi and Mathematics subjects with 15 initial skills each.
- Initial holistic domains, a badge, and empty skill-progress rows.

All seeded users use `password` and `.test` email addresses.

## Application processes

For focused work, run the web server and Vite separately:

```sh
php artisan serve
npm run dev
```

Queue workers become required when queued reports and notifications are introduced in later phases.
