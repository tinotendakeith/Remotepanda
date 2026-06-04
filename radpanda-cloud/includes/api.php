<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/schema.php';

function rp_cloud_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    header('Cache-Control: no-store');
    echo json_encode($payload);
    exit;
}

function rp_cloud_input_json(): array
{
    $raw = (string) file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : array();
}

function rp_cloud_require_post(): void
{
    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        rp_cloud_json(array('success' => false, 'error' => 'POST required.'), 405);
    }
}

function rp_cloud_require_sync_key(): void
{
    $configured = trim((string) RP_CLOUD_SYNC_KEY);
    if ($configured === '') {
        return;
    }
    $provided = trim((string) ($_SERVER['HTTP_X_RADPANDA_SYNC_KEY'] ?? ($_POST['api_key'] ?? '')));
    if ($provided === '') {
        $json = rp_cloud_input_json();
        $provided = trim((string) ($json['api_key'] ?? ''));
    }
    if (!hash_equals($configured, $provided)) {
        rp_cloud_json(array('success' => false, 'error' => 'Unauthorized.'), 401);
    }
}

function rp_cloud_request_sync_key(?array $input = null): string
{
    $provided = trim((string) ($_SERVER['HTTP_X_RADPANDA_SYNC_KEY'] ?? ($_POST['api_key'] ?? '')));
    if ($provided !== '') {
        return $provided;
    }
    if ($input === null) {
        $input = rp_cloud_input_json();
    }
    return trim((string) ($input['api_key'] ?? ''));
}

function rp_cloud_require_clinic_sync_key(mysqli $con, string $clinicId, ?array $input = null): void
{
    $provided = rp_cloud_request_sync_key($input);
    $global = trim((string) RP_CLOUD_SYNC_KEY);
    if ($global !== '') {
        if ($provided !== '' && hash_equals($global, $provided)) {
            return;
        }
        rp_cloud_json(array('success' => false, 'error' => 'Unauthorized.'), 401);
    }

    $clinicId = trim($clinicId);
    if ($clinicId === '') {
        return;
    }

    $stmt = mysqli_prepare($con, "SELECT api_key_hash FROM cloud_clinics WHERE clinic_uid = ? AND status = 'active' LIMIT 1");
    if (!$stmt) {
        rp_cloud_json(array('success' => false, 'error' => 'Could not verify clinic credentials.'), 500);
    }
    mysqli_stmt_bind_param($stmt, 's', $clinicId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    $hash = trim((string) ($row['api_key_hash'] ?? ''));
    if ($hash === '') {
        return;
    }
    if ($provided !== '' && password_verify($provided, $hash)) {
        return;
    }

    rp_cloud_json(array('success' => false, 'error' => 'Unauthorized.'), 401);
}

function rp_cloud_safe_name(string $value, string $fallback = 'item'): string
{
    $clean = preg_replace('/[^A-Za-z0-9_.-]/', '_', trim($value));
    return $clean !== '' ? $clean : $fallback;
}

function rp_cloud_audit(mysqli $con, string $eventType, string $entityType, string $entityId, string $clinicId, bool $success, string $message = '', array $context = array()): void
{
    rp_cloud_ensure_schema($con);
    $eventTypeEsc = mysqli_real_escape_string($con, $eventType);
    $entityTypeEsc = mysqli_real_escape_string($con, $entityType);
    $entityIdEsc = mysqli_real_escape_string($con, $entityId);
    $clinicIdEsc = mysqli_real_escape_string($con, $clinicId);
    $messageEsc = mysqli_real_escape_string($con, $message);
    $contextEsc = mysqli_real_escape_string($con, json_encode($context));
    $ok = $success ? 1 : 0;
    mysqli_query($con, "INSERT INTO cloud_audit_log (event_type, entity_type, entity_id, clinic_id, success, message, context_json)
        VALUES ('{$eventTypeEsc}', '{$entityTypeEsc}', '{$entityIdEsc}', '{$clinicIdEsc}', {$ok}, '{$messageEsc}', '{$contextEsc}')");
}

function rp_cloud_normalize_token_list(string $value): array
{
    $tokens = preg_split('/[,\|;\/\s]+/', strtoupper($value));
    $clean = array();
    foreach ($tokens as $token) {
        $token = trim((string) $token);
        if ($token !== '') {
            $clean[$token] = true;
        }
    }
    return array_keys($clean);
}

function rp_cloud_radiologist_matches_modality(array $radiologist, string $modality): bool
{
    $scope = trim((string) ($radiologist['modalities'] ?? ''));
    if ($scope === '') {
        return true;
    }
    $modality = strtoupper(trim($modality));
    if ($modality === '') {
        return true;
    }
    return in_array($modality, rp_cloud_normalize_token_list($scope), true);
}

function rp_cloud_radiologist_under_daily_limit(array $radiologist): bool
{
    $maxDaily = (int) ($radiologist['max_daily_cases'] ?? 0);
    if ($maxDaily <= 0) {
        return true;
    }
    return (int) ($radiologist['today_count'] ?? 0) < $maxDaily;
}

function rp_cloud_assignment_candidate_available(array $radiologist, string $modality): bool
{
    if (strtolower((string) ($radiologist['status'] ?? '')) !== 'active') {
        return false;
    }
    if (strtolower((string) ($radiologist['availability_status'] ?? 'available')) !== 'available') {
        return false;
    }
    if (!rp_cloud_radiologist_matches_modality($radiologist, $modality)) {
        return false;
    }
    return rp_cloud_radiologist_under_daily_limit($radiologist);
}

function rp_cloud_find_assignment_radiologist(mysqli $con, string $clinicId, string $modality, string $procedure): array
{
    rp_cloud_ensure_schema($con);
    $clinicId = trim($clinicId);
    $modality = strtoupper(trim($modality));
    $procedureText = strtoupper(trim($procedure));

    $ruleSql = "SELECT ar.*, r.id AS radiologist_id, r.username, r.display_name, r.email, r.status AS radiologist_status,
            r.availability_status, r.modalities, r.max_daily_cases,
            (SELECT COUNT(*) FROM cloud_report_orders o
                WHERE o.radiologist_username = r.username
                AND DATE(COALESCE(o.assigned_at, o.received_at)) = CURDATE()) AS today_count,
            (SELECT COUNT(*) FROM cloud_report_orders o
                WHERE o.radiologist_username = r.username
                AND o.status IN ('received','assigned','sent_to_remotepanda','in_progress')) AS open_count
        FROM cloud_assignment_rules ar
        INNER JOIN cloud_radiologists r ON r.username = ar.radiologist_username
        WHERE ar.status = 'active'
        ORDER BY ar.priority ASC, ar.id ASC";
    $rules = mysqli_query($con, $ruleSql);
    if ($rules) {
        while ($rule = mysqli_fetch_assoc($rules)) {
            $ruleClinic = trim((string) ($rule['clinic_uid'] ?? ''));
            if ($ruleClinic !== '' && strcasecmp($ruleClinic, $clinicId) !== 0) {
                continue;
            }

            $ruleModality = strtoupper(trim((string) ($rule['modality'] ?? '')));
            if ($ruleModality !== '' && $modality !== '' && $ruleModality !== $modality) {
                continue;
            }

            $ruleProcedure = strtoupper(trim((string) ($rule['procedure_text'] ?? '')));
            if ($ruleProcedure !== '' && ($procedureText === '' || strpos($procedureText, $ruleProcedure) === false)) {
                continue;
            }

            if (!rp_cloud_assignment_candidate_available(array(
                'status' => (string) ($rule['radiologist_status'] ?? ''),
                'availability_status' => (string) ($rule['availability_status'] ?? ''),
                'modalities' => (string) ($rule['modalities'] ?? ''),
                'max_daily_cases' => (int) ($rule['max_daily_cases'] ?? 0),
                'today_count' => (int) ($rule['today_count'] ?? 0),
            ), $modality)) {
                continue;
            }

            return array(
                'id' => (int) ($rule['radiologist_id'] ?? 0),
                'username' => (string) ($rule['username'] ?? ''),
                'display_name' => (string) ($rule['display_name'] ?? ''),
                'source' => 'rule',
                'rule_id' => (int) ($rule['id'] ?? 0),
                'message' => 'Assigned by cloud rule: ' . (string) ($rule['rule_name'] ?? 'assignment rule'),
            );
        }
    }

    $poolSql = "SELECT r.*,
            (SELECT COUNT(*) FROM cloud_report_orders o
                WHERE o.radiologist_username = r.username
                AND DATE(COALESCE(o.assigned_at, o.received_at)) = CURDATE()) AS today_count,
            (SELECT COUNT(*) FROM cloud_report_orders o
                WHERE o.radiologist_username = r.username
                AND o.status IN ('received','assigned','sent_to_remotepanda','in_progress')) AS open_count
        FROM cloud_radiologists r
        WHERE r.status = 'active' AND r.availability_status = 'available'
        ORDER BY open_count ASC, today_count ASC, COALESCE(r.last_seen_at, r.updated_at) DESC, r.display_name ASC";
    $pool = mysqli_query($con, $poolSql);
    if ($pool) {
        while ($radiologist = mysqli_fetch_assoc($pool)) {
            if (!rp_cloud_assignment_candidate_available($radiologist, $modality)) {
                continue;
            }
            return array(
                'id' => (int) ($radiologist['id'] ?? 0),
                'username' => (string) ($radiologist['username'] ?? ''),
                'display_name' => (string) ($radiologist['display_name'] ?? ''),
                'source' => 'pool',
                'rule_id' => 0,
                'message' => 'Assigned by available radiologist pool.',
            );
        }
    }

    return array();
}
?>
