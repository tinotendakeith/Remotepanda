<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only.\n";
    exit(1);
}

$password = (string) ($argv[1] ?? '');
if ($password === '') {
    echo "Usage: php create-admin-password-hash.php \"your-strong-password\"\n";
    exit(1);
}

echo password_hash($password, PASSWORD_DEFAULT) . PHP_EOL;
echo "Set this as RP_CLOUD_ADMIN_PASSWORD_HASH on the Cloud server.\n";
?>
