<?php
header('Content-Type: application/json');
header('Cache-Control: no-store');

require_once __DIR__ . '/../includes/dbconnection.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../includes/cloud_bridge_service.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isCli = PHP_SAPI === 'cli';
if (!$isCli && !isLoggedIn()) {
    http_response_code(401);
    echo json_encode(array('success' => false, 'error' => 'Login required.'));
    exit;
}

$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : (int) ($_POST['limit'] ?? 10);
$summary = rp_remote_cloud_push_returned_reports($con, $limit);

echo json_encode(array(
    'success' => empty($summary['failed']),
    'summary' => $summary
));
?>
