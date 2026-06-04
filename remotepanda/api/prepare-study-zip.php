<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/api_security.php';
require_once __DIR__ . '/../includes/clinic_orthanc_bridge.php';

rp_remote_require_global_api_enabled($con);
rp_remote_require_login($con);

if (!rp_remote_feature_enabled($con, 'feature_remote_zip_export_enabled', true)) {
    rp_remote_api_log($con, 'feature_blocked', false, 503, 'ZIP export disabled');
    rp_remote_json_response(['success' => false, 'error' => 'Study ZIP export is disabled'], 503);
}

$studyint = isset($_GET['studyint']) ? trim((string)$_GET['studyint']) : '';
if ($studyint === '') {
    rp_remote_api_log($con, 'validation_failed', false, 400, 'Missing study identifier');
    rp_remote_json_response(['success' => false, 'error' => 'Study identifier missing'], 400);
}

rp_remote_api_log($con, 'request_received', true, 200, 'Prepare study ZIP request', $studyint);
rp_remote_require_study_access($con, $studyint);

$zipDirectory = __DIR__ . '/../extensions/uploads';
$zipFileName = $studyint . '.zip';
$zipFilePath = $zipDirectory . '/' . $zipFileName;

if (!is_dir($zipDirectory) && !mkdir($zipDirectory, 0775, true)) {
    rp_remote_api_log($con, 'zip_directory_error', false, 500, 'Upload directory unavailable', $studyint);
    rp_remote_json_response(['success' => false, 'error' => 'Upload directory unavailable'], 500);
}

$found = rp_remote_clinic_find_orthanc_study($studyint);
if (empty($found['ok'])) {
    rp_remote_api_log($con, 'orthanc_study_missing', false, 404, 'PACS study not found', $studyint, ['orthanc_error' => (string) ($found['error'] ?? '')]);
    rp_remote_json_response(['success' => false, 'error' => 'PACS study not found'], 404);
}

$clinicCon = $found['clinic_con'] ?? null;
$orthancStudyId = (string) ($found['orthanc_study_id'] ?? '');
if (!$clinicCon instanceof mysqli || $orthancStudyId === '' || !rp_remote_clinic_orthanc_bootstrap()) {
    rp_remote_api_log($con, 'orthanc_bridge_unavailable', false, 500, 'PACS bridge unavailable', $studyint);
    rp_remote_json_response(['success' => false, 'error' => 'PACS bridge unavailable'], 500);
}

$archive = rp_orthanc_request($clinicCon, 'GET', '/studies/' . rawurlencode($orthancStudyId) . '/archive', null, false);
if (empty($archive['success']) || (string) ($archive['raw'] ?? '') === '') {
    rp_remote_api_log($con, 'orthanc_archive_failed', false, 502, 'Could not download PACS archive', $studyint, ['orthanc_error' => (string) ($archive['error'] ?? '')]);
    rp_remote_json_response(['success' => false, 'error' => 'Could not download PACS archive'], 502);
}

if (file_put_contents($zipFilePath, (string) $archive['raw']) === false) {
    rp_remote_api_log($con, 'zip_create_failed', false, 500, 'Could not save PACS ZIP', $studyint);
    rp_remote_json_response(['success' => false, 'error' => 'Could not save PACS ZIP'], 500);
}

$fileCount = 0;

$webPath = 'extensions/uploads/' . $zipFileName;

$stmt = $con->prepare('UPDATE study SET zip_file = ? WHERE studyint = ?');
if ($stmt) {
    $stmt->bind_param('ss', $webPath, $studyint);
    $stmt->execute();
    $stmt->close();
}

rp_remote_api_log($con, 'zip_prepare_success', true, 200, 'PACS study ZIP prepared', $studyint, ['files' => $fileCount, 'download_url' => $webPath]);

rp_remote_json_response([
    'success' => true,
    'download_url' => $webPath
]);
