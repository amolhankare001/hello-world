# Marathi and Mathematics Learning Portal

A Laravel platform for personalized remedial Marathi and Mathematics learning, mentor intervention, and holistic student progress.

Phase 1 establishes the architecture, normalized database, Eloquent domain model, representative seed data, Bootstrap frontend foundation, and development/deployment documentation. Authentication and product workflows are delivered in later phases.

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

All seeded development accounts use the password `password`:

| Role | Email |
| --- | --- |
| Super administrator | `admin@example.test` |
| School administrator | `school-admin@example.test` |
| Mentor | `mentor@example.test` |
| Students | `student1@example.test` through `student15@example.test` |

These accounts are development fixtures only. Authentication UI is not part of Phase 1.

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
- [AWS deployment foundation](docs/deployment.md)
