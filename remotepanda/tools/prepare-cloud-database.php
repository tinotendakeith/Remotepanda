<?php
require_once __DIR__ . '/../includes/database_config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$targetDb = $argv[1] ?? 'remotepanda_cloud';
$copyData = in_array('--copy-data', $argv, true);

if (!preg_match('/^[A-Za-z0-9_]+$/', $targetDb)) {
    fwrite(STDERR, "Invalid database name.\n");
    exit(1);
}

$cfg = rp_remote_database_config();
$sourceDb = (string) $cfg['database'];
$admin = rp_remote_database_connect('');

mysqli_query($admin, "CREATE DATABASE IF NOT EXISTS `{$targetDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

$tables = array(
    'users',
    'study',
    'remote_report_orders',
    'remote_report_return_outbox',
    'remote_sync_uploads',
    'remote_api_audit_logs',
    'system_settings',
    'study_path_cache',
    'templates',
    'report_templates',
);

$created = array();
$copied = array();
$missing = array();

foreach ($tables as $table) {
    $tableEsc = mysqli_real_escape_string($admin, $table);
    $existsRes = mysqli_query($admin, "SHOW TABLES FROM `{$sourceDb}` LIKE '{$tableEsc}'");
    if (!$existsRes || mysqli_num_rows($existsRes) === 0) {
        $missing[] = $table;
        continue;
    }

    $createRes = mysqli_query($admin, "SHOW CREATE TABLE `{$sourceDb}`.`{$table}`");
    $createRow = $createRes ? mysqli_fetch_assoc($createRes) : null;
    $createSql = $createRow['Create Table'] ?? '';
    if ($createSql === '') {
        $missing[] = $table;
        continue;
    }

    mysqli_query($admin, "DROP TABLE IF EXISTS `{$targetDb}`.`{$table}`");
    $targetCreate = preg_replace('/^CREATE TABLE `[^`]+`/i', "CREATE TABLE `{$targetDb}`.`{$table}`", $createSql, 1);
    if (!mysqli_query($admin, $targetCreate)) {
        fwrite(STDERR, "Could not create {$targetDb}.{$table}: " . mysqli_error($admin) . "\n");
        continue;
    }
    $created[] = $table;

    if ($copyData) {
        if (mysqli_query($admin, "INSERT INTO `{$targetDb}`.`{$table}` SELECT * FROM `{$sourceDb}`.`{$table}`")) {
            $copied[] = $table;
        } else {
            fwrite(STDERR, "Could not copy {$table}: " . mysqli_error($admin) . "\n");
        }
    }
}

$examplePath = realpath(__DIR__ . '/../includes') . DIRECTORY_SEPARATOR . 'database_config.local.php';

echo json_encode(array(
    'success' => true,
    'source_database' => $sourceDb,
    'target_database' => $targetDb,
    'created_tables' => $created,
    'copied_tables' => $copied,
    'missing_source_tables' => $missing,
    'copy_data' => $copyData,
    'next_step' => "Create {$examplePath} from database_config.local.example.php when ready to switch Remotepanda.",
), JSON_PRETTY_PRINT) . PHP_EOL;
?>
