<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../includes/api_security.php';
require_once __DIR__ . '/../includes/remote_reporting_service.php';

rp_remote_require_global_api_enabled($con);
rp_remote_require_login($con);

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    rp_remote_json_response(array('success' => false, 'error' => 'POST required.'), 405);
}

$contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
if (stripos($contentType, 'application/json') !== false) {
    $input = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = array();
    }
} else {
    $input = $_POST;
}

$studyint = trim((string) ($input['studyint'] ?? ''));
$reportText = (string) ($input['report_text'] ?? ($input['notes'] ?? ''));

if ($studyint === '') {
    rp_remote_api_log($con, 'validation_failed', false, 400, 'Missing studyint');
    rp_remote_json_response(array('success' => false, 'error' => 'Missing studyint.'), 400);
}

rp_remote_require_study_access($con, $studyint);

$result = rp_remote_reporting_finalize_report($con, $studyint, $reportText, rp_remote_reporting_current_user());
if (empty($result['ok'])) {
    rp_remote_api_log($con, 'final_report_failed', false, 400, (string) ($result['error'] ?? 'Could not finalize report'), $studyint);
    rp_remote_json_response(array('success' => false, 'error' => (string) ($result['error'] ?? 'Could not finalize report.')), 400);
}

rp_remote_api_log($con, 'final_report_saved', true, 200, 'Final report queued for clinic return', $studyint, array(
    'order_uid' => (string) ($result['order_uid'] ?? '')
));

rp_remote_json_response(array(
    'success' => true,
    'studyint' => $studyint,
    'order_uid' => (string) ($result['order_uid'] ?? ''),
    'status' => (string) ($result['status'] ?? 'reported'),
    'message' => (string) ($result['message'] ?? 'Report finalized.')
));
?>
