<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/api_security.php';

rp_remote_require_global_api_enabled($con);
rp_remote_require_login($con);

$studyint = isset($_GET['studyint']) ? trim((string) $_GET['studyint']) : '';
if ($studyint === '') {
    http_response_code(400);
    echo 'Study identifier missing.';
    exit;
}

rp_remote_api_log($con, 'request_received', true, 200, 'Download study package request', $studyint);
rp_remote_require_study_access($con, $studyint);

$stmt = mysqli_prepare($con, "SELECT order_uid, accession_number, patient_name, package_path
    FROM remote_report_orders
    WHERE studyint = ? AND COALESCE(package_path, '') <> ''
    ORDER BY id DESC
    LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo 'Could not prepare download.';
    exit;
}

mysqli_stmt_bind_param($stmt, 's', $studyint);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

$packagePath = $row ? trim((string) ($row['package_path'] ?? '')) : '';
if ($packagePath === '' || !is_file($packagePath) || !is_readable($packagePath)) {
    rp_remote_api_log($con, 'download_package_missing', false, 404, 'Study package not found', $studyint, array('package_path' => $packagePath));
    http_response_code(404);
    echo 'Study package not found.';
    exit;
}

$orderUid = preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) ($row['order_uid'] ?? 'study'));
$accession = preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) ($row['accession_number'] ?? ''));
$patientName = preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) ($row['patient_name'] ?? 'patient'));
$fileName = trim($accession . '-' . $patientName . '-' . $orderUid, '-_') . '.zip';
if ($fileName === '.zip') {
    $fileName = basename($packagePath);
}

rp_remote_api_log($con, 'download_package_success', true, 200, 'Study package download started', $studyint, array('package_path' => $packagePath));

while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/zip');
header('Content-Length: ' . filesize($packagePath));
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('X-Content-Type-Options: nosniff');
readfile($packagePath);
exit;
?>
