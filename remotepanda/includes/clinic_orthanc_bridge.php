<?php

function rp_remote_clinic_db_connect()
{
    $con = @mysqli_connect('localhost', 'root', '', 'radpandaco_appointment');
    return $con instanceof mysqli ? $con : null;
}

function rp_remote_clinic_orthanc_bootstrap()
{
    $orthancService = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'radpanda' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'orthanc_service.php';
    if (!is_file($orthancService)) {
        return false;
    }
    require_once $orthancService;
    return function_exists('rp_orthanc_find_study_by_uid') && function_exists('rp_orthanc_request');
}

function rp_remote_clinic_study_accession(mysqli $clinicCon, string $studyint): string
{
    $stmt = mysqli_prepare($clinicCon, 'SELECT accession_number FROM study WHERE studyint = ? LIMIT 1');
    if (!$stmt) {
        return '';
    }
    mysqli_stmt_bind_param($stmt, 's', $studyint);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    return $row ? trim((string) ($row['accession_number'] ?? '')) : '';
}

function rp_remote_clinic_find_orthanc_study(string $studyint): array
{
    $studyint = trim($studyint);
    if ($studyint === '' || !rp_remote_clinic_orthanc_bootstrap()) {
        return array('ok' => false, 'clinic_con' => null, 'orthanc_study_id' => '', 'error' => 'Orthanc bridge is unavailable.');
    }

    $clinicCon = rp_remote_clinic_db_connect();
    if (!$clinicCon) {
        return array('ok' => false, 'clinic_con' => null, 'orthanc_study_id' => '', 'error' => 'Clinic database is unavailable.');
    }

    $candidates = array();
    $byUid = rp_orthanc_find_study_by_uid($clinicCon, $studyint);
    if (!empty($byUid['success']) && is_array($byUid['data'])) {
        $candidates = $byUid['data'];
    }

    if (empty($candidates)) {
        $accession = rp_remote_clinic_study_accession($clinicCon, $studyint);
        if ($accession !== '') {
            $byAccession = rp_orthanc_find_study_by_accession($clinicCon, $accession);
            if (!empty($byAccession['success']) && is_array($byAccession['data'])) {
                $candidates = $byAccession['data'];
            }
        }
    }

    $orthancStudyId = !empty($candidates) ? (string) reset($candidates) : '';
    if ($orthancStudyId === '') {
        return array('ok' => false, 'clinic_con' => $clinicCon, 'orthanc_study_id' => '', 'error' => 'Study was not found in PACS.');
    }

    return array('ok' => true, 'clinic_con' => $clinicCon, 'orthanc_study_id' => $orthancStudyId, 'error' => '');
}

function rp_remote_clinic_list_orthanc_instances(string $studyint): array
{
    $found = rp_remote_clinic_find_orthanc_study($studyint);
    if (empty($found['ok'])) {
        return array('success' => false, 'error' => (string) ($found['error'] ?? 'Study was not found in PACS.'), 'files' => array());
    }

    $clinicCon = $found['clinic_con'];
    $orthancStudyId = (string) $found['orthanc_study_id'];
    $instances = rp_orthanc_list_instances($clinicCon, $orthancStudyId);
    if (empty($instances['success']) || !is_array($instances['data'])) {
        return array('success' => false, 'error' => (string) ($instances['error'] ?? 'Could not list PACS instances.'), 'files' => array());
    }

    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $remoteBaseUrl = '';
    $apiPos = strpos($scriptName, '/api/');
    if ($apiPos !== false) {
        $remoteBaseUrl = substr($scriptName, 0, $apiPos);
    }
    $remoteBaseUrl = rtrim($remoteBaseUrl, '/');

    $files = array();
    $index = 1;
    foreach ($instances['data'] as $instance) {
        $id = is_array($instance) ? (string) ($instance['ID'] ?? '') : (string) $instance;
        if ($id === '') {
            continue;
        }
        $series = 'Series';
        if (is_array($instance)) {
            $tags = isset($instance['MainDicomTags']) && is_array($instance['MainDicomTags']) ? $instance['MainDicomTags'] : array();
            $series = trim((string) ($tags['SeriesNumber'] ?? $tags['SeriesDescription'] ?? 'Series'));
            if ($series === '') {
                $series = 'Series';
            }
        }
        $path = $series . '/' . str_pad((string) $index, 4, '0', STR_PAD_LEFT) . '-' . $id . '.dcm';
        $files[] = array(
            'name' => basename($path),
            'path' => $path,
            'size' => 0,
            'source' => 'orthanc',
            'orthanc_instance_id' => $id,
            'url' => $remoteBaseUrl . '/api/dicom-file.php?studyint=' . rawurlencode($studyint) . '&orthanc_instance=' . rawurlencode($id)
        );
        $index++;
    }

    return array(
        'success' => !empty($files),
        'error' => empty($files) ? 'No PACS instances found for this study.' : '',
        'orthanc_study_id' => $orthancStudyId,
        'files' => $files
    );
}

function rp_remote_clinic_stream_orthanc_instance(string $studyint, string $instanceId): void
{
    $instanceId = trim($instanceId);
    $listed = rp_remote_clinic_list_orthanc_instances($studyint);
    if (empty($listed['success'])) {
        http_response_code(404);
        exit((string) ($listed['error'] ?? 'Study was not found in PACS.'));
    }

    $allowed = false;
    foreach ($listed['files'] as $file) {
        if ((string) ($file['orthanc_instance_id'] ?? '') === $instanceId) {
            $allowed = true;
            break;
        }
    }
    if (!$allowed) {
        http_response_code(403);
        exit('PACS instance does not belong to this study.');
    }

    $found = rp_remote_clinic_find_orthanc_study($studyint);
    $clinicCon = $found['clinic_con'] ?? null;
    if (!$clinicCon instanceof mysqli) {
        http_response_code(500);
        exit('Clinic database is unavailable.');
    }

    $download = rp_orthanc_request($clinicCon, 'GET', '/instances/' . rawurlencode($instanceId) . '/file', null, false);
    if (empty($download['success']) || (string) ($download['raw'] ?? '') === '') {
        http_response_code(502);
        exit((string) ($download['error'] ?? 'Could not download PACS instance.'));
    }

    $raw = (string) $download['raw'];
    header('Content-Type: application/dicom');
    header('Content-Length: ' . strlen($raw));
    header('Content-Disposition: inline; filename="' . $instanceId . '.dcm"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $raw;
    exit;
}

?>
