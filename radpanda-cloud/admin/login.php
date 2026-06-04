<?php
require_once __DIR__ . '/../includes/admin_auth.php';
rp_cloud_admin_security_headers();

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$next = trim((string) ($_GET['next'] ?? $_POST['next'] ?? '/radpanda-cloud/admin/index.php'));
if ($next === '' || strpos($next, '/radpanda-cloud/admin/') !== 0 || strpos($next, '/radpanda-cloud/admin/login.php') === 0) {
    $next = '/radpanda-cloud/admin/index.php';
}

if (rp_cloud_admin_is_logged_in()) {
    header('Location: ' . $next);
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf'] ?? '');
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (!rp_cloud_admin_verify_csrf($token)) {
        $error = 'Session expired. Refresh and try again.';
    } elseif (rp_cloud_admin_throttle_remaining() > 0) {
        $minutes = max(1, (int) ceil(rp_cloud_admin_throttle_remaining() / 60));
        $error = 'Too many failed attempts. Try again in about ' . $minutes . ' minute' . ($minutes === 1 ? '' : 's') . '.';
    } elseif (rp_cloud_admin_login($username, $password)) {
        header('Location: ' . $next);
        exit;
    } else {
        $error = 'Invalid Cloud admin username or password.';
    }
}

$usingDefaultPassword = RP_CLOUD_ADMIN_PASSWORD_HASH === '' && RP_CLOUD_ADMIN_PASSWORD === 'radpanda-admin';
$requiresHash = RP_CLOUD_REQUIRE_HASHED_ADMIN_PASSWORD && RP_CLOUD_ADMIN_PASSWORD_HASH === '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Radpanda Cloud Login</title>
    <link rel="icon" type="image/x-icon" href="/radpanda/extensions/images/favicon.png">
    <style>
        :root{--navy:#001b36;--red:#ed1b24;--soft:#eef5ff;--line:#cfe0f5;--muted:#52677f}
        *{box-sizing:border-box}body{font-family:Arial,sans-serif;background:var(--soft);margin:0;color:#061a33;min-height:100vh;display:grid;place-items:center;padding:24px}
        .login{width:min(460px,100%);background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 18px 44px rgba(6,26,51,.08);padding:28px}
        .logo{width:190px;margin-bottom:22px}.eyebrow{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#5d7188;font-weight:900}
        h1{margin:6px 0 8px;font-size:28px;color:var(--navy)}p{margin:0 0 22px;color:var(--muted);font-size:14px;line-height:1.45}
        label{display:block;font-weight:900;margin:14px 0 6px;color:#001b36}input{width:100%;border:1px solid #bfd4ed;border-radius:8px;padding:12px 13px;font-size:15px}
        button{width:100%;margin-top:18px;border:0;border-radius:20px;background:var(--navy);color:#fff;padding:12px 16px;font-weight:900;font-size:15px;cursor:pointer}
        .notice{border-radius:10px;padding:12px 14px;margin-bottom:14px;font-weight:800;font-size:13px}.notice.err{background:#fee2e2;border:1px solid #fecaca;color:#991b1b}.notice.warn{background:#fff4d6;border:1px solid #f4cf75;color:#8a5400}
        .foot{margin-top:18px;font-size:12px;color:var(--muted);line-height:1.45}
    </style>
</head>
<body>
    <main class="login">
        <img class="logo" src="/radpanda/extensions/images/logo.png" alt="Radpanda">
        <div class="eyebrow">Cloud Admin</div>
        <h1>Sign in</h1>
        <p>Access report orders, clinic nodes, return queues, and production recovery tools.</p>
        <?php if ($error !== '') { ?><div class="notice err"><?php echo h($error); ?></div><?php } ?>
        <?php if ($usingDefaultPassword) { ?><div class="notice warn">Using the default local password. Set <code>RP_CLOUD_ADMIN_PASSWORD_HASH</code> before internet-facing deployment.</div><?php } ?>
        <?php if ($requiresHash) { ?><div class="notice err">Hashed admin password is required, but <code>RP_CLOUD_ADMIN_PASSWORD_HASH</code> is not set.</div><?php } ?>
        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf" value="<?php echo h(rp_cloud_admin_csrf_token()); ?>">
            <input type="hidden" name="next" value="<?php echo h($next); ?>">
            <label for="username">Username</label>
            <input id="username" name="username" value="<?php echo h(RP_CLOUD_ADMIN_USER); ?>" required>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autofocus>
            <button type="submit">Open Cloud Admin</button>
        </form>
        <div class="foot">API workers continue to use clinic keys. This login protects only the browser admin area.</div>
    </main>
</body>
</html>
