<?php

if (!function_exists('rp_remote_has_column')) {
    function rp_remote_has_column(mysqli $con, string $table, string $column): bool
    {
        $tableEsc = str_replace('`', '``', $table);
        $columnEsc = str_replace('`', '``', $column);
        $sql = "SHOW COLUMNS FROM `{$tableEsc}` LIKE '{$columnEsc}'";
        $res = @mysqli_query($con, $sql);
        return $res instanceof mysqli_result && mysqli_num_rows($res) > 0;
    }

    function rp_remote_settings_ensure(mysqli $con): void
    {
        @mysqli_query($con, "CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(120) NOT NULL UNIQUE,
            setting_value TEXT NULL,
            updated_by INT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $defaults = [
            'feature_remote_api_enabled' => '1',
            'feature_remote_dicom_stream_enabled' => '1',
            'feature_remote_zip_export_enabled' => '1',
            'feature_remote_study_notes_enabled' => '1',
            'feature_remote_sync_receiver_enabled' => '1',
            'remote_sync_api_key' => '',
            'feature_remote_strict_study_acl' => '0',
            'feature_remote_strict_study_acl_mode' => 'off',
            'feature_remote_strict_study_acl_fail_open' => '1',
            'pacs_base_directory' => rp_remote_default_pacs_base_directory(),
            'pacs_allow_recursive_lookup' => '1',
        ];

        foreach ($defaults as $key => $value) {
            $keyEsc = mysqli_real_escape_string($con, $key);
            $valueEsc = mysqli_real_escape_string($con, $value);
            @mysqli_query(
                $con,
                "INSERT INTO system_settings (setting_key, setting_value) VALUES ('{$keyEsc}', '{$valueEsc}')
                 ON DUPLICATE KEY UPDATE setting_value = setting_value"
            );
        }
    }

    function rp_remote_setting_get(mysqli $con, string $key, string $default = ''): string
    {
        $keyEsc = mysqli_real_escape_string($con, $key);
        $res = @mysqli_query($con, "SELECT setting_value FROM system_settings WHERE setting_key = '{$keyEsc}' LIMIT 1");
        if ($res instanceof mysqli_result && mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_assoc($res);
            return isset($row['setting_value']) ? (string) $row['setting_value'] : $default;
        }
        return $default;
    }

    function rp_remote_setting_set(mysqli $con, string $key, string $value, ?int $updatedBy = null): bool
    {
        $keyEsc = mysqli_real_escape_string($con, $key);
        $valueEsc = mysqli_real_escape_string($con, $value);
        $updatedBySql = $updatedBy === null ? 'NULL' : (string) ((int) $updatedBy);

        $sql = "INSERT INTO system_settings (setting_key, setting_value, updated_by)
                VALUES ('{$keyEsc}', '{$valueEsc}', {$updatedBySql})
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP";

        return @mysqli_query($con, $sql) === true;
    }

    function rp_remote_feature_enabled(mysqli $con, string $flagKey, bool $default = false): bool
    {
        $fallback = $default ? '1' : '0';
        $value = rp_remote_setting_get($con, $flagKey, $fallback);
        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    function rp_remote_acl_mode(mysqli $con): string
    {
        $raw = strtolower(trim(rp_remote_setting_get($con, 'feature_remote_strict_study_acl_mode', '')));
        if (in_array($raw, ['off', 'monitor', 'enforce'], true)) {
            return $raw;
        }

        // Backward compatibility with legacy boolean flag.
        return rp_remote_feature_enabled($con, 'feature_remote_strict_study_acl', false) ? 'enforce' : 'off';
    }

    function rp_remote_acl_fail_open(mysqli $con): bool
    {
        return rp_remote_feature_enabled($con, 'feature_remote_strict_study_acl_fail_open', true);
    }

    function rp_remote_default_pacs_base_directory(): string
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return 'C:/Sante Server DB';
        }

        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'pacs';
    }

    function rp_remote_get_pacs_base_directory(mysqli $con): string
    {
        $configured = trim(rp_remote_setting_get($con, 'pacs_base_directory', rp_remote_default_pacs_base_directory()));
        if ($configured === '') {
            return rp_remote_default_pacs_base_directory();
        }

        if (DIRECTORY_SEPARATOR !== '\\' && preg_match('/^[A-Za-z]:[\\\\\\/]/', $configured)) {
            return rp_remote_default_pacs_base_directory();
        }

        return rtrim($configured, "\\/");
    }

    function rp_remote_allow_recursive_lookup(mysqli $con): bool
    {
        return rp_remote_feature_enabled($con, 'pacs_allow_recursive_lookup', true);
    }
}
