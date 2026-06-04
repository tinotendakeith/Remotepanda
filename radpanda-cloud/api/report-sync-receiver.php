<?php
require_once __DIR__ . '/../includes/api.php';

rp_cloud_ensure_schema($con);
rp_cloud_require_post();

$payloadRaw = (string) ($_POST['payload_json'] ?? '');
$payload = json_decode($payloadRaw, true);
if (!is_array($payload)) {
    rp_cloud_audit($con, 'sync_validation_failed', 'report_order', '', '', false, 'Invalid payload JSON');
    rp_cloud_json(array('success' => false, 'error' => 'Invalid payload.'), 400);
}

$orderUid = trim((string) ($payload['order_uid'] ?? ''));
$studyint = trim((string) ($payload['studyint'] ?? ''));
$clinicId = trim((string) ($payload['clinic_id'] ?? ''));
if ($clinicId === '') {
    $clinicId = 'local-clinic';
}
rp_cloud_require_clinic_sync_key($con, $clinicId, $payload);

if ($orderUid === '' || $studyint === '') {
    rp_cloud_audit($con, 'sync_validation_failed', 'report_order', $orderUid, $clinicId, false, 'Missing order or study identifier', $payload);
    rp_cloud_json(array('success' => false, 'error' => 'Missing order or study identifier.'), 400);
}

if (empty($_FILES['study_package']) || !is_uploaded_file($_FILES['study_package']['tmp_name'])) {
    rp_cloud_audit($con, 'sync_validation_failed', 'report_order', $orderUid, $clinicId, false, 'Missing study package', $payload);
    rp_cloud_json(array('success' => false, 'error' => 'Missing study package.'), 400);
}

$baseDir = RP_CLOUD_STORAGE_DIR . DIRECTORY_SEPARATOR . 'study-packages';
if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true)) {
    rp_cloud_audit($con, 'sync_storage_failed', 'report_order', $orderUid, $clinicId, false, 'Could not create cloud package directory');
    rp_cloud_json(array('success' => false, 'error' => 'Could not create cloud package directory.'), 500);
}

$clinicDir = $baseDir . DIRECTORY_SEPARATOR . rp_cloud_safe_name($clinicId, 'clinic');
$studyDir = $clinicDir . DIRECTORY_SEPARATOR . rp_cloud_safe_name($studyint, 'study');
if (!is_dir($studyDir) && !mkdir($studyDir, 0775, true)) {
    rp_cloud_audit($con, 'sync_storage_failed', 'report_order', $orderUid, $clinicId, false, 'Could not create cloud study directory');
    rp_cloud_json(array('success' => false, 'error' => 'Could not create cloud study directory.'), 500);
}

$zipPath = $studyDir . DIRECTORY_SEPARATOR . rp_cloud_safe_name($orderUid, 'order') . '.zip';
if (!move_uploaded_file($_FILES['study_package']['tmp_name'], $zipPath)) {
    rp_cloud_audit($con, 'sync_upload_failed', 'report_order', $orderUid, $clinicId, false, 'Could not store uploaded package');
    rp_cloud_json(array('success' => false, 'error' => 'Could not store uploaded package.'), 500);
}

$extractOk = false;
$extractMessage = '';
$extractDir = $studyDir . DIRECTORY_SEPARATOR . 'dicom';
if (!is_dir($extractDir)) {
    @mkdir($extractDir, 0775, true);
}
if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($zipPath) === true) {
        $extractOk = $zip->extractTo($extractDir);
        $zip->close();
        $extractMessage = $extractOk ? 'Extracted.' : 'ZIP extraction failed.';
    } else {
        $extractMessage = 'Could not open ZIP package.';
    }
} else {
    $extractMessage = 'ZipArchive is not enabled on the cloud receiver.';
}

$clinicEsc = mysqli_real_escape_string($con, $clinicId);
$branch = trim((string) ($payload['branch'] ?? ''));
$branchEsc = mysqli_real_escape_string($con, $branch);
mysqli_query($con, "INSERT INTO cloud_clinics (clinic_uid, clinic_name, default_branch, last_seen_at)
    VALUES ('{$clinicEsc}', '{$clinicEsc}', '{$branchEsc}', NOW())
    ON DUPLICATE KEY UPDATE default_branch = IF(default_branch = '', VALUES(default_branch), default_branch), last_seen_at = NOW(), updated_at = NOW()");

$accession = (string) ($payload['accession_number'] ?? '');
$patientId = (int) ($payload['patient_id'] ?? 0);
$patientName = (string) ($payload['patient_name'] ?? '');
$dob = (string) ($payload['date_of_birth'] ?? '');
$gender = (string) ($payload['gender'] ?? '');
$requesting = (string) ($payload['requesting_physician'] ?? '');
$modality = (string) ($payload['modality'] ?? '');
$procedure = (string) ($payload['procedure_name'] ?? '');
$orthancStudyId = (string) ($payload['orthanc_study_id'] ?? '');
$radiologistUsername = trim((string) ($payload['radiologist_username'] ?? ''));
$radiologistId = (int) ($payload['radiologist_id'] ?? 0);
$invoiceId = (int) ($payload['invoice_id'] ?? 0);
$packagePolicy = (string) ($payload['package_policy'] ?? 'full_dicom_zip');
$packageSize = (int) ($_FILES['study_package']['size'] ?? filesize($zipPath));
$assignment = array();

if ($radiologistUsername === '') {
    $assignment = rp_cloud_find_assignment_radiologist($con, $clinicId, $modality, $procedure);
    if (!empty($assignment['username'])) {
        $radiologistUsername = (string) $assignment['username'];
        $radiologistId = (int) ($assignment['id'] ?? 0);
        $payload['radiologist_username'] = $radiologistUsername;
        $payload['radiologist_id'] = $radiologistId;
        $payload['cloud_assignment_source'] = (string) ($assignment['source'] ?? 'pool');
        $payload['cloud_assignment_rule_id'] = (int) ($assignment['rule_id'] ?? 0);
    }
} else {
    $payload['cloud_assignment_source'] = 'clinic_selection';
}

if ($radiologistUsername !== '') {
    $usernameEsc = mysqli_real_escape_string($con, $radiologistUsername);
    mysqli_query($con, "INSERT INTO cloud_radiologists (username, display_name)
        VALUES ('{$usernameEsc}', '{$usernameEsc}')
        ON DUPLICATE KEY UPDATE updated_at = NOW()");
}

$orderPayload = json_encode($payload);
$assignedAt = $radiologistUsername !== '' ? date('Y-m-d H:i:s') : null;
$status = $extractOk
    ? ($radiologistUsername !== '' ? 'assigned' : 'received')
    : 'received_zip_only';

$stmt = mysqli_prepare($con, "INSERT INTO cloud_report_orders (
        order_uid, clinic_id, branch, studyint, accession_number, patient_id, patient_name, date_of_birth, gender,
        requesting_physician, modality, procedure_name, orthanc_study_id, radiologist_id, radiologist_username,
        local_invoice_id, package_policy, package_path, package_size, payload_json, status, assigned_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE package_path=VALUES(package_path), package_size=VALUES(package_size), payload_json=VALUES(payload_json),
        status=VALUES(status), radiologist_id=VALUES(radiologist_id), radiologist_username=VALUES(radiologist_username),
        assigned_at=IF(VALUES(radiologist_username) <> '', COALESCE(assigned_at, VALUES(assigned_at)), assigned_at),
        updated_at=NOW()");
if (!$stmt) {
    rp_cloud_json(array('success' => false, 'error' => 'Could not prepare cloud report order.'), 500);
}
mysqli_stmt_bind_param($stmt, 'sssssisssssssisississs', $orderUid, $clinicId, $branch, $studyint, $accession, $patientId, $patientName, $dob, $gender, $requesting, $modality, $procedure, $orthancStudyId, $radiologistId, $radiologistUsername, $invoiceId, $packagePolicy, $zipPath, $packageSize, $orderPayload, $status, $assignedAt);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

$fileName = (string) ($_FILES['study_package']['name'] ?? basename($zipPath));
$uploadStmt = mysqli_prepare($con, "INSERT INTO cloud_study_packages (order_uid, studyint, file_name, file_size, storage_path, extract_path, upload_status, message)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if ($uploadStmt) {
    mysqli_stmt_bind_param($uploadStmt, 'sssissss', $orderUid, $studyint, $fileName, $packageSize, $zipPath, $extractDir, $status, $extractMessage);
    mysqli_stmt_execute($uploadStmt);
    mysqli_stmt_close($uploadStmt);
}

rp_cloud_audit($con, 'sync_upload_received', 'report_order', $orderUid, $clinicId, true, 'Report order package received', array(
    'studyint' => $studyint,
    'status' => $status,
    'radiologist_username' => $radiologistUsername,
    'assignment_source' => (string) ($payload['cloud_assignment_source'] ?? ''),
    'package_size' => $packageSize,
    'extract_message' => $extractMessage
));

rp_cloud_json(array(
    'success' => true,
    'order_uid' => $orderUid,
    'studyint' => $studyint,
    'clinic_id' => $clinicId,
    'status' => $status,
    'radiologist_username' => $radiologistUsername,
    'assignment_source' => (string) ($payload['cloud_assignment_source'] ?? ''),
    'extract_ok' => $extractOk,
    'extract_message' => $extractMessage,
    'cloud_order_url' => '/radpanda-cloud/admin/order.php?order_uid=' . rawurlencode($orderUid)
));
?>
