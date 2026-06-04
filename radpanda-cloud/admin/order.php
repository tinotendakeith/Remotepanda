<?php
require_once __DIR__ . '/../includes/admin_auth.php';
rp_cloud_admin_require_login();
rp_cloud_ensure_schema($con);

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rp_cloud_order_rows(mysqli $con, string $sql, string $types = '', array $params = array()): array
{
    $rows = array();
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        return $rows;
    }
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

function rp_cloud_order_status_class(string $status): string
{
    $status = strtolower(trim($status));
    if (in_array($status, array('returned', 'sent', 'reported', 'assigned', 'active', 'success'), true)) {
        return 'good';
    }
    if (in_array($status, array('received', 'received_zip_only', 'sent_to_remotepanda', 'in_progress', 'queued'), true)) {
        return 'work';
    }
    if (in_array($status, array('failed', 'error', 'inactive'), true)) {
        return 'bad';
    }
    return 'neutral';
}

function rp_cloud_order_event_label(string $eventType): string
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

function rp_cloud_format_bytes($bytes): string
{
    $bytes = (int) $bytes;
    if ($bytes <= 0) {
        return '0 B';
    }
    $units = array('B', 'KB', 'MB', 'GB');
    $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
    return round($bytes / pow(1024, $power), $power === 0 ? 0 : 1) . ' ' . $units[$power];
}

function rp_cloud_date_value($value): string
{
    $value = trim((string) $value);
    return $value !== '' && $value !== '0000-00-00 00:00:00' ? $value : '-';
}

function rp_cloud_timeline_item(array &$items, string $key, string $label, string $time, string $state, string $detail = ''): void
{
    $items[] = array(
        'key' => $key,
        'label' => $label,
        'time' => rp_cloud_date_value($time),
        'state' => $state,
        'detail' => $detail,
    );
}

$orderUid = trim((string) ($_REQUEST['order_uid'] ?? ''));
$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $orderUid !== '') {
    $action = trim((string) ($_POST['action'] ?? ''));
    if ($action === 'requeue_return') {
        $stmt = mysqli_prepare($con, "UPDATE cloud_report_return_outbox
            SET status = 'queued', last_error = NULL, next_retry_at = NOW(), updated_at = NOW()
            WHERE order_uid = ? AND status IN ('failed','error')");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $orderUid);
            mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            rp_cloud_audit($con, 'operator_requeued_return', 'order', $orderUid, '', true, 'Cloud admin requeued a failed report return.');
            $flash = $affected > 0 ? 'Return requeued for clinic pickup.' : 'No failed return was waiting to be requeued.';
        } else {
            $error = 'Could not prepare the return requeue action.';
        }
    } elseif ($action === 'release_remote_import') {
        $stmt = mysqli_prepare($con, "UPDATE cloud_report_orders
            SET status = IF(COALESCE(radiologist_username, '') <> '', 'assigned', 'received'), updated_at = NOW()
            WHERE order_uid = ? AND status IN ('received','received_zip_only','assigned')");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $orderUid);
            mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            rp_cloud_audit($con, 'operator_released_remote_import', 'order', $orderUid, '', true, 'Cloud admin released an order for Remotepanda import.');
            $flash = $affected > 0 ? 'Order released for Remotepanda sync.' : 'Order is not currently eligible for remote import release.';
        } else {
            $error = 'Could not prepare the remote import release.';
        }
    } elseif ($action === 'auto_assign_order') {
        $rows = rp_cloud_order_rows($con, "SELECT * FROM cloud_report_orders WHERE order_uid = ? LIMIT 1", 's', array($orderUid));
        $target = $rows[0] ?? null;
        if (!$target) {
            $error = 'Order not found for assignment.';
        } else {
            $match = rp_cloud_find_assignment_radiologist($con, (string) ($target['clinic_id'] ?? ''), (string) ($target['modality'] ?? ''), (string) ($target['procedure_name'] ?? ''));
            if (!$match) {
                $error = 'No active assignment rule or available radiologist matched this order.';
            } else {
                $username = (string) $match['username'];
                $rid = (int) ($match['id'] ?? 0);
                $stmt = mysqli_prepare($con, "UPDATE cloud_report_orders
                    SET radiologist_username = ?, radiologist_id = ?, status = 'assigned', assigned_at = COALESCE(assigned_at, NOW()), updated_at = NOW()
                    WHERE order_uid = ?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'sis', $username, $rid, $orderUid);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    rp_cloud_audit($con, 'operator_auto_assigned_order', 'order', $orderUid, (string) ($target['clinic_id'] ?? ''), true, 'Cloud admin auto-assigned an order.', array('radiologist' => $username));
                    $flash = 'Order assigned to ' . $username . '.';
                } else {
                    $error = 'Could not prepare the auto-assignment action.';
                }
            }
        }
    }
}

$order = null;
if ($orderUid !== '') {
    $rows = rp_cloud_order_rows($con, "SELECT o.*, c.clinic_name, c.default_branch, c.contact_name, c.contact_email,
            r.display_name AS radiologist_display_name, r.email AS radiologist_email, r.availability_status
        FROM cloud_report_orders o
        LEFT JOIN cloud_clinics c ON c.clinic_uid = o.clinic_id
        LEFT JOIN cloud_radiologists r ON r.username = o.radiologist_username
        WHERE o.order_uid = ? LIMIT 1", 's', array($orderUid));
    $order = $rows[0] ?? null;
}

$packages = $orderUid !== ''
    ? rp_cloud_order_rows($con, "SELECT * FROM cloud_study_packages WHERE order_uid = ? ORDER BY created_at DESC", 's', array($orderUid))
    : array();
$returns = $orderUid !== ''
    ? rp_cloud_order_rows($con, "SELECT * FROM cloud_report_return_outbox WHERE order_uid = ? ORDER BY created_at DESC", 's', array($orderUid))
    : array();
$audits = $orderUid !== ''
    ? rp_cloud_order_rows($con, "SELECT * FROM cloud_audit_log WHERE entity_id = ? OR context_json LIKE ? ORDER BY created_at DESC, id DESC LIMIT 40", 'ss', array($orderUid, '%' . $orderUid . '%'))
    : array();

$payload = array();
if ($order && trim((string) ($order['payload_json'] ?? '')) !== '') {
    $decoded = json_decode((string) $order['payload_json'], true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

$latestReturn = $returns[0] ?? null;
$timeline = array();
if ($order) {
    rp_cloud_timeline_item($timeline, 'received', 'Clinic package received', (string) ($order['received_at'] ?? ''), 'done', 'Study uploaded from ' . (string) ($order['clinic_name'] ?: $order['clinic_id']));
    rp_cloud_timeline_item($timeline, 'assigned', 'Radiologist assigned', (string) ($order['assigned_at'] ?? ''), trim((string) ($order['assigned_at'] ?? '')) !== '' ? 'done' : 'pending', trim((string) ($order['radiologist_username'] ?? '')) !== '' ? (string) ($order['radiologist_display_name'] ?: $order['radiologist_username']) : 'Waiting for assignment');
    rp_cloud_timeline_item($timeline, 'pulled', 'Pulled into Remotepanda', strtolower((string) ($order['status'] ?? '')) === 'sent_to_remotepanda' ? (string) ($order['updated_at'] ?? '') : '', in_array(strtolower((string) ($order['status'] ?? '')), array('sent_to_remotepanda', 'in_progress', 'reported', 'returned'), true) ? 'done' : 'pending', 'Radiologist workspace has imported the case.');
    rp_cloud_timeline_item($timeline, 'reported', 'Report finalized', (string) ($order['reported_at'] ?? ''), trim((string) ($order['reported_at'] ?? '')) !== '' ? 'done' : 'pending', $latestReturn ? 'Return package queued by ' . (string) ($latestReturn['reported_by_username'] ?? '-') : 'Waiting for final report.');
    rp_cloud_timeline_item($timeline, 'queued', 'Queued for clinic pickup', (string) ($latestReturn['created_at'] ?? ''), $latestReturn ? 'done' : 'pending', $latestReturn ? 'Return status: ' . (string) ($latestReturn['status'] ?? '-') : 'Not yet queued.');
    rp_cloud_timeline_item($timeline, 'returned', 'Clinic picked up report', (string) ($order['returned_at'] ?? ($latestReturn['sent_at'] ?? '')), trim((string) ($order['returned_at'] ?? ($latestReturn['sent_at'] ?? ''))) !== '' ? 'done' : 'pending', 'Clinic node acknowledged the returned report.');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Radpanda Cloud Order</title>
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
        .content{padding:28px;max-width:1700px}.grid{display:grid;gap:16px}.hero{grid-template-columns:minmax(0,1.35fr) minmax(360px,.65fr);align-items:stretch}.columns{grid-template-columns:minmax(0,1fr) 460px;align-items:start}
        .card,.stat{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 10px 24px rgba(6,26,51,.04);overflow:hidden}.card-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 18px;border-bottom:1px solid #e4edf8}.card-head h2{margin:0;font-size:18px;color:#001b36}.card-body{padding:18px}
        .case-title{padding:22px}.case-title h2{margin:0;font-size:34px;color:#001b36}.case-sub{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;color:var(--muted);font-weight:700}.summary-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:18px}.field{border:1px solid #dbe8f7;border-radius:10px;padding:12px;background:#fbfdff}.field .k{font-size:10px;text-transform:uppercase;letter-spacing:.07em;color:#60758f;font-weight:900}.field .v{margin-top:6px;font-weight:800;word-break:break-word}
        code{background:#f3f7fc;padding:2px 5px;border-radius:5px;color:#133a67}.pill{display:inline-flex;border-radius:999px;padding:7px 10px;font-weight:900;font-size:11px;white-space:nowrap}.pill.good{background:#dcfce7;color:#166534}.pill.work{background:#fff4d6;color:#8a5400}.pill.bad{background:#fee2e2;color:#991b1b}.pill.neutral{background:#eaf2ff;color:#0b3b72}
        .timeline{display:grid;gap:12px}.step{display:grid;grid-template-columns:28px 1fr;gap:10px;align-items:start}.dot{width:22px;height:22px;border-radius:50%;border:2px solid #b9cde7;background:#fff;margin-top:2px}.step.done .dot{background:#16a34a;border-color:#16a34a;box-shadow:inset 0 0 0 5px #dcfce7}.step.pending .dot{background:#fff7dc;border-color:#f3c65a}.step h3{margin:0;font-size:14px;color:#001b36}.step .meta{font-size:12px;color:var(--muted);margin-top:4px}.step .detail{font-size:12px;color:#344d69;margin-top:4px;line-height:1.4}
        .table-wrap{overflow:auto} table{width:100%;border-collapse:separate;border-spacing:0} th,td{padding:12px 10px;border-bottom:1px solid #e4edf8;text-align:left;font-size:13px;vertical-align:top} th{background:#f6f9fd;color:#38506d;font-size:11px;text-transform:uppercase;letter-spacing:.04em} tr:last-child td{border-bottom:0}
        .empty{border:1px dashed #bdd3ec;border-radius:10px;padding:18px;color:var(--muted);background:#fbfdff}.muted{color:var(--muted)}.report-box{white-space:pre-wrap;line-height:1.5;background:#fbfdff;border:1px solid #dbe8f7;border-radius:10px;padding:14px;min-height:120px}.payload{max-height:260px;overflow:auto;background:#071426;color:#d7e8ff;border-radius:10px;padding:14px;font-size:12px;line-height:1.5}
        .notice{border-radius:10px;padding:12px 14px;margin-bottom:14px;font-weight:800}.notice.ok{background:#dcfce7;border:1px solid #86efac;color:#166534}.notice.err{background:#fee2e2;border:1px solid #fecaca;color:#991b1b}.row-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.inline{display:inline}
        @media(max-width:1100px){.hero,.columns{grid-template-columns:1fr}.summary-grid{grid-template-columns:1fr}}@media(max-width:850px){.cloud-app{grid-template-columns:1fr}.sidebar{display:none}.topbar{height:auto;align-items:flex-start;padding:18px;gap:14px;flex-direction:column}.content{padding:18px}.top-actions{justify-content:flex-start}}
    </style>
<?php include_once(__DIR__ . '/../includes/skeleton-loader.php'); ?>
</head>
<body>
<div class="cloud-app">
    <aside class="sidebar">
        <div class="brand"><img src="/radpanda/extensions/images/logo.png" alt="Radpanda"></div>
        <nav class="side-nav">
            <a class="side-link" href="index.php"><i class="fa fa-dashboard"></i> Cloud Dashboard</a>
            <a class="side-link active" href="index.php#orders"><i class="fa fa-file-text-o"></i> Report Orders</a>
            <a class="side-link" href="clinics.php"><i class="fa fa-hospital-o"></i> Clinics</a>
            <a class="side-link" href="radiologists.php"><i class="fa fa-user-md"></i> Radiologists</a>
            <a class="side-link" href="typists.php"><i class="fa fa-keyboard-o"></i> Typists</a>
            <a class="side-link" href="assignment-rules.php"><i class="fa fa-random"></i> Assignment Rules</a>
            <a class="side-link" href="index.php#returns"><i class="fa fa-cloud-upload"></i> Return Queue</a>
            <a class="side-link" href="index.php#audit"><i class="fa fa-shield"></i> Audit Log</a>
            <div class="side-label">Operations</div>
            <a class="side-link" href="production-health.php"><i class="fa fa-check-circle"></i> Production Health</a>
            <a class="side-link" href="workers.php"><i class="fa fa-cogs"></i> Workers</a>
            <a class="side-link" href="../api/health.php" target="_blank"><i class="fa fa-heartbeat"></i> Health API</a>
        </nav>
    </aside>
    <main class="main">
        <header class="topbar">
            <div class="page-title">
                <h1>Cloud Order Detail</h1>
                <div>Trace the study from clinic upload to final report return.</div>
            </div>
            <div class="top-actions">
                <a class="btn" href="index.php"><i class="fa fa-arrow-left"></i> Dashboard</a>
                <a class="btn primary" href="order.php?order_uid=<?php echo h(rawurlencode($orderUid)); ?>"><i class="fa fa-refresh"></i> Refresh</a>
                <a class="btn" href="logout.php"><i class="fa fa-sign-out"></i> Logout</a>
            </div>
        </header>
        <section class="content">
            <?php if ($flash !== '') { ?><div class="notice ok"><?php echo h($flash); ?></div><?php } ?>
            <?php if ($error !== '') { ?><div class="notice err"><?php echo h($error); ?></div><?php } ?>
            <?php if (!$order) { ?>
                <div class="empty">Order not found.</div>
            <?php } else { ?>
                <section class="grid hero">
                    <div class="card">
                        <div class="case-title">
                            <h2><?php echo h($order['patient_name']); ?></h2>
                            <div class="case-sub">
                                <span class="pill <?php echo h(rp_cloud_order_status_class((string) $order['status'])); ?>"><?php echo h($order['status']); ?></span>
                                <span>Accession <?php echo h($order['accession_number']); ?></span>
                                <span><?php echo h($order['procedure_name']); ?> / <?php echo h($order['modality']); ?></span>
                            </div>
                            <div class="summary-grid">
                                <div class="field"><div class="k">Order UID</div><div class="v"><code><?php echo h($order['order_uid']); ?></code></div></div>
                                <div class="field"><div class="k">Clinic</div><div class="v"><?php echo h($order['clinic_name'] ?: $order['clinic_id']); ?><br><span class="muted"><?php echo h($order['branch'] ?: $order['default_branch']); ?></span></div></div>
                                <div class="field"><div class="k">Study UID</div><div class="v"><code><?php echo h($order['studyint']); ?></code></div></div>
                                <div class="field"><div class="k">Radiologist</div><div class="v"><?php echo h($order['radiologist_display_name'] ?: $order['radiologist_username'] ?: '-'); ?><br><span class="muted"><?php echo h($order['radiologist_email'] ?: $order['availability_status']); ?></span></div></div>
                                <div class="field"><div class="k">DOB / Gender</div><div class="v"><?php echo h($order['date_of_birth'] ?: '-'); ?> / <?php echo h($order['gender'] ?: '-'); ?></div></div>
                                <div class="field"><div class="k">Requesting Physician</div><div class="v"><?php echo h($order['requesting_physician'] ?: '-'); ?></div></div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-head"><h2>Workflow Timeline</h2><span class="pill <?php echo h(rp_cloud_order_status_class((string) $order['status'])); ?>"><?php echo h($order['status']); ?></span></div>
                        <div class="card-body">
                            <div class="timeline">
                                <?php foreach ($timeline as $step) { ?>
                                    <div class="step <?php echo h($step['state']); ?>">
                                        <div class="dot"></div>
                                        <div>
                                            <h3><?php echo h($step['label']); ?></h3>
                                            <div class="meta"><?php echo h($step['time']); ?></div>
                                            <div class="detail"><?php echo h($step['detail']); ?></div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="row-actions" style="margin-top:16px">
                                <?php if (trim((string) ($order['radiologist_username'] ?? '')) === '') { ?>
                                    <form method="post" class="inline">
                                        <input type="hidden" name="action" value="auto_assign_order">
                                        <input type="hidden" name="order_uid" value="<?php echo h($orderUid); ?>">
                                        <button class="btn green small" type="submit"><i class="fa fa-random"></i> Auto Assign</button>
                                    </form>
                                <?php } ?>
                                <?php if (in_array(strtolower((string) ($order['status'] ?? '')), array('received', 'received_zip_only', 'assigned'), true)) { ?>
                                    <form method="post" class="inline">
                                        <input type="hidden" name="action" value="release_remote_import">
                                        <input type="hidden" name="order_uid" value="<?php echo h($orderUid); ?>">
                                        <button class="btn warn small" type="submit"><i class="fa fa-refresh"></i> Release for Sync</button>
                                    </form>
                                <?php } ?>
                                <?php if ($latestReturn && in_array(strtolower((string) ($latestReturn['status'] ?? '')), array('failed', 'error'), true)) { ?>
                                    <form method="post" class="inline">
                                        <input type="hidden" name="action" value="requeue_return">
                                        <input type="hidden" name="order_uid" value="<?php echo h($orderUid); ?>">
                                        <button class="btn green small" type="submit"><i class="fa fa-repeat"></i> Requeue Return</button>
                                    </form>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid columns" style="margin-top:16px">
                    <div class="grid">
                        <div class="card">
                            <div class="card-head"><h2>Study Package</h2><span><?php echo h(rp_cloud_format_bytes($order['package_size'])); ?></span></div>
                            <div class="card-body">
                                <?php if (empty($packages)) { ?>
                                    <div class="empty">No package records.</div>
                                <?php } else { ?>
                                    <div class="table-wrap">
                                        <table>
                                            <thead><tr><th>Status</th><th>File</th><th>Storage</th><th>Message</th><th>Created</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($packages as $package) { ?>
                                                    <tr>
                                                        <td><span class="pill <?php echo h(rp_cloud_order_status_class((string) $package['upload_status'])); ?>"><?php echo h($package['upload_status']); ?></span></td>
                                                        <td><?php echo h($package['file_name']); ?><br><span class="muted"><?php echo h(rp_cloud_format_bytes($package['file_size'])); ?></span></td>
                                                        <td><code><?php echo h($package['storage_path']); ?></code><br><span class="muted"><?php echo h($package['extract_path']); ?></span></td>
                                                        <td><?php echo h($package['message']); ?></td>
                                                        <td><?php echo h($package['created_at']); ?></td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-head"><h2>Return Report</h2><span class="muted"><?php echo h($latestReturn['status'] ?? 'No return yet'); ?></span></div>
                            <div class="card-body">
                                <?php if (!$latestReturn) { ?>
                                    <div class="empty">No finalized report has been queued back to clinic yet.</div>
                                <?php } else { ?>
                                    <div class="summary-grid" style="margin-top:0;margin-bottom:14px">
                                        <div class="field"><div class="k">Reported By</div><div class="v"><?php echo h($latestReturn['reported_by_username']); ?></div></div>
                                        <div class="field"><div class="k">Attempts / Sent</div><div class="v"><?php echo h($latestReturn['attempts']); ?> / <?php echo h(rp_cloud_date_value($latestReturn['sent_at'])); ?></div></div>
                                    </div>
                                    <div class="report-box"><?php echo h($latestReturn['report_text']); ?></div>
                                    <?php if (trim((string) ($latestReturn['last_error'] ?? '')) !== '') { ?>
                                        <p class="pill bad" style="margin-top:12px"><?php echo h($latestReturn['last_error']); ?></p>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <aside class="grid">
                        <div class="card">
                            <div class="card-head"><h2>Key Dates</h2></div>
                            <div class="card-body">
                                <div class="summary-grid" style="grid-template-columns:1fr;margin-top:0">
                                    <div class="field"><div class="k">Received</div><div class="v"><?php echo h(rp_cloud_date_value($order['received_at'])); ?></div></div>
                                    <div class="field"><div class="k">Assigned</div><div class="v"><?php echo h(rp_cloud_date_value($order['assigned_at'])); ?></div></div>
                                    <div class="field"><div class="k">Reported</div><div class="v"><?php echo h(rp_cloud_date_value($order['reported_at'])); ?></div></div>
                                    <div class="field"><div class="k">Returned</div><div class="v"><?php echo h(rp_cloud_date_value($order['returned_at'])); ?></div></div>
                                    <div class="field"><div class="k">Updated</div><div class="v"><?php echo h(rp_cloud_date_value($order['updated_at'])); ?></div></div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-head"><h2>Audit Events</h2><span class="muted"><?php echo h(count($audits)); ?></span></div>
                            <div class="card-body">
                                <?php if (empty($audits)) { ?>
                                    <div class="empty">No related audit events found.</div>
                                <?php } else { ?>
                                    <div class="timeline">
                                        <?php foreach ($audits as $audit) { ?>
                                            <div class="step <?php echo (int) $audit['success'] === 1 ? 'done' : 'pending'; ?>">
                                                <div class="dot"></div>
                                                <div>
                                                    <h3><?php echo h(rp_cloud_order_event_label((string) $audit['event_type'])); ?></h3>
                                                    <div class="meta"><?php echo h($audit['created_at']); ?> / <?php echo h($audit['clinic_id'] ?: 'cloud'); ?> / <?php echo h($audit['event_type']); ?></div>
                                                    <div class="detail"><?php echo h($audit['message']); ?></div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-head"><h2>Payload</h2></div>
                            <div class="card-body">
                                <?php if (empty($payload)) { ?>
                                    <div class="empty">No payload JSON stored.</div>
                                <?php } else { ?>
                                    <pre class="payload"><?php echo h(json_encode($payload, JSON_PRETTY_PRINT)); ?></pre>
                                <?php } ?>
                            </div>
                        </div>
                    </aside>
                </section>
            <?php } ?>
        </section>
    </main>
</div>
</body>
</html>
