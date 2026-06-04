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
    if (in_array($status, array('active', 'available', 'assigned'), true)) {
        return 'good';
    }
    if (in_array($status, array('received', 'sent_to_remotepanda', 'in_progress'), true)) {
        return 'work';
    }
    if (in_array($status, array('inactive', 'offline', 'failed'), true)) {
        return 'bad';
    }
    return 'neutral';
}

$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));
    $id = (int) ($_POST['id'] ?? 0);
    $ruleName = trim((string) ($_POST['rule_name'] ?? ''));
    $clinicUid = trim((string) ($_POST['clinic_uid'] ?? ''));
    $modality = strtoupper(trim((string) ($_POST['modality'] ?? '')));
    $procedureText = trim((string) ($_POST['procedure_text'] ?? ''));
    $radiologistUsername = trim((string) ($_POST['radiologist_username'] ?? ''));
    $priority = max(1, (int) ($_POST['priority'] ?? 100));
    $status = trim((string) ($_POST['status'] ?? 'active'));
    $notes = trim((string) ($_POST['notes'] ?? ''));

    if (!in_array($status, array('active', 'inactive'), true)) {
        $status = 'active';
    }

    if ($action === 'save_rule') {
        if ($ruleName === '') {
            $ruleName = 'Rule ' . date('Ymd-His');
        }
        if ($radiologistUsername === '') {
            $error = 'Choose the radiologist this rule should assign to.';
        }

        if ($error === '') {
            if ($id > 0) {
                $stmt = mysqli_prepare($con, "UPDATE cloud_assignment_rules
                    SET rule_name = ?, clinic_uid = ?, modality = ?, procedure_text = ?, radiologist_username = ?,
                        priority = ?, status = ?, notes = ?, updated_at = NOW()
                    WHERE id = ? LIMIT 1");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'sssssissi', $ruleName, $clinicUid, $modality, $procedureText, $radiologistUsername, $priority, $status, $notes, $id);
                    if (mysqli_stmt_execute($stmt)) {
                        $flash = 'Assignment rule updated.';
                        rp_cloud_audit($con, 'assignment_rule_updated', 'assignment_rule', (string) $id, $clinicUid, true, 'Cloud assignment rule updated.', array('radiologist' => $radiologistUsername));
                    } else {
                        $error = 'Could not update assignment rule.';
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error = 'Could not prepare assignment rule update.';
                }
            } else {
                $stmt = mysqli_prepare($con, "INSERT INTO cloud_assignment_rules
                    (rule_name, clinic_uid, modality, procedure_text, radiologist_username, priority, status, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'sssssiss', $ruleName, $clinicUid, $modality, $procedureText, $radiologistUsername, $priority, $status, $notes);
                    if (mysqli_stmt_execute($stmt)) {
                        $flash = 'Assignment rule created.';
                        rp_cloud_audit($con, 'assignment_rule_created', 'assignment_rule', (string) mysqli_insert_id($con), $clinicUid, true, 'Cloud assignment rule created.', array('radiologist' => $radiologistUsername));
                    } else {
                        $error = 'Could not create assignment rule.';
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error = 'Could not prepare assignment rule insert.';
                }
            }
        }
    } elseif ($action === 'set_status' && $id > 0) {
        $stmt = mysqli_prepare($con, "UPDATE cloud_assignment_rules SET status = ?, updated_at = NOW() WHERE id = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $status, $id);
            if (mysqli_stmt_execute($stmt)) {
                $flash = 'Assignment rule status updated.';
            } else {
                $error = 'Could not update assignment rule status.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editRule = null;
if ($editId > 0) {
    $stmt = mysqli_prepare($con, "SELECT * FROM cloud_assignment_rules WHERE id = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $editId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $editRule = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
    }
}

$clinics = rp_cloud_admin_rows($con, "SELECT clinic_uid, clinic_name, default_branch, status FROM cloud_clinics ORDER BY status = 'active' DESC, clinic_name ASC, clinic_uid ASC");
$radiologists = rp_cloud_admin_rows($con, "SELECT username, display_name, availability_status, modalities, status FROM cloud_radiologists ORDER BY status = 'active' DESC, availability_status = 'available' DESC, display_name ASC, username ASC");
$rules = rp_cloud_admin_rows($con, "SELECT ar.*, c.clinic_name, r.display_name, r.availability_status, r.status AS radiologist_status,
        (SELECT COUNT(*) FROM cloud_report_orders o WHERE o.radiologist_username = ar.radiologist_username AND o.status IN ('received','assigned','sent_to_remotepanda','in_progress')) AS open_count
    FROM cloud_assignment_rules ar
    LEFT JOIN cloud_clinics c ON c.clinic_uid = ar.clinic_uid
    LEFT JOIN cloud_radiologists r ON r.username = ar.radiologist_username
    ORDER BY ar.status = 'active' DESC, ar.priority ASC, ar.id ASC");
$activeRules = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_assignment_rules WHERE status = 'active'");
$availableRadiologists = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_radiologists WHERE status = 'active' AND availability_status = 'available'");
$unassignedOrders = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_report_orders WHERE status IN ('received','received_zip_only') AND COALESCE(radiologist_username, '') = ''");
$assignedOrders = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_report_orders WHERE status IN ('assigned','sent_to_remotepanda','in_progress')");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Radpanda Cloud Assignment Rules</title>
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
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid var(--line);background:#fff;border-radius:18px;padding:10px 15px;color:#123c68;font-weight:800;font-size:13px;min-height:38px;cursor:pointer}
        .btn.primary{background:var(--navy);border-color:var(--navy);color:#fff}.btn.red{background:var(--red);border-color:var(--red);color:#fff}.btn.green{background:var(--green);border-color:var(--green);color:#fff}
        .content{padding:28px;max-width:1700px}.grid{display:grid;gap:16px}.stats{grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:18px}.columns{grid-template-columns:430px minmax(0,1fr);align-items:start}
        .card,.stat{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 10px 24px rgba(6,26,51,.04);overflow:hidden}.stat{padding:18px;border-radius:8px;min-height:104px}
        .stat-label{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#5d7188;font-weight:800}.stat-value{font-size:34px;font-weight:900;margin-top:10px;color:#001b36;line-height:1}.stat-note{font-size:12px;color:var(--muted);margin-top:8px}
        .card-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 18px;border-bottom:1px solid #e4edf8}.card-head h2{margin:0;font-size:18px;color:#001b36}.card-body{padding:18px}
        label{display:block;font-weight:800;font-size:12px;color:#123c68;margin:0 0 6px} input,select,textarea{width:100%;border:1px solid #bfd3eb;border-radius:8px;padding:10px 11px;font-size:14px;background:#fff} textarea{min-height:92px;resize:vertical}
        .form-grid{display:grid;gap:12px}.two{grid-template-columns:1fr 1fr}.actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;margin-top:14px}.hint{font-size:12px;color:var(--muted);line-height:1.4}
        .notice{border-radius:10px;padding:12px 14px;margin-bottom:16px;font-weight:800}.notice.ok{background:#eafaf0;border:1px solid #bce8ca;color:#166534}.notice.err{background:#fff1f1;border:1px solid #ffc9c9;color:#991b1b}
        .table-wrap{overflow:auto} table{width:100%;border-collapse:separate;border-spacing:0} th,td{padding:12px 10px;border-bottom:1px solid #e4edf8;text-align:left;font-size:13px;vertical-align:top} th{background:#f6f9fd;color:#38506d;font-size:11px;text-transform:uppercase;letter-spacing:.04em} tr:last-child td{border-bottom:0}
        code{background:#f3f7fc;padding:2px 5px;border-radius:5px;color:#133a67}.pill{display:inline-flex;border-radius:999px;padding:6px 9px;font-weight:900;font-size:11px;white-space:nowrap}.pill.good{background:#dcfce7;color:#166534}.pill.work{background:#fff4d6;color:#8a5400}.pill.bad{background:#fee2e2;color:#991b1b}.pill.neutral{background:#eaf2ff;color:#0b3b72}
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
            <a class="side-link" href="clinics.php"><i class="fa fa-hospital-o"></i> Clinics</a>
            <a class="side-link" href="radiologists.php"><i class="fa fa-user-md"></i> Radiologists</a>
            <a class="side-link" href="typists.php"><i class="fa fa-keyboard-o"></i> Typists</a>
            <a class="side-link active" href="assignment-rules.php"><i class="fa fa-random"></i> Assignment Rules</a>
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
                <h1>Assignment Rules</h1>
                <div>Route incoming studies by clinic, modality, procedure, and radiologist availability.</div>
            </div>
            <div class="top-actions">
                <a class="btn" href="index.php"><i class="fa fa-dashboard"></i> Dashboard</a>
                <a class="btn" href="radiologists.php"><i class="fa fa-user-md"></i> Radiologists</a>
                <a class="btn primary" href="assignment-rules.php"><i class="fa fa-plus"></i> New Rule</a>
                <a class="btn" href="logout.php"><i class="fa fa-sign-out"></i> Logout</a>
            </div>
        </header>
        <section class="content">
            <?php if ($flash !== '') { ?><div class="notice ok"><?php echo h($flash); ?></div><?php } ?>
            <?php if ($error !== '') { ?><div class="notice err"><?php echo h($error); ?></div><?php } ?>

            <section class="grid stats">
                <div class="stat"><div class="stat-label">Active Rules</div><div class="stat-value"><?php echo h($activeRules); ?></div><div class="stat-note">Priority based routing</div></div>
                <div class="stat"><div class="stat-label">Available Radiologists</div><div class="stat-value"><?php echo h($availableRadiologists); ?></div><div class="stat-note">Eligible for auto assignment</div></div>
                <div class="stat"><div class="stat-label">Unassigned Orders</div><div class="stat-value"><?php echo h($unassignedOrders); ?></div><div class="stat-note">Need manual routing</div></div>
                <div class="stat"><div class="stat-label">Assigned Open</div><div class="stat-value"><?php echo h($assignedOrders); ?></div><div class="stat-note">In reporting workflow</div></div>
            </section>

            <section class="grid columns">
                <div class="card">
                    <div class="card-head">
                        <h2><?php echo $editRule ? 'Edit Rule' : 'Add Rule'; ?></h2>
                        <?php if ($editRule) { ?><a class="btn" href="assignment-rules.php">Cancel</a><?php } ?>
                    </div>
                    <div class="card-body">
                        <form method="post" class="form-grid">
                            <input type="hidden" name="action" value="save_rule">
                            <input type="hidden" name="id" value="<?php echo h($editRule['id'] ?? 0); ?>">
                            <div>
                                <label>Rule Name</label>
                                <input name="rule_name" value="<?php echo h($editRule['rule_name'] ?? ''); ?>" placeholder="Chest X-rays to Dr Mark">
                            </div>
                            <div>
                                <label>Clinic</label>
                                <?php $selectedClinic = (string) ($editRule['clinic_uid'] ?? ''); ?>
                                <select name="clinic_uid">
                                    <option value="">Any clinic</option>
                                    <?php foreach ($clinics as $clinic) { ?>
                                        <option value="<?php echo h($clinic['clinic_uid']); ?>" <?php echo $selectedClinic === $clinic['clinic_uid'] ? 'selected' : ''; ?>>
                                            <?php echo h(($clinic['clinic_name'] ?: $clinic['clinic_uid']) . ' / ' . ($clinic['default_branch'] ?: 'No branch')); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-grid two">
                                <div>
                                    <label>Modality</label>
                                    <input name="modality" value="<?php echo h($editRule['modality'] ?? ''); ?>" placeholder="CR">
                                    <div class="hint">Leave blank for any modality.</div>
                                </div>
                                <div>
                                    <label>Priority</label>
                                    <input type="number" min="1" name="priority" value="<?php echo h($editRule['priority'] ?? 100); ?>">
                                    <div class="hint">Lower number runs first.</div>
                                </div>
                            </div>
                            <div>
                                <label>Procedure Contains</label>
                                <input name="procedure_text" value="<?php echo h($editRule['procedure_text'] ?? ''); ?>" placeholder="CHEST">
                                <div class="hint">Optional text match against the study/procedure name.</div>
                            </div>
                            <div>
                                <label>Assign To</label>
                                <?php $selectedRadiologist = (string) ($editRule['radiologist_username'] ?? ''); ?>
                                <select name="radiologist_username" required>
                                    <option value="">Choose radiologist</option>
                                    <?php foreach ($radiologists as $radiologist) { ?>
                                        <option value="<?php echo h($radiologist['username']); ?>" <?php echo $selectedRadiologist === $radiologist['username'] ? 'selected' : ''; ?>>
                                            <?php echo h(($radiologist['display_name'] ?: $radiologist['username']) . ' - ' . $radiologist['availability_status'] . ' - ' . ($radiologist['modalities'] ?: 'all modalities')); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div>
                                <label>Status</label>
                                <?php $selectedStatus = (string) ($editRule['status'] ?? 'active'); ?>
                                <select name="status">
                                    <option value="active" <?php echo $selectedStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $selectedStatus === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div>
                                <label>Notes</label>
                                <textarea name="notes" placeholder="Why this routing exists, clinic-specific instructions, turnaround expectations..."><?php echo h($editRule['notes'] ?? ''); ?></textarea>
                            </div>
                            <div class="actions">
                                <button class="btn primary" type="submit"><?php echo $editRule ? 'Save Rule' : 'Create Rule'; ?></button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-head"><h2>Routing Rules</h2><span class="muted"><?php echo h(count($rules)); ?> total</span></div>
                    <div class="card-body">
                        <?php if (empty($rules)) { ?>
                            <div class="empty">No assignment rules yet. If no rule matches, Cloud will use the available radiologist pool.</div>
                        <?php } else { ?>
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Rule</th>
                                            <th>Match</th>
                                            <th>Radiologist</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rules as $rule) { ?>
                                            <tr>
                                                <td><strong><?php echo h($rule['rule_name']); ?></strong><br><span class="muted"><?php echo h($rule['notes']); ?></span></td>
                                                <td>
                                                    <strong><?php echo h($rule['clinic_uid'] !== '' ? ($rule['clinic_name'] ?: $rule['clinic_uid']) : 'Any clinic'); ?></strong><br>
                                                    <span class="muted">Modality: <?php echo h($rule['modality'] ?: 'Any'); ?></span><br>
                                                    <span class="muted">Procedure: <?php echo h($rule['procedure_text'] ?: 'Any'); ?></span>
                                                </td>
                                                <td><strong><?php echo h($rule['display_name'] ?: $rule['radiologist_username']); ?></strong><br><code><?php echo h($rule['radiologist_username']); ?></code><br><span class="muted"><?php echo h($rule['open_count']); ?> open cases</span></td>
                                                <td><?php echo h($rule['priority']); ?></td>
                                                <td>
                                                    <span class="pill <?php echo h(rp_cloud_status_class((string) $rule['status'])); ?>"><?php echo h($rule['status']); ?></span><br>
                                                    <span class="pill <?php echo h(rp_cloud_status_class((string) $rule['availability_status'])); ?>"><?php echo h($rule['availability_status'] ?: 'unknown'); ?></span>
                                                </td>
                                                <td>
                                                    <div class="row-actions">
                                                        <a class="btn" href="assignment-rules.php?edit=<?php echo h($rule['id']); ?>"><i class="fa fa-pencil"></i> Edit</a>
                                                        <form class="inline" method="post">
                                                            <input type="hidden" name="action" value="set_status">
                                                            <input type="hidden" name="id" value="<?php echo h($rule['id']); ?>">
                                                            <input type="hidden" name="status" value="<?php echo $rule['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                            <button class="btn <?php echo $rule['status'] === 'active' ? 'red' : 'green'; ?>" type="submit"><?php echo $rule['status'] === 'active' ? 'Disable' : 'Enable'; ?></button>
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
