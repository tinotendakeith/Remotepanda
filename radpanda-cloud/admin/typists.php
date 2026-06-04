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
    if (in_array($status, array('inactive', 'offline', 'removed'), true)) {
        return 'bad';
    }
    return 'neutral';
}

function rp_cloud_username_from_name(string $name): string
{
    $clean = strtolower(trim($name));
    $clean = preg_replace('/[^a-z0-9]+/', '.', $clean);
    $clean = trim((string) $clean, '.');
    return $clean !== '' ? $clean : 'typist' . strtolower(bin2hex(random_bytes(3)));
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

function rp_cloud_typist_login_exists(mysqli $db, string $username, string $oldUsername = ''): ?array
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

function rp_cloud_provision_typist_login(string $username, string $oldUsername, string $displayName, string $email, string $phone, string $password, string $status): array
{
    $db = rp_cloud_reporting_db();
    if (!$db) {
        return array('ok' => false, 'message' => 'Could not connect to the Remotepanda login database.');
    }

    $existing = rp_cloud_typist_login_exists($db, $username, $oldUsername);
    $email = $email !== '' ? $email : $username . '@radpanda.local';
    $isBlocked = $status === 'active' ? 0 : 1;
    $password = trim($password);

    if ($existing) {
        if ($password !== '') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($db, "UPDATE users
                SET username = ?, email = ?, MobileNumber = ?, user_type = 'typist', branch = '',
                    password = ?, is_blocked = ?, blocked_until = NULL, last_password_reset_at = NOW()
                WHERE id = ? LIMIT 1");
            if (!$stmt) {
                mysqli_close($db);
                return array('ok' => false, 'message' => 'Could not prepare typist login update.');
            }
            $id = (int) $existing['id'];
            mysqli_stmt_bind_param($stmt, 'ssssii', $username, $email, $phone, $passwordHash, $isBlocked, $id);
        } else {
            $stmt = mysqli_prepare($db, "UPDATE users
                SET username = ?, email = ?, MobileNumber = ?, user_type = 'typist', branch = '',
                    is_blocked = ?, blocked_until = NULL
                WHERE id = ? LIMIT 1");
            if (!$stmt) {
                mysqli_close($db);
                return array('ok' => false, 'message' => 'Could not prepare typist login update.');
            }
            $id = (int) $existing['id'];
            mysqli_stmt_bind_param($stmt, 'sssii', $username, $email, $phone, $isBlocked, $id);
        }

        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($db);
        return array('ok' => $ok, 'message' => $ok ? 'Remotepanda typist login updated.' : 'Could not update Remotepanda typist login.');
    }

    if ($password === '') {
        mysqli_close($db);
        return array('ok' => true, 'message' => 'Typist profile saved. Add a login password to activate Remotepanda access.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($db, "INSERT INTO users
        (username, email, MobileNumber, user_type, branch, password, is_blocked, blocked_until, last_password_reset_at)
        VALUES (?, ?, ?, 'typist', '', ?, ?, NULL, NOW())");
    if (!$stmt) {
        mysqli_close($db);
        return array('ok' => false, 'message' => 'Could not prepare typist login creation.');
    }
    mysqli_stmt_bind_param($stmt, 'ssssi', $username, $email, $phone, $passwordHash, $isBlocked);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($db);
    return array('ok' => $ok, 'message' => $ok ? 'Remotepanda typist login created.' : 'Could not create Remotepanda typist login.');
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
    $specialties = trim((string) ($_POST['specialties'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? 'active'));

    if (!in_array($availability, array('available', 'busy', 'away', 'offline'), true)) {
        $availability = 'available';
    }
    if (!in_array($status, array('active', 'inactive'), true)) {
        $status = 'active';
    }

    if ($action === 'save_typist') {
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

        if ($error === '') {
            if ($id > 0) {
                $stmt = mysqli_prepare($con, "UPDATE cloud_typists
                    SET username = ?, display_name = ?, email = ?, phone = ?, availability_status = ?,
                        specialties = ?, notes = ?, status = ?, updated_at = NOW()
                    WHERE id = ? LIMIT 1");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'ssssssssi', $username, $displayName, $email, $phone, $availability, $specialties, $notes, $status, $id);
                    if (mysqli_stmt_execute($stmt)) {
                        $flash = 'Typist updated.';
                        $loginResult = rp_cloud_provision_typist_login($username, $oldUsername, $displayName, $email, $phone, $loginPassword, $status);
                        if (!empty($loginResult['ok'])) {
                            $flash .= ' ' . $loginResult['message'];
                        } else {
                            $error = $loginResult['message'];
                            $flash = '';
                        }
                        rp_cloud_audit($con, 'typist_updated', 'typist', $username, '', true, 'Cloud typist profile updated.');
                    } else {
                        $error = 'Could not update typist. The username may already be used.';
                    }
                    mysqli_stmt_close($stmt);
                }
            } else {
                $stmt = mysqli_prepare($con, "INSERT INTO cloud_typists
                    (username, display_name, email, phone, availability_status, specialties, notes, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 'ssssssss', $username, $displayName, $email, $phone, $availability, $specialties, $notes, $status);
                    if (mysqli_stmt_execute($stmt)) {
                        $flash = 'Typist created.';
                        $loginResult = rp_cloud_provision_typist_login($username, '', $displayName, $email, $phone, $loginPassword, $status);
                        if (!empty($loginResult['ok'])) {
                            $flash .= ' ' . $loginResult['message'];
                        } else {
                            $error = $loginResult['message'];
                            $flash = '';
                        }
                        rp_cloud_audit($con, 'typist_created', 'typist', $username, '', true, 'Cloud typist created.');
                    } else {
                        $error = 'Could not create typist. The username may already be used.';
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
    } elseif ($action === 'set_status' && $id > 0) {
        $stmt = mysqli_prepare($con, "UPDATE cloud_typists SET status = ?, updated_at = NOW() WHERE id = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $status, $id);
            $flash = mysqli_stmt_execute($stmt) ? 'Typist status updated.' : '';
            $error = $flash === '' ? 'Could not update typist status.' : '';
            mysqli_stmt_close($stmt);
        }
    } elseif ($action === 'assign_typist') {
        $radiologistUsername = trim((string) ($_POST['radiologist_username'] ?? ''));
        $typistUsername = trim((string) ($_POST['typist_username'] ?? ''));
        $linkNotes = trim((string) ($_POST['link_notes'] ?? ''));
        if ($radiologistUsername === '' || $typistUsername === '') {
            $error = 'Choose both a radiologist and a typist.';
        } else {
            $stmt = mysqli_prepare($con, "INSERT INTO cloud_radiologist_typists
                (radiologist_username, typist_username, status, assigned_by, notes)
                VALUES (?, ?, 'active', ?, ?)
                ON DUPLICATE KEY UPDATE status = 'active', assigned_by = VALUES(assigned_by), notes = VALUES(notes), assigned_at = NOW()");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ssss', $radiologistUsername, $typistUsername, $actor, $linkNotes);
                if (mysqli_stmt_execute($stmt)) {
                    $flash = 'Typist assigned to radiologist.';
                    rp_cloud_audit($con, 'typist_assigned', 'radiologist_typist', $radiologistUsername . ':' . $typistUsername, '', true, 'Typist assigned to radiologist.');
                } else {
                    $error = 'Could not assign typist.';
                }
                mysqli_stmt_close($stmt);
            }
        }
    } elseif ($action === 'remove_assignment') {
        $linkId = (int) ($_POST['link_id'] ?? 0);
        $stmt = mysqli_prepare($con, "UPDATE cloud_radiologist_typists SET status = 'removed' WHERE id = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $linkId);
            $flash = mysqli_stmt_execute($stmt) ? 'Assignment removed.' : '';
            $error = $flash === '' ? 'Could not remove assignment.' : '';
            mysqli_stmt_close($stmt);
        }
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editTypist = null;
if ($editId > 0) {
    $stmt = mysqli_prepare($con, "SELECT * FROM cloud_typists WHERE id = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $editId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $editTypist = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
    }
}

$typists = rp_cloud_admin_rows($con, "SELECT t.*,
        (SELECT GROUP_CONCAT(rt.radiologist_username ORDER BY rt.radiologist_username SEPARATOR ', ')
            FROM cloud_radiologist_typists rt
            WHERE rt.typist_username = t.username AND rt.status = 'active') AS radiologist_pool
    FROM cloud_typists t
    ORDER BY t.status = 'active' DESC, t.availability_status = 'available' DESC, t.display_name ASC");
$radiologists = rp_cloud_admin_rows($con, "SELECT username, display_name FROM cloud_radiologists WHERE status = 'active' ORDER BY display_name ASC, username ASC");
$assignments = rp_cloud_admin_rows($con, "SELECT rt.*, r.display_name AS radiologist_name, t.display_name AS typist_name
    FROM cloud_radiologist_typists rt
    LEFT JOIN cloud_radiologists r ON r.username = rt.radiologist_username
    LEFT JOIN cloud_typists t ON t.username = rt.typist_username
    WHERE rt.status = 'active'
    ORDER BY COALESCE(r.display_name, rt.radiologist_username), COALESCE(t.display_name, rt.typist_username)");
$activeTypists = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_typists WHERE status = 'active'");
$availableTypists = rp_cloud_admin_one($con, "SELECT COUNT(*) AS total FROM cloud_typists WHERE status = 'active' AND availability_status = 'available'");
$linkedTypists = rp_cloud_admin_one($con, "SELECT COUNT(DISTINCT typist_username) AS total FROM cloud_radiologist_typists WHERE status = 'active'");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Radpanda Cloud Typists</title>
    <link rel="icon" type="image/x-icon" href="/radpanda/extensions/images/favicon.png">
    <link href="/radpanda/extensions/css/font-awesome.css" rel="stylesheet">
    <style>
        :root{--navy:#001b36;--red:#ed1b24;--soft:#eef5ff;--line:#cfe0f5;--muted:#52677f;--green:#148a43;--danger:#b42318;--work:#8a5400}
        *{box-sizing:border-box}body{font-family:Arial,sans-serif;background:var(--soft);margin:0;color:#061a33}a{text-decoration:none}.cloud-app{min-height:100vh;display:grid;grid-template-columns:250px 1fr}.sidebar{background:#00172e;color:#c9d7e8;min-height:100vh;position:sticky;top:0}.brand{height:94px;background:#fff;display:flex;align-items:center;padding:0 22px;border-right:1px solid #e6eef8}.brand img{width:178px;max-width:100%;height:auto}.side-nav{padding:22px 0}.side-link{display:flex;align-items:center;gap:12px;color:#c9d7e8;padding:13px 24px;font-size:14px;font-weight:700;border-left:4px solid transparent}.side-link:hover,.side-link.active{background:#062746;color:#fff;border-left-color:var(--red)}.side-label{padding:18px 24px 8px;color:#7088a4;font-size:11px;text-transform:uppercase;letter-spacing:.08em;font-weight:800}.main{min-width:0}.topbar{height:94px;background:#fff;border-bottom:1px solid #dce7f4;display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:5}.page-title h1{margin:0;font-size:24px;color:#001b36}.page-title div{margin-top:5px;color:var(--muted);font-size:13px}.top-actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end}.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid var(--line);background:#fff;border-radius:18px;padding:10px 15px;color:#123c68;font-weight:800;font-size:13px;min-height:38px;cursor:pointer}.btn.primary{background:var(--navy);border-color:var(--navy);color:#fff}.btn.red{background:var(--red);border-color:var(--red);color:#fff}.btn.green{background:var(--green);border-color:var(--green);color:#fff}.content{padding:28px;max-width:1700px}.grid{display:grid;gap:16px}.stats{grid-template-columns:repeat(3,minmax(0,1fr));margin-bottom:18px}.columns{grid-template-columns:420px minmax(0,1fr);align-items:start}.card,.stat{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 10px 24px rgba(6,26,51,.04);overflow:hidden}.stat{padding:18px;border-radius:8px;min-height:104px}.stat-label{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#5d7188;font-weight:800}.stat-value{font-size:34px;font-weight:900;margin-top:10px;color:#001b36;line-height:1}.stat-note{font-size:12px;color:var(--muted);margin-top:8px}.card-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 18px;border-bottom:1px solid #e4edf8}.card-head h2{margin:0;font-size:18px;color:#001b36}.card-body{padding:18px}label{display:block;font-weight:800;font-size:12px;color:#123c68;margin:0 0 6px}input,select,textarea{width:100%;border:1px solid #bfd3eb;border-radius:8px;padding:10px 11px;font-size:14px;background:#fff}textarea{min-height:92px;resize:vertical}.form-grid{display:grid;gap:12px}.two{grid-template-columns:1fr 1fr}.actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;margin-top:14px}.hint{font-size:12px;color:var(--muted);line-height:1.4}.notice{border-radius:10px;padding:12px 14px;margin-bottom:16px;font-weight:800}.notice.ok{background:#eafaf0;border:1px solid #bce8ca;color:#166534}.notice.err{background:#fff1f1;border:1px solid #ffc9c9;color:#991b1b}.table-wrap{overflow:auto}table{width:100%;border-collapse:separate;border-spacing:0}th,td{padding:12px 10px;border-bottom:1px solid #e4edf8;text-align:left;font-size:13px;vertical-align:top}th{background:#f6f9fd;color:#38506d;font-size:11px;text-transform:uppercase;letter-spacing:.04em}tr:last-child td{border-bottom:0}code{background:#f3f7fc;padding:2px 5px;border-radius:5px;color:#133a67}.pill{display:inline-flex;border-radius:999px;padding:6px 9px;font-weight:900;font-size:11px;white-space:nowrap}.pill.good{background:#dcfce7;color:#166534}.pill.work{background:#fff4d6;color:#8a5400}.pill.bad{background:#fee2e2;color:#991b1b}.pill.neutral{background:#eaf2ff;color:#0b3b72}.row-actions{display:flex;gap:8px;flex-wrap:wrap}.inline{display:inline}.empty{border:1px dashed #bdd3ec;border-radius:10px;padding:18px;color:var(--muted);background:#fbfdff}.muted{color:var(--muted)}.mobile-brand{display:none}@media(max-width:1150px){.columns{grid-template-columns:1fr}.stats{grid-template-columns:repeat(2,1fr)}}@media(max-width:850px){.cloud-app{grid-template-columns:1fr}.sidebar{display:none}.mobile-brand{display:block}.topbar{height:auto;min-height:92px;align-items:flex-start;padding:18px;gap:14px;flex-direction:column}.content{padding:18px}.stats,.two{grid-template-columns:1fr}.top-actions{justify-content:flex-start}}
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
            <a class="side-link active" href="typists.php"><i class="fa fa-keyboard-o"></i> Typists</a>
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
                <h1>Typist Pool</h1>
                <div>Cloud-managed typists who work with remote radiologists, not individual clinics.</div>
            </div>
            <div class="top-actions">
                <a class="btn" href="radiologists.php"><i class="fa fa-user-md"></i> Radiologists</a>
                <a class="btn" href="index.php"><i class="fa fa-dashboard"></i> Dashboard</a>
                <a class="btn" href="logout.php"><i class="fa fa-sign-out"></i> Logout</a>
            </div>
        </header>
        <section class="content">
            <?php if ($flash !== '') { ?><div class="notice ok"><?php echo h($flash); ?></div><?php } ?>
            <?php if ($error !== '') { ?><div class="notice err"><?php echo h($error); ?></div><?php } ?>

            <section class="grid stats">
                <div class="stat"><div class="stat-label">Active Typists</div><div class="stat-value"><?php echo h($activeTypists); ?></div><div class="stat-note">Available to reporting groups</div></div>
                <div class="stat"><div class="stat-label">Available Now</div><div class="stat-value"><?php echo h($availableTypists); ?></div><div class="stat-note">Marked available</div></div>
                <div class="stat"><div class="stat-label">Linked to Radiologists</div><div class="stat-value"><?php echo h($linkedTypists); ?></div><div class="stat-note">In an active typing pool</div></div>
            </section>

            <section class="grid columns">
                <div class="grid">
                    <div class="card">
                        <div class="card-head">
                            <h2><?php echo $editTypist ? 'Edit Typist' : 'Add Typist'; ?></h2>
                            <?php if ($editTypist) { ?><a class="btn" href="typists.php">Cancel</a><?php } ?>
                        </div>
                        <div class="card-body">
                            <form method="post" class="form-grid">
                                <input type="hidden" name="action" value="save_typist">
                                <input type="hidden" name="id" value="<?php echo h($editTypist['id'] ?? 0); ?>">
                                <input type="hidden" name="old_username" value="<?php echo h($editTypist['username'] ?? ''); ?>">
                                <div>
                                    <label>Display Name</label>
                                    <input name="display_name" required value="<?php echo h($editTypist['display_name'] ?? ''); ?>" placeholder="Typing Pool A">
                                </div>
                                <div class="two form-grid">
                                    <div><label>Username</label><input name="username" value="<?php echo h($editTypist['username'] ?? ''); ?>" placeholder="typing.pool.a"></div>
                                    <div><label>Status</label><select name="status"><option value="active" <?php echo (($editTypist['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo (($editTypist['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option></select></div>
                                </div>
                                <div><label>Email</label><input type="email" name="email" value="<?php echo h($editTypist['email'] ?? ''); ?>" placeholder="typist@example.com"></div>
                                <div><label>Remotepanda Login Password</label><input type="password" name="login_password" placeholder="<?php echo $editTypist ? 'Leave blank to keep existing password' : 'Set initial login password'; ?>"><div class="hint">Used only for the typist login. Leave blank to keep the current password.</div></div>
                                <div class="two form-grid">
                                    <div><label>Phone</label><input name="phone" value="<?php echo h($editTypist['phone'] ?? ''); ?>"></div>
                                    <div><label>Availability</label><select name="availability_status"><?php foreach (array('available','busy','away','offline') as $availability) { ?><option value="<?php echo h($availability); ?>" <?php echo (($editTypist['availability_status'] ?? 'available') === $availability) ? 'selected' : ''; ?>><?php echo h(ucfirst($availability)); ?></option><?php } ?></select></div>
                                </div>
                                <div><label>Specialties</label><input name="specialties" value="<?php echo h($editTypist['specialties'] ?? ''); ?>" placeholder="General, X-ray, ultrasound"></div>
                                <div><label>Notes</label><textarea name="notes" placeholder="Timezone, preferred radiologists, turnaround notes"><?php echo h($editTypist['notes'] ?? ''); ?></textarea></div>
                                <div class="actions"><button class="btn primary" type="submit"><i class="fa fa-save"></i> Save Typist</button></div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-head"><h2>Assign to Radiologist</h2></div>
                        <div class="card-body">
                            <form method="post" class="form-grid">
                                <input type="hidden" name="action" value="assign_typist">
                                <div><label>Radiologist</label><select name="radiologist_username" required><option value="">Choose radiologist</option><?php foreach ($radiologists as $radiologist) { ?><option value="<?php echo h($radiologist['username']); ?>"><?php echo h(($radiologist['display_name'] ?: $radiologist['username']) . ' (' . $radiologist['username'] . ')'); ?></option><?php } ?></select></div>
                                <div><label>Typist</label><select name="typist_username" required><option value="">Choose typist</option><?php foreach ($typists as $typist) { ?><option value="<?php echo h($typist['username']); ?>"><?php echo h(($typist['display_name'] ?: $typist['username']) . ' (' . $typist['username'] . ')'); ?></option><?php } ?></select></div>
                                <div><label>Notes</label><textarea name="link_notes" placeholder="e.g. Primary daytime typist"></textarea></div>
                                <div class="hint">This creates a cloud typing pool relationship. Clinics do not see or manage this link.</div>
                                <div class="actions"><button class="btn green" type="submit"><i class="fa fa-link"></i> Assign Typist</button></div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="grid">
                    <div class="card">
                        <div class="card-head"><h2>Typists</h2><span class="muted"><?php echo count($typists); ?> registered</span></div>
                        <div class="card-body table-wrap">
                            <?php if (!$typists) { ?>
                                <div class="empty">No typists have been added yet.</div>
                            <?php } else { ?>
                                <table>
                                    <thead><tr><th>Typist</th><th>Status</th><th>Availability</th><th>Radiologist Pool</th><th>Contact</th><th>Action</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($typists as $typist) { ?>
                                        <tr>
                                            <td><strong><?php echo h($typist['display_name'] ?: $typist['username']); ?></strong><br><code><?php echo h($typist['username']); ?></code><br><span class="muted"><?php echo h($typist['specialties'] ?: '-'); ?></span></td>
                                            <td><span class="pill <?php echo h(rp_cloud_status_class($typist['status'])); ?>"><?php echo h($typist['status']); ?></span></td>
                                            <td><span class="pill <?php echo h(rp_cloud_status_class($typist['availability_status'])); ?>"><?php echo h($typist['availability_status']); ?></span></td>
                                            <td><?php echo h($typist['radiologist_pool'] ?: 'Not assigned'); ?></td>
                                            <td><?php echo h($typist['email'] ?: '-'); ?><br><span class="muted"><?php echo h($typist['phone'] ?: ''); ?></span></td>
                                            <td class="row-actions"><a class="btn" href="typists.php?edit=<?php echo h($typist['id']); ?>"><i class="fa fa-pencil"></i> Edit</a></td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-head"><h2>Active Typing Pools</h2><span class="muted"><?php echo count($assignments); ?> links</span></div>
                        <div class="card-body table-wrap">
                            <?php if (!$assignments) { ?>
                                <div class="empty">No radiologist-typist links yet.</div>
                            <?php } else { ?>
                                <table>
                                    <thead><tr><th>Radiologist</th><th>Typist</th><th>Assigned</th><th>Notes</th><th>Action</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($assignments as $assignment) { ?>
                                        <tr>
                                            <td><strong><?php echo h($assignment['radiologist_name'] ?: $assignment['radiologist_username']); ?></strong><br><code><?php echo h($assignment['radiologist_username']); ?></code></td>
                                            <td><strong><?php echo h($assignment['typist_name'] ?: $assignment['typist_username']); ?></strong><br><code><?php echo h($assignment['typist_username']); ?></code></td>
                                            <td><?php echo h($assignment['assigned_at']); ?><br><span class="muted"><?php echo h($assignment['assigned_by']); ?></span></td>
                                            <td><?php echo h($assignment['notes'] ?: '-'); ?></td>
                                            <td>
                                                <form method="post" class="inline">
                                                    <input type="hidden" name="action" value="remove_assignment">
                                                    <input type="hidden" name="link_id" value="<?php echo h($assignment['id']); ?>">
                                                    <button class="btn red" type="submit"><i class="fa fa-unlink"></i> Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </section>
        </section>
    </main>
</div>
</body>
</html>
