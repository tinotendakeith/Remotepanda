<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/api_security.php';
require_once __DIR__ . '/../includes/clinic_orthanc_bridge.php';

rp_remote_require_global_api_enabled($con);

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
$viewerTokenAuth = rp_remote_require_login_or_viewer_token($con, $studyint);
if (!$viewerTokenAuth) {
    rp_remote_require_study_access($con, $studyint);
}

$orthanc = rp_remote_clinic_list_orthanc_instances($studyint);
if (!empty($orthanc['files']) && $viewerTokenAuth) {
    $viewerExp = isset($_GET['viewer_exp']) ? (string)$_GET['viewer_exp'] : '';
    $viewerToken = isset($_GET['viewer_token']) ? (string)$_GET['viewer_token'] : '';
    $viewerQuery = '&viewer_exp=' . rawurlencode($viewerExp) . '&viewer_token=' . rawurlencode($viewerToken);
    foreach ($orthanc['files'] as &$file) {
        if (isset($file['url']) && is_string($file['url'])) {
            $file['url'] .= $viewerQuery;
        }
    }
    unset($file);
}
if (empty($orthanc['success']) || empty($orthanc['files'])) {
    $studyFolder = rp_remote_resolve_study_folder($con, $studyint);
    $localFiles = array();
    if ($studyFolder !== false) {
        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $remoteBaseUrl = '';
        $apiPos = strpos($scriptName, '/api/');
        if ($apiPos !== false) {
            $remoteBaseUrl = substr($scriptName, 0, $apiPos);
        }
        $remoteBaseUrl = rtrim($remoteBaseUrl, '/');

        try {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($studyFolder, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($it as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                $real = $file->getRealPath();
                if ($real === false || basename($real) === '' || strtolower(pathinfo($real, PATHINFO_EXTENSION)) === 'zip') {
                    continue;
                }
                $relative = ltrim(str_replace('\\', '/', substr($real, strlen($studyFolder))), '/');
                if ($relative === '') {
                    continue;
                }
                $localFiles[] = array(
                    'name' => basename($real),
                    'path' => $relative,
                    'size' => (int) $file->getSize(),
                    'source' => 'local_package',
                    'url' => $remoteBaseUrl . '/api/dicom-file.php?studyint=' . rawurlencode($studyint) . '&path=' . rawurlencode($relative)
                );
            }
        } catch (Exception $e) {
            $localFiles = array();
        }
    }

    if (!empty($localFiles) && $viewerTokenAuth) {
        $viewerExp = isset($_GET['viewer_exp']) ? (string)$_GET['viewer_exp'] : '';
        $viewerToken = isset($_GET['viewer_token']) ? (string)$_GET['viewer_token'] : '';
        $viewerQuery = '&viewer_exp=' . rawurlencode($viewerExp) . '&viewer_token=' . rawurlencode($viewerToken);
        foreach ($localFiles as &$file) {
            $file['url'] .= $viewerQuery;
        }
        unset($file);
    }

    if (!empty($localFiles)) {
        rp_remote_api_log($con, 'list_study_files_local_success', true, 200, 'Imported package DICOM files listed', $studyint, ['count' => count($localFiles), 'orthanc_error' => (string) ($orthanc['error'] ?? '')]);
        rp_remote_json_response([
            'success' => true,
            'studyint' => $studyint,
            'source' => 'local_package',
            'count' => count($localFiles),
            'skipped_non_renderable' => 0,
            'files' => $localFiles
        ]);
    }

    rp_remote_api_log($con, 'list_study_files_missing', false, 404, 'No PACS or imported package study was found', $studyint, ['orthanc_error' => (string) ($orthanc['error'] ?? '')]);
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
