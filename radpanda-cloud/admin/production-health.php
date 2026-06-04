<?php
require_once __DIR__ . '/../includes/admin_auth.php';
rp_cloud_admin_require_login();
rp_cloud_ensure_schema($con);

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rp_cloud_health_rows(mysqli $con, string $sql): array
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

function rp_cloud_health_one(mysqli $con, string $sql, string $field = 'total'): string
{
    $res = mysqli_query($con, $sql);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    return (string) ($row[$field] ?? '');
}

function rp_cloud_health_status_class(string $status): string
{
    $status = strtolower(trim($status));
    if (in_array($status, array('ok', 'ready', 'healthy', 'active', 'available', 'returned', 'reported', 'sent', 'received'), true)) {
        return 'good';
    }
    if (in_array($status, array('watch', 'pending', 'warning', 'queued', 'received_zip_only', 'assigned', 'sent_to_remotepanda', 'in_progress', 'busy', 'away'), true)) {
        return 'work';
    }
    if (in_array($status, array('action', 'failed', 'error', 'inactive', 'offline', 'stale', 'down'), true)) {
        return 'bad';
    }
    return 'neutral';
}

function rp_cloud_health_event_label(string $eventType): string
{
    $labels = array(
        'operator_auto_assigned_order' => 'Operator auto-assigned order',
        'operator_released_remote_import' => 'Operator released remote import',
        'operator_requeued_return' => 'Operator requeued return',
        'sync_upload_received' => 'Clinic uploaded study',
        'report_return_received' => 'Report returned from Remotepanda',
        'report_return_feed' => 'Clinic fetched returned report',
    );
    return $labels[$eventType] ?? ucwords(str_replace('_', ' ', $eventType));
}

function rp_cloud_health_log_status(string $path, int $freshSeconds = 180): array
{
    if (!is_file($path)) {
        return array('status' => 'action', 'label' => 'Missing', 'time' => '-', 'age' => null, 'tail' => array());
    }
    $mtime = filemtime($path);
    $age = time() - (int) $mtime;
    $tail = array();
    try {
        $file = new SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $start = max(0, $lastLine - 5);
        $file->seek($start);
        while (!$file->eof()) {
            $line = trim((string) $file->fgets());
            if ($line !== '') {
                $tail[] = $line;
            }
        }
        $tail = array_slice($tail, -5);
    } catch (Throwable $e) {
        $tail = array('Could not read log tail: ' . $e->getMessage());
    }
    return array(
        'status' => $age <= $freshSeconds ? 'ok' : ($age <= 900 ? 'watch' : 'stale'),
        'label' => $age <= $freshSeconds ? 'Fresh' : ($age <= 900 ? 'Watch' : 'Stale'),
        'time' => date('Y-m-d H:i:s', (int) $mtime),
        'age' => $age,
        'tail' => $tail,
    );
}

function rp_cloud_health_add_check(array &$checks, string $area, string $name, string $status, string $detail, string $action = ''): void
{
    $checks[] = array(
        'area' => $area,
        'name' => $name,
        'status' => $status,
        'detail' => $detail,
        'action' => $action,
    );
}

$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));
    $orderUid = trim((string) ($_POST['order_uid'] ?? ''));

    if ($orderUid === '') {
        $error = 'Missing order UID.';
    } elseif ($action === 'requeue_return') {
        $stmt = mysqli_prepare($con, "UPDATE cloud_report_return_outbox
            SET status = 'queued', last_error = NULL, next_retry_at = NOW(), updated_at = NOW()
            WHERE order_uid = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $orderUid);
            if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) >= 0) {
                $flash = 'Return requeued for clinic pickup.';
                rp_cloud_audit($con, 'operator_requeued_return', 'report_order', $orderUid, '', true, 'Cloud operator requeued report return.');
            } else {
                $error = 'Could not requeue return.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = 'Could not prepare return retry.';
        }
    } elseif ($action === 'release_remote_import') {
        $stmt = mysqli_prepare($con, "UPDATE cloud_report_orders
            SET status = IF(COALESCE(radiologist_username, '') <> '', 'assigned', 'received'), updated_at = NOW()
            WHERE order_uid = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $orderUid);
            if (mysqli_stmt_execute($stmt)) {
                $flash = 'Order released for Remotepanda import.';
                rp_cloud_audit($con, 'operator_released_remote_import', 'report_order', $orderUid, '', true, 'Cloud operator released order for Remotepanda import.');
            } else {
                $error = 'Could not release order.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = 'Could not prepare order release.';
        }
    } elseif ($action === 'auto_assign_order') {
        $rows = rp_cloud_health_rows($con, "SELECT * FROM cloud_report_orders WHERE order_uid = '" . mysqli_real_escape_string($con, $orderUid) . "' LIMIT 1");
        $order = $rows[0] ?? null;
        if (!$order) {
            $error = 'Order not found.';
        } else {
            $assignment = rp_cloud_find_assignment_radiologist($con, (string) ($order['clinic_id'] ?? ''), (string) ($order['modality'] ?? ''), (string) ($order['procedure_name'] ?? ''));
            if (empty($assignment['username'])) {
                $error = 'No available radiologist matched this order.';
            } else {
                $username = (string) $assignment['username'];
                $radiologistId = (int) ($assignment['id'] ?? 0);
                $stmt = mysqli_prepare($con, "UPDATE cloud_report_orders
                    SET radiologist_username = ?, radiologist_id = ?, status = 'assigned',
                        assigned_at = COALESCE(assigned_at, NOW()), updated_at = NOW()
                    WHERE order_uid = ? LIMIT 1");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'sis', $username, $radiologistId, $orderUid);
                    if (mysqli_stmt_execute($stmt)) {
                        $flash = 'Order assigned to ' . $username . ' and released for Remotepanda import.';
                        rp_cloud_audit($con, 'operator_auto_assigned_order', 'report_order', $orderUid, (string) ($order['clinic_id'] ?? ''), true, 'Cloud operator auto-assigned order.', $assignment);
                    } else {
                        $error = 'Could not assign order.';
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error = 'Could not prepare assignment.';
                }
            }
        }
    }
}

$activeClinics = (int) rp_cloud_health_one($con, "SELECT COUNT(*) AS total FROM cloud_clinics WHERE status = 'active'");
$recentClinics = (int) rp_cloud_health_one($con, "SELECT COUNT(*) AS total FROM cloud_clinics WHERE status = 'active' AND last_seen_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
$availableRadiologists = (int) rp_cloud_health_one($con, "SELECT COUNT(*) AS total FROM cloud_radiologists WHERE status = 'active' AND availability_status = 'available'");
$activeRules = (int) rp_cloud_health_one($con, "SELECT COUNT(*) AS total FROM cloud_assignment_rules WHERE status = 'active'");
$totalOrders = (int) rp_cloud_health_one($con, "SELECT COUNT(*) AS total FROM cloud_report_orders");
$openOrders = (int) rp_cloud_health_one($con, "SELECT COUNT(*) AS total FROM cloud_report_orders WHERE status IN ('received','assigned','sent_to_remotepanda','in_progress','reported')");
$unassignedOrders = (int) rp_cloud_health_one($con, "SELECT COUNT(*) AS total FROM cloud_report_orders WHERE status IN ('received','received_zip_only') AND COALESCE(radiologist_username, '') = ''");
$queuedReturns = (int) rp_cloud_health_one($con, "SELECT COUNT(*) AS total FROM cloud_report_return_outbox WHERE status = 'queued'");
$failedReturns = (int) rp_cloud_health_one($con, "SELECT COUNT(*) AS total FROM cloud_report_return_outbox WHERE status = 'failed' OR last_error IS NOT NULL");
$auditFailures24h = (int) rp_cloud_health_one($con, "SELECT COUNT(*) AS total FROM cloud_audit_log WHERE success = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$lastUpload = rp_cloud_health_one($con, "SELECT MAX(created_at) AS value FROM cloud_audit_log WHERE event_type = 'sync_upload_received'", 'value');
$lastReturn = rp_cloud_health_one($con, "SELECT MAX(created_at) AS value FROM cloud_audit_log WHERE event_type = 'report_return_received'", 'value');
$lastFeed = rp_cloud_health_one($con, "SELECT MAX(created_at) AS value FROM cloud_audit_log WHERE event_type = 'report_return_feed'", 'value');

$logDir = realpath(__DIR__ . '/../storage/logs') ?: (__DIR__ . '/../storage/logs');
$workers = array(
    'Clinic Cloud Worker' => rp_cloud_health_log_status($logDir . DIRECTORY_SEPARATOR . 'clinic-cloud-worker.log'),
    'Remotepanda Cloud Sync' => rp_cloud_health_log_status($logDir . DIRECTORY_SEPARATOR . 'remotepanda-cloud-sync.log'),
    'Image Detection Worker' => rp_cloud_health_log_status($logDir . DIRECTORY_SEPARATOR . 'image-detection-worker.log'),
    'Notification Worker' => rp_cloud_health_log_status($logDir . DIRECTORY_SEPARATOR . 'notification-worker.log'),
);

$checks = array();
rp_cloud_health_add_check($checks, 'Core', 'Cloud database', 'ok', 'Cloud schema loaded and dashboard queries completed.');
rp_cloud_health_add_check($checks, 'Clinics', 'Active clinic nodes', $activeClinics > 0 ? 'ok' : 'action', $activeClinics . ' active clinic(s).', 'Register at least one clinic before production.');
rp_cloud_health_add_check($checks, 'Clinics', 'Recent clinic heartbeat', $recentClinics > 0 ? 'ok' : 'watch', $recentClinics . ' active clinic(s) seen in the last 15 minutes.', 'Confirm clinic worker/API key if this is 0 during business hours.');
rp_cloud_health_add_check($checks, 'Radiologists', 'Available radiologists', $availableRadiologists > 0 ? 'ok' : 'action', $availableRadiologists . ' available radiologist(s).', 'Mark at least one radiologist available.');
rp_cloud_health_add_check($checks, 'Routing', 'Assignment rules', $activeRules > 0 ? 'ok' : 'watch', $activeRules . ' active rule(s).', 'Rules are optional, but recommended before multiple clinics.');
rp_cloud_health_add_check($checks, 'Orders', 'Unassigned orders', $unassignedOrders === 0 ? 'ok' : 'action', $unassignedOrders . ' order(s) need assignment.', 'Open Assignment Rules or assign manually.');
rp_cloud_health_add_check($checks, 'Returns', 'Failed returns', $failedReturns === 0 ? 'ok' : 'action', $failedReturns . ' failed return item(s).', 'Inspect return queue and retry.');
rp_cloud_health_add_check($checks, 'Audit', 'Failed events in 24h', $auditFailures24h === 0 ? 'ok' : 'watch', $auditFailures24h . ' failed audit event(s) in 24 hours.', 'Review audit events before pilot starts.');
foreach ($workers as $name => $worker) {
    rp_cloud_health_add_check($checks, 'Workers', $name, (string) $worker['status'], 'Last log update: ' . (string) $worker['time'], 'Open Workers page if stale.');
}

$actionCount = 0;
$watchCount = 0;
foreach ($checks as $check) {
    if ($check['status'] === 'action' || $check['status'] === 'failed' || $check['status'] === 'stale' || $check['status'] === 'down') {
        $actionCount++;
    } elseif ($check['status'] === 'watch' || $check['status'] === 'pending' || $check['status'] === 'warning') {
        $watchCount++;
    }
}
$overallStatus = $actionCount > 0 ? 'action' : ($watchCount > 0 ? 'watch' : 'ready');
$overallLabel = $overallStatus === 'ready' ? 'Ready' : ($overallStatus === 'watch' ? 'Watch' : 'Needs Action');

$recentOrders = rp_cloud_health_rows($con, "SELECT order_uid, clinic_id, patient_name, accession_number, modality, procedure_name, radiologist_username, status, updated_at
    FROM cloud_report_orders
    ORDER BY updated_at DESC, received_at DESC
    LIMIT 8");
$unassignedRows = rp_cloud_health_rows($con, "SELECT order_uid, clinic_id, patient_name, accession_number, modality, procedure_name, status, received_at
    FROM cloud_report_orders
    WHERE status IN ('received','received_zip_only') AND COALESCE(radiologist_username, '') = ''
    ORDER BY received_at ASC
    LIMIT 8");
$remoteRetryRows = rp_cloud_health_rows($con, "SELECT order_uid, clinic_id, patient_name, accession_number, modality, procedure_name, radiologist_username, status, updated_at
    FROM cloud_report_orders
    WHERE status IN ('received','received_zip_only','assigned')
    ORDER BY updated_at ASC
    LIMIT 8");
$problemReturns = rp_cloud_health_rows($con, "SELECT order_uid, clinic_id, accession_number, status, attempts, last_error, updated_at
    FROM cloud_report_return_outbox
    WHERE status = 'failed' OR last_error IS NOT NULL
    ORDER BY updated_at DESC
    LIMIT 8");
$recentFailures = rp_cloud_health_rows($con, "SELECT event_type, entity_type, entity_id, clinic_id, message, created_at
    FROM cloud_audit_log
    WHERE success = 0
    ORDER BY created_at DESC, id DESC
    LIMIT 8");
$operatorAudit = rp_cloud_health_rows($con, "SELECT event_type, entity_type, entity_id, clinic_id, success, message, created_at
    FROM cloud_audit_log
    WHERE event_type LIKE 'operator_%'
    ORDER BY created_at DESC, id DESC
    LIMIT 8");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Radpanda Production Health</title>
    <link rel="icon" type="image/x-icon" href="/radpanda/extensions/images/favicon.png">
    <link href="/radpanda/extensions/css/font-awesome.css" rel="stylesheet">
    <style>
        :root{--navy:#001b36;--red:#ed1b24;--soft:#eef5ff;--line:#cfe0f5;--muted:#52677f;--green:#148a43;--danger:#b42318;--work:#8a5400}
        *{box-sizing:border-box} body{font-family:Arial,sans-serif;background:var(--soft);margin:0;color:#061a33} a{text-decoration:none}
        .cloud-app{min-height:100vh;display:grid;grid-template-columns:250px 1fr}.sidebar{background:#00172e;color:#c9d7e8;min-height:100vh;position:sticky;top:0}
        .brand{height:94px;background:#fff;display:flex;align-items:center;padding:0 22px;border-right:1px solid #e6eef8}.brand img{width:178px;max-width:100%;height:auto}
        .side-nav{padding:22px 0}.side-link{display:flex;align-items:center;gap:12px;color:#c9d7e8;padding:13px 24px;font-size:14px;font-weight:700;border-left:4px solid transparent}
        .side-link:hover,.side-link.active{background:#062746;color:#fff;border-left-color:var(--red)}.side-label{padding:18px 24px 8px;color:#7088a4;font-size:11px;text-transform:uppercase;letter-spacing:.08em;font-weight:800}
        .main{min-width:0}.topbar{height:94px;background:#fff;border-bottom:1px solid #dce7f4;display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:5}
        .page-title h1{margin:0;font-size:24px;color:#001b36}.page-title div{margin-top:5px;color:var(--muted);font-size:13px}.top-actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid var(--line);background:#fff;border-radius:18px;padding:10px 15px;color:#123c68;font-weight:800;font-size:13px;min-height:38px;cursor:pointer}.btn.primary{background:var(--navy);border-color:var(--navy);color:#fff}.btn.green{background:#16a34a;border-color:#16a34a;color:#fff}.btn.warn{background:#f59e0b;border-color:#f59e0b;color:#fff}.btn.small{padding:7px 11px;min-height:30px;font-size:12px}
        .content{padding:28px;max-width:1700px}.grid{display:grid;gap:16px}.stats{grid-template-columns:repeat(5,minmax(0,1fr));margin-bottom:18px}.columns{grid-template-columns:minmax(0,1.2fr) minmax(420px,.8fr);align-items:start}
        .card,.stat{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 10px 24px rgba(6,26,51,.04);overflow:hidden}.stat{padding:18px;border-radius:8px;min-height:104px}
        .stat-label{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#5d7188;font-weight:800}.stat-value{font-size:34px;font-weight:900;margin-top:10px;color:#001b36;line-height:1}.stat-note{font-size:12px;color:var(--muted);margin-top:8px}
        .hero{background:#fff;border:1px solid var(--line);border-radius:12px;padding:22px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;gap:16px;box-shadow:0 10px 24px rgba(6,26,51,.04)}
        .hero h2{margin:0;font-size:30px;color:#001b36}.hero p{margin:7px 0 0;color:var(--muted)}.hero-status{display:grid;gap:8px;justify-items:end}
        .card-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 18px;border-bottom:1px solid #e4edf8}.card-head h2{margin:0;font-size:18px;color:#001b36}.card-body{padding:18px}
        .pill{display:inline-flex;border-radius:999px;padding:7px 10px;font-weight:900;font-size:11px;white-space:nowrap}.pill.good{background:#dcfce7;color:#166534}.pill.work{background:#fff4d6;color:#8a5400}.pill.bad{background:#fee2e2;color:#991b1b}.pill.neutral{background:#eaf2ff;color:#0b3b72}
        .check-list{display:grid;gap:10px}.check{display:grid;grid-template-columns:120px 1fr auto;gap:12px;align-items:start;border:1px solid #e2edf9;border-radius:10px;padding:12px;background:#fbfdff}.check strong{display:block;color:#001b36}.check .area{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#5d7188;font-weight:900}.check .detail{font-size:12px;color:var(--muted);margin-top:4px}.check .action{font-size:12px;color:#8a5400;margin-top:4px;font-weight:700}
        .table-wrap{overflow:auto} table{width:100%;border-collapse:separate;border-spacing:0} th,td{padding:12px 10px;border-bottom:1px solid #e4edf8;text-align:left;font-size:13px;vertical-align:top} th{background:#f6f9fd;color:#38506d;font-size:11px;text-transform:uppercase;letter-spacing:.04em} tr:last-child td{border-bottom:0}
        code{background:#f3f7fc;padding:2px 5px;border-radius:5px;color:#133a67}.muted{color:var(--muted)}.empty{border:1px dashed #bdd3ec;border-radius:10px;padding:18px;color:var(--muted);background:#fbfdff}
        .notice{border-radius:10px;padding:12px 14px;margin-bottom:14px;font-weight:800}.notice.ok{background:#dcfce7;border:1px solid #86efac;color:#166534}.notice.err{background:#fee2e2;border:1px solid #fecaca;color:#991b1b}
        .row-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:10px}.inline{display:inline}.mini-card{border:1px solid #e2edf9;border-radius:10px;background:#fbfdff;padding:12px}.mini-card + .mini-card{margin-top:10px}
        .log-list{display:grid;gap:12px}.log-box{border:1px solid #e2edf9;border-radius:10px;background:#fbfdff;padding:12px}.log-tail{margin-top:8px;background:#071426;color:#d7e8ff;border-radius:8px;padding:10px;font-size:11px;line-height:1.45;max-height:118px;overflow:auto;white-space:pre-wrap}
        @media(max-width:1200px){.stats{grid-template-columns:repeat(2,1fr)}.columns{grid-template-columns:1fr}.hero{align-items:flex-start;flex-direction:column}.hero-status{justify-items:start}}@media(max-width:850px){.cloud-app{grid-template-columns:1fr}.sidebar{display:none}.topbar{height:auto;min-height:92px;align-items:flex-start;padding:18px;gap:14px;flex-direction:column}.content{padding:18px}.stats{grid-template-columns:1fr}.top-actions{justify-content:flex-start}.check{grid-template-columns:1fr}}
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
            <a class="side-link active" href="production-health.php"><i class="fa fa-check-circle"></i> Production Health</a>
            <a class="side-link" href="workers.php"><i class="fa fa-cogs"></i> Workers</a>
            <a class="side-link" href="../api/health.php" target="_blank"><i class="fa fa-heartbeat"></i> Health API</a>
        </nav>
    </aside>
    <main class="main">
        <header class="topbar">
            <div class="page-title">
                <h1>Production Health</h1>
                <div>One cockpit for launch readiness, sync health, and operational blockers.</div>
            </div>
            <div class="top-actions">
                <a class="btn" href="workers.php"><i class="fa fa-cogs"></i> Workers</a>
                <a class="btn" href="../api/health.php" target="_blank"><i class="fa fa-heartbeat"></i> Health API</a>
                <a class="btn primary" href="production-health.php"><i class="fa fa-refresh"></i> Refresh</a>
                <a class="btn" href="logout.php"><i class="fa fa-sign-out"></i> Logout</a>
            </div>
        </header>
        <section class="content">
            <?php if ($flash !== '') { ?><div class="notice ok"><?php echo h($flash); ?></div><?php } ?>
            <?php if ($error !== '') { ?><div class="notice err"><?php echo h($error); ?></div><?php } ?>

            <div class="hero">
                <div>
                    <h2>Production Status: <?php echo h($overallLabel); ?></h2>
                    <p><?php echo h($actionCount); ?> action item(s), <?php echo h($watchCount); ?> watch item(s). Use this page before and during every pilot session.</p>
                </div>
                <div class="hero-status">
                    <span class="pill <?php echo h(rp_cloud_health_status_class($overallStatus)); ?>"><?php echo h($overallLabel); ?></span>
                    <span class="muted">Last upload <?php echo h($lastUpload ?: '-'); ?></span>
                    <span class="muted">Last return <?php echo h($lastReturn ?: '-'); ?></span>
                </div>
            </div>

            <section class="grid stats">
                <div class="stat"><div class="stat-label">Total Orders</div><div class="stat-value"><?php echo h($totalOrders); ?></div><div class="stat-note">Cloud cases received</div></div>
                <div class="stat"><div class="stat-label">Open Orders</div><div class="stat-value"><?php echo h($openOrders); ?></div><div class="stat-note">Still in workflow</div></div>
                <div class="stat"><div class="stat-label">Unassigned</div><div class="stat-value"><?php echo h($unassignedOrders); ?></div><div class="stat-note">Needs routing</div></div>
                <div class="stat"><div class="stat-label">Queued Returns</div><div class="stat-value"><?php echo h($queuedReturns); ?></div><div class="stat-note">Waiting for clinic pickup</div></div>
                <div class="stat"><div class="stat-label">Failures 24h</div><div class="stat-value"><?php echo h($auditFailures24h); ?></div><div class="stat-note">Audit failures</div></div>
            </section>

            <section class="grid columns">
                <div class="grid">
                    <div class="card">
                        <div class="card-head"><h2>Readiness Checks</h2><span class="muted"><?php echo h(count($checks)); ?> checks</span></div>
                        <div class="card-body">
                            <div class="check-list">
                                <?php foreach ($checks as $check) { ?>
                                    <div class="check">
                                        <div class="area"><?php echo h($check['area']); ?></div>
                                        <div>
                                            <strong><?php echo h($check['name']); ?></strong>
                                            <div class="detail"><?php echo h($check['detail']); ?></div>
                                            <?php if ($check['action'] !== '') { ?><div class="action"><?php echo h($check['action']); ?></div><?php } ?>
                                        </div>
                                        <span class="pill <?php echo h(rp_cloud_health_status_class((string) $check['status'])); ?>"><?php echo h($check['status']); ?></span>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-head"><h2>Recent Orders</h2><span class="muted">Latest 8</span></div>
                        <div class="card-body">
                            <?php if (empty($recentOrders)) { ?>
                                <div class="empty">No Cloud orders yet.</div>
                            <?php } else { ?>
                                <div class="table-wrap">
                                    <table>
                                        <thead><tr><th>Patient</th><th>Clinic</th><th>Study</th><th>Radiologist</th><th>Status</th><th>Updated</th></tr></thead>
                                        <tbody>
                                            <?php foreach ($recentOrders as $order) { ?>
                                                <tr>
                                                    <td><a href="order.php?order_uid=<?php echo rawurlencode((string) $order['order_uid']); ?>"><strong><?php echo h($order['patient_name']); ?></strong></a><br><span class="muted">Accession <?php echo h($order['accession_number']); ?></span></td>
                                                    <td><?php echo h($order['clinic_id']); ?></td>
                                                    <td><?php echo h($order['procedure_name']); ?><br><span class="muted"><?php echo h($order['modality']); ?></span></td>
                                                    <td><?php echo h($order['radiologist_username'] ?: '-'); ?></td>
                                                    <td><span class="pill <?php echo h(rp_cloud_health_status_class((string) $order['status'])); ?>"><?php echo h($order['status']); ?></span></td>
                                                    <td><?php echo h($order['updated_at']); ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-head"><h2>Manual Recovery</h2><span class="muted">Operator controls</span></div>
                        <div class="card-body">
                            <div class="check-list">
                                <div class="mini-card">
                                    <strong>Unassigned Orders</strong>
                                    <div class="detail">Use this when a clinic sent a case without a radiologist and assignment rules should pick one.</div>
                                    <?php if (empty($unassignedRows)) { ?>
                                        <div class="empty" style="margin-top:10px">No unassigned orders waiting.</div>
                                    <?php } else { ?>
                                        <?php foreach ($unassignedRows as $row) { ?>
                                            <div class="mini-card">
                                                <strong><a href="order.php?order_uid=<?php echo rawurlencode((string) $row['order_uid']); ?>"><?php echo h($row['patient_name'] ?: $row['order_uid']); ?></a></strong>
                                                <div class="detail"><?php echo h($row['clinic_id']); ?> / accession <?php echo h($row['accession_number']); ?> / <?php echo h($row['modality']); ?> <?php echo h($row['procedure_name']); ?></div>
                                                <div class="row-actions">
                                                    <form method="post" class="inline">
                                                        <input type="hidden" name="action" value="auto_assign_order">
                                                        <input type="hidden" name="order_uid" value="<?php echo h($row['order_uid']); ?>">
                                                        <button class="btn green small" type="submit"><i class="fa fa-random"></i> Auto Assign</button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    <?php } ?>
                                </div>

                                <div class="mini-card">
                                    <strong>Remote Import Retry</strong>
                                    <div class="detail">Use this if Remotepanda did not pull a case or a radiologist cannot see it after syncing.</div>
                                    <?php if (empty($remoteRetryRows)) { ?>
                                        <div class="empty" style="margin-top:10px">No remote-import retry candidates.</div>
                                    <?php } else { ?>
                                        <?php foreach ($remoteRetryRows as $row) { ?>
                                            <div class="mini-card">
                                                <strong><a href="order.php?order_uid=<?php echo rawurlencode((string) $row['order_uid']); ?>"><?php echo h($row['patient_name'] ?: $row['order_uid']); ?></a></strong>
                                                <div class="detail"><?php echo h($row['clinic_id']); ?> / accession <?php echo h($row['accession_number']); ?> / assigned to <?php echo h($row['radiologist_username'] ?: '-'); ?></div>
                                                <div class="row-actions">
                                                    <span class="pill neutral"><?php echo h($row['status']); ?></span>
                                                    <form method="post" class="inline">
                                                        <input type="hidden" name="action" value="release_remote_import">
                                                        <input type="hidden" name="order_uid" value="<?php echo h($row['order_uid']); ?>">
                                                        <button class="btn warn small" type="submit"><i class="fa fa-refresh"></i> Release for Sync</button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-head"><h2>Manual Interventions</h2><span class="muted">Latest 8</span></div>
                        <div class="card-body">
                            <?php if (empty($operatorAudit)) { ?>
                                <div class="empty">No operator recovery actions have been recorded.</div>
                            <?php } else { ?>
                                <div class="check-list">
                                    <?php foreach ($operatorAudit as $event) { ?>
                                        <div class="check" style="grid-template-columns:1fr auto">
                                            <div>
                                                <strong><?php echo h(rp_cloud_health_event_label((string) $event['event_type'])); ?></strong>
                                                <div class="detail"><?php echo h($event['created_at']); ?> / <?php echo h($event['clinic_id'] ?: 'cloud'); ?> / <a href="order.php?order_uid=<?php echo rawurlencode((string) $event['entity_id']); ?>"><?php echo h($event['entity_id']); ?></a></div>
                                                <div class="action"><?php echo h($event['message']); ?></div>
                                            </div>
                                            <span class="pill <?php echo ((int) $event['success'] === 1) ? 'good' : 'bad'; ?>"><?php echo ((int) $event['success'] === 1) ? 'OK' : 'Failed'; ?></span>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <aside class="grid">
                    <div class="card">
                        <div class="card-head"><h2>Worker Freshness</h2><a class="btn" href="workers.php">Open Workers</a></div>
                        <div class="card-body">
                            <div class="log-list">
                                <?php foreach ($workers as $name => $worker) { ?>
                                    <div class="log-box">
                                        <strong><?php echo h($name); ?></strong>
                                        <span class="pill <?php echo h(rp_cloud_health_status_class((string) $worker['status'])); ?>" style="float:right"><?php echo h($worker['label']); ?></span>
                                        <div class="muted" style="margin-top:6px">Last update <?php echo h($worker['time']); ?></div>
                                        <div class="log-tail"><?php echo h(implode("\n", (array) $worker['tail'])); ?></div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-head"><h2>Problem Returns</h2><span class="muted"><?php echo h(count($problemReturns)); ?></span></div>
                        <div class="card-body">
                            <?php if (empty($problemReturns)) { ?>
                                <div class="empty">No failed return items.</div>
                            <?php } else { ?>
                                <div class="check-list">
                                    <?php foreach ($problemReturns as $row) { ?>
                                        <div class="check" style="grid-template-columns:1fr auto">
                                            <div>
                                                <strong><a href="order.php?order_uid=<?php echo rawurlencode((string) $row['order_uid']); ?>"><?php echo h($row['order_uid']); ?></a></strong>
                                                <div class="detail"><?php echo h($row['clinic_id']); ?> / accession <?php echo h($row['accession_number']); ?> / attempts <?php echo h($row['attempts']); ?></div>
                                                <div class="action"><?php echo h($row['last_error']); ?></div>
                                                <div class="row-actions">
                                                    <form method="post" class="inline">
                                                        <input type="hidden" name="action" value="requeue_return">
                                                        <input type="hidden" name="order_uid" value="<?php echo h($row['order_uid']); ?>">
                                                        <button class="btn green small" type="submit"><i class="fa fa-repeat"></i> Requeue Return</button>
                                                    </form>
                                                </div>
                                            </div>
                                            <span class="pill bad"><?php echo h($row['status']); ?></span>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-head"><h2>Recent Failures</h2><span class="muted"><?php echo h(count($recentFailures)); ?></span></div>
                        <div class="card-body">
                            <?php if (empty($recentFailures)) { ?>
                                <div class="empty">No failed audit events.</div>
                            <?php } else { ?>
                                <div class="check-list">
                                    <?php foreach ($recentFailures as $row) { ?>
                                        <div class="check" style="grid-template-columns:1fr">
                                            <div>
                                                <strong><?php echo h($row['event_type']); ?></strong>
                                                <div class="detail"><?php echo h($row['created_at']); ?> / <?php echo h($row['clinic_id']); ?> / <?php echo h($row['entity_id']); ?></div>
                                                <div class="action"><?php echo h($row['message']); ?></div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </aside>
            </section>
        </section>
    </main>
</div>
</body>
</html>
