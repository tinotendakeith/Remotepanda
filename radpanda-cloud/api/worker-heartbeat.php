<?php

require_once __DIR__ . '/../includes/api.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/schema.php';

rp_cloud_require_post();
$input = rp_cloud_input_json();
$clinicId = trim((string) ($input['clinic_id'] ?? ''));
$workerKey = strtolower(trim((string) ($input['worker_key'] ?? '')));
$nodeUid = trim((string) ($input['node_uid'] ?? $clinicId));
$status = strtolower(trim((string) ($input['status'] ?? 'ok')));
$lastError = trim((string) ($input['last_error'] ?? ''));
$metrics = is_array($input['metrics'] ?? null) ? $input['metrics'] : [];

$allowedWorkers = [
    'clinic_cloud_worker',
    'remotepanda_cloud_sync',
    'image_detection_worker',
    'notification_worker',
];
$allowedStatuses = ['ok', 'warning', 'error'];

if ($clinicId === '' || $nodeUid === '' || !in_array($workerKey, $allowedWorkers, true)) {
    rp_cloud_json(['ok' => false, 'message' => 'Valid clinic_id, node_uid and worker_key are required.'], 422);
}
if (!in_array($status, $allowedStatuses, true)) {
    rp_cloud_json(['ok' => false, 'message' => 'Invalid worker status.'], 422);
}

$con = rp_cloud_database_connect();
rp_cloud_ensure_schema($con);
rp_cloud_require_registered_clinic_sync_key($con, $clinicId);

$stmt = $con->prepare(
    "INSERT INTO cloud_worker_heartbeats
        (worker_key, node_uid, clinic_id, owner_type, status, last_error, metrics_json, last_seen_at)
     VALUES (?, ?, ?, 'clinic', ?, ?, ?, NOW())
     ON DUPLICATE KEY UPDATE
        clinic_id = VALUES(clinic_id),
        status = VALUES(status),
        last_error = VALUES(last_error),
        metrics_json = VALUES(metrics_json),
        last_seen_at = NOW(),
        updated_at = NOW()"
);
$metricsJson = json_encode($metrics, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$stmt->bind_param('ssssss', $workerKey, $nodeUid, $clinicId, $status, $lastError, $metricsJson);
$stmt->execute();

$con->query("UPDATE cloud_clinics SET last_seen_at = NOW(), updated_at = NOW() WHERE clinic_uid = '" . $con->real_escape_string($clinicId) . "'");

rp_cloud_audit($con, 'worker_heartbeat', 'worker', $workerKey . ':' . $nodeUid, $clinicId, $status !== 'error', 'Worker heartbeat received.', [
    'status' => $status,
    'metrics' => $metrics,
]);

rp_cloud_json([
    'ok' => true,
    'worker_key' => $workerKey,
    'node_uid' => $nodeUid,
    'received_at' => gmdate('c'),
]);
