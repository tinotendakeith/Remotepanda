<?php
require_once __DIR__ . '/../includes/admin_auth.php';
rp_cloud_admin_require_login();
rp_cloud_ensure_schema($con);

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rp_cloud_admin_scalar(mysqli $con, string $sql, string $field = 'total'): string
{
    $res = mysqli_query($con, $sql);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    return (string) ($row[$field] ?? '');
}

function rp_cloud_admin_rows(mysqli $con, string $sql): array
{
    $rows = array();
    $res = mysqli_query($con, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function rp_cloud_admin_status_class(string $status): string
{
    $status = strtolower(trim($status));
    if (in_array($status, array('ok', 'active', 'sent', 'returned', 'healthy'), true)) {
        return 'good';
    }
    if (in_array($status, array('queued', 'warning', 'pending'), true)) {
        return 'work';
    }
    if (in_array($status, array('failed', 'stale', 'error'), true)) {
        return 'bad';
    }
    return 'neutral';
}

function rp_cloud_latest_clinic_backup(): array
{
    $files = glob('C:/xampp/htdocs/radpanda/storage/backups/radpanda-clinic-backup-*.zip');
    if (!is_array($files) || empty($files)) {
        return array('path' => '', 'time' => '', 'age_hours' => null, 'health' => 'pending');
    }

    usort($files, function ($a, $b) {
        return filemtime($b) <=> filemtime($a);
    });

    $latest = $files[0];
    $mtime = filemtime($latest);
    $ageHours = $mtime ? floor((time() - $mtime) / 3600) : null;
    $health = ($ageHours !== null && $ageHours <= 30) ? 'ok' : 'stale';

    return array(
        'path' => $latest,
        'time' => $mtime ? date('Y-m-d H:i:s', $mtime) : '',
        'age_hours' => $ageHours,
        'health' => $health,
    );
}

function rp_cloud_latest_restore_drill(): array
{
    $path = 'C:/xampp/htdocs/radpanda/storage/backups/restore-drill-last.json';
    if (!is_file($path)) {
        return array('path' => $path, 'time' => '', 'age_hours' => null, 'health' => 'pending', 'message' => 'No restore drill has been run yet.');
    }

    $raw = file_get_contents($path);
    $data = $raw !== false ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        return array('path' => $path, 'time' => '', 'age_hours' => null, 'health' => 'error', 'message' => 'Restore drill result file is unreadable.');
    }

    $finished = (string) ($data['finished_at'] ?? '');
    $finishedTs = $finished !== '' ? strtotime($finished) : false;
    $ageHours = $finishedTs ? floor((time() - $finishedTs) / 3600) : null;
    $ok = !empty($data['ok']);
    $health = $ok ? (($ageHours !== null && $ageHours <= 168) ? 'ok' : 'stale') : 'error';

    return array(
        'path' => $path,
        'time' => $finished,
        'age_hours' => $ageHours,
        'health' => $health,
        'message' => (string) ($data['message'] ?? ''),
        'table_count' => (int) ($data['table_count'] ?? 0),
        'backup_path' => (string) ($data['backup_path'] ?? ''),
    );
}

$cloudLastUpload = rp_cloud_admin_scalar($con, "SELECT MAX(created_at) AS value FROM cloud_audit_log WHERE event_type='sync_upload_received'", 'value');
$cloudLastReturn = rp_cloud_admin_scalar($con, "SELECT MAX(created_at) AS value FROM cloud_audit_log WHERE event_type='report_return_received'", 'value');
$cloudLastFeed = rp_cloud_admin_scalar($con, "SELECT MAX(created_at) AS value FROM cloud_audit_log WHERE event_type='report_return_feed'", 'value');
$queuedReturns = (int) rp_cloud_admin_scalar($con, "SELECT COUNT(*) AS total FROM cloud_report_return_outbox WHERE status='queued'");
$failedReturns = (int) rp_cloud_admin_scalar($con, "SELECT COUNT(*) AS total FROM cloud_report_return_outbox WHERE status='failed' OR last_error IS NOT NULL");
$pendingOrders = (int) rp_cloud_admin_scalar($con, "SELECT COUNT(*) AS total FROM cloud_report_orders WHERE status IN ('received','sent_to_remotepanda','assigned','reported')");
$auditFailures = (int) rp_cloud_admin_scalar($con, "SELECT COUNT(*) AS total FROM cloud_audit_log WHERE success=0");
$latestAudit = rp_cloud_admin_rows($con, "SELECT event_type, clinic_id, success, message, created_at FROM cloud_audit_log ORDER BY created_at DESC, id DESC LIMIT 10");
$latestBackup = rp_cloud_latest_clinic_backup();
$latestRestoreDrill = rp_cloud_latest_restore_drill();

$workers = array(
    array(
        'name' => 'Clinic Cloud Worker',
        'owner' => 'Radpanda Clinic Node',
        'purpose' => 'Uploads queued studies to Cloud and pulls returned reports back into reception.',
        'command' => 'powershell -NoProfile -ExecutionPolicy Bypass -File C:\\xampp\\htdocs\\radpanda-cloud\\tools\\run-clinic-cloud-worker.ps1',
        'cadence' => 'Every 1 minute',
        'health' => $cloudLastUpload !== '' || $cloudLastFeed !== '' ? 'ok' : 'pending',
        'last_seen' => max($cloudLastUpload, $cloudLastFeed),
    ),
    array(
        'name' => 'Remotepanda Cloud Sync',
        'owner' => 'Remotepanda',
        'purpose' => 'Imports assigned Cloud cases and pushes finalized reports back to Cloud.',
        'command' => 'powershell -NoProfile -ExecutionPolicy Bypass -File C:\\xampp\\htdocs\\radpanda-cloud\\tools\\run-remotepanda-cloud-sync.ps1',
        'cadence' => 'Every 1 minute',
        'health' => $cloudLastReturn !== '' ? 'ok' : 'pending',
        'last_seen' => $cloudLastReturn,
    ),
    array(
        'name' => 'Image Detection Worker',
        'owner' => 'Radpanda Clinic Node',
        'purpose' => 'Polls Orthanc for received images and updates the reception Images Received queue.',
        'command' => 'powershell -NoProfile -ExecutionPolicy Bypass -File C:\\xampp\\htdocs\\radpanda-cloud\\tools\\run-image-detection-worker.ps1',
        'cadence' => 'Every 1 minute',
        'health' => 'pending',
        'last_seen' => '',
    ),
    array(
        'name' => 'Notification Worker',
        'owner' => 'Radpanda Clinic Node',
        'purpose' => 'Creates operational alerts for reception, radiographers, and admins.',
        'command' => 'powershell -NoProfile -ExecutionPolicy Bypass -File C:\\xampp\\htdocs\\radpanda-cloud\\tools\\run-notification-worker.ps1',
        'cadence' => 'Every 1 minute',
        'health' => 'pending',
        'last_seen' => '',
    ),
    array(
        'name' => 'Clinic Backup Worker',
        'owner' => 'Radpanda Clinic Node',
        'purpose' => 'Creates a daily database and clinic document backup, then prunes old backup archives.',
        'command' => 'powershell -NoProfile -ExecutionPolicy Bypass -File C:\\xampp\\htdocs\\radpanda-cloud\\tools\\run-clinic-backup.ps1',
        'cadence' => 'Daily at 23:30',
        'health' => (string) ($latestBackup['health'] ?? 'pending'),
        'last_seen' => (string) ($latestBackup['time'] ?? ''),
    ),
    array(
        'name' => 'Restore Drill',
        'owner' => 'Admin',
        'purpose' => 'Validates the latest clinic backup by importing it into a separate drill database.',
        'command' => 'powershell -NoProfile -ExecutionPolicy Bypass -File C:\\xampp\\htdocs\\radpanda-cloud\\tools\\test-clinic-backup-restore.ps1',
        'cadence' => 'Weekly before pilot review',
        'health' => (string) ($latestRestoreDrill['health'] ?? 'pending'),
        'last_seen' => (string) ($latestRestoreDrill['time'] ?? ''),
    ),
);

$taskUser = '%USERDOMAIN%\\%USERNAME%';
$taskCommands = array(
    'Clinic Cloud Worker' => 'schtasks /Create /SC MINUTE /MO 1 /TN "Radpanda Clinic Cloud Worker" /TR "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe -NoProfile -ExecutionPolicy Bypass -File C:\\xampp\\htdocs\\radpanda-cloud\\tools\\run-clinic-cloud-worker.ps1" /RU "' . $taskUser . '" /IT /F',
    'Remotepanda Cloud Sync' => 'schtasks /Create /SC MINUTE /MO 1 /TN "Remotepanda Cloud Sync" /TR "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe -NoProfile -ExecutionPolicy Bypass -File C:\\xampp\\htdocs\\radpanda-cloud\\tools\\run-remotepanda-cloud-sync.ps1" /RU "' . $taskUser . '" /IT /F',
    'Image Detection Worker' => 'schtasks /Create /SC MINUTE /MO 1 /TN "Radpanda Orthanc Image Detection" /TR "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe -NoProfile -ExecutionPolicy Bypass -File C:\\xampp\\htdocs\\radpanda-cloud\\tools\\run-image-detection-worker.ps1" /RU "' . $taskUser . '" /IT /F',
    'Notification Worker' => 'schtasks /Create /SC MINUTE /MO 1 /TN "Radpanda Notification Worker" /TR "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe -NoProfile -ExecutionPolicy Bypass -File C:\\xampp\\htdocs\\radpanda-cloud\\tools\\run-notification-worker.ps1" /RU "' . $taskUser . '" /IT /F',
    'Clinic Backup Worker' => 'schtasks /Create /SC DAILY /ST 23:30 /TN "Radpanda Clinic Backup" /TR "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe -NoProfile -ExecutionPolicy Bypass -File C:\\xampp\\htdocs\\radpanda-cloud\\tools\\run-clinic-backup.ps1" /RU "' . $taskUser . '" /IT /F',
    'Restore Drill' => 'C:\\xampp\\htdocs\\radpanda-cloud\\tools\\test-clinic-backup-restore.cmd',
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Radpanda Cloud Workers</title>
    <link rel="icon" type="image/x-icon" href="/radpanda/extensions/images/favicon.png">
    <link href="/radpanda/extensions/css/font-awesome.css" rel="stylesheet">
    <style>
        :root{--navy:#001b36;--red:#ed1b24;--soft:#eef5ff;--line:#cfe0f5;--muted:#52677f;--green:#148a43;--danger:#b42318}
        *{box-sizing:border-box}
        body{font-family:Arial,sans-serif;background:var(--soft);margin:0;color:#061a33}
        a{text-decoration:none}
        .cloud-app{min-height:100vh;display:grid;grid-template-columns:250px 1fr}
        .sidebar{background:#00172e;color:#c9d7e8;min-height:100vh;position:sticky;top:0}
        .brand{height:94px;background:#fff;display:flex;align-items:center;padding:0 22px;border-right:1px solid #e6eef8}
        .brand img{width:178px;max-width:100%;height:auto}
        .side-nav{padding:22px 0}
        .side-link{display:flex;align-items:center;gap:12px;color:#c9d7e8;padding:13px 24px;font-size:14px;font-weight:700;border-left:4px solid transparent}
        .side-link:hover,.side-link.active{background:#062746;color:#fff;border-left-color:var(--red)}
        .side-label{padding:18px 24px 8px;color:#7088a4;font-size:11px;text-transform:uppercase;letter-spacing:.08em;font-weight:800}
        .main{min-width:0}
        .topbar{height:94px;background:#fff;border-bottom:1px solid #dce7f4;display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:5}
        .page-title h1{margin:0;font-size:24px;color:#001b36}.page-title div{margin-top:5px;color:var(--muted);font-size:13px}
        .top-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid var(--line);background:#fff;border-radius:18px;padding:10px 15px;color:#123c68;font-weight:800;font-size:13px;min-height:38px}
        .btn.primary{background:var(--navy);border-color:var(--navy);color:#fff}
        .content{padding:28px;max-width:1500px}
        .grid{display:grid;gap:16px}.stats{grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:18px}.columns{grid-template-columns:1.25fr .9fr;align-items:start}
        .card,.stat{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 10px 24px rgba(6,26,51,.04)}
        .card{overflow:hidden}.card-head{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:16px 18px;border-bottom:1px solid #e4edf8}.card-head h2{margin:0;font-size:18px;color:#001b36}.card-body{padding:18px}
        .stat{padding:18px;min-height:104px;border-radius:8px}.stat-label{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#5d7188;font-weight:800}.stat-value{font-size:34px;font-weight:900;margin-top:10px;color:#001b36;line-height:1}.stat-note{font-size:12px;color:var(--muted);margin-top:8px}
        table{width:100%;border-collapse:separate;border-spacing:0}th,td{padding:12px 10px;border-bottom:1px solid #e4edf8;text-align:left;font-size:13px;vertical-align:top}th{background:#f6f9fd;color:#38506d;font-size:11px;text-transform:uppercase;letter-spacing:.04em}tr:last-child td{border-bottom:0}
        code{display:block;background:#f3f7fc;padding:8px 10px;border-radius:8px;color:#133a67;word-break:break-all;margin-top:6px}
        .pill{display:inline-flex;border-radius:999px;padding:6px 9px;font-weight:900;font-size:11px;white-space:nowrap}.pill.good{background:#dcfce7;color:#166534}.pill.work{background:#fff4d6;color:#8a5400}.pill.bad{background:#fee2e2;color:#991b1b}.pill.neutral{background:#eaf2ff;color:#0b3b72}
        .mini-list{display:grid;gap:10px}.mini-row{border:1px solid #e4edf8;border-radius:10px;padding:12px 14px;background:#fbfdff}.mini-row strong{font-size:13px;color:#001b36}
        .muted{color:var(--muted)}.ok{color:var(--green);font-weight:800}.error{color:var(--danger);font-weight:800}
        @media(max-width:1150px){.stats{grid-template-columns:repeat(2,1fr)}.columns{grid-template-columns:1fr}}
        @media(max-width:850px){.cloud-app{grid-template-columns:1fr}.sidebar{display:none}.topbar{height:auto;align-items:flex-start;padding:18px;gap:14px;flex-direction:column}.content{padding:18px}.stats{grid-template-columns:1fr}}
    </style>
<?php include_once(__DIR__ . '/../includes/skeleton-loader.php'); ?>
</head>
<body>
<div class="cloud-app">
    <aside class="sidebar">
        <div class="brand"><img src="/radpanda/extensions/images/logo.png" alt="Radpanda"></div>
        <nav class="side-nav">
            <a class="side-link" href="index.php"><i class="fa fa-dashboard"></i> Cloud Dashboard</a>
            <a class="side-link" href="index.php#orders"><i class="fa fa-file-text-o"></i> Report Orders</a>
            <a class="side-link" href="clinics.php"><i class="fa fa-hospital-o"></i> Clinics</a>
            <a class="side-link" href="radiologists.php"><i class="fa fa-user-md"></i> Radiologists</a>
            <a class="side-link" href="typists.php"><i class="fa fa-keyboard-o"></i> Typists</a>
            <a class="side-link" href="assignment-rules.php"><i class="fa fa-random"></i> Assignment Rules</a>
            <a class="side-link" href="index.php#returns"><i class="fa fa-cloud-upload"></i> Return Queue</a>
            <a class="side-link" href="index.php#audit"><i class="fa fa-shield"></i> Audit Log</a>
            <div class="side-label">Operations</div>
            <a class="side-link" href="production-health.php"><i class="fa fa-check-circle"></i> Production Health</a>
            <a class="side-link active" href="workers.php"><i class="fa fa-cogs"></i> Workers</a>
            <a class="side-link" href="../api/health.php" target="_blank"><i class="fa fa-heartbeat"></i> Health API</a>
        </nav>
    </aside>
    <main class="main">
        <header class="topbar">
            <div class="page-title">
                <h1>Cloud Workers</h1>
                <div>Automatic sync loop for clinic uploads, remote reporting, returns, images, and alerts.</div>
            </div>
            <div class="top-actions">
                <a class="btn" href="index.php"><i class="fa fa-dashboard"></i> Dashboard</a>
                <a class="btn primary" href="workers.php"><i class="fa fa-refresh"></i> Refresh</a>
                <a class="btn" href="logout.php"><i class="fa fa-sign-out"></i> Logout</a>
            </div>
        </header>
        <section class="content">
            <section class="grid stats">
                <div class="stat"><div class="stat-label">Queued Returns</div><div class="stat-value"><?php echo h($queuedReturns); ?></div><div class="stat-note">Waiting for clinic pickup</div></div>
                <div class="stat"><div class="stat-label">Failed Returns</div><div class="stat-value"><?php echo h($failedReturns); ?></div><div class="stat-note">Need operator attention</div></div>
                <div class="stat"><div class="stat-label">Open Cloud Orders</div><div class="stat-value"><?php echo h($pendingOrders); ?></div><div class="stat-note">Not fully closed yet</div></div>
                <div class="stat"><div class="stat-label">Audit Failures</div><div class="stat-value"><?php echo h($auditFailures); ?></div><div class="stat-note">Rejected or failed events</div></div>
            </section>

            <section class="grid columns">
                <div class="card">
                    <div class="card-head"><h2>Worker Plan</h2><span class="muted">Recommended local launch cadence</span></div>
                    <div class="card-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>Worker</th>
                                    <th>Purpose</th>
                                    <th>Cadence</th>
                                    <th>Health</th>
                                    <th>Command</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($workers as $worker) { ?>
                                    <tr>
                                        <td><strong><?php echo h($worker['name']); ?></strong><br><span class="muted"><?php echo h($worker['owner']); ?></span></td>
                                        <td><?php echo h($worker['purpose']); ?></td>
                                        <td><?php echo h($worker['cadence']); ?></td>
                                        <td><span class="pill <?php echo h(rp_cloud_admin_status_class((string) $worker['health'])); ?>"><?php echo h($worker['health']); ?></span><br><span class="muted"><?php echo h($worker['last_seen'] ?: 'No cloud activity yet'); ?></span></td>
                                        <td><code><?php echo h($worker['command']); ?></code></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <aside class="grid">
                    <div class="card">
                        <div class="card-head"><h2>Windows Task Scheduler</h2></div>
                        <div class="card-body">
                            <div class="mini-list">
                                <?php foreach ($taskCommands as $label => $command) { ?>
                                    <div class="mini-row">
                                        <strong><?php echo h($label); ?></strong>
                                        <code><?php echo h($command); ?></code>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-head"><h2>Recent Cloud Activity</h2></div>
                        <div class="card-body">
                            <div class="mini-list">
                                <?php if (empty($latestAudit)) { ?>
                                    <div class="mini-row"><span class="muted">No audit activity yet.</span></div>
                                <?php } ?>
                                <?php foreach ($latestAudit as $event) { ?>
                                    <div class="mini-row">
                                        <strong><?php echo h($event['event_type']); ?></strong><br>
                                        <span class="muted"><?php echo h($event['created_at']); ?> / <?php echo h($event['clinic_id'] ?: '-'); ?></span><br>
                                        <?php echo ((int) $event['success'] === 1) ? '<span class="ok">OK</span>' : '<span class="error">Failed</span>'; ?>
                                        <span class="muted"><?php echo h($event['message']); ?></span>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-head"><h2>Backup Restore Drill</h2></div>
                        <div class="card-body">
                            <div class="mini-row">
                                <strong>Status</strong><br>
                                <span class="pill <?php echo h(rp_cloud_admin_status_class((string) $latestRestoreDrill['health'])); ?>"><?php echo h($latestRestoreDrill['health']); ?></span>
                                <span class="muted"><?php echo h($latestRestoreDrill['time'] ?: 'Not run yet'); ?></span>
                            </div>
                            <div class="mini-row">
                                <strong>Last result</strong><br>
                                <span class="muted"><?php echo h($latestRestoreDrill['message']); ?></span>
                                <?php if (!empty($latestRestoreDrill['table_count'])) { ?>
                                    <br><span class="ok"><?php echo h($latestRestoreDrill['table_count']); ?> tables imported into drill database</span>
                                <?php } ?>
                            </div>
                            <div class="mini-row">
                                <strong>Run command</strong>
                                <code>C:\xampp\htdocs\radpanda-cloud\tools\test-clinic-backup-restore.cmd</code>
                            </div>
                        </div>
                    </div>
                </aside>
            </section>
        </section>
    </main>
</div>
</body>
</html>
