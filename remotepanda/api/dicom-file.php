<?php
require_once __DIR__ . '/../includes/api_security.php';
require_once __DIR__ . '/../includes/clinic_orthanc_bridge.php';

rp_remote_require_global_api_enabled($con);

if (!rp_remote_feature_enabled($con, 'feature_remote_dicom_stream_enabled', true)) {
    rp_remote_api_log($con, 'feature_blocked', false, 503, 'DICOM stream disabled');
    rp_remote_json_response(['success' => false, 'error' => 'DICOM streaming is disabled'], 503);
}

$studyint = isset($_GET['studyint']) ? trim((string)$_GET['studyint']) : '';
$orthancInstance = isset($_GET['orthanc_instance']) ? trim((string)$_GET['orthanc_instance']) : '';
$relativePath = isset($_GET['path']) ? trim((string)$_GET['path']) : '';

if ($studyint === '' || ($orthancInstance === '' && $relativePath === '')) {
    rp_remote_api_log($con, 'validation_failed', false, 400, 'Missing studyint/DICOM locator', $studyint);
    http_response_code(400);
    exit('Missing required PACS parameters');
}

rp_remote_api_log($con, 'request_received', true, 200, 'DICOM file request', $studyint);
$viewerTokenAuth = rp_remote_require_login_or_viewer_token($con, $studyint);
if (!$viewerTokenAuth) {
    rp_remote_require_study_access($con, $studyint);
}

if ($orthancInstance !== '') {
    rp_remote_api_log($con, 'orthanc_stream_requested', true, 200, 'PACS DICOM instance request', $studyint, ['orthanc_instance' => $orthancInstance]);
    rp_remote_clinic_stream_orthanc_instance($studyint, $orthancInstance);
}

$studyFolder = rp_remote_resolve_study_folder($con, $studyint);
if ($studyFolder === false) {
    rp_remote_api_log($con, 'local_stream_missing_folder', false, 404, 'Imported package folder not found', $studyint);
    http_response_code(404);
    exit('Study folder not found');
}

$candidate = $studyFolder . DIRECTORY_SEPARATOR . str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $relativePath);
$real = realpath($candidate);
$baseReal = realpath($studyFolder);
if ($real === false || $baseReal === false || strpos($real, rtrim($baseReal, "\\/") . DIRECTORY_SEPARATOR) !== 0 || !is_file($real) || !is_readable($real)) {
    rp_remote_api_log($con, 'local_stream_forbidden_or_missing', false, 404, 'Imported package DICOM file not found', $studyint, ['path' => $relativePath]);
    http_response_code(404);
    exit('DICOM file not found');
}

rp_remote_api_log($con, 'local_stream_success', true, 200, 'Imported package DICOM file streamed', $studyint, ['path' => $relativePath]);

while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/dicom');
header('Content-Length: ' . filesize($real));
header('Content-Disposition: inline; filename="' . basename($real) . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
readfile($real);
exit;
