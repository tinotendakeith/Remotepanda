<?php
require_once __DIR__ . '/dbconnection.php';
require_once __DIR__ . '/cloud_bridge_service.php';

$importLimit = 25;
$returnLimit = 10;
if (PHP_SAPI === 'cli') {
    if (isset($argv[1])) {
        $importLimit = (int) $argv[1];
    }
    if (isset($argv[2])) {
        $returnLimit = (int) $argv[2];
    }
} else {
    if (isset($_GET['import_limit'])) {
        $importLimit = (int) $_GET['import_limit'];
    }
    if (isset($_GET['return_limit'])) {
        $returnLimit = (int) $_GET['return_limit'];
    }
}
$importLimit = max(1, min(100, $importLimit));
$returnLimit = max(1, min(50, $returnLimit));

$importSummary = rp_remote_cloud_bridge_import_orders($con, $importLimit);
$returnSummary = rp_remote_cloud_push_returned_reports($con, $returnLimit);
$success = empty($importSummary['failed']) && empty($returnSummary['failed']);

if (PHP_SAPI === 'cli') {
    echo '[cloud-sync-worker] import checked=' . (int) $importSummary['checked']
        . ' imported=' . (int) $importSummary['imported']
        . ' failed=' . (int) $importSummary['failed'] . PHP_EOL;
    echo '[cloud-sync-worker] return checked=' . (int) $returnSummary['checked']
        . ' sent=' . (int) $returnSummary['sent']
        . ' failed=' . (int) $returnSummary['failed'] . PHP_EOL;
    foreach (array_merge((array) ($importSummary['errors'] ?? array()), (array) ($returnSummary['errors'] ?? array())) as $error) {
        echo (string) $error . PHP_EOL;
    }
    exit($success ? 0 : 1);
}

header('Content-Type: application/json');
header('Cache-Control: no-store');
echo json_encode(array(
    'success' => $success,
    'import' => $importSummary,
    'return' => $returnSummary,
));
?>
