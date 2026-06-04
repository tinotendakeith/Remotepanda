<?php

function rp_remote_database_config(): array
{
    $defaults = array(
        'host' => 'localhost',
        'port' => 3306,
        'username' => 'root',
        'password' => '',
        'database' => 'radpandaco_appointment',
        'cloud_database' => 'radpanda_cloud',
    );

    $localConfig = __DIR__ . '/database_config.local.php';
    if (is_file($localConfig)) {
        $loaded = include $localConfig;
        if (is_array($loaded)) {
            $defaults = array_merge($defaults, $loaded);
        }
    }

    $envMap = array(
        'host' => 'REMOTEPANDA_DB_HOST',
        'port' => 'REMOTEPANDA_DB_PORT',
        'username' => 'REMOTEPANDA_DB_USER',
        'password' => 'REMOTEPANDA_DB_PASS',
        'database' => 'REMOTEPANDA_DB_NAME',
        'cloud_database' => 'REMOTEPANDA_CLOUD_DB_NAME',
    );

    foreach ($envMap as $key => $envName) {
        $value = getenv($envName);
        if ($value !== false && $value !== '') {
            $defaults[$key] = $key === 'port' ? (int) $value : (string) $value;
        }
    }

    return $defaults;
}

function rp_remote_database_connect(?string $databaseOverride = null): mysqli
{
    static $connections = array();

    $cfg = rp_remote_database_config();
    $database = $databaseOverride !== null ? $databaseOverride : (string) $cfg['database'];
    $key = implode('|', array(
        (string) $cfg['host'],
        (string) $cfg['port'],
        (string) $cfg['username'],
        $database,
    ));

    if (isset($connections[$key]) && $connections[$key] instanceof mysqli) {
        if (@mysqli_ping($connections[$key])) {
            return $connections[$key];
        }
        unset($connections[$key]);
    }

    $con = mysqli_connect(
        (string) $cfg['host'],
        (string) $cfg['username'],
        (string) $cfg['password'],
        $database,
        (int) $cfg['port']
    );

    if (!$con) {
        http_response_code(500);
        exit('Database connection failed.');
    }

    mysqli_set_charset($con, 'utf8mb4');
    $connections[$key] = $con;
    return $con;
}

function rp_remote_database_pdo(?string $databaseOverride = null): PDO
{
    $cfg = rp_remote_database_config();
    $database = $databaseOverride !== null ? $databaseOverride : (string) $cfg['database'];
    $dsn = 'mysql:host=' . (string) $cfg['host'] . ';port=' . (int) $cfg['port'] . ';dbname=' . $database . ';charset=utf8mb4';
    $pdo = new PDO($dsn, (string) $cfg['username'], (string) $cfg['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}
?>
