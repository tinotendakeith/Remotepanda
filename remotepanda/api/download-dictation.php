<?php
require_once __DIR__ . '/../includes/api_security.php';
require_once __DIR__ . '/../includes/typist_workflow_service.php';

rp_remote_require_global_api_enabled($con);
rp_remote_require_login($con);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo 'Missing dictation id.';
    exit;
}

rp_typist_workflow_ensure_schema($con);
$stmt = mysqli_prepare($con, "SELECT * FROM report_dictations WHERE id = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo 'Could not prepare dictation lookup.';
    exit;
}
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if (!$row) {
    http_response_code(404);
    echo 'Dictation not found.';
    exit;
}

$studyint = (string)$row['studyint'];
$user = rp_typist_workflow_user();
if (rp_typist_workflow_is_typist($user)) {
    if (!rp_typist_workflow_can_typist_access($con, $studyint, $user)) {
        http_response_code(403);
        echo 'Access denied.';
        exit;
    }
} else {
    rp_remote_require_study_access($con, $studyint);
}

$path = (string)$row['audio_path'];
$root = realpath(rp_typist_workflow_storage_root());
$real = realpath($path);
if ($root === false || $real === false || strpos($real, $root) !== 0 || !is_file($real)) {
    http_response_code(404);
    echo 'Audio file not found.';
    exit;
}

$mime = trim((string)($row['mime_type'] ?? ''));
if ($mime === '') {
    $mime = 'application/octet-stream';
}
$name = basename((string)($row['original_file_name'] ?? 'dictation.webm'));
if ($name === '' || $name === '.' || $name === '..') {
    $name = 'dictation-' . $id . '.webm';
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($real));
header('Content-Disposition: inline; filename="' . str_replace('"', '', $name) . '"');
header('Cache-Control: private, max-age=0, no-cache');
readfile($real);
exit;
?>
