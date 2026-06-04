<?php
require_once __DIR__ . '/includes/dbconnection.php';

header('Content-Type: text/plain; charset=utf-8');

$checks = array();
$checks[] = 'Remotepanda deploy check';
$checks[] = 'PHP: ' . PHP_VERSION;
$checks[] = 'mysqli: ' . (extension_loaded('mysqli') ? 'ok' : 'missing');
$checks[] = 'curl: ' . (extension_loaded('curl') ? 'ok' : 'missing');
$checks[] = 'zip: ' . (extension_loaded('zip') ? 'ok' : 'missing');
$checks[] = 'fileinfo: ' . (extension_loaded('fileinfo') ? 'ok' : 'missing');
$checks[] = 'database: ' . ($con ? 'connected' : 'failed');

$storageDir = __DIR__ . '/storage/pacs';
if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0775, true);
}
$checks[] = 'remote pacs storage: ' . (is_dir($storageDir) && is_writable($storageDir) ? 'writable' : 'not writable');

echo implode(PHP_EOL, $checks);
echo PHP_EOL;
