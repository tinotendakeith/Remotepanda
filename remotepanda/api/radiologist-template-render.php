<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

include('../includes/dbconnection.php');
include('../functions.php');
include('../includes/remote_reporting_service.php');
include('../includes/report_template_helper.php');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(array('ok' => false, 'message' => 'Please sign in again.'));
    exit;
}

rp_remote_reporting_ensure_schema($con);

$currentReporter = rp_remote_reporting_current_user();
$accession = trim((string)($_GET['accession'] ?? ''));
$file = basename((string)($_GET['file'] ?? ''));

if ($accession === '' || $file === '') {
    http_response_code(400);
    echo json_encode(array('ok' => false, 'message' => 'Template or accession was missing.'));
    exit;
}

$case = rp_remote_reporting_get_case($con, $accession, $currentReporter);
if (!$case) {
    http_response_code(404);
    echo json_encode(array('ok' => false, 'message' => 'Case was not found for your account.'));
    exit;
}
$currentReporterUsername = is_array($currentReporter) ? trim((string)($currentReporter['username'] ?? '')) : trim((string)$currentReporter);

$templateDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'dashboards' . DIRECTORY_SEPARATOR . 'radiologist' . DIRECTORY_SEPARATOR . 'Templates';
$path = $templateDir . DIRECTORY_SEPARATOR . $file;
$realDir = realpath($templateDir);
$realPath = realpath($path);
if (!$realDir || !$realPath || strpos($realPath, $realDir) !== 0 || !is_file($realPath)) {
    http_response_code(404);
    echo json_encode(array('ok' => false, 'message' => 'Template file was not found.'));
    exit;
}

$currentReporterEsc = mysqli_real_escape_string($con, $currentReporterUsername);
$fileEsc = mysqli_real_escape_string($con, $file);
$ownerColumnCheck = mysqli_query($con, "SHOW COLUMNS FROM Templates LIKE 'owner_type'");
$ownerUserColumnCheck = mysqli_query($con, "SHOW COLUMNS FROM Templates LIKE 'owner_username'");
$hasOwnerColumns = $ownerColumnCheck && $ownerUserColumnCheck && mysqli_num_rows($ownerColumnCheck) > 0 && mysqli_num_rows($ownerUserColumnCheck) > 0;

if ($hasOwnerColumns) {
    $templateQuery = mysqli_query($con, "SELECT tempID FROM Templates WHERE temp_file = '$fileEsc' AND ((owner_type = 'radiologist' AND owner_username = '$currentReporterEsc') OR Author = '$currentReporterEsc') LIMIT 1");
} else {
    $templateQuery = mysqli_query($con, "SELECT tempID FROM Templates WHERE temp_file = '$fileEsc' AND Author = '$currentReporterEsc' LIMIT 1");
}

if (!$templateQuery || mysqli_num_rows($templateQuery) < 1) {
    http_response_code(403);
    echo json_encode(array('ok' => false, 'message' => 'This template is not assigned to your account.'));
    exit;
}

$eventRecord = array();
$eventLookup = mysqli_real_escape_string($con, $accession);
$eventQuery = mysqli_query($con, "SELECT * FROM events WHERE accession_number = '$eventLookup' OR id = '$eventLookup' ORDER BY id DESC LIMIT 1");
if ($eventQuery && ($eventRow = mysqli_fetch_assoc($eventQuery))) {
    $eventRecord = $eventRow;
}

$context = rp_template_context_from_records($case, $eventRecord, $currentReporter);
$context['study_id'] = rp_template_value($case['studyint'] ?? ($case['accession_number'] ?? ($context['study_id'] ?? '')));
$context['radiologist_name'] = rp_template_value($currentReporterUsername);

$content = rp_render_template_placeholders((string)file_get_contents($realPath), $context);
echo json_encode(array(
    'ok' => true,
    'file' => $file,
    'content' => $content
));
