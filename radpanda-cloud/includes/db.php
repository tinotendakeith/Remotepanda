<?php
require_once __DIR__ . '/config.php';

$con = mysqli_connect(RP_CLOUD_DB_HOST, RP_CLOUD_DB_USER, RP_CLOUD_DB_PASS, RP_CLOUD_DB_NAME);
if (!$con) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'error' => 'Radpanda Cloud database connection failed.'));
    exit;
}
mysqli_set_charset($con, 'utf8mb4');
?>
