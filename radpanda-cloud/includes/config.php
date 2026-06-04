<?php
define('RP_CLOUD_DB_HOST', getenv('RP_CLOUD_DB_HOST') ?: '127.0.0.1');
define('RP_CLOUD_DB_USER', getenv('RP_CLOUD_DB_USER') ?: 'root');
define('RP_CLOUD_DB_PASS', getenv('RP_CLOUD_DB_PASS') ?: '');
define('RP_CLOUD_DB_NAME', getenv('RP_CLOUD_DB_NAME') ?: 'radpanda_cloud');
define('RP_CLOUD_SYNC_KEY', getenv('RP_CLOUD_SYNC_KEY') ?: '');
define('RP_CLOUD_STORAGE_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage');
define('RP_CLOUD_ADMIN_USER', getenv('RP_CLOUD_ADMIN_USER') ?: 'admin');
define('RP_CLOUD_ADMIN_PASSWORD', getenv('RP_CLOUD_ADMIN_PASSWORD') ?: '');
define('RP_CLOUD_ADMIN_PASSWORD_HASH', getenv('RP_CLOUD_ADMIN_PASSWORD_HASH') ?: '');
define('RP_CLOUD_ADMIN_IDLE_TIMEOUT', (int) (getenv('RP_CLOUD_ADMIN_IDLE_TIMEOUT') ?: 1800));
define('RP_CLOUD_ADMIN_ABSOLUTE_TIMEOUT', (int) (getenv('RP_CLOUD_ADMIN_ABSOLUTE_TIMEOUT') ?: 28800));
define('RP_CLOUD_ADMIN_MAX_LOGIN_ATTEMPTS', (int) (getenv('RP_CLOUD_ADMIN_MAX_LOGIN_ATTEMPTS') ?: 5));
define('RP_CLOUD_ADMIN_LOCKOUT_SECONDS', (int) (getenv('RP_CLOUD_ADMIN_LOCKOUT_SECONDS') ?: 900));
define('RP_CLOUD_REQUIRE_HASHED_ADMIN_PASSWORD', (getenv('RP_CLOUD_REQUIRE_HASHED_ADMIN_PASSWORD') ?: '0') === '1');
define('RP_CLOUD_PUBLIC_BASE_URL', getenv('RP_CLOUD_PUBLIC_BASE_URL') ?: 'https://radpanda.cloud');
?>
