<?php
$rpCloudLocalConfig = array();
$rpCloudLocalConfigPath = __DIR__ . '/config.local.php';
if (is_file($rpCloudLocalConfigPath)) {
    $rpCloudLoadedConfig = include $rpCloudLocalConfigPath;
    if (is_array($rpCloudLoadedConfig)) {
        $rpCloudLocalConfig = $rpCloudLoadedConfig;
    }
}

function rp_cloud_config_value($key, $envName, $default = '')
{
    global $rpCloudLocalConfig;
    $envValue = getenv($envName);
    if ($envValue !== false && $envValue !== '') {
        return $envValue;
    }
    if (array_key_exists($key, $rpCloudLocalConfig)) {
        return $rpCloudLocalConfig[$key];
    }
    return $default;
}

define('RP_CLOUD_DB_HOST', rp_cloud_config_value('db_host', 'RP_CLOUD_DB_HOST', '127.0.0.1'));
define('RP_CLOUD_DB_USER', rp_cloud_config_value('db_user', 'RP_CLOUD_DB_USER', 'root'));
define('RP_CLOUD_DB_PASS', rp_cloud_config_value('db_pass', 'RP_CLOUD_DB_PASS', ''));
define('RP_CLOUD_DB_NAME', rp_cloud_config_value('db_name', 'RP_CLOUD_DB_NAME', 'radpanda_cloud'));
define('RP_CLOUD_SYNC_KEY', rp_cloud_config_value('sync_key', 'RP_CLOUD_SYNC_KEY', ''));
define('RP_CLOUD_STORAGE_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage');
define('RP_CLOUD_ADMIN_USER', rp_cloud_config_value('admin_user', 'RP_CLOUD_ADMIN_USER', 'admin'));
define('RP_CLOUD_ADMIN_PASSWORD', rp_cloud_config_value('admin_password', 'RP_CLOUD_ADMIN_PASSWORD', ''));
define('RP_CLOUD_ADMIN_PASSWORD_HASH', rp_cloud_config_value('admin_password_hash', 'RP_CLOUD_ADMIN_PASSWORD_HASH', ''));
define('RP_CLOUD_ADMIN_IDLE_TIMEOUT', (int) rp_cloud_config_value('admin_idle_timeout', 'RP_CLOUD_ADMIN_IDLE_TIMEOUT', 1800));
define('RP_CLOUD_ADMIN_ABSOLUTE_TIMEOUT', (int) rp_cloud_config_value('admin_absolute_timeout', 'RP_CLOUD_ADMIN_ABSOLUTE_TIMEOUT', 28800));
define('RP_CLOUD_ADMIN_MAX_LOGIN_ATTEMPTS', (int) rp_cloud_config_value('admin_max_login_attempts', 'RP_CLOUD_ADMIN_MAX_LOGIN_ATTEMPTS', 5));
define('RP_CLOUD_ADMIN_LOCKOUT_SECONDS', (int) rp_cloud_config_value('admin_lockout_seconds', 'RP_CLOUD_ADMIN_LOCKOUT_SECONDS', 900));
define('RP_CLOUD_REQUIRE_HASHED_ADMIN_PASSWORD', (string) rp_cloud_config_value('require_hashed_admin_password', 'RP_CLOUD_REQUIRE_HASHED_ADMIN_PASSWORD', '0') === '1');
define('RP_CLOUD_PUBLIC_BASE_URL', rp_cloud_config_value('public_base_url', 'RP_CLOUD_PUBLIC_BASE_URL', 'https://radpanda.cloud'));
?>
