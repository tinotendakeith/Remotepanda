<?php
require_once __DIR__ . '/../includes/api.php';

rp_cloud_ensure_schema($con);
rp_cloud_require_post();

$input = rp_cloud_input_json();
if (empty($input)) {
    $input = $_POST;
}

$report = $input['report'] ?? $input;
if (!is_array($report)) {
    rp_cloud_json(array('success' => false, 'error' => 'Report payload is required.'), 400);
}

$orderUid = trim((string) ($report['order_uid'] ?? ''));
$clinicId = trim((string) ($report['clinic_id'] ?? ''));
$studyint = trim((string) ($report['studyint'] ?? ''));
$accession = trim((string) ($report['accession_number'] ?? ''));
$reportText = trim((string) ($report['report_text'] ?? ''));
$reportedBy = trim((string) ($report['reported_by_username'] ?? ''));
rp_cloud_require_clinic_sync_key($con, $clinicId, $input);

if ($orderUid === '' || $studyint === '' || $reportText === '') {
    rp_cloud_audit($con, 'report_return_receive_failed', 'report_order', $orderUid, $clinicId, false, 'Missing order, study, or report text.');
    rp_cloud_json(array('success' => false, 'error' => 'order_uid, studyint, and report_text are required.'), 400);
}

if ($clinicId === '') {
    $stmt = mysqli_prepare($con, "SELECT clinic_id FROM cloud_report_orders WHERE order_uid = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $orderUid);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        $clinicId = trim((string) ($row['clinic_id'] ?? ''));
    }
}

$payloadJson = json_encode($report);
$stmt = mysqli_prepare($con, "INSERT INTO cloud_report_return_outbox
        (order_uid, clinic_id, studyint, accession_number, payload_json, report_text, reported_by_username, status, next_retry_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'queued', NOW())
    ON DUPLICATE KEY UPDATE clinic_id = VALUES(clinic_id), studyint = VALUES(studyint),
        accession_number = VALUES(accession_number), payload_json = VALUES(payload_json),
        report_text = VALUES(report_text), reported_by_username = VALUES(reported_by_username),
        status = 'queued', last_error = NULL, next_retry_at = NOW(), updated_at = NOW()");
if (!$stmt) {
    rp_cloud_json(array('success' => false, 'error' => 'Could not prepare report return insert.'), 500);
}
mysqli_stmt_bind_param($stmt, 'sssssss', $orderUid, $clinicId, $studyint, $accession, $payloadJson, $reportText, $reportedBy);
$ok = mysqli_stmt_execute($stmt);
$err = mysqli_stmt_error($stmt);
mysqli_stmt_close($stmt);
if (!$ok) {
    rp_cloud_audit($con, 'report_return_receive_failed', 'report_order', $orderUid, $clinicId, false, $err);
    rp_cloud_json(array('success' => false, 'error' => 'Could not queue report return.'), 500);
}

$up = mysqli_prepare($con, "UPDATE cloud_report_orders
    SET status = 'reported', reported_at = NOW(), updated_at = NOW()
    WHERE order_uid = ? LIMIT 1");
if ($up) {
    mysqli_stmt_bind_param($up, 's', $orderUid);
    mysqli_stmt_execute($up);
    mysqli_stmt_close($up);
}

rp_cloud_audit($con, 'report_return_received', 'report_order', $orderUid, $clinicId, true, 'Report queued for clinic pickup.');

rp_cloud_json(array(
    'success' => true,
    'order_uid' => $orderUid,
    'clinic_id' => $clinicId,
    'status' => 'queued'
));
?>
