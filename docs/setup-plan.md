# Repository Setup Plan

## Dependencies

- Install PHP 8.5 and Composer 2 using the Laravel-supported `php.new` installer.
- Use the generated Laravel 13 application and commit its lock files.
- Use Node.js 22+ and npm for Vite assets.
- Use Docker Compose for MySQL 8 so local setup does not depend on a machine-specific MySQL installation.
- Replace the generated Tailwind dependency with the required Bootstrap 5 design foundation.

## Build and verification

1. Install Composer and npm dependencies.
2. Copy `.env.example` to `.env` and generate an application key.
3. Start MySQL and configure the local database variables.
4. Run migrations and development seeders.
5. Build frontend assets with Vite.
6. Run PHPUnit and Laravel Pint.
7. Boot the application and verify the health endpoint and main route.

## Environment blueprint

After Phase 1 works from a clean checkout, propose a repository blueprint that:

- installs PHP 8.5 and Composer;
- installs npm dependencies;
- copies `.env.example` only when `.env` is absent;
- creates the SQLite test database;
- runs a frontend build;
- avoids embedding secrets or machine-specific state.

## Pre-commit hooks

The initial repository has no `.pre-commit-config.yaml`. Phase 1 will not introduce a second hook system; Laravel Pint and PHPUnit remain explicit verification commands until the team chooses a shared hook policy.
