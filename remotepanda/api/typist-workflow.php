<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../includes/api_security.php';
require_once __DIR__ . '/../includes/typist_workflow_service.php';

rp_remote_require_global_api_enabled($con);
rp_remote_require_login($con);

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$input = array();
if ($method === 'POST') {
    $contentType = (string)($_SERVER['CONTENT_TYPE'] ?? '');
    if (stripos($contentType, 'application/json') !== false) {
        $input = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = array();
        }
    } else {
        $input = $_POST;
    }
} else {
    $input = $_GET;
}

$action = trim((string)($input['action'] ?? 'state'));
$studyint = trim((string)($input['studyint'] ?? ''));
if ($studyint === '') {
    rp_remote_json_response(array('success' => false, 'error' => 'Missing studyint.'), 400);
}

$user = rp_typist_workflow_user();
$userType = strtolower((string)($user['type'] ?? ''));
$isTypist = rp_typist_workflow_is_typist($user);
$isRadiologist = rp_typist_workflow_is_radiologist($user);
$isAdmin = rp_remote_reporting_user_is_admin($user);

if ($isTypist) {
    if (!rp_typist_workflow_can_typist_access($con, $studyint, $user)) {
        rp_remote_json_response(array('success' => false, 'error' => 'This case is not in the typist queue.'), 403);
    }
} else {
    rp_remote_require_study_access($con, $studyint);
}

if ($method === 'GET' || $action === 'state') {
    $state = rp_typist_workflow_get_case_state($con, $studyint);
    rp_remote_json_response(array('success' => true, 'state' => $state));
}

if (!$isAdmin && in_array($action, array('send_to_typist', 'request_edits', 'approve_draft'), true) && !$isRadiologist) {
    rp_remote_json_response(array('success' => false, 'error' => 'Radiologist access required.'), 403);
}
if (!$isAdmin && in_array($action, array('save_draft', 'submit_draft'), true) && !$isTypist) {
    rp_remote_json_response(array('success' => false, 'error' => 'Typist access required.'), 403);
}

$result = array('ok' => false, 'error' => 'Unknown action.');
if ($action === 'send_to_typist') {
    $result = rp_typist_workflow_send_to_typist($con, $studyint, (string)($input['message'] ?? ''), $user);
} elseif ($action === 'save_draft') {
    $result = rp_typist_workflow_save_draft($con, $studyint, (string)($input['draft_text'] ?? ''), false, $user);
} elseif ($action === 'submit_draft') {
    $draftText = trim((string)($input['draft_text'] ?? ''));
    if ($draftText === '') {
        rp_remote_json_response(array('success' => false, 'error' => 'Draft cannot be blank.'), 400);
    }
    $result = rp_typist_workflow_save_draft($con, $studyint, $draftText, true, $user);
} elseif ($action === 'request_edits') {
    $result = rp_typist_workflow_request_edits($con, $studyint, (string)($input['message'] ?? ''), $user);
} elseif ($action === 'approve_draft') {
    $result = rp_typist_workflow_approve_draft($con, $studyint, $user);
}

if (empty($result['ok'])) {
    rp_remote_api_log($con, 'typist_workflow_failed', false, 400, (string)($result['error'] ?? 'Workflow action failed'), $studyint, array('action' => $action, 'user_type' => $userType));
    rp_remote_json_response(array('success' => false, 'error' => (string)($result['error'] ?? 'Workflow action failed.')), 400);
}

rp_remote_api_log($con, 'typist_workflow_' . $action, true, 200, (string)($result['message'] ?? 'Workflow updated'), $studyint, array('user_type' => $userType));
rp_remote_json_response(array(
    'success' => true,
    'message' => (string)($result['message'] ?? 'Workflow updated.'),
    'status' => (string)($result['status'] ?? ''),
    'order_uid' => (string)($result['order_uid'] ?? '')
));
?>
