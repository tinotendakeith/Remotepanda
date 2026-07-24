# Cloud directory and worker heartbeat setup

This directory contains the Windows worker wrappers used by a clinic node and the
heartbeat publisher consumed by Radpanda Cloud Production Health.

## Deployment order

1. Deploy the cloud schema and APIs:
   - `includes/schema.php`
   - `includes/api.php`
   - `api/radiologist-directory.php`
   - `api/worker-heartbeat.php`
   - `admin/production-health.php`
2. Confirm the clinic exists in Cloud Admin, is active, and has an API key.
3. Configure the same key and clinic identity on the clinic server.
4. Install or update the scheduled tasks.
5. Run each task once manually and confirm Production Health shows a recent
   heartbeat from the expected node.

Do not mark the worker checks healthy manually. They become healthy only after a
successful authenticated heartbeat is stored.

## Required clinic machine variables

Set these as machine-level environment variables on the clinic Windows server:

- `RADPANDA_CLOUD_BASE_URL` — for example `https://radpanda.cloud/cloud`
- `RADPANDA_CLOUD_SYNC_KEY` — the clinic API key or configured global sync key
- `RADPANDA_CLINIC_ID` — the exact active `clinic_uid` registered in Cloud Admin
- `RADPANDA_NODE_UID` — stable appliance identifier; optional, defaults to the
  Windows computer name

Restart the Task Scheduler service or recreate the tasks after changing
machine-level variables so new worker processes receive them.

## Suggested Windows Task Scheduler cadence

Run with a service account that can read the Radpanda installation and execute
PowerShell and PHP.

- `run-clinic-cloud-worker.ps1`: every minute
- `run-image-detection-worker.ps1`: every minute
- `run-notification-worker.ps1`: every two minutes

Recommended task settings:

- Run whether the user is logged on or not.
- Start in `C:\xampp\htdocs\radpanda-cloud\tools`.
- Do not start a new instance when a previous run is still active.
- Restart on failure up to three times with a one-minute delay.
- Stop a task that runs materially longer than its normal batch window.

Example action:

```text
Program: powershell.exe
Arguments: -NoProfile -ExecutionPolicy Bypass -File "C:\xampp\htdocs\radpanda-cloud\tools\run-clinic-cloud-worker.ps1"
```

## Verification

A successful wrapper run writes its normal log and then calls
`publish-worker-heartbeat.ps1`. Production Health should show:

- the worker as Fresh,
- the stable node UID,
- a recent heartbeat timestamp,
- Error only when the underlying worker exits non-zero.

If a heartbeat does not appear:

1. Confirm the clinic is active in Cloud Admin.
2. Confirm the machine clinic ID exactly matches `clinic_uid`.
3. Confirm the API key matches the registered clinic key or global sync key.
4. Test that the clinic can reach
   `/api/worker-heartbeat.php` over HTTPS.
5. Read the relevant worker log for the publisher warning.
6. Confirm the scheduled task uses the intended Windows account and environment.

## Cloud radiologist directory contract

Clinic marketplaces should request:

```text
GET /api/radiologist-directory.php?clinic_id=<clinic_uid>
X-Radpanda-Sync-Key: <clinic key>
```

The endpoint returns only active cloud radiologists. The clinic should:

- cache the last successful response locally,
- send the returned ETag on refresh,
- refresh after at most 60 seconds while the marketplace is open,
- retain clinic-specific selection and fee overrides separately,
- never turn a failed cloud fetch into deletion of the last known directory,
- visibly label cached/stale data when the cloud cannot be reached.

Availability, modalities, contact details, daily limits, and profile notes come
from the cloud registry. Clinic panel membership and clinic-negotiated fees remain
local clinic data.
