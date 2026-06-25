<?php

require_once __DIR__ . '/dbconnection.php';
require_once __DIR__ . '/platform_settings.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

rp_remote_settings_ensure($con);

if (!defined('RP_REMOTE_REQUEST_ID')) {
    $seed = function_exists('random_bytes') ? bin2hex(random_bytes(8)) : uniqid('', true);
    define('RP_REMOTE_REQUEST_ID', $seed);
}

function rp_remote_audit_ensure_schema(mysqli $con): void
{
    @mysqli_query($con, "CREATE TABLE IF NOT EXISTS remote_api_audit_logs (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        request_id VARCHAR(64) NOT NULL,
        endpoint VARCHAR(255) NOT NULL,
        event_type VARCHAR(80) NOT NULL,
        http_method VARCHAR(12) NOT NULL,
        studyint VARCHAR(128) NULL,
        user_id INT NULL,
        username VARCHAR(191) NULL,
        user_type VARCHAR(64) NULL,
        status_code INT NULL,
        success TINYINT(1) NOT NULL DEFAULT 0,
        message VARCHAR(500) NULL,
        client_ip VARCHAR(64) NULL,
        user_agent VARCHAR(255) NULL,
        meta_json LONGTEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_request_id (request_id),
        KEY idx_studyint (studyint),
        KEY idx_created_at (created_at),
        KEY idx_endpoint (endpoint)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

rp_remote_audit_ensure_schema($con);

function rp_remote_json_response(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function rp_remote_endpoint_name(): string
{
    $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    if ($script !== '') {
        return $script;
    }
    return basename((string)($_SERVER['PHP_SELF'] ?? 'unknown'));
}

function rp_remote_client_ip(): string
{
    $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($headers as $h) {
        if (!empty($_SERVER[$h])) {
            $raw = (string)$_SERVER[$h];
            $ip = trim(explode(',', $raw)[0]);
            if ($ip !== '') {
                return substr($ip, 0, 63);
            }
        }
    }
    return '';
}

function rp_remote_current_user(): ?array
{
    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    return null;
}

function rp_remote_current_username(): string
{
    $user = rp_remote_current_user();
    if ($user && isset($user['username'])) {
        return (string) $user['username'];
    }
    if (isset($_SESSION['username'])) {
        return (string) $_SESSION['username'];
    }
    return '';
}

function rp_remote_current_user_id(): int
{
    $user = rp_remote_current_user();
    if ($user && isset($user['id'])) {
        return (int) $user['id'];
    }
    return 0;
}

function rp_remote_current_user_type(): string
{
    $user = rp_remote_current_user();
    if ($user && isset($user['user_type'])) {
        return strtolower((string) $user['user_type']);
    }
    if (isset($_SESSION['user_type'])) {
        return strtolower((string) $_SESSION['user_type']);
    }
    return '';
}

function rp_remote_api_log(mysqli $con, string $eventType, bool $success, int $statusCode, string $message = '', string $studyint = '', array $meta = []): void
{
    $endpoint = rp_remote_endpoint_name();
    $httpMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $userId = rp_remote_current_user_id();
    $username = rp_remote_current_username();
    $userType = rp_remote_current_user_type();
    $clientIp = rp_remote_client_ip();
    $userAgent = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    $requestId = RP_REMOTE_REQUEST_ID;
    $metaJson = empty($meta) ? null : json_encode($meta);

    $sql = 'INSERT INTO remote_api_audit_logs (request_id, endpoint, event_type, http_method, studyint, user_id, username, user_type, status_code, success, message, client_ip, user_agent, meta_json)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = @mysqli_prepare($con, $sql);
    if (!$stmt) {
        return;
    }

    $studyVal = $studyint !== '' ? $studyint : null;
    $uidVal = $userId > 0 ? $userId : null;
    $usernameVal = $username !== '' ? $username : null;
    $userTypeVal = $userType !== '' ? $userType : null;
    $messageVal = $message !== '' ? $message : null;
    $clientIpVal = $clientIp !== '' ? $clientIp : null;
    $userAgentVal = $userAgent !== '' ? substr($userAgent, 0, 255) : null;
    $successInt = $success ? 1 : 0;

    mysqli_stmt_bind_param(
        $stmt,
        'sssssissiissss',
        $requestId,
        $endpoint,
        $eventType,
        $httpMethod,
        $studyVal,
        $uidVal,
        $usernameVal,
        $userTypeVal,
        $statusCode,
        $successInt,
        $messageVal,
        $clientIpVal,
        $userAgentVal,
        $metaJson
    );
    @mysqli_stmt_execute($stmt);
    @mysqli_stmt_close($stmt);
}

function rp_remote_require_global_api_enabled(mysqli $con): void
{
    if (!rp_remote_feature_enabled($con, 'feature_remote_api_enabled', true)) {
        rp_remote_api_log($con, 'global_api_blocked', false, 503, 'Remote API is disabled');
        rp_remote_json_response(['success' => false, 'error' => 'Remote API is currently disabled'], 503);
    }
}

function rp_remote_require_login(mysqli $con): void
{
    if (!rp_remote_current_user()) {
        rp_remote_api_log($con, 'auth_failed', false, 401, 'Unauthenticated request');
        rp_remote_json_response(['success' => false, 'error' => 'Unauthorized'], 401);
    }
}

function rp_remote_viewer_token_secret(): string
{
    $cfg = function_exists('rp_remote_database_config') ? rp_remote_database_config() : array();
    return hash('sha256', implode('|', array(
        (string)($cfg['username'] ?? ''),
        (string)($cfg['password'] ?? ''),
        (string)($cfg['database'] ?? ''),
        __DIR__
    )));
}

function rp_remote_create_viewer_token(string $studyint, int $expiresAt): string
{
    return hash_hmac('sha256', $studyint . '|' . $expiresAt, rp_remote_viewer_token_secret());
}

function rp_remote_viewer_token_valid(string $studyint): bool
{
    $token = isset($_GET['viewer_token']) ? trim((string)$_GET['viewer_token']) : '';
    $expiresAt = isset($_GET['viewer_exp']) ? (int)$_GET['viewer_exp'] : 0;
    if ($studyint === '' || $token === '' || $expiresAt < time()) {
        return false;
    }
    $expected = rp_remote_create_viewer_token($studyint, $expiresAt);
    return hash_equals($expected, $token);
}

function rp_remote_require_login_or_viewer_token(mysqli $con, string $studyint): bool
{
    if (rp_remote_current_user()) {
        return false;
    }

    if (rp_remote_viewer_token_valid($studyint)) {
        rp_remote_api_log($con, 'viewer_token_auth', true, 200, 'Viewer token accepted', $studyint);
        return true;
    }

    rp_remote_api_log($con, 'auth_failed', false, 401, 'Unauthenticated request');
    rp_remote_json_response(['success' => false, 'error' => 'Unauthorized'], 401);
}

function rp_remote_is_admin_or_supervisor(): bool
{
    $type = rp_remote_current_user_type();
    return in_array($type, ['admin', 'superadmin', 'owner'], true);
}

function rp_remote_evaluate_study_access(mysqli $con, string $studyint): array
{
    $mode = rp_remote_acl_mode($con);
    $failOpen = rp_remote_acl_fail_open($con);

    $result = [
        'allowed' => true,
        'mode' => $mode,
        'fail_open' => $failOpen,
        'fail_open_used' => false,
        'would_block' => false,
        'reason' => 'allowed',
    ];

    if ($studyint === '') {
        $result['allowed'] = false;
        $result['reason'] = 'missing_studyint';
        return $result;
    }

    if (rp_remote_is_admin_or_supervisor()) {
        $result['reason'] = 'admin_bypass';
        return $result;
    }

    if ($mode === 'off') {
        $result['reason'] = 'acl_off';
        return $result;
    }

    $username = rp_remote_current_username();
    $userId = rp_remote_current_user_id();

    $conditions = [];
    $types = '';
    $params = [];

    if (rp_remote_has_column($con, 'study', 'reporting_radiologist') && $username !== '') {
        $conditions[] = 'reporting_radiologist = ?';
        $types .= 's';
        $params[] = $username;
    }

    if (rp_remote_has_column($con, 'study', 'assigned_radiologist_id') && $userId > 0) {
        $conditions[] = 'assigned_radiologist_id = ?';
        $types .= 'i';
        $params[] = $userId;
    }

    if (empty($conditions)) {
        if ($mode === 'enforce' && !$failOpen) {
            $result['allowed'] = false;
            $result['reason'] = 'acl_columns_missing_fail_closed';
            return $result;
        }

        $result['allowed'] = true;
        $result['would_block'] = true;
        $result['reason'] = 'acl_columns_missing';
        if ($mode === 'enforce' && $failOpen) {
            $result['fail_open_used'] = true;
            $result['reason'] = 'acl_columns_missing_fail_open';
        }
        return $result;
    }

    $sql = 'SELECT 1 FROM study WHERE studyint = ? AND (' . implode(' OR ', $conditions) . ') LIMIT 1';
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        if ($mode === 'enforce' && !$failOpen) {
            $result['allowed'] = false;
            $result['reason'] = 'acl_db_error_fail_closed';
            return $result;
        }

        $result['allowed'] = true;
        $result['would_block'] = true;
        $result['reason'] = 'acl_db_error';
        if ($mode === 'enforce' && $failOpen) {
            $result['fail_open_used'] = true;
            $result['reason'] = 'acl_db_error_fail_open';
        }
        return $result;
    }

    $types = 's' . $types;
    array_unshift($params, $studyint);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $matched = $res instanceof mysqli_result && mysqli_num_rows($res) > 0;
    mysqli_stmt_close($stmt);

    if ($matched) {
        $result['allowed'] = true;
        $result['reason'] = 'acl_match';
        return $result;
    }

    if ($mode === 'enforce') {
        $result['allowed'] = false;
        $result['reason'] = 'not_assigned_to_study';
        return $result;
    }

    $result['allowed'] = true;
    $result['would_block'] = true;
    $result['reason'] = 'not_assigned_monitor_allow';
    return $result;
}

function rp_remote_user_can_access_study(mysqli $con, string $studyint): bool
{
    $eval = rp_remote_evaluate_study_access($con, $studyint);
    return (bool)($eval['allowed'] ?? false);
}

function rp_remote_require_study_access(mysqli $con, string $studyint): void
{
    $eval = rp_remote_evaluate_study_access($con, $studyint);
    $meta = [
        'acl_mode' => $eval['mode'] ?? 'off',
        'reason' => $eval['reason'] ?? '',
        'fail_open' => !empty($eval['fail_open']),
        'fail_open_used' => !empty($eval['fail_open_used']),
        'would_block' => !empty($eval['would_block']),
    ];

    if (empty($eval['allowed'])) {
        rp_remote_api_log($con, 'study_access_denied', false, 403, 'Access denied for study', $studyint, $meta);
        rp_remote_json_response(['success' => false, 'error' => 'Access denied for this study'], 403);
    }

    if (($eval['mode'] ?? 'off') === 'monitor' && !empty($eval['would_block'])) {
        rp_remote_api_log($con, 'study_acl_monitor_allow', true, 200, 'Monitor mode allowed access that enforce would block', $studyint, $meta);
    }

    if (($eval['mode'] ?? 'off') === 'enforce' && !empty($eval['fail_open_used'])) {
        rp_remote_api_log($con, 'study_acl_fail_open_allow', true, 200, 'Enforce mode fallback allowed access', $studyint, $meta);
    }
}

function rp_remote_storage_safe_name(string $value, string $fallback = 'study'): string
{
    $clean = preg_replace('/[^A-Za-z0-9_.-]/', '_', trim($value));
    return $clean !== '' ? $clean : $fallback;
}

function rp_remote_folder_has_dicom_payload(string $folder): bool
{
    if (!is_dir($folder)) {
        return false;
    }

    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $name = $file->getFilename();
            if ($name === '' || $name[0] === '.') {
                continue;
            }

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($extension === 'zip' || $extension === 'json' || $extension === 'txt') {
                continue;
            }

            return true;
        }
    } catch (Exception $e) {
        return false;
    }

    return false;
}

function rp_remote_extract_study_zip(string $zipPath, string $targetFolder): bool
{
    if (!class_exists('ZipArchive') || !is_file($zipPath) || !is_readable($zipPath)) {
        return false;
    }

    if (!is_dir($targetFolder) && !@mkdir($targetFolder, 0775, true)) {
        return false;
    }

    $targetReal = realpath($targetFolder);
    if ($targetReal === false) {
        return false;
    }

    $lockPath = $targetReal . DIRECTORY_SEPARATOR . '.rp_extracting';
    if (is_file($lockPath) && (time() - (int) @filemtime($lockPath)) < 300) {
        return false;
    }

    @file_put_contents($lockPath, (string) time());

    $zip = new ZipArchive();
    $ok = false;
    if ($zip->open($zipPath) === true) {
        $ok = (bool) $zip->extractTo($targetReal);
        $zip->close();
    }

    @unlink($lockPath);
    return $ok;
}

function rp_remote_prepare_study_folder(string $folder): void
{
    if (!is_dir($folder) || rp_remote_folder_has_dicom_payload($folder)) {
        return;
    }

    $zips = glob(rtrim($folder, "\\/") . DIRECTORY_SEPARATOR . '*.zip') ?: [];
    foreach ($zips as $zipPath) {
        rp_remote_extract_study_zip($zipPath, $folder);
        if (rp_remote_folder_has_dicom_payload($folder)) {
            return;
        }
    }
}

function rp_remote_try_study_folder(string $folder)
{
    if (!is_dir($folder)) {
        return false;
    }

    $real = realpath($folder);
    if ($real === false) {
        return false;
    }

    rp_remote_prepare_study_folder($real);
    return $real;
}

function rp_remote_study_storage_candidates(mysqli $con, string $studyint): array
{
    $candidates = [
        'identifiers' => [$studyint, rp_remote_storage_safe_name($studyint)],
        'package_paths' => [],
    ];

    $studyStmt = mysqli_prepare($con, "SELECT accession_number FROM study WHERE studyint = ? LIMIT 1");
    if ($studyStmt) {
        mysqli_stmt_bind_param($studyStmt, 's', $studyint);
        mysqli_stmt_execute($studyStmt);
        $studyRes = mysqli_stmt_get_result($studyStmt);
        if ($studyRes instanceof mysqli_result && ($row = mysqli_fetch_assoc($studyRes))) {
            $accession = trim((string) ($row['accession_number'] ?? ''));
            if ($accession !== '') {
                $candidates['identifiers'][] = $accession;
                $candidates['identifiers'][] = rp_remote_storage_safe_name($accession);
            }
        }
        mysqli_stmt_close($studyStmt);
    }

    $orderStmt = mysqli_prepare($con, "SELECT order_uid, accession_number, package_path FROM remote_report_orders WHERE studyint = ? ORDER BY id DESC LIMIT 10");
    if ($orderStmt) {
        mysqli_stmt_bind_param($orderStmt, 's', $studyint);
        mysqli_stmt_execute($orderStmt);
        $orderRes = mysqli_stmt_get_result($orderStmt);
        if ($orderRes instanceof mysqli_result) {
            while ($row = mysqli_fetch_assoc($orderRes)) {
                foreach (['order_uid', 'accession_number'] as $key) {
                    $value = trim((string) ($row[$key] ?? ''));
                    if ($value !== '') {
                        $candidates['identifiers'][] = $value;
                        $candidates['identifiers'][] = rp_remote_storage_safe_name($value);
                    }
                }

                $packagePath = trim((string) ($row['package_path'] ?? ''));
                if ($packagePath !== '') {
                    $candidates['package_paths'][] = $packagePath;
                }
            }
        }
        mysqli_stmt_close($orderStmt);
    }

    $candidates['identifiers'] = array_values(array_unique(array_filter($candidates['identifiers'])));
    $candidates['package_paths'] = array_values(array_unique(array_filter($candidates['package_paths'])));

    return $candidates;
}

function rp_remote_resolve_package_path(string $path, string $baseReal)
{
    $paths = [$path];
    $remoteRoot = realpath(__DIR__ . '/..');
    if ($remoteRoot !== false && !preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/])/', $path)) {
        $paths[] = $remoteRoot . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }

    foreach ($paths as $candidate) {
        $candidate = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $candidate);
        if (is_dir($candidate)) {
            $real = rp_remote_try_study_folder($candidate);
            if ($real !== false && strpos($real, $baseReal) === 0) {
                return $real;
            }
        }

        if (is_file($candidate) && strtolower(pathinfo($candidate, PATHINFO_EXTENSION)) === 'zip') {
            $zipReal = realpath($candidate);
            $target = realpath(dirname($candidate));
            if ($zipReal !== false && $target !== false && strpos($target, $baseReal) === 0) {
                rp_remote_extract_study_zip($zipReal, $target);
                return $target;
            }
        }
    }

    return false;
}

function rp_remote_cache_study_folder(string $cachePath, array &$cache, string $studyint, string $folder): void
{
    $cache[$studyint] = $folder;
    $logDir = dirname($cachePath);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    @file_put_contents($cachePath, json_encode($cache));
}

function rp_remote_resolve_study_folder(mysqli $con, string $studyint)
{
    $baseDirectory = rp_remote_get_pacs_base_directory($con);
    if (!is_dir($baseDirectory)) {
        @mkdir($baseDirectory, 0775, true);
    }

    $baseReal = realpath($baseDirectory);
    if ($baseReal === false) {
        return false;
    }

    $cachePath = __DIR__ . '/../logs/study-path-cache.json';
    $cache = [];
    if (is_file($cachePath)) {
        $raw = @file_get_contents($cachePath);
        $decoded = json_decode((string) $raw, true);
        if (is_array($decoded)) {
            $cache = $decoded;
        }
    }

    if (isset($cache[$studyint]) && is_string($cache[$studyint]) && is_dir($cache[$studyint])) {
        $real = realpath($cache[$studyint]);
        if ($real !== false && strpos($real, $baseReal) === 0) {
            rp_remote_prepare_study_folder($real);
            return $real;
        }
    }

    $candidates = rp_remote_study_storage_candidates($con, $studyint);

    foreach ($candidates['package_paths'] as $packagePath) {
        $real = rp_remote_resolve_package_path($packagePath, $baseReal);
        if ($real !== false) {
            rp_remote_cache_study_folder($cachePath, $cache, $studyint, $real);
            return $real;
        }
    }

    foreach ($candidates['identifiers'] as $identifier) {
        $direct = $baseReal . DIRECTORY_SEPARATOR . $identifier;
        $real = rp_remote_try_study_folder($direct);
        if ($real !== false) {
            rp_remote_cache_study_folder($cachePath, $cache, $studyint, $real);
            return $real;
        }
    }

    if (!rp_remote_allow_recursive_lookup($con)) {
        return false;
    }

    $identifierMap = array_fill_keys($candidates['identifiers'], true);

    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseReal, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $file) {
            if ($file->isDir() && isset($identifierMap[$file->getFilename()])) {
                $real = rp_remote_try_study_folder($file->getPathname());
                if ($real !== false) {
                    rp_remote_cache_study_folder($cachePath, $cache, $studyint, $real);
                    return $real;
                }
            }
        }
    } catch (Exception $e) {
        return false;
    }

    return false;
}