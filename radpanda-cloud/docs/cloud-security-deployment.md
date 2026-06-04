# Radpanda Cloud Security Deployment

Use this checklist before pointing `radpanda.cloud` at the public hosting account.

## Required Environment Values

Set these in the hosting environment or cPanel PHP environment configuration:

`RP_CLOUD_DB_HOST`
`RP_CLOUD_DB_USER`
`RP_CLOUD_DB_PASS`
`RP_CLOUD_DB_NAME`
`RP_CLOUD_PUBLIC_BASE_URL=https://radpanda.cloud`
`RP_CLOUD_ADMIN_USER`
`RP_CLOUD_ADMIN_PASSWORD_HASH`
`RP_CLOUD_REQUIRE_HASHED_ADMIN_PASSWORD=1`

Do not use `RP_CLOUD_ADMIN_PASSWORD` on production except as a temporary emergency fallback.

Generate the admin password hash locally:

`php C:\xampp\htdocs\radpanda-cloud\tools\create-admin-password-hash.php`

## Admin Protection

The Cloud admin area now has:

- HttpOnly session cookie scoped to `/radpanda-cloud/admin`
- CSRF token on login
- Login rate limiting by IP and browser
- Idle timeout, default 30 minutes
- Absolute session timeout, default 8 hours
- No-store cache headers
- Frame and content-type protection headers

## API Keys

Clinic nodes should use per-clinic API keys stored as hashes in Cloud, not one shared global key.

`RP_CLOUD_SYNC_KEY` should stay empty in production once all clinics have their own keys. If it is set, it behaves as a global override key and should be treated as highly sensitive.

## Public Health Endpoint

`/api/health.php` intentionally returns only minimal status:

- success
- service
- status
- time

Detailed production health remains behind Cloud admin login.

## Hosting Notes

- Enable HTTPS before sending real studies.
- Keep `storage/` outside public listing and block direct browsing if the host allows it.
- Increase PHP upload limits enough for study packages.
- Configure cron jobs for Cloud/Remotepanda workers if the production host runs them.
- Keep database backups enabled at the hosting layer.
