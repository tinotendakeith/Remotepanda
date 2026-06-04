<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/schema.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

rp_cloud_ensure_schema($con);

$clinicUid = trim((string) ($argv[1] ?? ''));
$clinicName = trim((string) ($argv[2] ?? $clinicUid));
$branch = trim((string) ($argv[3] ?? ''));
$plainKey = trim((string) ($argv[4] ?? ''));

if ($clinicUid === '') {
    echo "Usage: php C:/xampp/htdocs/radpanda-cloud/tools/create-clinic-key.php <clinic_uid> [clinic_name] [branch] [plain_key]\n";
    exit(1);
}
if ($plainKey === '') {
    $plainKey = 'rpk_' . bin2hex(random_bytes(24));
}

$hash = password_hash($plainKey, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($con, "INSERT INTO cloud_clinics
        (clinic_uid, clinic_name, default_branch, api_key_hash, status)
    VALUES (?, ?, ?, ?, 'active')
    ON DUPLICATE KEY UPDATE clinic_name = VALUES(clinic_name), default_branch = VALUES(default_branch),
        api_key_hash = VALUES(api_key_hash), status = 'active', updated_at = NOW()");
if (!$stmt) {
    echo 'Could not prepare clinic key insert: ' . mysqli_error($con) . PHP_EOL;
    exit(1);
}
mysqli_stmt_bind_param($stmt, 'ssss', $clinicUid, $clinicName, $branch, $hash);
if (!mysqli_stmt_execute($stmt)) {
    echo 'Could not save clinic key: ' . mysqli_stmt_error($stmt) . PHP_EOL;
    exit(1);
}
mysqli_stmt_close($stmt);

echo "Clinic UID: {$clinicUid}\n";
echo "Clinic name: {$clinicName}\n";
echo "API key: {$plainKey}\n";
echo "Store this key in the clinic node setting: sync_cloud_api_key\n";
?>
