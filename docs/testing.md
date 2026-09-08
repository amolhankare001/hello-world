# Testing strategy

## Phase 1

- Fresh migration and rollback behavior.
- Foreign-key and unique constraints.
- Model relationships and attribute casts.
- Development seeder counts and required records.
- SQLite and MySQL schema compatibility.
- Production frontend asset compilation.

## Later phases

Feature coverage will follow each vertical product slice:

- Authentication, rate limiting, session rotation, and logout.
- Role, school, mentor-assignment, and student isolation.
- Question evaluation, attempts, duplicate submission, expiration, and refresh recovery.
- Pre-test diagnosis, post-test improvement, mastery, recommendation, XP, streak, and badge calculations.
- Portfolio permissions, reports, timezone boundaries, deleted content, and missing-data behavior.

Tests must assert authorization and observable outcomes rather than framework implementation details. Browser tests will cover core student and mentor journeys once those interfaces exist.
