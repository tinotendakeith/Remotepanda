<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../includes/api_security.php';
require_once __DIR__ . '/../includes/remote_reporting_service.php';

rp_remote_require_global_api_enabled($con);
rp_remote_reporting_ensure_schema($con);

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    rp_remote_json_response(array('success' => false, 'error' => 'POST required.'), 405);
}

$configuredKey = trim(rp_remote_setting_get($con, 'remote_sync_api_key', ''));
$providedKey = trim((string) ($_SERVER['HTTP_X_RADPANDA_SYNC_KEY'] ?? ($_POST['api_key'] ?? '')));

$contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
$input = array();
if (stripos($contentType, 'application/json') !== false) {
    $decoded = json_decode((string) file_get_contents('php://input'), true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}
if ($providedKey === '' && isset($input['api_key'])) {
    $providedKey = trim((string) $input['api_key']);
}

if ($configuredKey !== '' && !hash_equals($configuredKey, $providedKey)) {
    rp_remote_api_log($con, 'report_return_auth_failed', false, 401, 'Invalid sync API key');
    rp_remote_json_response(array('success' => false, 'error' => 'Unauthorized.'), 401);
}

$clinicId = trim((string) ($input['clinic_id'] ?? ($_POST['clinic_id'] ?? '')));
$limit = (int) ($input['limit'] ?? ($_POST['limit'] ?? 10));
$limit = max(1, min(50, $limit));

$acks = $input['ack_order_uids'] ?? ($_POST['ack_order_uids'] ?? array());
if (is_string($acks)) {
    $decodedAcks = json_decode($acks, true);
    $acks = is_array($decodedAcks) ? $decodedAcks : array_filter(array_map('trim', explode(',', $acks)));
}
if (is_array($acks)) {
    foreach ($acks as $ackUid) {
        $ackUid = trim((string) $ackUid);
        if ($ackUid === '') {
            continue;
        }
        $stmt = mysqli_prepare($con, "UPDATE remote_report_return_outbox ro
            LEFT JOIN remote_report_orders r ON r.order_uid = ro.order_uid
            SET ro.status = 'sent', ro.sent_at = NOW(), ro.last_error = NULL, ro.updated_at = NOW(),
                r.status = 'returned', r.returned_at = NOW(), r.updated_at = NOW()
            WHERE ro.order_uid = ? " . ($clinicId !== '' ? "AND ro.clinic_id = ?" : "") . " LIMIT 1");
        if ($stmt) {
            if ($clinicId !== '') {
                mysqli_stmt_bind_param($stmt, 'ss', $ackUid, $clinicId);
            } else {
                mysqli_stmt_bind_param($stmt, 's', $ackUid);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

$where = "ro.status = 'queued' AND (ro.next_retry_at IS NULL OR ro.next_retry_at <= NOW())";
$types = '';
$params = array();
if ($clinicId !== '') {
    $where .= " AND ro.clinic_id = ?";
    $types .= 's';
    $params[] = $clinicId;
}

$sql = "SELECT ro.order_uid, ro.studyint, ro.clinic_id, ro.accession_number, ro.payload_json, ro.attempts, ro.created_at,
               r.reported_at, r.reported_by_username
        FROM remote_report_return_outbox ro
        LEFT JOIN remote_report_orders r ON r.order_uid = ro.order_uid
        WHERE {$where}
        ORDER BY ro.created_at ASC
        LIMIT {$limit}";
$stmt = mysqli_prepare($con, $sql);
if (!$stmt) {
    rp_remote_json_response(array('success' => false, 'error' => 'Could not prepare return feed.'), 500);
}
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$reports = array();
$uids = array();
while ($row = $res ? mysqli_fetch_assoc($res) : null) {
    if (!$row) {
        break;
    }
    $payload = json_decode((string) ($row['payload_json'] ?? ''), true);
    if (!is_array($payload)) {
        $payload = array();
    }
    $payload['order_uid'] = (string) ($row['order_uid'] ?? ($payload['order_uid'] ?? ''));
    $payload['studyint'] = (string) ($row['studyint'] ?? ($payload['studyint'] ?? ''));
    $payload['clinic_id'] = (string) ($row['clinic_id'] ?? ($payload['clinic_id'] ?? ''));
    $payload['accession_number'] = (string) ($row['accession_number'] ?? ($payload['accession_number'] ?? ''));
    if (empty($payload['reported_at']) && !empty($row['reported_at'])) {
        $payload['reported_at'] = (string) $row['reported_at'];
    }
    if (empty($payload['reported_by_username']) && !empty($row['reported_by_username'])) {
        $payload['reported_by_username'] = (string) $row['reported_by_username'];
    }
    $reports[] = $payload;
    $uids[] = (string) $payload['order_uid'];
}
mysqli_stmt_close($stmt);

foreach ($uids as $uid) {
    $uidEsc = mysqli_real_escape_string($con, $uid);
    mysqli_query($con, "UPDATE remote_report_return_outbox SET attempts = attempts + 1, updated_at = NOW() WHERE order_uid = '{$uidEsc}' LIMIT 1");
}

rp_remote_api_log($con, 'report_return_feed', true, 200, 'Report return feed served', '', array(
    'clinic_id' => $clinicId,
    'count' => count($reports)
));

rp_remote_json_response(array(
    'success' => true,
    'clinic_id' => $clinicId,
    'count' => count($reports),
    'reports' => $reports
));
?>
