<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../includes/api_security.php';
require_once __DIR__ . '/../includes/typist_workflow_service.php';

rp_remote_require_global_api_enabled($con);
rp_remote_require_login($con);

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    rp_remote_json_response(array('success' => false, 'error' => 'POST required.'), 405);
}

$studyint = trim((string)($_POST['studyint'] ?? ''));
$noteText = trim((string)($_POST['note_text'] ?? ''));
if ($studyint === '') {
    rp_remote_json_response(array('success' => false, 'error' => 'Missing studyint.'), 400);
}

rp_remote_require_study_access($con, $studyint);

if (empty($_FILES['audio']) || !is_array($_FILES['audio'])) {
    rp_remote_json_response(array('success' => false, 'error' => 'No audio file uploaded.'), 400);
}

$file = $_FILES['audio'];
if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    rp_remote_json_response(array('success' => false, 'error' => 'Audio upload failed.'), 400);
}

$size = (int)($file['size'] ?? 0);
if ($size <= 0) {
    rp_remote_json_response(array('success' => false, 'error' => 'Audio file is empty.'), 400);
}
if ($size > 80 * 1024 * 1024) {
    rp_remote_json_response(array('success' => false, 'error' => 'Audio file is too large.'), 413);
}

$mime = (string)($file['type'] ?? 'audio/webm');
$allowed = array('audio/webm', 'audio/ogg', 'audio/mpeg', 'audio/mp4', 'audio/wav', 'video/webm');
if (!in_array(strtolower($mime), $allowed, true) && strpos(strtolower($mime), 'audio/') !== 0) {
    rp_remote_json_response(array('success' => false, 'error' => 'Unsupported audio type.'), 400);
}

$root = rp_typist_workflow_storage_root();
$studyDir = $root . DIRECTORY_SEPARATOR . rp_typist_workflow_safe_slug($studyint);
if (!is_dir($studyDir) && !@mkdir($studyDir, 0775, true)) {
    rp_remote_json_response(array('success' => false, 'error' => 'Could not create dictation folder.'), 500);
}

$extension = 'webm';
if (stripos($mime, 'ogg') !== false) {
    $extension = 'ogg';
} elseif (stripos($mime, 'mpeg') !== false || stripos($mime, 'mp3') !== false) {
    $extension = 'mp3';
} elseif (stripos($mime, 'wav') !== false) {
    $extension = 'wav';
} elseif (stripos($mime, 'mp4') !== false) {
    $extension = 'm4a';
}

$fileName = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
$dest = $studyDir . DIRECTORY_SEPARATOR . $fileName;
if (!move_uploaded_file((string)$file['tmp_name'], $dest)) {
    rp_remote_json_response(array('success' => false, 'error' => 'Could not save uploaded audio.'), 500);
}

$result = rp_typist_workflow_create_dictation(
    $con,
    $studyint,
    $dest,
    (string)($file['name'] ?? $fileName),
    $mime,
    $size,
    $noteText,
    rp_typist_workflow_user()
);

if (empty($result['ok'])) {
    @unlink($dest);
    rp_remote_json_response(array('success' => false, 'error' => (string)($result['error'] ?? 'Could not save dictation.')), 500);
}

rp_remote_api_log($con, 'dictation_uploaded', true, 200, 'Dictation uploaded', $studyint, array('dictation_id' => (int)$result['id']));

rp_remote_json_response(array(
    'success' => true,
    'dictation_id' => (int)$result['id'],
    'message' => 'Dictation saved for typists.'
));
?>
