<?php
require_once __DIR__ . '/dbconnection.php';
require_once __DIR__ . '/cloud_bridge_service.php';

$limit = 25;
if (PHP_SAPI === 'cli' && isset($argv[1])) {
    $limit = (int) $argv[1];
} elseif (isset($_GET['limit'])) {
    $limit = (int) $_GET['limit'];
}
$limit = max(1, min(100, $limit));

$summary = rp_remote_cloud_bridge_import_orders($con, $limit);

if (PHP_SAPI === 'cli') {
    echo '[cloud-import-worker] checked=' . (int) $summary['checked']
        . ' imported=' . (int) $summary['imported']
        . ' failed=' . (int) $summary['failed'] . PHP_EOL;
    if (!empty($summary['errors'])) {
        echo implode(PHP_EOL, array_map('strval', $summary['errors'])) . PHP_EOL;
    }
    exit(empty($summary['failed']) ? 0 : 1);
}

header('Content-Type: application/json');
header('Cache-Control: no-store');
echo json_encode(array('success' => empty($summary['failed']), 'summary' => $summary));
?>
