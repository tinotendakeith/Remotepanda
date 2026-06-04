<?php
require_once __DIR__ . '/../includes/api.php';

rp_cloud_ensure_schema($con);
rp_cloud_require_post();

$input = rp_cloud_input_json();
if (empty($input)) {
    $input = $_POST;
}

$clinicId = trim((string) ($input['clinic_id'] ?? ''));
rp_cloud_require_clinic_sync_key($con, $clinicId, $input);
$limit = (int) ($input['limit'] ?? 10);
$limit = max(1, min(50, $limit));

$acks = $input['ack_order_uids'] ?? array();
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
        $stmt = mysqli_prepare($con, "UPDATE cloud_report_return_outbox ro
            LEFT JOIN cloud_report_orders r ON r.order_uid = ro.order_uid
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

$sql = "SELECT ro.order_uid, ro.studyint, ro.clinic_id, ro.accession_number, ro.payload_json, ro.report_text,
               ro.reported_by_username, ro.created_at
        FROM cloud_report_return_outbox ro
        WHERE {$where}
        ORDER BY ro.created_at ASC
        LIMIT {$limit}";
$stmt = mysqli_prepare($con, $sql);
if (!$stmt) {
    rp_cloud_json(array('success' => false, 'error' => 'Could not prepare return feed.'), 500);
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
    $payload['report_text'] = (string) ($row['report_text'] ?? ($payload['report_text'] ?? ''));
    $payload['reported_by_username'] = (string) ($row['reported_by_username'] ?? ($payload['reported_by_username'] ?? ''));
    if (empty($payload['reported_at'])) {
        $payload['reported_at'] = (string) ($row['created_at'] ?? date('Y-m-d H:i:s'));
    }
    $reports[] = $payload;
    $uids[] = (string) $payload['order_uid'];
}
mysqli_stmt_close($stmt);

foreach ($uids as $uid) {
    $uidEsc = mysqli_real_escape_string($con, $uid);
    mysqli_query($con, "UPDATE cloud_report_return_outbox SET attempts = attempts + 1, updated_at = NOW() WHERE order_uid = '{$uidEsc}' LIMIT 1");
}

rp_cloud_audit($con, 'report_return_feed', 'clinic', $clinicId, $clinicId, true, 'Report return feed served', array('count' => count($reports)));

rp_cloud_json(array(
    'success' => true,
    'clinic_id' => $clinicId,
    'count' => count($reports),
    'reports' => $reports
));
?>
