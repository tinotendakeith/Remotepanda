<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../includes/api_security.php';

rp_remote_require_global_api_enabled($con);
rp_remote_require_login($con);

if (!rp_remote_feature_enabled($con, 'feature_remote_study_notes_enabled', true)) {
    rp_remote_api_log($con, 'feature_blocked', false, 503, 'Study notes disabled');
    rp_remote_json_response(['success' => false, 'error' => 'Study notes feature is disabled'], 503);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function getStudyintFromRequest($method)
{
    if ($method === 'GET') {
        return isset($_GET['studyint']) ? trim((string)$_GET['studyint']) : '';
    }

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        return isset($input['studyint']) ? trim((string)$input['studyint']) : '';
    }

    return isset($_POST['studyint']) ? trim((string)$_POST['studyint']) : '';
}

function isLikelyLegacyHtml($text)
{
    if (!is_string($text) || $text === '') {
        return false;
    }

    return preg_match('/<\s*(p|span|br|div|strong|em|font|a)\b/i', $text) === 1;
}

function isLikelyTemplateText($text)
{
    if (!is_string($text)) {
        return false;
    }

    $normalized = strtoupper(trim($text));
    if ($normalized === '') {
        return false;
    }

    $markers = [
        'NAME OF PATIENT',
        'DATE OF EXAM',
        'DATE OF BIRTH',
        'REF BY',
        'HISTORY:',
        'ULTRASOUND SCAN'
    ];

    $hits = 0;
    foreach ($markers as $m) {
        if (strpos($normalized, $m) !== false) {
            $hits++;
        }
    }

    return $hits >= 3;
}

$studyint = getStudyintFromRequest($method);
if ($studyint === '') {
    rp_remote_api_log($con, 'validation_failed', false, 400, 'Missing studyint');
    rp_remote_json_response(['success' => false, 'error' => 'Missing studyint'], 400);
}

rp_remote_api_log($con, 'request_received', true, 200, 'Study notes request', $studyint, ['method' => $method]);
rp_remote_require_study_access($con, $studyint);

$notesColumn = 'radiologist_notes';
$metaByColumn = 'radiologist_notes_updated_by';
$metaAtColumn = 'radiologist_notes_updated_at';

$hasRadiologistNotes = rp_remote_has_column($con, 'study', $notesColumn);
$hasMetaBy = rp_remote_has_column($con, 'study', $metaByColumn);
$hasMetaAt = rp_remote_has_column($con, 'study', $metaAtColumn);

if (!$hasRadiologistNotes) {
    rp_remote_api_log($con, 'schema_missing', false, 500, 'Missing radiologist_notes column', $studyint);
    rp_remote_json_response([
        'success' => false,
        'error' => 'Column `study.radiologist_notes` is missing.',
        'migration_sql' => 'ALTER TABLE study ADD COLUMN radiologist_notes LONGTEXT NULL, ADD COLUMN radiologist_notes_updated_by VARCHAR(191) NULL, ADD COLUMN radiologist_notes_updated_at DATETIME NULL;'
    ], 500);
}

if ($method === 'GET') {
    $selectFields = ["`{$notesColumn}`"];
    if ($hasMetaBy) {
        $selectFields[] = "`{$metaByColumn}`";
    }
    if ($hasMetaAt) {
        $selectFields[] = "`{$metaAtColumn}`";
    }

    $stmt = $con->prepare('SELECT ' . implode(', ', $selectFields) . ' FROM `study` WHERE `studyint` = ? LIMIT 1');
    if (!$stmt) {
        rp_remote_api_log($con, 'db_prepare_failed', false, 500, 'Database prepare failed', $studyint);
        rp_remote_json_response(['success' => false, 'error' => 'Database prepare failed'], 500);
    }

    $stmt->bind_param('s', $studyint);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        rp_remote_api_log($con, 'study_not_found', false, 404, 'Study not found', $studyint);
        rp_remote_json_response(['success' => false, 'error' => 'Study not found'], 404);
    }

    $notesValue = isset($row[$notesColumn]) ? (string)$row[$notesColumn] : '';
    $lastSavedBy = ($hasMetaBy && isset($row[$metaByColumn])) ? (string)$row[$metaByColumn] : '';
    $lastSavedAt = ($hasMetaAt && isset($row[$metaAtColumn])) ? (string)$row[$metaAtColumn] : '';

    if ($hasMetaBy && $hasMetaAt && trim($lastSavedBy) === '' && trim($lastSavedAt) === '') {
        $notesValue = '';
    }

    if (isLikelyLegacyHtml($notesValue) || isLikelyTemplateText($notesValue)) {
        $notesValue = '';
    }

    rp_remote_api_log($con, 'study_notes_read', true, 200, 'Study notes read', $studyint);

    rp_remote_json_response([
        'success' => true,
        'studyint' => $studyint,
        'notes' => $notesValue,
        'last_saved_by' => $lastSavedBy,
        'last_saved_at' => $lastSavedAt,
        'meta_columns_present' => $hasMetaBy && $hasMetaAt,
        'notes_source' => $notesColumn
    ]);
}

if ($method === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $notes = '';

    if (stripos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        $notes = isset($input['notes']) ? (string)$input['notes'] : '';
    } else {
        $notes = isset($_POST['notes']) ? (string)$_POST['notes'] : '';
    }

    $savedBy = rp_remote_current_username();

    if ($hasMetaBy && $hasMetaAt) {
        $stmt = $con->prepare("UPDATE `study` SET `{$notesColumn}` = ?, `{$metaByColumn}` = ?, `{$metaAtColumn}` = NOW() WHERE `studyint` = ?");
        if (!$stmt) {
            rp_remote_api_log($con, 'db_prepare_failed', false, 500, 'Database prepare failed', $studyint);
            rp_remote_json_response(['success' => false, 'error' => 'Database prepare failed'], 500);
        }
        $stmt->bind_param('sss', $notes, $savedBy, $studyint);
    } else {
        $stmt = $con->prepare("UPDATE `study` SET `{$notesColumn}` = ? WHERE `studyint` = ?");
        if (!$stmt) {
            rp_remote_api_log($con, 'db_prepare_failed', false, 500, 'Database prepare failed', $studyint);
            rp_remote_json_response(['success' => false, 'error' => 'Database prepare failed'], 500);
        }
        $stmt->bind_param('ss', $notes, $studyint);
    }

    $ok = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if (!$ok) {
        rp_remote_api_log($con, 'study_notes_save_failed', false, 500, 'Failed to save notes', $studyint);
        rp_remote_json_response(['success' => false, 'error' => 'Failed to save notes'], 500);
    }

    if ($affected === 0) {
        $check = $con->prepare('SELECT 1 FROM `study` WHERE `studyint` = ? LIMIT 1');
        if (!$check) {
            rp_remote_api_log($con, 'db_prepare_failed', false, 500, 'Database prepare failed', $studyint);
            rp_remote_json_response(['success' => false, 'error' => 'Database prepare failed'], 500);
        }
        $check->bind_param('s', $studyint);
        $check->execute();
        $exists = $check->get_result()->fetch_row();
        $check->close();

        if (!$exists) {
            rp_remote_api_log($con, 'study_not_found', false, 404, 'Study not found', $studyint);
            rp_remote_json_response(['success' => false, 'error' => 'Study not found'], 404);
        }
    }

    $lastSavedAt = '';
    if ($hasMetaAt) {
        $metaStmt = $con->prepare("SELECT `{$metaAtColumn}` FROM `study` WHERE `studyint` = ? LIMIT 1");
        if ($metaStmt) {
            $metaStmt->bind_param('s', $studyint);
            $metaStmt->execute();
            $metaRow = $metaStmt->get_result()->fetch_assoc();
            $metaStmt->close();
            if ($metaRow && isset($metaRow[$metaAtColumn])) {
                $lastSavedAt = (string)$metaRow[$metaAtColumn];
            }
        }
    }

    rp_remote_api_log($con, 'study_notes_saved', true, 200, 'Radiologist notes saved', $studyint, ['notes_length' => strlen($notes)]);

    rp_remote_json_response([
        'success' => true,
        'studyint' => $studyint,
        'message' => 'Radiologist notes saved',
        'last_saved_by' => $hasMetaBy ? $savedBy : '',
        'last_saved_at' => $lastSavedAt,
        'meta_columns_present' => $hasMetaBy && $hasMetaAt
    ]);
}

rp_remote_api_log($con, 'method_not_allowed', false, 405, 'Method not allowed', $studyint, ['method' => $method]);
rp_remote_json_response(['success' => false, 'error' => 'Method not allowed'], 405);
