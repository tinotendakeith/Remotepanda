<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/api_security.php';
require_once __DIR__ . '/../includes/remote_reporting_service.php';

rp_remote_require_global_api_enabled($con);

if (!rp_remote_feature_enabled($con, 'feature_remote_sync_receiver_enabled', true)) {
    rp_remote_api_log($con, 'sync_receiver_disabled', false, 503, 'Sync receiver disabled');
    rp_remote_json_response(array('success' => false, 'error' => 'Remote sync receiver is disabled.'), 503);
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    rp_remote_json_response(array('success' => false, 'error' => 'POST required.'), 405);
}

$configuredKey = trim(rp_remote_setting_get($con, 'remote_sync_api_key', ''));
$providedKey = trim((string) ($_SERVER['HTTP_X_RADPANDA_SYNC_KEY'] ?? ($_POST['api_key'] ?? '')));
if ($configuredKey !== '' && !hash_equals($configuredKey, $providedKey)) {
    rp_remote_api_log($con, 'sync_auth_failed', false, 401, 'Invalid sync API key');
    rp_remote_json_response(array('success' => false, 'error' => 'Unauthorized.'), 401);
}

function rp_remote_sync_ensure_schema(mysqli $con): void
{
    @mysqli_query($con, "CREATE TABLE IF NOT EXISTS remote_report_orders (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        order_uid VARCHAR(80) NOT NULL UNIQUE,
        clinic_id VARCHAR(120) NOT NULL DEFAULT '',
        branch VARCHAR(120) NULL,
        studyint VARCHAR(255) NOT NULL,
        accession_number VARCHAR(80) NULL,
        patient_id INT NULL,
        patient_name VARCHAR(255) NOT NULL DEFAULT '',
        modality VARCHAR(80) NULL,
        procedure_name VARCHAR(255) NULL,
        radiologist_id INT NULL,
        radiologist_username VARCHAR(191) NULL,
        local_invoice_id BIGINT NULL,
        package_path TEXT NULL,
        package_size BIGINT NOT NULL DEFAULT 0,
        payload_json MEDIUMTEXT NULL,
        status VARCHAR(40) NOT NULL DEFAULT 'received',
        received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_remote_orders_studyint (studyint),
        KEY idx_remote_orders_radiologist (radiologist_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @mysqli_query($con, "CREATE TABLE IF NOT EXISTS remote_sync_uploads (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        order_uid VARCHAR(80) NOT NULL,
        studyint VARCHAR(255) NOT NULL,
        upload_status VARCHAR(40) NOT NULL DEFAULT 'received',
        file_name VARCHAR(255) NULL,
        file_size BIGINT NOT NULL DEFAULT 0,
        extract_path TEXT NULL,
        message TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_upload_order (order_uid),
        KEY idx_upload_studyint (studyint)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function rp_remote_sync_safe_name(string $value, string $fallback = 'study'): string
{
    $clean = preg_replace('/[^A-Za-z0-9_.-]/', '_', trim($value));
    return $clean !== '' ? $clean : $fallback;
}

function rp_remote_sync_json_response(array $payload, int $code = 200): void
{
    rp_remote_json_response($payload, $code);
}

function rp_remote_sync_upsert_study(mysqli $con, array $payload): void
{
    $studyint = trim((string) ($payload['studyint'] ?? ''));
    $accession = (int) preg_replace('/\D+/', '', (string) ($payload['accession_number'] ?? '0'));
    if ($accession <= 0) {
        $accession = abs((int) crc32($studyint));
    }

    $studyId = (int) ($payload['event_id'] ?? $payload['study_id'] ?? $accession);
    $patientId = (int) ($payload['patient_id'] ?? 0);
    $patientName = trim((string) ($payload['patient_name'] ?? ''));
    $dateOfBirth = trim((string) ($payload['date_of_birth'] ?? ''));
    $gender = trim((string) ($payload['gender'] ?? ''));
    $requestingPhysician = trim((string) ($payload['requesting_physician'] ?? ''));
    $procedure = trim((string) ($payload['procedure_name'] ?? ''));
    $modality = trim((string) ($payload['modality'] ?? ''));
    $startDate = trim((string) ($payload['start_date'] ?? ''));
    $technicianName = trim((string) ($payload['technician_name'] ?? ''));
    $radiologistUsername = trim((string) ($payload['radiologist_username'] ?? ''));
    $radiologistId = (int) ($payload['radiologist_id'] ?? 0);

    $stmt = mysqli_prepare($con, "SELECT accession_number FROM study WHERE studyint = ? OR accession_number = ? LIMIT 1");
    $existingAccession = 0;
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'si', $studyint, $accession);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
        $existingAccession = $row ? (int) ($row['accession_number'] ?? 0) : 0;
    }

    if ($existingAccession > 0) {
        $up = mysqli_prepare($con, "UPDATE study
            SET study_id=?, patient_id=?, Name=?, date_of_birth=NULLIF(?, ''), gender=?, requesting_physician=?,
                requested_procedure=?, modality=?, start_date=NULLIF(?, ''), technician_name=?, study=?, status='Awaiting Report',
                studyint=?, reporting_radiologist=?, assigned_radiologist_id=?, requires_report=1,
                report_required_by='radiologist', assignment_integrity_status='assigned', assignment_updated_at=NOW()
            WHERE accession_number=? LIMIT 1");
        if ($up) {
            mysqli_stmt_bind_param($up, 'iisssssssssssii', $studyId, $patientId, $patientName, $dateOfBirth, $gender, $requestingPhysician, $procedure, $modality, $startDate, $technicianName, $procedure, $studyint, $radiologistUsername, $radiologistId, $existingAccession);
            mysqli_stmt_execute($up);
            mysqli_stmt_close($up);
        }
        return;
    }

    $empty = '';
    $status = 'Awaiting Report';
    $requiredBy = 'radiologist';
    $integrity = 'assigned';
    $stmt = mysqli_prepare($con, "INSERT INTO study (
            accession_number, study_id, patient_id, Name, date_of_birth, gender, requesting_physician, requested_procedure, modality,
            start_date, technician_name, textarea, templateed, study, status, studyint, reporting_radiologist,
            requires_report, report_required_by, assigned_radiologist_id, assignment_integrity_status, assignment_updated_at
        ) VALUES (?, ?, ?, ?, NULLIF(?, ''), ?, ?, ?, ?, NULLIF(?, ''), ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, NOW())");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'iiisssssssssssssssis', $accession, $studyId, $patientId, $patientName, $dateOfBirth, $gender, $requestingPhysician, $procedure, $modality, $startDate, $technicianName, $empty, $empty, $procedure, $status, $studyint, $radiologistUsername, $requiredBy, $radiologistId, $integrity);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

rp_remote_sync_ensure_schema($con);
rp_remote_reporting_ensure_schema($con);

$payloadRaw = (string) ($_POST['payload_json'] ?? '');
$payload = json_decode($payloadRaw, true);
if (!is_array($payload)) {
    rp_remote_api_log($con, 'sync_validation_failed', false, 400, 'Invalid payload JSON');
    rp_remote_sync_json_response(array('success' => false, 'error' => 'Invalid payload.'), 400);
}

$orderUid = trim((string) ($payload['order_uid'] ?? ''));
$studyint = trim((string) ($payload['studyint'] ?? ''));
if ($orderUid === '' || $studyint === '') {
    rp_remote_api_log($con, 'sync_validation_failed', false, 400, 'Missing order or study identifier', $studyint);
    rp_remote_sync_json_response(array('success' => false, 'error' => 'Missing order or study identifier.'), 400);
}

if (empty($_FILES['study_package']) || !is_uploaded_file($_FILES['study_package']['tmp_name'])) {
    rp_remote_api_log($con, 'sync_validation_failed', false, 400, 'Missing study package', $studyint);
    rp_remote_sync_json_response(array('success' => false, 'error' => 'Missing study package.'), 400);
}

$baseDir = rp_remote_get_pacs_base_directory($con);
if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true)) {
    rp_remote_api_log($con, 'sync_storage_failed', false, 500, 'Could not create PACS base directory', $studyint, array('base' => $baseDir));
    rp_remote_sync_json_response(array('success' => false, 'error' => 'Could not create remote storage directory.'), 500);
}

$studyDir = rtrim($baseDir, "\\/") . DIRECTORY_SEPARATOR . rp_remote_sync_safe_name($studyint);
if (!is_dir($studyDir) && !mkdir($studyDir, 0775, true)) {
    rp_remote_api_log($con, 'sync_storage_failed', false, 500, 'Could not create study directory', $studyint);
    rp_remote_sync_json_response(array('success' => false, 'error' => 'Could not create study storage directory.'), 500);
}

$zipPath = $studyDir . DIRECTORY_SEPARATOR . rp_remote_sync_safe_name($orderUid, 'order') . '.zip';
if (!move_uploaded_file($_FILES['study_package']['tmp_name'], $zipPath)) {
    rp_remote_api_log($con, 'sync_upload_failed', false, 500, 'Could not move uploaded package', $studyint);
    rp_remote_sync_json_response(array('success' => false, 'error' => 'Could not store uploaded package.'), 500);
}

$extractOk = false;
$extractMessage = 'ZIP stored. Extraction deferred.';

rp_remote_sync_upsert_study($con, $payload);

$orderPayload = json_encode($payload);
$clinicId = (string) ($payload['clinic_id'] ?? '');
$branch = (string) ($payload['branch'] ?? '');
$accession = (string) ($payload['accession_number'] ?? '');
$patientId = (int) ($payload['patient_id'] ?? 0);
$patientName = (string) ($payload['patient_name'] ?? '');
$modality = (string) ($payload['modality'] ?? '');
$procedure = (string) ($payload['procedure_name'] ?? '');
$radiologistId = (int) ($payload['radiologist_id'] ?? 0);
$radiologistUsername = (string) ($payload['radiologist_username'] ?? '');
$invoiceId = (int) ($payload['invoice_id'] ?? 0);
$packageSize = (int) ($_FILES['study_package']['size'] ?? filesize($zipPath));
$status = 'received_zip_only';

$stmt = mysqli_prepare($con, "INSERT INTO remote_report_orders (
        order_uid, clinic_id, branch, studyint, accession_number, patient_id, patient_name, modality, procedure_name,
        radiologist_id, radiologist_username, local_invoice_id, package_path, package_size, payload_json, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE package_path=VALUES(package_path), package_size=VALUES(package_size), payload_json=VALUES(payload_json), status=VALUES(status), updated_at=NOW()");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'sssssisssisisiis', $orderUid, $clinicId, $branch, $studyint, $accession, $patientId, $patientName, $modality, $procedure, $radiologistId, $radiologistUsername, $invoiceId, $zipPath, $packageSize, $orderPayload, $status);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

$fileName = (string) ($_FILES['study_package']['name'] ?? basename($zipPath));
$uploadStmt = mysqli_prepare($con, "INSERT INTO remote_sync_uploads (order_uid, studyint, upload_status, file_name, file_size, extract_path, message) VALUES (?, ?, ?, ?, ?, ?, ?)");
if ($uploadStmt) {
    mysqli_stmt_bind_param($uploadStmt, 'ssssiss', $orderUid, $studyint, $status, $fileName, $packageSize, $studyDir, $extractMessage);
    mysqli_stmt_execute($uploadStmt);
    mysqli_stmt_close($uploadStmt);
}

rp_remote_api_log($con, 'sync_upload_received', true, 200, 'Report order package received', $studyint, array(
    'order_uid' => $orderUid,
    'status' => $status,
    'package_size' => $packageSize,
    'extract_message' => $extractMessage
));

$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$remoteBaseUrl = '';
$apiPos = strpos($scriptName, '/api/');
if ($apiPos !== false) {
    $remoteBaseUrl = substr($scriptName, 0, $apiPos);
}
$remoteBaseUrl = rtrim($remoteBaseUrl, '/');

rp_remote_sync_json_response(array(
    'success' => true,
    'order_uid' => $orderUid,
    'studyint' => $studyint,
    'status' => $status,
    'extract_ok' => $extractOk,
    'extract_message' => $extractMessage,
    'viewer_url' => $remoteBaseUrl . '/viewer/index.php?studyint=' . rawurlencode($studyint)
));
?>
