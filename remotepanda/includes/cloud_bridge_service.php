<?php
require_once __DIR__ . '/database_config.php';
require_once __DIR__ . '/remote_reporting_service.php';
require_once __DIR__ . '/platform_settings.php';

function rp_remote_cloud_database_connect(): mysqli
{
    $cloudDb = getenv('REMOTEPANDA_CLOUD_DB_NAME');
    if ($cloudDb === false || trim((string) $cloudDb) === '') {
        $cfg = rp_remote_database_config();
        $cloudDb = (string) ($cfg['cloud_database'] ?? 'radpanda_cloud');
    }
    return rp_remote_database_connect((string) $cloudDb);
}

function rp_remote_cloud_bridge_safe_name(string $value, string $fallback = 'study'): string
{
    $clean = preg_replace('/[^A-Za-z0-9_.-]/', '_', trim($value));
    return $clean !== '' ? $clean : $fallback;
}

function rp_remote_cloud_bridge_upsert_study(mysqli $con, array $payload, array $order): void
{
    $studyint = trim((string) ($payload['studyint'] ?? ($order['studyint'] ?? '')));
    $accessionRaw = trim((string) ($payload['accession_number'] ?? ($order['accession_number'] ?? '')));
    $accession = (int) preg_replace('/\D+/', '', $accessionRaw);
    if ($accession <= 0) {
        $accession = abs((int) crc32($studyint));
    }

    $studyId = (int) ($payload['event_id'] ?? $payload['study_id'] ?? $accession);
    $patientId = (int) ($payload['patient_id'] ?? ($order['patient_id'] ?? 0));
    $patientName = trim((string) ($payload['patient_name'] ?? ($order['patient_name'] ?? '')));
    $dateOfBirth = trim((string) ($payload['date_of_birth'] ?? ($order['date_of_birth'] ?? '')));
    $gender = trim((string) ($payload['gender'] ?? ($order['gender'] ?? '')));
    $requestingPhysician = trim((string) ($payload['requesting_physician'] ?? ($order['requesting_physician'] ?? '')));
    $procedure = trim((string) ($payload['procedure_name'] ?? ($order['procedure_name'] ?? '')));
    $modality = trim((string) ($payload['modality'] ?? ($order['modality'] ?? '')));
    $startDate = trim((string) ($payload['start_date'] ?? ''));
    $technicianName = trim((string) ($payload['technician_name'] ?? ''));
    $radiologistUsername = trim((string) ($payload['radiologist_username'] ?? ($order['radiologist_username'] ?? '')));
    $radiologistId = (int) ($payload['radiologist_id'] ?? ($order['radiologist_id'] ?? 0));

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

function rp_remote_cloud_bridge_copy_package(mysqli $remoteCon, array $order): array
{
    $sourcePath = trim((string) ($order['package_path'] ?? ''));
    $studyint = trim((string) ($order['studyint'] ?? ''));
    $orderUid = trim((string) ($order['order_uid'] ?? ''));
    if ($sourcePath === '' || !is_file($sourcePath)) {
        return array('ok' => false, 'path' => '', 'message' => 'Cloud package file is missing.');
    }

    $baseDir = rp_remote_get_pacs_base_directory($remoteCon);
    if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true)) {
        return array('ok' => false, 'path' => '', 'message' => 'Could not create Remotepanda PACS storage directory.');
    }

    $studyDir = rtrim($baseDir, "\\/") . DIRECTORY_SEPARATOR . rp_remote_cloud_bridge_safe_name($studyint);
    if (!is_dir($studyDir) && !mkdir($studyDir, 0775, true)) {
        return array('ok' => false, 'path' => '', 'message' => 'Could not create Remotepanda study directory.');
    }

    $targetZip = $studyDir . DIRECTORY_SEPARATOR . rp_remote_cloud_bridge_safe_name($orderUid, 'order') . '.zip';
    if (!is_file($targetZip) && !copy($sourcePath, $targetZip)) {
        return array('ok' => false, 'path' => '', 'message' => 'Could not copy package into Remotepanda storage.');
    }

    $extractOk = false;
    $message = 'Stored ZIP package.';
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($targetZip) === true) {
            $extractOk = $zip->extractTo($studyDir);
            $zip->close();
            $message = $extractOk ? 'Copied and extracted.' : 'Copied, but extraction failed.';
        } else {
            $message = 'Copied, but could not open ZIP.';
        }
    }

    return array('ok' => true, 'path' => $targetZip, 'study_dir' => $studyDir, 'extract_ok' => $extractOk, 'message' => $message);
}

function rp_remote_cloud_bridge_upsert_order(mysqli $con, array $payload, array $order, array $package): void
{
    $orderUid = trim((string) ($order['order_uid'] ?? ($payload['order_uid'] ?? '')));
    $clinicId = trim((string) ($order['clinic_id'] ?? ($payload['clinic_id'] ?? '')));
    $branch = trim((string) ($order['branch'] ?? ($payload['branch'] ?? '')));
    $studyint = trim((string) ($order['studyint'] ?? ($payload['studyint'] ?? '')));
    $accession = trim((string) ($order['accession_number'] ?? ($payload['accession_number'] ?? '')));
    $patientId = (int) ($order['patient_id'] ?? ($payload['patient_id'] ?? 0));
    $patientName = trim((string) ($order['patient_name'] ?? ($payload['patient_name'] ?? '')));
    $modality = trim((string) ($order['modality'] ?? ($payload['modality'] ?? '')));
    $procedure = trim((string) ($order['procedure_name'] ?? ($payload['procedure_name'] ?? '')));
    $radiologistId = (int) ($order['radiologist_id'] ?? ($payload['radiologist_id'] ?? 0));
    $radiologistUsername = trim((string) ($order['radiologist_username'] ?? ($payload['radiologist_username'] ?? '')));
    $invoiceId = (int) ($order['local_invoice_id'] ?? ($payload['invoice_id'] ?? 0));
    $packagePath = trim((string) ($package['path'] ?? ($order['package_path'] ?? '')));
    $packageSize = (int) ($order['package_size'] ?? 0);
    $payloadJson = json_encode($payload);
    $status = 'received';

    $stmt = mysqli_prepare($con, "INSERT INTO remote_report_orders (
            order_uid, clinic_id, branch, studyint, accession_number, patient_id, patient_name, modality, procedure_name,
            radiologist_id, radiologist_username, local_invoice_id, package_path, package_size, payload_json, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE clinic_id=VALUES(clinic_id), branch=VALUES(branch), patient_name=VALUES(patient_name),
            package_path=VALUES(package_path), package_size=VALUES(package_size), payload_json=VALUES(payload_json),
            radiologist_username=VALUES(radiologist_username), updated_at=NOW()");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sssssisssisisiis', $orderUid, $clinicId, $branch, $studyint, $accession, $patientId, $patientName, $modality, $procedure, $radiologistId, $radiologistUsername, $invoiceId, $packagePath, $packageSize, $payloadJson, $status);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function rp_remote_cloud_bridge_import_orders(mysqli $remoteCon, int $limit = 25): array
{
    rp_remote_reporting_ensure_schema($remoteCon);
    rp_remote_settings_ensure($remoteCon);
    $cloudCon = rp_remote_cloud_database_connect();
    $limit = max(1, min(100, $limit));
    $summary = array('checked' => 0, 'imported' => 0, 'failed' => 0, 'errors' => array());

    $res = mysqli_query($cloudCon, "SELECT *
        FROM cloud_report_orders
        WHERE status IN ('received', 'received_zip_only', 'assigned', 'in_progress')
        ORDER BY received_at ASC
        LIMIT {$limit}");
    if (!$res) {
        $summary['failed']++;
        $summary['errors'][] = 'Could not query cloud report orders.';
        mysqli_close($cloudCon);
        return $summary;
    }

    while ($order = mysqli_fetch_assoc($res)) {
        $summary['checked']++;
        $payload = json_decode((string) ($order['payload_json'] ?? ''), true);
        if (!is_array($payload)) {
            $payload = array();
        }
        $payload = array_merge($payload, array(
            'order_uid' => (string) ($order['order_uid'] ?? ''),
            'clinic_id' => (string) ($order['clinic_id'] ?? ''),
            'studyint' => (string) ($order['studyint'] ?? ''),
            'accession_number' => (string) ($order['accession_number'] ?? ''),
            'patient_name' => (string) ($order['patient_name'] ?? ''),
            'radiologist_username' => (string) ($order['radiologist_username'] ?? '')
        ));

        $package = rp_remote_cloud_bridge_copy_package($remoteCon, $order);
        if (empty($package['ok'])) {
            $summary['failed']++;
            $summary['errors'][] = (string) ($order['order_uid'] ?? '') . ': ' . (string) ($package['message'] ?? 'Package copy failed.');
            continue;
        }

        rp_remote_cloud_bridge_upsert_study($remoteCon, $payload, $order);
        rp_remote_cloud_bridge_upsert_order($remoteCon, $payload, $order, $package);
        $orderUidEsc = mysqli_real_escape_string($cloudCon, (string) ($order['order_uid'] ?? ''));
        if ($orderUidEsc !== '') {
            mysqli_query($cloudCon, "UPDATE cloud_report_orders
                SET status = 'sent_to_remotepanda', updated_at = CURRENT_TIMESTAMP
                WHERE order_uid = '{$orderUidEsc}'");
        }
        $summary['imported']++;
    }

    mysqli_close($cloudCon);
    return $summary;
}

function rp_remote_cloud_return_settings(mysqli $con): array
{
    rp_remote_settings_ensure($con);
    $endpoint = getenv('REMOTEPANDA_CLOUD_RETURN_ENDPOINT');
    if ($endpoint === false || trim((string) $endpoint) === '') {
        $endpoint = rp_remote_setting_get($con, 'cloud_report_return_endpoint', 'http://127.0.0.1/radpanda-cloud/api/report-return-receiver.php');
    }

    $apiKey = getenv('REMOTEPANDA_CLOUD_SYNC_KEY');
    if ($apiKey === false) {
        $apiKey = '';
    }
    if (trim((string) $apiKey) === '') {
        $apiKey = rp_remote_setting_get($con, 'cloud_sync_api_key', '');
    }

    $timeout = (int) rp_remote_setting_get($con, 'cloud_sync_timeout_sec', '60');
    if ($timeout <= 0) {
        $timeout = 60;
    }

    return array(
        'endpoint' => trim((string) $endpoint),
        'api_key' => trim((string) $apiKey),
        'timeout' => $timeout,
    );
}

function rp_remote_cloud_post_json(array $settings, array $payload): array
{
    if (!function_exists('curl_init')) {
        return array('ok' => false, 'status' => 0, 'response' => null, 'error' => 'PHP cURL extension is not enabled.');
    }

    $endpoint = trim((string) ($settings['endpoint'] ?? ''));
    if ($endpoint === '') {
        return array('ok' => false, 'status' => 0, 'response' => null, 'error' => 'Cloud return endpoint is not configured.');
    }

    $headers = array('Accept: application/json', 'Content-Type: application/json');
    if (trim((string) ($settings['api_key'] ?? '')) !== '') {
        $headers[] = 'X-Radpanda-Sync-Key: ' . trim((string) $settings['api_key']);
    }

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(15, (int) ($settings['timeout'] ?? 60)));
    curl_setopt($ch, CURLOPT_TIMEOUT, (int) ($settings['timeout'] ?? 60));
    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError !== '') {
        return array('ok' => false, 'status' => $status, 'response' => null, 'raw' => (string) $raw, 'error' => $curlError);
    }

    $decoded = json_decode((string) $raw, true);
    $ok = $status >= 200 && $status < 300 && is_array($decoded) && !empty($decoded['success']);
    return array(
        'ok' => $ok,
        'status' => $status,
        'response' => $decoded,
        'raw' => (string) $raw,
        'error' => $ok ? '' : (is_array($decoded) && isset($decoded['error']) ? (string) $decoded['error'] : 'Cloud return push failed with HTTP ' . $status)
    );
}

function rp_remote_cloud_push_returned_reports(mysqli $con, int $limit = 10): array
{
    rp_remote_reporting_ensure_schema($con);
    $settings = rp_remote_cloud_return_settings($con);
    $limit = max(1, min(50, $limit));
    $summary = array('checked' => 0, 'sent' => 0, 'failed' => 0, 'errors' => array());

    $res = mysqli_query($con, "SELECT ro.id, ro.order_uid, ro.studyint, ro.clinic_id, ro.accession_number, ro.payload_json, ro.attempts
        FROM remote_report_return_outbox ro
        WHERE ro.status = 'queued'
          AND ro.order_uid LIKE 'RPO-%'
          AND ro.clinic_id <> ''
          AND (ro.next_retry_at IS NULL OR ro.next_retry_at <= NOW())
        ORDER BY ro.created_at ASC
        LIMIT {$limit}");
    if (!$res) {
        $summary['failed']++;
        $summary['errors'][] = 'Could not query Remotepanda return outbox.';
        return $summary;
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $summary['checked']++;
        $id = (int) ($row['id'] ?? 0);
        $attempts = (int) ($row['attempts'] ?? 0) + 1;
        $payload = json_decode((string) ($row['payload_json'] ?? ''), true);
        if (!is_array($payload)) {
            $payload = array();
        }
        $payload['order_uid'] = (string) ($row['order_uid'] ?? ($payload['order_uid'] ?? ''));
        $payload['studyint'] = (string) ($row['studyint'] ?? ($payload['studyint'] ?? ''));
        $payload['clinic_id'] = (string) ($row['clinic_id'] ?? ($payload['clinic_id'] ?? ''));
        $payload['accession_number'] = (string) ($row['accession_number'] ?? ($payload['accession_number'] ?? ''));

        $result = rp_remote_cloud_post_json($settings, array('report' => $payload));
        if (!empty($result['ok'])) {
            $summary['sent']++;
            mysqli_query($con, "UPDATE remote_report_return_outbox
                SET status = 'sent', attempts = {$attempts}, last_error = NULL, sent_at = NOW(), next_retry_at = NULL, updated_at = NOW()
                WHERE id = {$id} LIMIT 1");
            $orderUidEsc = mysqli_real_escape_string($con, (string) $payload['order_uid']);
            mysqli_query($con, "UPDATE remote_report_orders
                SET status = 'returned', returned_at = NOW(), updated_at = NOW()
                WHERE order_uid = '{$orderUidEsc}' LIMIT 1");
            continue;
        }

        $summary['failed']++;
        $err = (string) ($result['error'] ?? 'Cloud return push failed.');
        $summary['errors'][] = (string) $payload['order_uid'] . ': ' . $err;
        $errEsc = mysqli_real_escape_string($con, $err);
        $delayMinutes = min(60, max(5, $attempts * 5));
        mysqli_query($con, "UPDATE remote_report_return_outbox
            SET status = 'queued', attempts = {$attempts}, last_error = '{$errEsc}',
                next_retry_at = DATE_ADD(NOW(), INTERVAL {$delayMinutes} MINUTE), updated_at = NOW()
            WHERE id = {$id} LIMIT 1");
    }

    return $summary;
}
?>
