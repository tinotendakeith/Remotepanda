<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/api_security.php';
require_once __DIR__ . '/../includes/clinic_orthanc_bridge.php';

rp_remote_require_global_api_enabled($con);
rp_remote_require_login($con);

if (!rp_remote_feature_enabled($con, 'feature_remote_dicom_stream_enabled', true)) {
    rp_remote_api_log($con, 'feature_blocked', false, 503, 'DICOM stream disabled');
    rp_remote_json_response(['success' => false, 'error' => 'DICOM streaming is disabled'], 503);
}

$studyint = isset($_GET['studyint']) ? trim((string)$_GET['studyint']) : '';
if ($studyint === '') {
    rp_remote_api_log($con, 'validation_failed', false, 400, 'Missing study identifier');
    rp_remote_json_response(['success' => false, 'error' => 'Missing study identifier'], 400);
}

rp_remote_api_log($con, 'request_received', true, 200, 'List study files request', $studyint);
rp_remote_require_study_access($con, $studyint);

$orthanc = rp_remote_clinic_list_orthanc_instances($studyint);
if (empty($orthanc['success']) || empty($orthanc['files'])) {
    rp_remote_api_log($con, 'list_study_files_orthanc_missing', false, 404, 'No PACS study was found', $studyint, ['orthanc_error' => (string) ($orthanc['error'] ?? '')]);
    rp_remote_json_response(['success' => false, 'error' => 'No PACS study was found for this Radpanda study'], 404);
}

rp_remote_api_log($con, 'list_study_files_orthanc_success', true, 200, 'PACS DICOM instances listed', $studyint, ['count' => count($orthanc['files'])]);

rp_remote_json_response([
    'success' => true,
    'studyint' => $studyint,
    'source' => 'orthanc',
    'orthanc_study_id' => (string) ($orthanc['orthanc_study_id'] ?? ''),
    'count' => count($orthanc['files']),
    'skipped_non_renderable' => 0,
    'files' => $orthanc['files']
]);
