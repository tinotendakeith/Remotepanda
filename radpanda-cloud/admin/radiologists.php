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
    if (in_array($status, array('active', 'available'), true)) {
        return 'good';
    }
    if (in_array($status, array('busy', 'away'), true)) {
        return 'work';
    }
    if (in_array($status, array('inactive', 'offline'), true)) {
        return 'bad';
    }
    return 'neutral';
}

function rp_cloud_username_from_name(string $name): string
{
    $clean = strtolower(trim($name));
    $clean = preg_replace('/[^a-z0-9]+/', '.', $clean);
    $clean = trim((string) $clean, '.');
    return $clean !== '' ? $clean : 'radiologist' . strtolower(bin2hex(random_bytes(3)));
}

function rp_cloud_reporting_db(): ?mysqli
{
    $remoteConfigPath = dirname(__DIR__, 2) . '/remotepanda/includes/database_config.php';
    if (is_file($remoteConfigPath)) {
        require_once $remoteConfigPath;
        if (function_exists('rp_remote_database_connect')) {
            try {
                return rp_remote_database_connect();
            } catch (Throwable $e) {
                return null;
            }
        }
    }

    $db = @mysqli_connect(RP_CLOUD_DB_HOST, RP_CLOUD_DB_USER, RP_CLOUD_DB_PASS, 'radpandaco_appointment');
    if (!$db) {
        return null;
    }

    mysqli_set_charset($db, 'utf8mb4');
    return $db;
}

function rp_cloud_radiologist_login_exists(mysqli $db, string $username, string $oldUsername = ''): ?array
{
    $sql = "SELECT id, username, password FROM users WHERE username = ?";
    $types = 's';
    $params = array($username);
    if ($oldUsername !== '' && $oldUsername !== $username) {
        $sql .= " OR username = ?";
        $types .= 's';
        $params[] = $oldUsername;
    }
    $sql .= " ORDER BY username = ? DESC, id ASC LIMIT 1";
    $types .= 's';
    $params[] = $username;

    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function rp_cloud_provision_radiologist_login(string $username, string $oldUsername, string $displayName, string $email, string $phone, string $password, string $status): array
{
    $db = rp_cloud_reporting_db();
    if (!$db) {
        return array('ok' => false, 'message' => 'Could not connect to the Remotepanda login database.');
    }

    $existing = rp_cloud_radiologist_login_exists($db, $username, $oldUsername);
    $email = $email !== '' ? $email : $username . '@radpanda.local';
    $isBlocked = $status === 'active' ? 0 : 1;
    $password = trim($password);

    if ($existing) {
        if ($password !== '') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($db, "UPDATE users
                SET username = ?, email = ?, MobileNumber = ?, user_type = 'radiologist', branch = '',
                    password = ?, is_blocked = ?, blocked_until = NULL, last_password_reset_at = NOW()
                WHERE id = ? LIMIT 1");
            if (!$stmt) {
                mysqli_close($db);
                return array('ok' => false, 'message' => 'Could not prepare radiologist login update.');
            }
            $id = (int) $existing['id'];
            mysqli_stmt_bind_param($stmt, 'ssssii', $username, $email, $phone, $passwordHash, $isBlocked, $id);
        } else {
            $stmt = mysqli_prepare($db, "UPDATE users
                SET username = ?, email = ?, MobileNumber = ?, user_type = 'radiologist', branch = '',
                    is_blocked = ?, blocked_until = NULL
                WHERE id = ? LIMIT 1");
            if (!$stmt) {
                mysqli_close($db);
                return array('ok' => false, 'message' => 'Could not prepare radiologist login update.');
            }
            $id = (int) $existing['id'];
            mysqli_stmt_bind_param($stmt, 'sssii', $username, $email, $phone, $isBlocked, $id);
        }

        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($db);
        return array('ok' => $ok, 'message' => $ok ? 'Remotepanda radiologist login updated.' : 'Could not update Remotepanda radiologist login.');
    }

    if ($password === '') {
        mysqli_close($db);
        return array('ok' => true, 'message' => 'Radiologist profile saved. Add a login password to activate Remotepanda access.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($db, "INSERT INTO users
        (username, email, MobileNumber, user_type, branch, password, is_blocked, blocked_until, last_password_reset_at)
        VALUES (?, ?, ?, 'radiologist', '', ?, ?, NULL, NOW())");
    if (!$stmt) {
        mysqli_close($db);
        return array('ok' => false, 'message' => 'Could not prepare radiologist login creation.');
    }
    mysqli_stmt_bind_param($stmt, 'ssssi', $username, $email, $phone, $passwordHash, $isBlocked);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($db);
    return array('ok' => $ok, 'message' => $ok ? 'Remotepanda radiologist login created.' : 'Could not create Remotepanda radiologist login.');
}

$flash = '';
$error = '';
$actor = (string) ($_SESSION['cloud_admin_username'] ?? ($_SESSION['username'] ?? 'cloud-admin'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));
    $id = (int) ($_POST['id'] ?? 0);
    $username = trim((string) ($_POST['username'] ?? ''));
    $displayName = trim((string) ($_POST['display_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $oldUsername = trim((string) ($_POST['old_username'] ?? ''));
    $loginPassword = trim((string) ($_POST['login_password'] ?? ''));
    $availability = trim((string) ($_POST['availability_status'] ?? 'available'));
    $modalities = trim((string) ($_POST['modalities'] ?? ''));
    $reportingNotes = trim((string) ($_POST['reporting_notes'] ?? ''));
    $maxDailyCases = max(0, (int) ($_POST['max_daily_cases'] ?? 0));
    $status = trim((string) ($_POST['status'] ?? 'active'));

    if (!in_array($availability, array('available', 'busy', 'away', 'offline'), true)) {
        $availability = 'available';
    }
    if (!in_array($status, array('active', 'inactive'), true)) {
        $status = 'active';
    }

    if ($action === 'save_radiologist') {
        if ($displayName === '') {
            $error = 'Display name is required.';
        } else {
            if ($username === '') {
                $username = rp_cloud_username_from_name($displayName);
            }
            if (!preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
                $error = 'Username can only contain letters, numbers, dots, underscores, and hyphens.';
            }
        }
        if ($error === '' && trim($loginPassword) !== '' && strlen(trim($loginPassword)) < 8) {
            $error = 'Login password must be at least 8 characters.';
        }

        if ($error === '') {
            $passwordHash = trim($loginPassword) !== '' ? password_hash(trim($loginPassword), PASSWORD_DEFAULT) : '';
            if ($id > 0) {
                $stmt = mysqli_prepare($con, "UPDATE cloud_radiologists
                    SET username = ?, display_name = ?, email = ?, phone = ?, availability_status = ?,
                        modalities = ?, reporting_notes = ?, max_daily_cases = ?, status = ?, updated_at = NOW()
                    WHERE id = ? LIMIT 1");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'sssssssisi', $username, $displayName, $email, $phone, $availability, $modalities, $reportingNotes, $maxDailyCases, $status, $id);
                    if (mysqli_stmt_execute($stmt)) {
                        $flash = 'Radiologist updated.';
                        if ($passwordHash !== '') {
                            $passwordStmt = mysqli_prepare($con, "UPDATE cloud_radiologists SET password_hash = ?, password_updated_at = NOW(), updated_at = NOW() WHERE id = ? LIMIT 1");
                            if ($passwordStmt) {
                                mysqli_stmt_bind_param($passwordStmt, 'si', $passwordHash, $id);
                                mysqli_stmt_execute($passwordStmt);
                                mysqli_stmt_close($passwordStmt);
                            }
                        }
                        $loginResult = rp_cloud_provision_radiologist_login($username, $oldUsername, $displayName, $email, $phone, $loginPassword, $status);
                        if (!empty($loginResult['ok'])) {
                            $flash .= ' ' . $loginResult['message'];
                        } else {
                            $error = $loginResult['message'];
                            $flash = '';
                        }
                        rp_cloud_audit($con, 'radiologist_updated', 'radiologist', $username, '', true, 'Cloud radiologist profile updated.');
                        if ($passwordHash !== '') {
                            rp_cloud_audit($con, 'radiologist_password_updated', 'radiologist', $username, '', true, 'Cloud radiologist password reset by ' . $actor . '.');
                        }
                    } else {
                        $error = 'Could not update radiologist. The username may already be used.';
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error = 'Could not prepare radiologist update.';
                }
            } else {
                $stmt = mysqli_prepare($con, "INSERT INTO cloud_radiologists
                    (username, display_name, email, phone, availability_status, modalities, reporting_notes, max_daily_cases, status, password_hash, password_updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, " . ($passwordHash !== '' ? 'NOW()' : 'NULL') . ")");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'sssssssiss', $username, $displayName, $email, $phone, $availability, $modalities, $reportingNotes, $maxDailyCases, $status, $passwordHash);
                    if (mysqli_stmt_execute($stmt)) {
                        $flash = 'Radiologist created.';
                        $loginResult = rp_cloud_provision_radiologist_login($username, '', $displayName, $email, $phone, $loginPassword, $status);
                        if (!empty($loginResult['ok'])) {
                            $flash .= ' ' . $loginResult['message'];
                        } else {
                            $error = $loginResult['message'];
                            $flash = '';
                        }
                        rp_cloud_audit($con, 'radiologist_created', 'radiologist', $username, '', true, 'Cloud radiologist created.');
                    } else {
                        $error = 'Could not create radiologist. The username may already be used.';
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error = 'Could not prepare radiologist insert.';
                }
            }
        }
    } elseif ($action === 'set_status' && $id > 0) {
        $stmt = mysqli_prepare($con, "UPDATE cloud_radiologists SET status = ?, updated_at = NOW() WHERE id = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $status, $id);
            if (mysqli_stmt_execute($stmt)) {
                $flash = 'Radiologist status updated.';
            } else {
                $error = 'Could not update radiologist status.';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($action === 'set_availability' && $id > 0) {
        $stmt = mysqli_prepare($con, "UPDATE cloud_radiologists SET availability_status = ?, updated_at = NOW() WHERE id = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $availability, $id);
            if (mysqli_stmt_execute($stmt)) {
                $flash = 'Availability updated.';
            } else {
                $error = 'Could not update availability.';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editRadiologist = null;
if ($editId > 0) {
    $stmt = mysqli_prepare($con, "SELECT * FROM cloud_radiologists WHERE id = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $editId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $editRadiologist = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
    }
}

$radiologists = rp_cloud_admin_rows($con, "SELECT r.*,
        (SELECT COUNT(*) FROM cloud_report_orders o WHERE o.radiologist_username = r.username) AS order_count,
        (SELECT COUNT(*) FROM cloud_report_orders o WHERE o.radiologist_username = r.username AND o.status IN ('received','sent_to_remotepanda','assigned','in_progress')) AS open_count,
        (SELECT MAX(updated_at) FROM cloud_report_orders o WHERE o.radiologist_username = r.username) AS last_order_at
    FROM cloud_radiologists r
    ORDER BY r.status = 'active' DESC, r.availability_status = 'available' DESC, r.display_name ASC");
$activeRadiologists = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_radiologists WHERE status = 'active'");
$availableRadiologists = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_radiologists WHERE status = 'active' AND availability_status = 'available'");
$openAssigned = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_report_orders WHERE status IN ('received','sent_to_remotepanda','assigned','in_progress')");
$reportedToday = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_report_orders WHERE status IN ('reported','returned') AND reported_at >= CURDATE()");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Radpanda Cloud Radiologists</title>
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
        .content{padding:28px;max-width:1700px}.grid{display:grid;gap:16px}.stats{grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:18px}.columns{grid-template-columns:420px minmax(0,1fr);align-items:start}
        .card,.stat{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 10px 24px rgba(6,26,51,.04);overflow:hidden}.stat{padding:18px;border-radius:8px;min-height:104px}
        .stat-label{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#5d7188;font-weight:800}.stat-value{font-size:34px;font-weight:900;margin-top:10px;color:#001b36;line-height:1}.stat-note{font-size:12px;color:var(--muted);margin-top:8px}
        .card-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 18px;border-bottom:1px solid #e4edf8}.card-head h2{margin:0;font-size:18px;color:#001b36}.card-body{padding:18px}
        label{display:block;font-weight:800;font-size:12px;color:#123c68;margin:0 0 6px} input,select,textarea{width:100%;border:1px solid #bfd3eb;border-radius:8px;padding:10px 11px;font-size:14px;background:#fff} textarea{min-height:92px;resize:vertical}
        .form-grid{display:grid;gap:12px}.two{grid-template-columns:1fr 1fr}.actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;margin-top:14px}.hint{font-size:12px;color:var(--muted);line-height:1.4}
        .credential-box{background:#f6faff;border:1px solid #d7e7f8;border-radius:12px;padding:14px;display:grid;gap:12px}
        .credential-box h3{margin:0;color:#001b36;font-size:15px}.credential-box .hint{margin-top:-4px}
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
            <a class="side-link active" href="radiologists.php"><i class="fa fa-user-md"></i> Radiologists</a>
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
                <h1>Radiologists</h1>
                <div>Manage the reporting network, availability, modalities, and cloud workload.</div>
            </div>
            <div class="top-actions">
                <a class="btn" href="index.php"><i class="fa fa-dashboard"></i> Dashboard</a>
                <a class="btn primary" href="radiologists.php"><i class="fa fa-plus"></i> New Radiologist</a>
                <a class="btn" href="logout.php"><i class="fa fa-sign-out"></i> Logout</a>
            </div>
        </header>
        <section class="content">
            <?php if ($flash !== '') { ?><div class="notice ok"><?php echo h($flash); ?></div><?php } ?>
            <?php if ($error !== '') { ?><div class="notice err"><?php echo h($error); ?></div><?php } ?>

            <section class="grid stats">
                <div class="stat"><div class="stat-label">Active Radiologists</div><div class="stat-value"><?php echo h($activeRadiologists); ?></div><div class="stat-note">Can receive assignments</div></div>
                <div class="stat"><div class="stat-label">Available Now</div><div class="stat-value"><?php echo h($availableRadiologists); ?></div><div class="stat-note">Marked available</div></div>
                <div class="stat"><div class="stat-label">Open Cases</div><div class="stat-value"><?php echo h($openAssigned); ?></div><div class="stat-note">Awaiting/in progress</div></div>
                <div class="stat"><div class="stat-label">Reported Today</div><div class="stat-value"><?php echo h($reportedToday); ?></div><div class="stat-note">Cloud completed cases</div></div>
            </section>

            <section class="grid columns">
                <div class="card">
                    <div class="card-head">
                        <h2><?php echo $editRadiologist ? 'Edit Radiologist' : 'Add Radiologist'; ?></h2>
                        <?php if ($editRadiologist) { ?><a class="btn" href="radiologists.php">Cancel</a><?php } ?>
                    </div>
                    <div class="card-body">
                        <form method="post" class="form-grid">
                            <input type="hidden" name="action" value="save_radiologist">
                            <input type="hidden" name="id" value="<?php echo h($editRadiologist['id'] ?? 0); ?>">
                            <input type="hidden" name="old_username" value="<?php echo h($editRadiologist['username'] ?? ''); ?>">
                            <div>
                                <label>Display Name</label>
                                <input name="display_name" required value="<?php echo h($editRadiologist['display_name'] ?? ''); ?>" placeholder="Dr Mark">
                            </div>
                            <div>
                                <label>Username</label>
                                <input name="username" value="<?php echo h($editRadiologist['username'] ?? ''); ?>" placeholder="mark">
                                <div class="hint">Should match the Remotepanda username for now.</div>
                            </div>
                            <div class="form-grid two">
                                <div>
                                    <label>Email</label>
                                    <input type="email" name="email" value="<?php echo h($editRadiologist['email'] ?? ''); ?>" placeholder="doctor@example.com">
                                </div>
                                <div>
                                    <label>Phone</label>
                                    <input name="phone" value="<?php echo h($editRadiologist['phone'] ?? ''); ?>" placeholder="+263...">
                                </div>
                            </div>
                            <div class="form-grid two">
                                <div>
                                    <label>Availability</label>
                                    <?php $availability = (string) ($editRadiologist['availability_status'] ?? 'available'); ?>
                                    <select name="availability_status">
                                        <option value="available" <?php echo $availability === 'available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="busy" <?php echo $availability === 'busy' ? 'selected' : ''; ?>>Busy</option>
                                        <option value="away" <?php echo $availability === 'away' ? 'selected' : ''; ?>>Away</option>
                                        <option value="offline" <?php echo $availability === 'offline' ? 'selected' : ''; ?>>Offline</option>
                                    </select>
                                </div>
                                <div>
                                    <label>Status</label>
                                    <?php $statusValue = (string) ($editRadiologist['status'] ?? 'active'); ?>
                                    <select name="status">
                                        <option value="active" <?php echo $statusValue === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $statusValue === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-grid two">
                                <div>
                                    <label>Modalities</label>
                                    <input name="modalities" value="<?php echo h($editRadiologist['modalities'] ?? ''); ?>" placeholder="CR, CT, US">
                                </div>
                                <div>
                                    <label>Max Daily Cases</label>
                                    <input type="number" min="0" name="max_daily_cases" value="<?php echo h($editRadiologist['max_daily_cases'] ?? 0); ?>">
                                </div>
                            </div>
                            <div class="credential-box">
                                <h3>Login Password</h3>
                                <div>
                                    <label>Set New Password</label>
                                    <input type="password" name="login_password" autocomplete="new-password" placeholder="<?php echo $editRadiologist ? 'Leave blank to keep existing password' : 'Set initial login password'; ?>">
                                    <div class="hint">Minimum 8 characters. Saving this updates the Cloud registry and the matching Remotepanda radiologist login.</div>
                                </div>
                            </div>
                            <div>
                                <label>Reporting Notes</label>
                                <textarea name="reporting_notes" placeholder="Preferred case types, turnaround notes, payment notes..."><?php echo h($editRadiologist['reporting_notes'] ?? ''); ?></textarea>
                            </div>
                            <div class="actions">
                                <button class="btn primary" type="submit"><?php echo $editRadiologist ? 'Save Radiologist' : 'Create Radiologist'; ?></button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-head"><h2>Radiologist Network</h2><span class="muted"><?php echo h(count($radiologists)); ?> total</span></div>
                    <div class="card-body">
                        <?php if (empty($radiologists)) { ?>
                            <div class="empty">No cloud radiologists yet. Add the first reporting user on the left.</div>
                        <?php } else { ?>
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Radiologist</th>
                                            <th>Contact</th>
                                            <th>Scope</th>
                                            <th>Workload</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($radiologists as $radiologist) { ?>
                                            <tr>
                                                <td><strong><?php echo h($radiologist['display_name'] ?: $radiologist['username']); ?></strong><br><code><?php echo h($radiologist['username']); ?></code><br><span class="muted">Last seen: <?php echo h($radiologist['last_seen_at'] ?: 'Never'); ?></span></td>
                                                <td><?php echo h($radiologist['email'] ?: '-'); ?><br><span class="muted"><?php echo h($radiologist['phone']); ?></span></td>
                                                <td><strong><?php echo h($radiologist['modalities'] ?: 'All / unset'); ?></strong><br><span class="muted">Max/day: <?php echo h($radiologist['max_daily_cases'] ?: 'No limit'); ?></span></td>
                                                <td><strong><?php echo h($radiologist['open_count']); ?> open</strong><br><span class="muted"><?php echo h($radiologist['order_count']); ?> total cases</span><br><span class="muted">Last order <?php echo h($radiologist['last_order_at'] ?: '-'); ?></span></td>
                                                <td><span class="pill <?php echo h(rp_cloud_status_class((string) $radiologist['availability_status'])); ?>"><?php echo h($radiologist['availability_status']); ?></span><br><span class="pill <?php echo h(rp_cloud_status_class((string) $radiologist['status'])); ?>"><?php echo h($radiologist['status']); ?></span></td>
                                                <td>
                                                    <div class="row-actions">
                                                        <a class="btn" href="radiologists.php?edit=<?php echo h($radiologist['id']); ?>"><i class="fa fa-pencil"></i> Edit</a>
                                                        <form class="inline" method="post">
                                                            <input type="hidden" name="action" value="set_availability">
                                                            <input type="hidden" name="id" value="<?php echo h($radiologist['id']); ?>">
                                                            <input type="hidden" name="availability_status" value="<?php echo $radiologist['availability_status'] === 'available' ? 'busy' : 'available'; ?>">
                                                            <button class="btn" type="submit"><?php echo $radiologist['availability_status'] === 'available' ? 'Mark Busy' : 'Mark Available'; ?></button>
                                                        </form>
                                                        <form class="inline" method="post">
                                                            <input type="hidden" name="action" value="set_status">
                                                            <input type="hidden" name="id" value="<?php echo h($radiologist['id']); ?>">
                                                            <input type="hidden" name="status" value="<?php echo $radiologist['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                            <button class="btn <?php echo $radiologist['status'] === 'active' ? 'red' : 'green'; ?>" type="submit"><?php echo $radiologist['status'] === 'active' ? 'Disable' : 'Enable'; ?></button>
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
