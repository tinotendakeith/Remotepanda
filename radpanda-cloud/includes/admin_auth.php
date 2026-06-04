<?php
require_once __DIR__ . '/api.php';

function rp_cloud_admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off');
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => '/radpanda-cloud/admin',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    session_name('RADPANDA_CLOUD_ADMIN');
    session_start();
}

function rp_cloud_admin_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

function rp_cloud_admin_client_key(): string
{
    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $agent = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    return hash('sha256', $ip . '|' . substr($agent, 0, 180));
}

function rp_cloud_admin_throttle_file(): string
{
    $dir = rtrim((string) RP_CLOUD_STORAGE_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'security';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir . DIRECTORY_SEPARATOR . 'admin-login-throttle.json';
}

function rp_cloud_admin_read_throttle(): array
{
    $path = rp_cloud_admin_throttle_file();
    if (!is_file($path)) {
        return array();
    }
    $raw = file_get_contents($path);
    $data = $raw !== false ? json_decode($raw, true) : null;
    return is_array($data) ? $data : array();
}

function rp_cloud_admin_write_throttle(array $data): void
{
    $now = time();
    foreach ($data as $key => $entry) {
        $last = (int) ($entry['last_at'] ?? 0);
        $locked = (int) ($entry['locked_until'] ?? 0);
        if ($last < ($now - 86400) && $locked < $now) {
            unset($data[$key]);
        }
    }
    @file_put_contents(rp_cloud_admin_throttle_file(), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function rp_cloud_admin_throttle_remaining(): int
{
    $data = rp_cloud_admin_read_throttle();
    $entry = $data[rp_cloud_admin_client_key()] ?? array();
    $lockedUntil = (int) ($entry['locked_until'] ?? 0);
    return max(0, $lockedUntil - time());
}

function rp_cloud_admin_register_login_failure(string $username): void
{
    $data = rp_cloud_admin_read_throttle();
    $key = rp_cloud_admin_client_key();
    $entry = $data[$key] ?? array('attempts' => 0, 'last_username' => '', 'locked_until' => 0, 'last_at' => 0);
    $entry['attempts'] = (int) ($entry['attempts'] ?? 0) + 1;
    $entry['last_username'] = substr($username, 0, 120);
    $entry['last_at'] = time();
    if ($entry['attempts'] >= max(1, (int) RP_CLOUD_ADMIN_MAX_LOGIN_ATTEMPTS)) {
        $entry['locked_until'] = time() + max(60, (int) RP_CLOUD_ADMIN_LOCKOUT_SECONDS);
    }
    $data[$key] = $entry;
    rp_cloud_admin_write_throttle($data);
}

function rp_cloud_admin_clear_login_failures(): void
{
    $data = rp_cloud_admin_read_throttle();
    $key = rp_cloud_admin_client_key();
    if (isset($data[$key])) {
        unset($data[$key]);
        rp_cloud_admin_write_throttle($data);
    }
}

function rp_cloud_admin_is_logged_in(): bool
{
    rp_cloud_admin_session_start();
    if (empty($_SESSION['rp_cloud_admin_user']) || empty($_SESSION['rp_cloud_admin_authenticated_at'])) {
        return false;
    }

    $now = time();
    $authenticatedAt = (int) ($_SESSION['rp_cloud_admin_authenticated_at'] ?? 0);
    $lastSeenAt = (int) ($_SESSION['rp_cloud_admin_last_seen_at'] ?? 0);
    $idleLimit = max(300, (int) RP_CLOUD_ADMIN_IDLE_TIMEOUT);
    $absoluteLimit = max($idleLimit, (int) RP_CLOUD_ADMIN_ABSOLUTE_TIMEOUT);
    if (($lastSeenAt > 0 && ($now - $lastSeenAt) > $idleLimit) || ($authenticatedAt > 0 && ($now - $authenticatedAt) > $absoluteLimit)) {
        rp_cloud_admin_logout();
        return false;
    }

    return true;
}

function rp_cloud_admin_csrf_token(): string
{
    rp_cloud_admin_session_start();
    if (empty($_SESSION['rp_cloud_admin_csrf'])) {
        $_SESSION['rp_cloud_admin_csrf'] = bin2hex(random_bytes(24));
    }
    return (string) $_SESSION['rp_cloud_admin_csrf'];
}

function rp_cloud_admin_verify_csrf(string $token): bool
{
    rp_cloud_admin_session_start();
    return hash_equals((string) ($_SESSION['rp_cloud_admin_csrf'] ?? ''), $token);
}

function rp_cloud_admin_password_ok(string $password): bool
{
    $hash = trim((string) RP_CLOUD_ADMIN_PASSWORD_HASH);
    if ($hash !== '') {
        return password_verify($password, $hash);
    }
    if (RP_CLOUD_REQUIRE_HASHED_ADMIN_PASSWORD) {
        return false;
    }
    return hash_equals((string) RP_CLOUD_ADMIN_PASSWORD, $password);
}

function rp_cloud_admin_login(string $username, string $password): bool
{
    rp_cloud_admin_session_start();
    $remaining = rp_cloud_admin_throttle_remaining();
    if ($remaining > 0) {
        rp_cloud_audit($GLOBALS['con'], 'cloud_admin_login_locked', 'admin', $username, '', false, 'Cloud admin login blocked by rate limit.', array('seconds_remaining' => $remaining));
        return false;
    }

    if (!hash_equals((string) RP_CLOUD_ADMIN_USER, $username) || !rp_cloud_admin_password_ok($password)) {
        rp_cloud_admin_register_login_failure($username);
        rp_cloud_audit($GLOBALS['con'], 'cloud_admin_login_failed', 'admin', $username, '', false, 'Cloud admin login failed.');
        return false;
    }

    rp_cloud_admin_clear_login_failures();
    session_regenerate_id(true);
    $_SESSION['rp_cloud_admin_user'] = $username;
    $_SESSION['rp_cloud_admin_authenticated_at'] = time();
    $_SESSION['rp_cloud_admin_last_seen_at'] = time();
    $_SESSION['rp_cloud_admin_csrf'] = bin2hex(random_bytes(24));
    rp_cloud_audit($GLOBALS['con'], 'cloud_admin_login', 'admin', $username, '', true, 'Cloud admin logged in.');
    return true;
}

function rp_cloud_admin_logout(): void
{
    rp_cloud_admin_session_start();
    $username = (string) ($_SESSION['rp_cloud_admin_user'] ?? '');
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
    if ($username !== '') {
        rp_cloud_audit($GLOBALS['con'], 'cloud_admin_logout', 'admin', $username, '', true, 'Cloud admin logged out.');
    }
}

function rp_cloud_admin_require_login(): void
{
    rp_cloud_admin_security_headers();
    rp_cloud_admin_session_start();
    if (rp_cloud_admin_is_logged_in()) {
        $_SESSION['rp_cloud_admin_last_seen_at'] = time();
        return;
    }

    $target = (string) ($_SERVER['REQUEST_URI'] ?? '/radpanda-cloud/admin/index.php');
    header('Location: /radpanda-cloud/admin/login.php?next=' . rawurlencode($target));
    exit;
}
?>
