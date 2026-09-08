# Testing strategy

## Phase 1

- Fresh migration and rollback behavior.
- Foreign-key and unique constraints.
- Model relationships and attribute casts.
- Development seeder counts and required records.
- SQLite and MySQL schema compatibility.
- Production frontend asset compilation.

## Phase 2

- Login form, valid and invalid credentials, inactive account/school denial, session rotation behavior, and audited logout.
- Password-reset link delivery, generic unknown-account response, token-based password update, and password validation.
- Role middleware for protected dashboards.
- Student policy coverage for own-profile, school-admin, current mentor-assignment, expired assignment, and cross-school denial.
- School dashboard tenant-scoped counts and mentor dashboard current-assignment filtering.

## Later phases

Feature coverage will follow each vertical product slice:

- Role, school, mentor-assignment, and student isolation.
- Question evaluation, attempts, duplicate submission, expiration, and refresh recovery.
- Pre-test diagnosis, post-test improvement, mastery, recommendation, XP, streak, and badge calculations.
- Portfolio permissions, reports, timezone boundaries, deleted content, and missing-data behavior.

Tests must assert authorization and observable outcomes rather than framework implementation details. Browser tests will cover core student and mentor journeys once those interfaces exist.
