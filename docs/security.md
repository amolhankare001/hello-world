# Security foundation

- Laravel authentication, CSRF protection, escaped Blade output, validated Form Requests, policies, and role middleware will guard every protected workflow.
- School-owned queries and policies must enforce school boundaries. Student policies must prevent access to every other student's records.
- Game, simulation, practice, and assessment scores are server-authoritative. UUID session or attempt keys provide idempotency and replay protection.
- Passwords use Laravel's configured hashing. Production sessions use secure, HTTP-only, same-site cookies over HTTPS.
- Portfolio and media objects are private in S3 and are exposed only through short-lived signed URLs after authorization.
- Audit logs record sensitive administrative changes without storing secrets.
- Production secrets belong in the deployment secret store or instance environment, never source control.
- Database encryption, automated backups, restore drills, dependency scanning, log retention, and alerting are release requirements.

The database schema alone does not provide tenant isolation. Phase 2 must add policies, middleware, and explicit isolation tests before any student data is exposed.
