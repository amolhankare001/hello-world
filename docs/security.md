# Security foundation

- Laravel session authentication, CSRF protection, escaped Blade output, validated Form Requests, policies, active-account middleware, and role middleware guard the Phase 2 dashboards.
- Login normalizes email addresses, rotates the session identifier, applies account-and-IP rate limiting, updates the last-login timestamp, and records redacted login/logout audit events.
- Password reset returns a generic response to avoid account enumeration and sends reset links only for active portal accounts.
- School, student, and mentor policies enforce school boundaries. Student access is limited to the student's own profile, and mentor access requires a current dated assignment.
- Game, simulation, practice, and assessment scores are server-authoritative. UUID session or attempt keys provide idempotency and replay protection.
- Passwords use Laravel's configured hashing. Production sessions use secure, HTTP-only, same-site cookies over HTTPS.
- Portfolio and media objects are private in S3 and are exposed only through short-lived signed URLs after authorization.
- Audit logs record sensitive administrative changes without storing secrets.
- Production secrets belong in the deployment secret store or instance environment, never source control.
- Database encryption, automated backups, restore drills, dependency scanning, log retention, and alerting are release requirements.

The schema still requires every later write workflow to validate cross-table school ownership inside its transaction. Policies and scoped queries are mandatory even when foreign keys exist.
