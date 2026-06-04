<?php
require_once __DIR__ . '/../includes/admin_auth.php';
rp_cloud_admin_require_login();
rp_cloud_ensure_schema($con);

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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

function rp_cloud_admin_one(mysqli $con, string $sql, string $field = 'total'): int
{
    $res = mysqli_query($con, $sql);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    return (int) ($row[$field] ?? 0);
}

function rp_cloud_admin_status_class(string $status): string
{
    $status = strtolower(trim($status));
    if (in_array($status, array('returned', 'reported', 'sent', 'active', 'received', 'available'), true)) {
        return 'good';
    }
    if (in_array($status, array('queued', 'sent_to_remotepanda', 'assigned', 'in_progress', 'busy', 'away'), true)) {
        return 'work';
    }
    if (in_array($status, array('failed', 'error', 'inactive', 'offline'), true)) {
        return 'bad';
    }
    return 'neutral';
}

function rp_cloud_admin_event_label(string $eventType): string
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

$totalOrders = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_report_orders");
$activeClinics = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_clinics WHERE status = 'active'");
$activeRadiologists = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_radiologists WHERE status = 'active'");
$availableRadiologists = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_radiologists WHERE status = 'active' AND availability_status = 'available'");
$queuedReturns = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_report_return_outbox WHERE status = 'queued'");
$failedReturns = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_report_return_outbox WHERE status = 'failed' OR last_error IS NOT NULL");
$reportedOrders = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_report_orders WHERE status IN ('reported','returned')");
$validationFailures = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_audit_log WHERE success = 0");

$orderStatusRows = rp_cloud_admin_rows($con, "SELECT status, COUNT(*) AS total FROM cloud_report_orders GROUP BY status ORDER BY total DESC, status ASC");
$returnStatusRows = rp_cloud_admin_rows($con, "SELECT status, COUNT(*) AS total FROM cloud_report_return_outbox GROUP BY status ORDER BY total DESC, status ASC");
$clinics = rp_cloud_admin_rows($con, "SELECT clinic_uid, clinic_name, default_branch, status, last_seen_at, updated_at FROM cloud_clinics ORDER BY last_seen_at DESC, clinic_uid ASC");
$radiologists = rp_cloud_admin_rows($con, "SELECT username, display_name, email, status, availability_status, modalities FROM cloud_radiologists ORDER BY status = 'active' DESC, availability_status = 'available' DESC, display_name ASC LIMIT 8");
$orders = rp_cloud_admin_rows($con, "SELECT order_uid, clinic_id, branch, patient_name, accession_number, modality, procedure_name, radiologist_username, status, received_at, reported_at, returned_at, updated_at
    FROM cloud_report_orders
    ORDER BY updated_at DESC, received_at DESC
    LIMIT 40");
$returns = rp_cloud_admin_rows($con, "SELECT order_uid, clinic_id, accession_number, status, attempts, last_error, sent_at, updated_at
    FROM cloud_report_return_outbox
    ORDER BY updated_at DESC
    LIMIT 20");
$audit = rp_cloud_admin_rows($con, "SELECT event_type, entity_type, entity_id, clinic_id, success, message, created_at
    FROM cloud_audit_log
    ORDER BY created_at DESC, id DESC
    LIMIT 25");
$operatorAudit = rp_cloud_admin_rows($con, "SELECT event_type, entity_type, entity_id, clinic_id, success, message, created_at
    FROM cloud_audit_log
    WHERE event_type LIKE 'operator_%'
    ORDER BY created_at DESC, id DESC
    LIMIT 8");
$statusAttention = rp_cloud_admin_rows($con, "SELECT order_uid, patient_name, clinic_id, accession_number, radiologist_username, status, updated_at,
        TIMESTAMPDIFF(MINUTE, updated_at, NOW()) AS minutes_waiting
    FROM cloud_report_orders
    WHERE status IN ('received','received_zip_only','assigned','sent_to_remotepanda','in_progress','reported')
    ORDER BY updated_at ASC
    LIMIT 8");

$workerCommands = array(
    'Clinic upload + return' => 'php C:\\xampp\\htdocs\\radpanda\\includes\\report-cloud-worker.php 5 10',
    'Remotepanda import + return' => 'php C:\\xampp\\htdocs\\remotepanda\\includes\\cloud-sync-worker.php 5 10',
    'Remotepanda push returns only' => 'php C:\\xampp\\htdocs\\remotepanda\\includes\\cloud-return-worker.php 5',
);
$usingDefaultAdminPassword = RP_CLOUD_ADMIN_PASSWORD_HASH === '' && RP_CLOUD_ADMIN_PASSWORD === 'radpanda-admin';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Radpanda Cloud Admin</title>
    <link rel="icon" type="image/x-icon" href="/radpanda/extensions/images/favicon.png">
    <link href="/radpanda/extensions/css/font-awesome.css" rel="stylesheet">
    <style>
        :root{--navy:#001b36;--navy2:#08284d;--red:#ed1b24;--blue:#1f73b7;--soft:#eef5ff;--line:#cfe0f5;--muted:#52677f;--green:#148a43;--orange:#a45c00;--danger:#b42318}
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
        .page-title h1{margin:0;font-size:24px;line-height:1.1;color:#001b36}
        .page-title div{margin-top:5px;color:var(--muted);font-size:13px}
        .top-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end}
        .badge{display:inline-flex;align-items:center;border-radius:999px;padding:8px 12px;font-weight:800;font-size:12px;background:#dcfce7;color:#166534}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid var(--line);background:#fff;border-radius:18px;padding:10px 15px;color:#123c68;font-weight:800;font-size:13px;min-height:38px}
        .btn.primary{background:var(--navy);border-color:var(--navy);color:#fff}
        .btn.red{background:var(--red);border-color:var(--red);color:#fff}
        .content{padding:28px;max-width:1700px}
        .grid{display:grid;gap:16px}
        .stats{grid-template-columns:repeat(6,minmax(0,1fr));margin-bottom:18px}
        .columns{grid-template-columns:minmax(0,1.45fr) minmax(370px,.9fr);align-items:start}
        .card,.stat{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 10px 24px rgba(6,26,51,.04)}
        .card{overflow:hidden}
        .card-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 18px;border-bottom:1px solid #e4edf8}
        .card-head h2{margin:0;font-size:18px;color:#001b36}
        .card-body{padding:18px}
        .stat{padding:18px;min-height:108px;border-radius:8px}
        .stat-label{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#5d7188;font-weight:800}
        .stat-value{font-size:34px;font-weight:900;margin-top:10px;color:#001b36;line-height:1}
        .stat-note{font-size:12px;color:var(--muted);margin-top:8px}
        .table-wrap{overflow:auto}
        table{width:100%;border-collapse:separate;border-spacing:0}
        th,td{padding:12px 10px;border-bottom:1px solid #e4edf8;text-align:left;font-size:13px;vertical-align:top}
        th{background:#f6f9fd;color:#38506d;font-size:11px;text-transform:uppercase;letter-spacing:.04em}
        tr:last-child td{border-bottom:0}
        code{background:#f3f7fc;padding:2px 5px;border-radius:5px;color:#133a67}
        .order-link{color:#0b5cad;font-weight:800}
        .empty{border:1px dashed #bdd3ec;border-radius:10px;padding:18px;color:var(--muted);background:#fbfdff}
        .notice{border-radius:10px;padding:12px 14px;margin-bottom:14px;font-weight:800}.notice.warn{background:#fff4d6;border:1px solid #f4cf75;color:#8a5400}
        .pill{display:inline-flex;border-radius:999px;padding:6px 9px;font-weight:900;font-size:11px;white-space:nowrap}
        .pill.good{background:#dcfce7;color:#166534}.pill.work{background:#fff4d6;color:#8a5400}.pill.bad{background:#fee2e2;color:#991b1b}.pill.neutral{background:#eaf2ff;color:#0b3b72}
        .mini-list{display:grid;gap:10px}
        .mini-row{display:flex;justify-content:space-between;gap:12px;align-items:center;border:1px solid #e4edf8;border-radius:10px;padding:12px 14px;background:#fbfdff}
        .mini-row.attention{background:#fff8eb;border-color:#f3d6a0}
        .mini-row.operator{background:#f8fbff;border-color:#bdd3ec}
        .mini-row strong{font-size:13px;color:#001b36}
        .muted{color:var(--muted)}
        .error{color:var(--danger);font-weight:800}
        .ok{color:var(--green);font-weight:800}
        .cmd{display:block;white-space:normal;word-break:break-all;margin-top:6px}
        .mobile-brand{display:none}
        @media(max-width:1250px){.stats{grid-template-columns:repeat(3,1fr)}.columns{grid-template-columns:1fr}}
        @media(max-width:850px){
            .cloud-app{grid-template-columns:1fr}
            .sidebar{display:none}
            .mobile-brand{display:block}
            .topbar{height:auto;min-height:92px;align-items:flex-start;padding:18px;gap:14px;flex-direction:column}
            .content{padding:18px}
            .stats{grid-template-columns:1fr}
            .top-actions{justify-content:flex-start}
        }
    </style>
<?php include_once(__DIR__ . '/../includes/skeleton-loader.php'); ?>
</head>
<body>
<div class="cloud-app">
    <aside class="sidebar">
        <div class="brand"><img src="/radpanda/extensions/images/logo.png" alt="Radpanda"></div>
        <nav class="side-nav">
            <a class="side-link active" href="index.php"><i class="fa fa-dashboard"></i> Cloud Dashboard</a>
            <a class="side-link" href="#orders"><i class="fa fa-file-text-o"></i> Report Orders</a>
            <a class="side-link" href="clinics.php"><i class="fa fa-hospital-o"></i> Clinics</a>
            <a class="side-link" href="radiologists.php"><i class="fa fa-user-md"></i> Radiologists</a>
            <a class="side-link" href="typists.php"><i class="fa fa-keyboard-o"></i> Typists</a>
            <a class="side-link" href="assignment-rules.php"><i class="fa fa-random"></i> Assignment Rules</a>
            <a class="side-link" href="#returns"><i class="fa fa-cloud-upload"></i> Return Queue</a>
            <a class="side-link" href="#audit"><i class="fa fa-shield"></i> Audit Log</a>
            <div class="side-label">Operations</div>
            <a class="side-link" href="production-health.php"><i class="fa fa-check-circle"></i> Production Health</a>
            <a class="side-link" href="workers.php"><i class="fa fa-cogs"></i> Workers</a>
            <a class="side-link" href="../api/health.php" target="_blank"><i class="fa fa-heartbeat"></i> Health API</a>
            <a class="side-link" href="index.php"><i class="fa fa-refresh"></i> Refresh</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="page-title">
                <div class="mobile-brand"><img src="/radpanda/extensions/images/logo.png" alt="Radpanda" style="width:168px;margin-bottom:12px"></div>
                <h1>Radpanda Cloud</h1>
                <div>Sync health, report orders, clinic nodes, and return queue.</div>
            </div>
            <div class="top-actions">
                <a class="btn" href="../api/health.php" target="_blank"><i class="fa fa-heartbeat"></i> Health API</a>
                <a class="btn" href="clinics.php"><i class="fa fa-hospital-o"></i> Clinics</a>
                <a class="btn" href="radiologists.php"><i class="fa fa-user-md"></i> Radiologists</a>
                <a class="btn" href="assignment-rules.php"><i class="fa fa-random"></i> Assignment Rules</a>
                <a class="btn" href="production-health.php"><i class="fa fa-check-circle"></i> Production Health</a>
                <a class="btn" href="workers.php"><i class="fa fa-cogs"></i> Workers</a>
                <a class="btn primary" href="index.php"><i class="fa fa-refresh"></i> Refresh</a>
                <a class="btn red" href="logout.php"><i class="fa fa-sign-out"></i> Logout</a>
                <span class="badge">Local Hub</span>
            </div>
        </header>

        <section class="content">
            <?php if ($usingDefaultAdminPassword) { ?>
                <div class="notice warn">Cloud admin is using the default local password. Generate a hash with <code>php C:\xampp\htdocs\radpanda-cloud\tools\create-admin-password-hash.php "strong-password"</code> and set <code>RP_CLOUD_ADMIN_PASSWORD_HASH</code> before public deployment.</div>
            <?php } ?>
            <section class="grid stats">
                <div class="stat"><div class="stat-label">Report Orders</div><div class="stat-value"><?php echo h($totalOrders); ?></div><div class="stat-note">All clinic uploads</div></div>
                <div class="stat"><div class="stat-label">Active Clinics</div><div class="stat-value"><?php echo h($activeClinics); ?></div><div class="stat-note">Registered clinic nodes</div></div>
                <div class="stat"><div class="stat-label">Radiologists</div><div class="stat-value"><?php echo h($activeRadiologists); ?></div><div class="stat-note"><?php echo h($availableRadiologists); ?> available now</div></div>
                <div class="stat"><div class="stat-label">Queued Returns</div><div class="stat-value"><?php echo h($queuedReturns); ?></div><div class="stat-note">Waiting for clinic pickup</div></div>
                <div class="stat"><div class="stat-label">Return Problems</div><div class="stat-value"><?php echo h($failedReturns); ?></div><div class="stat-note">Failed or errored returns</div></div>
                <div class="stat"><div class="stat-label">Audit Failures</div><div class="stat-value"><?php echo h($validationFailures); ?></div><div class="stat-note">Rejected/failed API events</div></div>
            </section>

            <section class="grid columns">
                <div class="grid">
                    <div class="card" id="orders">
                        <div class="card-head">
                            <h2>Report Orders</h2>
                            <span class="muted">Latest 40 by update time</span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($orders)) { ?>
                                <div class="empty">No report orders received yet.</div>
                            <?php } else { ?>
                                <div class="table-wrap">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Patient</th>
                                                <th>Clinic</th>
                                                <th>Study</th>
                                                <th>Radiologist</th>
                                                <th>Status</th>
                                                <th>Updated</th>
                                                <th>Order</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order) { ?>
                                                <tr>
                                                    <td><strong><?php echo h($order['patient_name']); ?></strong><br><span class="muted">Accession <?php echo h($order['accession_number']); ?></span></td>
                                                    <td><?php echo h($order['clinic_id']); ?><br><span class="muted"><?php echo h($order['branch']); ?></span></td>
                                                    <td><?php echo h($order['procedure_name']); ?><br><span class="muted"><?php echo h($order['modality']); ?></span></td>
                                                    <td><?php echo h($order['radiologist_username'] ?: '-'); ?></td>
                                                    <td><span class="pill <?php echo h(rp_cloud_admin_status_class((string) $order['status'])); ?>"><?php echo h($order['status']); ?></span></td>
                                                    <td><?php echo h($order['updated_at']); ?></td>
                                                    <td><a class="order-link" href="order.php?order_uid=<?php echo rawurlencode((string) $order['order_uid']); ?>">Open</a><br><code><?php echo h($order['order_uid']); ?></code></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="card" id="audit">
                        <div class="card-head">
                            <h2>Recent Audit</h2>
                            <span class="muted">Last 25 cloud events</span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($audit)) { ?>
                                <div class="empty">No audit events yet.</div>
                            <?php } else { ?>
                                <div class="table-wrap">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Time</th>
                                                <th>Event</th>
                                                <th>Clinic</th>
                                                <th>Entity</th>
                                                <th>Result</th>
                                                <th>Message</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($audit as $event) { ?>
                                                <tr>
                                                    <td><?php echo h($event['created_at']); ?></td>
                                                    <td><?php echo h(rp_cloud_admin_event_label((string) $event['event_type'])); ?><br><span class="muted"><?php echo h($event['event_type']); ?></span></td>
                                                    <td><?php echo h($event['clinic_id']); ?></td>
                                                    <td><?php echo h($event['entity_type']); ?><br><code><?php echo h($event['entity_id']); ?></code></td>
                                                    <td><?php echo ((int) $event['success'] === 1) ? '<span class="ok">OK</span>' : '<span class="error">Failed</span>'; ?></td>
                                                    <td><?php echo h($event['message']); ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <aside class="grid">
                    <div class="card" id="clinics">
                        <div class="card-head"><h2>Clinics</h2><a class="btn" href="clinics.php"><i class="fa fa-cog"></i> Manage</a></div>
                        <div class="card-body">
                            <?php if (empty($clinics)) { ?>
                                <div class="empty">No clinics registered yet.</div>
                            <?php } else { ?>
                                <div class="mini-list">
                                    <?php foreach ($clinics as $clinic) { ?>
                                        <div class="mini-row">
                                            <div>
                                                <strong><?php echo h($clinic['clinic_name'] ?: $clinic['clinic_uid']); ?></strong><br>
                                                <span class="muted"><?php echo h($clinic['clinic_uid']); ?> / <?php echo h($clinic['default_branch']); ?></span><br>
                                                <span class="muted">Last seen: <?php echo h($clinic['last_seen_at'] ?: 'Never'); ?></span>
                                            </div>
                                            <span class="pill <?php echo h(rp_cloud_admin_status_class((string) $clinic['status'])); ?>"><?php echo h($clinic['status']); ?></span>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-head"><h2>Radiologists</h2><a class="btn" href="radiologists.php"><i class="fa fa-cog"></i> Manage</a></div>
                        <div class="card-body">
                            <?php if (empty($radiologists)) { ?>
                                <div class="empty">No cloud radiologists registered yet.</div>
                            <?php } else { ?>
                                <div class="mini-list">
                                    <?php foreach ($radiologists as $radiologist) { ?>
                                        <div class="mini-row">
                                            <div>
                                                <strong><?php echo h($radiologist['display_name'] ?: $radiologist['username']); ?></strong><br>
                                                <span class="muted"><?php echo h($radiologist['username']); ?> / <?php echo h($radiologist['modalities'] ?: 'All modalities'); ?></span>
                                            </div>
                                            <div>
                                                <span class="pill <?php echo h(rp_cloud_admin_status_class((string) $radiologist['availability_status'])); ?>"><?php echo h($radiologist['availability_status']); ?></span>
                                                <span class="pill <?php echo h(rp_cloud_admin_status_class((string) $radiologist['status'])); ?>"><?php echo h($radiologist['status']); ?></span>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-head"><h2>Status Breakdown</h2></div>
                        <div class="card-body">
                            <div class="mini-list">
                                <?php foreach ($orderStatusRows as $row) { ?>
                                    <div class="mini-row"><strong>Orders: <?php echo h($row['status']); ?></strong><span class="pill <?php echo h(rp_cloud_admin_status_class((string) $row['status'])); ?>"><?php echo h($row['total']); ?></span></div>
                                <?php } ?>
                                <?php foreach ($returnStatusRows as $row) { ?>
                                    <div class="mini-row"><strong>Returns: <?php echo h($row['status']); ?></strong><span class="pill <?php echo h(rp_cloud_admin_status_class((string) $row['status'])); ?>"><?php echo h($row['total']); ?></span></div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-head"><h2>Status Watch</h2><span class="muted">Oldest active cases</span></div>
                        <div class="card-body">
                            <?php if (empty($statusAttention)) { ?>
                                <div class="empty">No active orders need status attention.</div>
                            <?php } else { ?>
                                <div class="mini-list">
                                    <?php foreach ($statusAttention as $row) { ?>
                                        <div class="mini-row attention">
                                            <div>
                                                <strong><a class="order-link" href="order.php?order_uid=<?php echo rawurlencode((string) $row['order_uid']); ?>"><?php echo h($row['patient_name'] ?: $row['order_uid']); ?></a></strong><br>
                                                <span class="muted"><?php echo h($row['clinic_id']); ?> / accession <?php echo h($row['accession_number']); ?></span><br>
                                                <span class="muted">Waiting <?php echo h($row['minutes_waiting']); ?> min / radiologist <?php echo h($row['radiologist_username'] ?: '-'); ?></span>
                                            </div>
                                            <span class="pill <?php echo h(rp_cloud_admin_status_class((string) $row['status'])); ?>"><?php echo h($row['status']); ?></span>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-head"><h2>Manual Interventions</h2><span class="muted">Latest operator actions</span></div>
                        <div class="card-body">
                            <?php if (empty($operatorAudit)) { ?>
                                <div class="empty">No operator recovery actions yet.</div>
                            <?php } else { ?>
                                <div class="mini-list">
                                    <?php foreach ($operatorAudit as $event) { ?>
                                        <div class="mini-row operator">
                                            <div>
                                                <strong><?php echo h(rp_cloud_admin_event_label((string) $event['event_type'])); ?></strong><br>
                                                <span class="muted"><?php echo h($event['created_at']); ?> / <?php echo h($event['clinic_id'] ?: 'cloud'); ?></span><br>
                                                <a class="order-link" href="order.php?order_uid=<?php echo rawurlencode((string) $event['entity_id']); ?>"><?php echo h($event['entity_id']); ?></a>
                                                <?php if (trim((string) $event['message']) !== '') { ?><br><span class="muted"><?php echo h($event['message']); ?></span><?php } ?>
                                            </div>
                                            <?php echo ((int) $event['success'] === 1) ? '<span class="pill good">OK</span>' : '<span class="pill bad">Failed</span>'; ?>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="card" id="returns">
                        <div class="card-head"><h2>Return Queue</h2><span class="muted">Latest 20</span></div>
                        <div class="card-body">
                            <?php if (empty($returns)) { ?>
                                <div class="empty">No returned reports queued yet.</div>
                            <?php } else { ?>
                                <div class="mini-list">
                                    <?php foreach ($returns as $return) { ?>
                                        <div class="mini-row">
                                            <div>
                                                <strong><?php echo h($return['order_uid']); ?></strong><br>
                                                <span class="muted"><?php echo h($return['clinic_id']); ?> / Accession <?php echo h($return['accession_number']); ?></span><br>
                                                <span class="muted">Attempts: <?php echo h($return['attempts']); ?> / Sent: <?php echo h($return['sent_at'] ?: '-'); ?></span>
                                                <?php if (trim((string) $return['last_error']) !== '') { ?><br><span class="error"><?php echo h($return['last_error']); ?></span><?php } ?>
                                            </div>
                                            <span class="pill <?php echo h(rp_cloud_admin_status_class((string) $return['status'])); ?>"><?php echo h($return['status']); ?></span>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-head"><h2>Worker Commands</h2></div>
                        <div class="card-body">
                            <div class="mini-list">
                                <?php foreach ($workerCommands as $label => $command) { ?>
                                    <div class="mini-row">
                                        <div>
                                            <strong><?php echo h($label); ?></strong>
                                            <code class="cmd"><?php echo h($command); ?></code>
                                        </div>
                                    </div>
                                <?php } ?>
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
