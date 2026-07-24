<?php

require_once __DIR__ . '/../includes/api.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/schema.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    rp_cloud_json(['ok' => false, 'message' => 'Method not allowed.'], 405);
}

$clinicId = trim((string) ($_GET['clinic_id'] ?? ''));
if ($clinicId === '') {
    rp_cloud_json(['ok' => false, 'message' => 'clinic_id is required.'], 422);
}

$con = rp_cloud_database_connect();
rp_cloud_ensure_schema($con);
rp_cloud_require_clinic_sync_key($con, $clinicId);

$rows = [];
$result = $con->query(
    "SELECT id, username, display_name, email, phone, availability, status,
            modalities, max_daily_cases, notes, updated_at
       FROM cloud_radiologists
      WHERE status = 'active'
      ORDER BY availability = 'available' DESC, display_name ASC, username ASC"
);

while ($result && ($row = $result->fetch_assoc())) {
    $modalities = array_values(array_filter(array_map('trim', preg_split('/[,;]+/', (string) ($row['modalities'] ?? '')))));
    $rows[] = [
        'cloud_id' => (int) $row['id'],
        'username' => (string) $row['username'],
        'display_name' => (string) $row['display_name'],
        'email' => (string) ($row['email'] ?? ''),
        'phone' => (string) ($row['phone'] ?? ''),
        'availability' => (string) $row['availability'],
        'status' => (string) $row['status'],
        'modalities' => $modalities,
        'max_daily_cases' => (int) ($row['max_daily_cases'] ?? 0),
        'notes' => (string) ($row['notes'] ?? ''),
        'updated_at' => (string) ($row['updated_at'] ?? ''),
    ];
}

$versionParts = array_map(static function (array $row): string {
    return $row['cloud_id'] . ':' . $row['updated_at'];
}, $rows);
$version = hash('sha256', implode('|', $versionParts));
$etag = '"' . $version . '"';

header('Cache-Control: private, max-age=60, must-revalidate');
header('ETag: ' . $etag);
if (trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
    http_response_code(304);
    exit;
}

rp_cloud_audit($con, 'radiologist_directory_read', 'clinic', $clinicId, [
    'clinic_id' => $clinicId,
    'count' => count($rows),
    'version' => $version,
]);

rp_cloud_json([
    'ok' => true,
    'source' => 'radpanda_cloud',
    'clinic_id' => $clinicId,
    'version' => $version,
    'generated_at' => gmdate('c'),
    'radiologists' => $rows,
]);
