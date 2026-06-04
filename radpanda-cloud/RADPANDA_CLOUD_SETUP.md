# Radpanda Cloud Local Setup

Local project:

- `C:\xampp\htdocs\radpanda-cloud`
- Database: `radpanda_cloud`
- Health check: `http://127.0.0.1/radpanda-cloud/api/health.php`
- Upload endpoint: `http://127.0.0.1/radpanda-cloud/api/report-sync-receiver.php`
- Report return receiver: `http://127.0.0.1/radpanda-cloud/api/report-return-receiver.php`
- Report return feed: `http://127.0.0.1/radpanda-cloud/api/report-return-feed.php`
- Admin list: `http://127.0.0.1/radpanda-cloud/admin/`

DNS plan for `radpanda.cloud`:

- `app.radpanda.cloud` -> cloud admin/dashboard
- `api.radpanda.cloud` -> clinic sync API
- `remote.radpanda.cloud` -> Remotepanda radiologist portal

Clinic node settings when ready to test:

- `sync_cloud_endpoint`: `http://127.0.0.1/radpanda-cloud/api/report-sync-receiver.php`
- `sync_report_return_endpoint`: `http://127.0.0.1/radpanda-cloud/api/report-return-feed.php`
- `sync_cloud_api_key`: blank locally unless `RP_CLOUD_SYNC_KEY` is configured
- `clinic_id`: use a stable value per clinic, for example `local-clinic` in local testing

Per-clinic API keys:

- Local pilot can run without keys while no key is registered for the clinic.
- Once a clinic key is registered in `cloud_clinics.api_key_hash`, that clinic must send the matching key in `sync_cloud_api_key`.
- Generate/register a clinic key:
  - `php C:/xampp/htdocs/radpanda-cloud/tools/create-clinic-key.php local-clinic "Local Clinic" "Main Branch"`
- The command prints the plain API key once. Store it in the clinic node setting `sync_cloud_api_key`.
- For production, also set a global `RP_CLOUD_SYNC_KEY` environment variable if you want every API call to require the same platform-level key.

Current local pilot flow:

1. Radpanda Clinic Node sends study packages to Radpanda Cloud.
2. Remotepanda dashboard uses `Sync Cloud Orders` to import received cloud orders into the existing radiologist worklist.
3. Radiologists finalize reports in Remotepanda.
4. Remotepanda dashboard uses `Push Final Reports` to send only real `RPO-...` cloud report orders back to Radpanda Cloud.
5. Radpanda Clinic Node polls the Radpanda Cloud return feed and applies finalized reports locally.

Local worker commands:

- Clinic node combined worker:
  - `php C:/xampp/htdocs/radpanda/includes/report-cloud-worker.php 3 10`
  - Uploads queued study packages, then pulls returned reports from Cloud.
- Clinic node separate workers:
  - `php C:/xampp/htdocs/radpanda/includes/report-sync-worker.php 3`
  - `php C:/xampp/htdocs/radpanda/includes/report-return-worker.php 10`
- Remotepanda combined worker:
  - `php C:/xampp/htdocs/remotepanda/includes/cloud-sync-worker.php 25 10`
  - Imports new Cloud orders, then pushes finalized reports back to Cloud.
- Remotepanda separate workers:
  - `php C:/xampp/htdocs/remotepanda/includes/cloud-import-worker.php 25`
  - `php C:/xampp/htdocs/remotepanda/includes/cloud-return-worker.php 10`

Suggested local schedule:

- Clinic node worker: every 1 minute.
- Remotepanda worker: every 1 minute.
- Keep the dashboard buttons as a manual fallback during launch.

Windows Task Scheduler examples:

- Clinic node:
  - Program: `C:\xampp\php\php.exe`
  - Arguments: `C:\xampp\htdocs\radpanda\includes\report-cloud-worker.php 3 10`
- Remotepanda:
  - Program: `C:\xampp\php\php.exe`
  - Arguments: `C:\xampp\htdocs\remotepanda\includes\cloud-sync-worker.php 25 10`

cPanel/production cron shape:

- Clinic node cron stays on the clinic server, not on public hosting.
- Radpanda Cloud hosting receives API calls; it does not need to pull from clinic networks.
- Remotepanda hosting should run the Remotepanda combined worker if Remotepanda and Cloud share the hosting environment/database.

Next integration step:

Add per-clinic API keys, then deploy Radpanda Cloud and Remotepanda against production hosting once DNS is pointed.
