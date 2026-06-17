<?php
require_once __DIR__ . '/dbconnection.php';
require_once __DIR__ . '/api_security.php';

header('Content-Type: application/json');

$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
$limit = max(1, min(50, $limit));
$summary = array('checked' => 0, 'extracted' => 0, 'failed' => 0);

if (!class_exists('ZipArchive')) {
    echo json_encode(array('success' => false, 'error' => 'ZipArchive is not enabled on this server.', 'summary' => $summary));
    exit;
}

$res = mysqli_query($con, "SELECT r.order_uid, r.studyint, r.package_path, u.id AS upload_id, u.extract_path
    FROM remote_report_orders r
    LEFT JOIN remote_sync_uploads u ON u.order_uid = r.order_uid
    WHERE r.status = 'received_zip_only'
      AND COALESCE(r.package_path, '') <> ''
    ORDER BY r.updated_at ASC
    LIMIT {$limit}");

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $summary['checked']++;
        $orderUid = (string) ($row['order_uid'] ?? '');
        $studyint = (string) ($row['studyint'] ?? '');
        $zipPath = (string) ($row['package_path'] ?? '');
        $extractPath = (string) ($row['extract_path'] ?? '');
        if ($extractPath === '') {
            $extractPath = dirname($zipPath);
        }

        $message = '';
        $ok = false;
        if ($zipPath === '' || !is_file($zipPath)) {
            $message = 'Stored ZIP package is missing.';
        } elseif (!is_dir($extractPath) && !mkdir($extractPath, 0775, true)) {
            $message = 'Could not create extraction directory.';
        } else {
            $zip = new ZipArchive();
            if ($zip->open($zipPath) === true) {
                $ok = $zip->extractTo($extractPath);
                $zip->close();
                $message = $ok ? 'Extracted.' : 'ZIP extraction failed.';
            } else {
                $message = 'Could not open ZIP package.';
            }
        }

        $orderEsc = mysqli_real_escape_string($con, $orderUid);
        $studyEsc = mysqli_real_escape_string($con, $studyint);
        $messageEsc = mysqli_real_escape_string($con, $message);
        $extractEsc = mysqli_real_escape_string($con, $extractPath);

        if ($ok) {
            $summary['extracted']++;
            mysqli_query($con, "UPDATE remote_report_orders SET status='received', updated_at=NOW() WHERE order_uid='{$orderEsc}' LIMIT 1");
            mysqli_query($con, "UPDATE remote_sync_uploads SET upload_status='received', extract_path='{$extractEsc}', message='{$messageEsc}' WHERE order_uid='{$orderEsc}'");
            rp_remote_api_log($con, 'sync_upload_extracted', true, 200, $message, $studyint, array('order_uid' => $orderUid));
        } else {
            $summary['failed']++;
            mysqli_query($con, "UPDATE remote_sync_uploads SET upload_status='extract_failed', extract_path='{$extractEsc}', message='{$messageEsc}' WHERE order_uid='{$orderEsc}'");
            rp_remote_api_log($con, 'sync_upload_extract_failed', false, 500, $message, $studyint, array('order_uid' => $orderUid));
        }
    }
}

echo json_encode(array('success' => true, 'summary' => $summary));
