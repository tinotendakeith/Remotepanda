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

function rp_cloud_status_class(string $status): string
{
    $status = strtolower(trim($status));
    if ($status === 'active') {
        return 'good';
    }
    if ($status === 'inactive') {
        return 'bad';
    }
    return 'neutral';
}

function rp_cloud_clinic_uid_from_name(string $name): string
{
    $clean = strtolower(trim($name));
    $clean = preg_replace('/[^a-z0-9]+/', '-', $clean);
    $clean = trim((string) $clean, '-');
    return $clean !== '' ? $clean : 'clinic-' . strtolower(bin2hex(random_bytes(3)));
}

$flash = '';
$error = '';
$generatedKey = '';
$generatedClinicUid = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));
    $id = (int) ($_POST['id'] ?? 0);
    $clinicUid = trim((string) ($_POST['clinic_uid'] ?? ''));
    $clinicName = trim((string) ($_POST['clinic_name'] ?? ''));
    $defaultBranch = trim((string) ($_POST['default_branch'] ?? ''));
    $contactName = trim((string) ($_POST['contact_name'] ?? ''));
    $contactEmail = trim((string) ($_POST['contact_email'] ?? ''));
    $contactPhone = trim((string) ($_POST['contact_phone'] ?? ''));
    $installNotes = trim((string) ($_POST['install_notes'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? 'active'));
    if (!in_array($status, array('active', 'inactive'), true)) {
        $status = 'active';
    }

    if ($action === 'save_clinic') {
        if ($clinicName === '') {
            $error = 'Clinic name is required.';
        } else {
            if ($clinicUid === '') {
                $clinicUid = rp_cloud_clinic_uid_from_name($clinicName);
            }
            if (!preg_match('/^[A-Za-z0-9_.-]+$/', $clinicUid)) {
                $error = 'Clinic UID can only contain letters, numbers, dots, underscores, and hyphens.';
            }
        }

        if ($error === '') {
            if ($id > 0) {
                $stmt = mysqli_prepare($con, "UPDATE cloud_clinics
                    SET clinic_uid = ?, clinic_name = ?, default_branch = ?, contact_name = ?, contact_email = ?,
                        contact_phone = ?, install_notes = ?, status = ?, updated_at = NOW()
                    WHERE id = ? LIMIT 1");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'ssssssssi', $clinicUid, $clinicName, $defaultBranch, $contactName, $contactEmail, $contactPhone, $installNotes, $status, $id);
                    if (mysqli_stmt_execute($stmt)) {
                        $flash = 'Clinic updated.';
                        rp_cloud_audit($con, 'clinic_updated', 'clinic', $clinicUid, $clinicUid, true, 'Cloud clinic settings updated.');
                    } else {
                        $error = 'Could not update clinic. The UID may already be used.';
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error = 'Could not prepare clinic update.';
                }
            } else {
                $generatedKey = 'rpk_' . bin2hex(random_bytes(24));
                $hash = password_hash($generatedKey, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($con, "INSERT INTO cloud_clinics
                    (clinic_uid, clinic_name, default_branch, contact_name, contact_email, contact_phone, install_notes, api_key_hash, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'sssssssss', $clinicUid, $clinicName, $defaultBranch, $contactName, $contactEmail, $contactPhone, $installNotes, $hash, $status);
                    if (mysqli_stmt_execute($stmt)) {
                        $generatedClinicUid = $clinicUid;
                        $flash = 'Clinic created. Copy the API key now; it will not be shown again.';
                        rp_cloud_audit($con, 'clinic_created', 'clinic', $clinicUid, $clinicUid, true, 'Cloud clinic created.');
                    } else {
                        $generatedKey = '';
                        $error = 'Could not create clinic. The UID may already be used.';
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error = 'Could not prepare clinic insert.';
                }
            }
        }
    } elseif ($action === 'rotate_key' && $id > 0) {
        $generatedKey = 'rpk_' . bin2hex(random_bytes(24));
        $hash = password_hash($generatedKey, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($con, "UPDATE cloud_clinics SET api_key_hash = ?, updated_at = NOW() WHERE id = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $hash, $id);
            if (mysqli_stmt_execute($stmt)) {
                $lookup = mysqli_query($con, "SELECT clinic_uid FROM cloud_clinics WHERE id = " . (int) $id . " LIMIT 1");
                $row = $lookup ? mysqli_fetch_assoc($lookup) : null;
                $generatedClinicUid = (string) ($row['clinic_uid'] ?? '');
                $flash = 'API key rotated. Copy the new key now.';
                rp_cloud_audit($con, 'clinic_key_rotated', 'clinic', $generatedClinicUid, $generatedClinicUid, true, 'Clinic API key rotated.');
            } else {
                $generatedKey = '';
                $error = 'Could not rotate key.';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($action === 'set_status' && $id > 0) {
        $stmt = mysqli_prepare($con, "UPDATE cloud_clinics SET status = ?, updated_at = NOW() WHERE id = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $status, $id);
            if (mysqli_stmt_execute($stmt)) {
                $flash = 'Clinic status updated.';
            } else {
                $error = 'Could not update clinic status.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editClinic = null;
if ($editId > 0) {
    $stmt = mysqli_prepare($con, "SELECT * FROM cloud_clinics WHERE id = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $editId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $editClinic = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
    }
}

$clinics = rp_cloud_admin_rows($con, "SELECT c.*,
        (SELECT COUNT(*) FROM cloud_report_orders o WHERE o.clinic_id = c.clinic_uid) AS order_count,
        (SELECT MAX(updated_at) FROM cloud_report_orders o WHERE o.clinic_id = c.clinic_uid) AS last_order_at
    FROM cloud_clinics c
    ORDER BY c.status = 'active' DESC, c.last_seen_at DESC, c.clinic_name ASC");
$activeClinics = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_clinics WHERE status = 'active'");
$inactiveClinics = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_clinics WHERE status = 'inactive'");
$totalOrders = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_report_orders");
$recentUploads = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_audit_log WHERE event_type='sync_upload_received' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Radpanda Cloud Clinics</title>
    <link rel="icon" type="image/x-icon" href="/radpanda/extensions/images/favicon.png">
    <link href="/radpanda/extensions/css/font-awesome.css" rel="stylesheet">
    <style>
        :root{--navy:#001b36;--red:#ed1b24;--soft:#eef5ff;--line:#cfe0f5;--muted:#52677f;--green:#148a43;--danger:#b42318}
        *{box-sizing:border-box} body{font-family:Arial,sans-serif;background:var(--soft);margin:0;color:#061a33} a{text-decoration:none}
        .cloud-app{min-height:100vh;display:grid;grid-template-columns:250px 1fr}.sidebar{background:#00172e;color:#c9d7e8;min-height:100vh;position:sticky;top:0}
        .brand{height:94px;background:#fff;display:flex;align-items:center;padding:0 22px;border-right:1px solid #e6eef8}.brand img{width:178px;max-width:100%;height:auto}
        .side-nav{padding:22px 0}.side-link{display:flex;align-items:center;gap:12px;color:#c9d7e8;padding:13px 24px;font-size:14px;font-weight:700;border-left:4px solid transparent}
        .side-link:hover,.side-link.active{background:#062746;color:#fff;border-left-color:var(--red)}.side-label{padding:18px 24px 8px;color:#7088a4;font-size:11px;text-transform:uppercase;letter-spacing:.08em;font-weight:800}
        .main{min-width:0}.topbar{height:94px;background:#fff;border-bottom:1px solid #dce7f4;display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:5}
        .page-title h1{margin:0;font-size:24px;color:#001b36}.page-title div{margin-top:5px;color:var(--muted);font-size:13px}.top-actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid var(--line);background:#fff;border-radius:18px;padding:10px 15px;color:#123c68;font-weight:800;font-size:13px;min-height:38px;cursor:pointer}
        .btn.primary{background:var(--navy);border-color:var(--navy);color:#fff}.btn.red{background:var(--red);border-color:var(--red);color:#fff}.btn.green{background:var(--green);border-color:var(--green);color:#fff}
        .content{padding:28px;max-width:1700px}.grid{display:grid;gap:16px}.stats{grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:18px}.columns{grid-template-columns:410px minmax(0,1fr);align-items:start}
        .card,.stat{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 10px 24px rgba(6,26,51,.04);overflow:hidden}.stat{padding:18px;border-radius:8px;min-height:104px}
        .stat-label{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#5d7188;font-weight:800}.stat-value{font-size:34px;font-weight:900;margin-top:10px;color:#001b36;line-height:1}.stat-note{font-size:12px;color:var(--muted);margin-top:8px}
        .card-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 18px;border-bottom:1px solid #e4edf8}.card-head h2{margin:0;font-size:18px;color:#001b36}.card-body{padding:18px}
        label{display:block;font-weight:800;font-size:12px;color:#123c68;margin:0 0 6px} input,select,textarea{width:100%;border:1px solid #bfd3eb;border-radius:8px;padding:10px 11px;font-size:14px;background:#fff} textarea{min-height:86px;resize:vertical}
        .form-grid{display:grid;gap:12px}.two{grid-template-columns:1fr 1fr}.actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;margin-top:14px}.hint{font-size:12px;color:var(--muted);line-height:1.4}
        .notice{border-radius:10px;padding:12px 14px;margin-bottom:16px;font-weight:800}.notice.ok{background:#eafaf0;border:1px solid #bce8ca;color:#166534}.notice.err{background:#fff1f1;border:1px solid #ffc9c9;color:#991b1b}
        .key-box{background:#fff8db;border:1px solid #f2d571;border-radius:12px;padding:14px;margin-bottom:16px}.key-box code{display:block;margin-top:8px;word-break:break-all;background:#fff;border:1px solid #f2d571;padding:10px;border-radius:8px;font-size:13px}
        .table-wrap{overflow:auto} table{width:100%;border-collapse:separate;border-spacing:0} th,td{padding:12px 10px;border-bottom:1px solid #e4edf8;text-align:left;font-size:13px;vertical-align:top} th{background:#f6f9fd;color:#38506d;font-size:11px;text-transform:uppercase;letter-spacing:.04em} tr:last-child td{border-bottom:0}
        code{background:#f3f7fc;padding:2px 5px;border-radius:5px;color:#133a67}.pill{display:inline-flex;border-radius:999px;padding:6px 9px;font-weight:900;font-size:11px;white-space:nowrap}.pill.good{background:#dcfce7;color:#166534}.pill.bad{background:#fee2e2;color:#991b1b}.pill.neutral{background:#eaf2ff;color:#0b3b72}
        .row-actions{display:flex;gap:8px;flex-wrap:wrap}.inline{display:inline}.empty{border:1px dashed #bdd3ec;border-radius:10px;padding:18px;color:var(--muted);background:#fbfdff}.muted{color:var(--muted)}.mobile-brand{display:none}
        @media(max-width:1150px){.columns{grid-template-columns:1fr}.stats{grid-template-columns:repeat(2,1fr)}}@media(max-width:850px){.cloud-app{grid-template-columns:1fr}.sidebar{display:none}.mobile-brand{display:block}.topbar{height:auto;min-height:92px;align-items:flex-start;padding:18px;gap:14px;flex-direction:column}.content{padding:18px}.stats,.two{grid-template-columns:1fr}.top-actions{justify-content:flex-start}}
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
            <a class="side-link active" href="clinics.php"><i class="fa fa-hospital-o"></i> Clinics</a>
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
                <div class="mobile-brand"><img src="/radpanda/extensions/images/logo.png" alt="Radpanda" style="width:168px;margin-bottom:12px"></div>
                <h1>Clinics</h1>
                <div>Register clinic nodes, manage API keys, and see sync health.</div>
            </div>
            <div class="top-actions">
                <a class="btn" href="index.php"><i class="fa fa-dashboard"></i> Dashboard</a>
                <a class="btn primary" href="clinics.php"><i class="fa fa-plus"></i> New Clinic</a>
                <a class="btn" href="logout.php"><i class="fa fa-sign-out"></i> Logout</a>
            </div>
        </header>
        <section class="content">
            <?php if ($flash !== '') { ?><div class="notice ok"><?php echo h($flash); ?></div><?php } ?>
            <?php if ($error !== '') { ?><div class="notice err"><?php echo h($error); ?></div><?php } ?>
            <?php if ($generatedKey !== '') { ?>
                <div class="key-box">
                    <strong>New clinic API key for <?php echo h($generatedClinicUid); ?></strong>
                    <div class="hint">Copy this into the clinic node cloud sync settings. For security, the plain key is shown only once.</div>
                    <code><?php echo h($generatedKey); ?></code>
                </div>
            <?php } ?>

            <section class="grid stats">
                <div class="stat"><div class="stat-label">Active Clinics</div><div class="stat-value"><?php echo h($activeClinics); ?></div><div class="stat-note">Can sync with Cloud</div></div>
                <div class="stat"><div class="stat-label">Inactive Clinics</div><div class="stat-value"><?php echo h($inactiveClinics); ?></div><div class="stat-note">Blocked from clinic API auth</div></div>
                <div class="stat"><div class="stat-label">Report Orders</div><div class="stat-value"><?php echo h($totalOrders); ?></div><div class="stat-note">Across all clinics</div></div>
                <div class="stat"><div class="stat-label">Uploads 24h</div><div class="stat-value"><?php echo h($recentUploads); ?></div><div class="stat-note">Recent sync activity</div></div>
            </section>

            <section class="grid columns">
                <div class="card">
                    <div class="card-head">
                        <h2><?php echo $editClinic ? 'Edit Clinic' : 'Add Clinic'; ?></h2>
                        <?php if ($editClinic) { ?><a class="btn" href="clinics.php">Cancel</a><?php } ?>
                    </div>
                    <div class="card-body">
                        <form method="post" class="form-grid">
                            <input type="hidden" name="action" value="save_clinic">
                            <input type="hidden" name="id" value="<?php echo h($editClinic['id'] ?? 0); ?>">
                            <div>
                                <label>Clinic Name</label>
                                <input name="clinic_name" required value="<?php echo h($editClinic['clinic_name'] ?? ''); ?>" placeholder="George Silundika Clinic">
                            </div>
                            <div>
                                <label>Clinic UID</label>
                                <input name="clinic_uid" value="<?php echo h($editClinic['clinic_uid'] ?? ''); ?>" placeholder="george-silundika">
                                <div class="hint">Stable identifier used by clinic nodes. Leave blank for auto-generation on new clinics.</div>
                            </div>
                            <div class="form-grid two">
                                <div>
                                    <label>Default Branch</label>
                                    <input name="default_branch" value="<?php echo h($editClinic['default_branch'] ?? ''); ?>" placeholder="Main branch">
                                </div>
                                <div>
                                    <label>Status</label>
                                    <select name="status">
                                        <?php $statusValue = (string) ($editClinic['status'] ?? 'active'); ?>
                                        <option value="active" <?php echo $statusValue === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $statusValue === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label>Contact Person</label>
                                <input name="contact_name" value="<?php echo h($editClinic['contact_name'] ?? ''); ?>" placeholder="Clinic owner or lead receptionist">
                            </div>
                            <div class="form-grid two">
                                <div>
                                    <label>Contact Email</label>
                                    <input type="email" name="contact_email" value="<?php echo h($editClinic['contact_email'] ?? ''); ?>" placeholder="clinic@example.com">
                                </div>
                                <div>
                                    <label>Contact Phone</label>
                                    <input name="contact_phone" value="<?php echo h($editClinic['contact_phone'] ?? ''); ?>" placeholder="+263...">
                                </div>
                            </div>
                            <div>
                                <label>Install Notes</label>
                                <textarea name="install_notes" placeholder="Server name, AE title notes, support notes..."><?php echo h($editClinic['install_notes'] ?? ''); ?></textarea>
                            </div>
                            <div class="actions">
                                <button class="btn primary" type="submit"><?php echo $editClinic ? 'Save Clinic' : 'Create Clinic + API Key'; ?></button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-head"><h2>Registered Clinics</h2><span class="muted"><?php echo h(count($clinics)); ?> total</span></div>
                    <div class="card-body">
                        <?php if (empty($clinics)) { ?>
                            <div class="empty">No clinics registered yet. Add your first clinic node on the left.</div>
                        <?php } else { ?>
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Clinic</th>
                                            <th>Contact</th>
                                            <th>Health</th>
                                            <th>Orders</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($clinics as $clinic) { ?>
                                            <tr>
                                                <td><strong><?php echo h($clinic['clinic_name']); ?></strong><br><code><?php echo h($clinic['clinic_uid']); ?></code><br><span class="muted"><?php echo h($clinic['default_branch']); ?></span></td>
                                                <td><?php echo h($clinic['contact_name'] ?: '-'); ?><br><span class="muted"><?php echo h($clinic['contact_email']); ?></span><br><span class="muted"><?php echo h($clinic['contact_phone']); ?></span></td>
                                                <td><strong>Last seen</strong><br><?php echo h($clinic['last_seen_at'] ?: 'Never'); ?><br><span class="muted">Updated <?php echo h($clinic['updated_at']); ?></span></td>
                                                <td><strong><?php echo h($clinic['order_count']); ?></strong><br><span class="muted">Last order <?php echo h($clinic['last_order_at'] ?: '-'); ?></span></td>
                                                <td><span class="pill <?php echo h(rp_cloud_status_class((string) $clinic['status'])); ?>"><?php echo h($clinic['status']); ?></span></td>
                                                <td>
                                                    <div class="row-actions">
                                                        <a class="btn" href="clinics.php?edit=<?php echo h($clinic['id']); ?>"><i class="fa fa-pencil"></i> Edit</a>
                                                        <form class="inline" method="post" onsubmit="return confirm('Rotate API key for <?php echo h(addslashes((string) $clinic['clinic_name'])); ?>? The old clinic key will stop working.');">
                                                            <input type="hidden" name="action" value="rotate_key">
                                                            <input type="hidden" name="id" value="<?php echo h($clinic['id']); ?>">
                                                            <button class="btn" type="submit"><i class="fa fa-key"></i> Rotate Key</button>
                                                        </form>
                                                        <form class="inline" method="post">
                                                            <input type="hidden" name="action" value="set_status">
                                                            <input type="hidden" name="id" value="<?php echo h($clinic['id']); ?>">
                                                            <input type="hidden" name="status" value="<?php echo $clinic['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                            <button class="btn <?php echo $clinic['status'] === 'active' ? 'red' : 'green'; ?>" type="submit"><?php echo $clinic['status'] === 'active' ? 'Disable' : 'Enable'; ?></button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </section>
        </section>
    </main>
</div>
</body>
</html>
