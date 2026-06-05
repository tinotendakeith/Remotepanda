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

if ($studyint === '' || $orthancInstance === '') {
    rp_remote_api_log($con, 'validation_failed', false, 400, 'Missing studyint/orthanc_instance', $studyint);
    http_response_code(400);
    exit('Missing required PACS parameters');
}

rp_remote_api_log($con, 'request_received', true, 200, 'DICOM file request', $studyint);
$viewerTokenAuth = rp_remote_require_login_or_viewer_token($con, $studyint);
if (!$viewerTokenAuth) {
    rp_remote_require_study_access($con, $studyint);
}

rp_remote_api_log($con, 'orthanc_stream_requested', true, 200, 'PACS DICOM instance request', $studyint, ['orthanc_instance' => $orthancInstance]);
rp_remote_clinic_stream_orthanc_instance($studyint, $orthancInstance);
