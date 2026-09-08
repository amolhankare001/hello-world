# AWS deployment foundation

## Target topology

```text
Internet
  -> HTTPS / Nginx
  -> EC2 / PHP-FPM / Laravel
  -> RDS MySQL 8
  -> private S3 bucket
```

Use a dedicated VPC, place RDS in private subnets, restrict security groups to required flows, and issue TLS certificates through an approved certificate workflow.

## Application release

1. Build a versioned application artifact after Composer, PHPUnit, Pint, and Vite checks pass.
2. Install production Composer dependencies with optimized autoloading.
3. Configure environment secrets outside the artifact.
4. Run `php artisan migrate --force` as a controlled release step.
5. Cache configuration, events, routes, and views.
6. Reload PHP-FPM and restart queue workers without dropping active requests.
7. Execute health checks and a smoke test before completing the release.

## Required production configuration

- `APP_ENV=production`, `APP_DEBUG=false`, and a generated application key.
- RDS credentials with least-privilege application access.
- Secure database/session/cache/queue drivers.
- Private S3 bucket, blocked public access, encryption, lifecycle rules, and narrowly scoped IAM.
- Scheduled Laravel tasks and supervised queue workers.
- Centralized logs, alarms, uptime checks, automated RDS snapshots, and tested restores.

Do not deploy the Phase 1 foundation as a student-facing system; authentication, authorization, validation, and complete user workflows are required first.
