# Marathi and Mathematics Learning Portal

A Laravel platform for personalized remedial Marathi and Mathematics learning, mentor intervention, and holistic student progress.

Phases 1–4 establish the architecture, normalized database, Eloquent domain model, representative seed data, Bootstrap frontend foundation, secure session authentication, tenant-aware authorization, protected role dashboards, school people-management workflows, curriculum taxonomy, mastery levels, and global or school-specific learning activities.

## Requirements

- PHP 8.3+
- Composer 2
- Node.js 22+
- npm
- Docker with Compose, or MySQL 8

## Local setup

```sh
cp .env.example .env
docker compose up -d mysql
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Run the migration and development seeder against an empty database. Re-running
the seeder on an existing demo dataset can violate unique fixture constraints.

All seeded development accounts use the password `password`:

| Role | Email |
| --- | --- |
| Super administrator | `admin@example.test` |
| School administrator | `school-admin@example.test` |
| Mentor | `mentor@example.test` |
| Students | `student1@example.test` through `student15@example.test` |

These accounts are development fixtures only. Open `/login` and use the account for the role-specific dashboard you want to verify.

Authentication rejects inactive users, users attached to inactive/deleted schools, roleless users, and student or mentor accounts without a matching school-owned profile. Login attempts are rate-limited by normalized email and IP address.

## Quality commands

```sh
vendor/bin/pint --dirty
php artisan test
npm run build
```

## Documentation

- [Architecture and implementation plan](docs/project-plan.md)
- [Local development](docs/local-development.md)
- [Testing strategy](docs/testing.md)
- [Security foundation](docs/security.md)
- [Shared-hosting deployment](docs/deployment.md)
