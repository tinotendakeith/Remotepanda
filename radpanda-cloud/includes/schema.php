<?php
function rp_cloud_table_has_column(mysqli $con, string $table, string $column): bool
{
    $tableEsc = mysqli_real_escape_string($con, $table);
    $columnEsc = mysqli_real_escape_string($con, $column);
    $res = mysqli_query($con, "SHOW COLUMNS FROM `{$tableEsc}` LIKE '{$columnEsc}'");
    return $res && mysqli_num_rows($res) > 0;
}

function rp_cloud_add_column_if_missing(mysqli $con, string $table, string $column, string $definition): void
{
    if (!rp_cloud_table_has_column($con, $table, $column)) {
        mysqli_query($con, "ALTER TABLE `{$table}` ADD COLUMN {$definition}");
    }
}

function rp_cloud_ensure_schema(mysqli $con): void
{
    static $done = false;
    if ($done) {
        return;
    }

    mysqli_query($con, "CREATE TABLE IF NOT EXISTS cloud_clinics (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        clinic_uid VARCHAR(120) NOT NULL UNIQUE,
        clinic_name VARCHAR(255) NOT NULL DEFAULT '',
        default_branch VARCHAR(120) NOT NULL DEFAULT '',
        contact_name VARCHAR(255) NOT NULL DEFAULT '',
        contact_email VARCHAR(255) NOT NULL DEFAULT '',
        contact_phone VARCHAR(80) NOT NULL DEFAULT '',
        install_notes TEXT NULL,
        api_key_hash VARCHAR(255) NOT NULL DEFAULT '',
        status VARCHAR(40) NOT NULL DEFAULT 'active',
        last_seen_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_cloud_clinics_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    rp_cloud_add_column_if_missing($con, 'cloud_clinics', 'contact_name', "contact_name VARCHAR(255) NOT NULL DEFAULT '' AFTER default_branch");
    rp_cloud_add_column_if_missing($con, 'cloud_clinics', 'contact_email', "contact_email VARCHAR(255) NOT NULL DEFAULT '' AFTER contact_name");
    rp_cloud_add_column_if_missing($con, 'cloud_clinics', 'contact_phone', "contact_phone VARCHAR(80) NOT NULL DEFAULT '' AFTER contact_email");
    rp_cloud_add_column_if_missing($con, 'cloud_clinics', 'install_notes', "install_notes TEXT NULL AFTER contact_phone");

    mysqli_query($con, "CREATE TABLE IF NOT EXISTS cloud_radiologists (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(191) NOT NULL UNIQUE,
        display_name VARCHAR(255) NOT NULL DEFAULT '',
        email VARCHAR(255) NOT NULL DEFAULT '',
        phone VARCHAR(80) NOT NULL DEFAULT '',
        availability_status VARCHAR(40) NOT NULL DEFAULT 'available',
        modalities TEXT NULL,
        reporting_notes TEXT NULL,
        max_daily_cases INT NOT NULL DEFAULT 0,
        last_seen_at DATETIME NULL,
        status VARCHAR(40) NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_cloud_radiologists_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    rp_cloud_add_column_if_missing($con, 'cloud_radiologists', 'phone', "phone VARCHAR(80) NOT NULL DEFAULT '' AFTER email");
    rp_cloud_add_column_if_missing($con, 'cloud_radiologists', 'availability_status', "availability_status VARCHAR(40) NOT NULL DEFAULT 'available' AFTER phone");
    rp_cloud_add_column_if_missing($con, 'cloud_radiologists', 'modalities', "modalities TEXT NULL AFTER availability_status");
    rp_cloud_add_column_if_missing($con, 'cloud_radiologists', 'reporting_notes', "reporting_notes TEXT NULL AFTER modalities");
    rp_cloud_add_column_if_missing($con, 'cloud_radiologists', 'max_daily_cases', "max_daily_cases INT NOT NULL DEFAULT 0 AFTER reporting_notes");
    rp_cloud_add_column_if_missing($con, 'cloud_radiologists', 'last_seen_at', "last_seen_at DATETIME NULL AFTER max_daily_cases");
    rp_cloud_add_column_if_missing($con, 'cloud_radiologists', 'password_hash', "password_hash VARCHAR(255) NOT NULL DEFAULT '' AFTER last_seen_at");
    rp_cloud_add_column_if_missing($con, 'cloud_radiologists', 'password_updated_at', "password_updated_at DATETIME NULL AFTER password_hash");

    mysqli_query($con, "CREATE TABLE IF NOT EXISTS cloud_typists (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(191) NOT NULL UNIQUE,
        display_name VARCHAR(255) NOT NULL DEFAULT '',
        email VARCHAR(255) NOT NULL DEFAULT '',
        phone VARCHAR(80) NOT NULL DEFAULT '',
        availability_status VARCHAR(40) NOT NULL DEFAULT 'available',
        specialties TEXT NULL,
        notes TEXT NULL,
        last_seen_at DATETIME NULL,
        status VARCHAR(40) NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_cloud_typists_status (status),
        KEY idx_cloud_typists_availability (availability_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($con, "CREATE TABLE IF NOT EXISTS cloud_radiologist_typists (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        radiologist_username VARCHAR(191) NOT NULL DEFAULT '',
        typist_username VARCHAR(191) NOT NULL DEFAULT '',
        status VARCHAR(40) NOT NULL DEFAULT 'active',
        assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        assigned_by VARCHAR(191) NOT NULL DEFAULT '',
        notes TEXT NULL,
        UNIQUE KEY uniq_cloud_rad_typist (radiologist_username, typist_username),
        KEY idx_cloud_rad_typist_rad (radiologist_username, status),
        KEY idx_cloud_rad_typist_typist (typist_username, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($con, "CREATE TABLE IF NOT EXISTS cloud_assignment_rules (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        rule_name VARCHAR(255) NOT NULL DEFAULT '',
        clinic_uid VARCHAR(120) NOT NULL DEFAULT '',
        modality VARCHAR(80) NOT NULL DEFAULT '',
        procedure_text VARCHAR(255) NOT NULL DEFAULT '',
        radiologist_username VARCHAR(191) NOT NULL DEFAULT '',
        priority INT NOT NULL DEFAULT 100,
        status VARCHAR(40) NOT NULL DEFAULT 'active',
        notes TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_cloud_assignment_active (status, priority),
        KEY idx_cloud_assignment_clinic (clinic_uid, modality)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($con, "CREATE TABLE IF NOT EXISTS cloud_report_orders (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        order_uid VARCHAR(80) NOT NULL UNIQUE,
        clinic_id VARCHAR(120) NOT NULL DEFAULT '',
        branch VARCHAR(120) NULL,
        studyint VARCHAR(255) NOT NULL,
        accession_number VARCHAR(80) NULL,
        patient_id INT NULL,
        patient_name VARCHAR(255) NOT NULL DEFAULT '',
        date_of_birth VARCHAR(40) NOT NULL DEFAULT '',
        gender VARCHAR(40) NOT NULL DEFAULT '',
        requesting_physician VARCHAR(255) NOT NULL DEFAULT '',
        modality VARCHAR(80) NULL,
        procedure_name VARCHAR(255) NULL,
        orthanc_study_id VARCHAR(120) NULL,
        radiologist_id BIGINT NULL,
        radiologist_username VARCHAR(191) NULL,
        local_invoice_id BIGINT NULL,
        package_policy VARCHAR(40) NOT NULL DEFAULT 'full_dicom_zip',
        package_path TEXT NULL,
        package_size BIGINT NOT NULL DEFAULT 0,
        payload_json MEDIUMTEXT NULL,
        status VARCHAR(40) NOT NULL DEFAULT 'received',
        received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        assigned_at DATETIME NULL,
        reported_at DATETIME NULL,
        returned_at DATETIME NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_cloud_orders_clinic (clinic_id, status),
        KEY idx_cloud_orders_studyint (studyint),
        KEY idx_cloud_orders_radiologist (radiologist_username, status),
        KEY idx_cloud_orders_accession (accession_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($con, "CREATE TABLE IF NOT EXISTS cloud_study_packages (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        order_uid VARCHAR(80) NOT NULL,
        studyint VARCHAR(255) NOT NULL,
        file_name VARCHAR(255) NOT NULL DEFAULT '',
        file_size BIGINT NOT NULL DEFAULT 0,
        storage_path TEXT NOT NULL,
        extract_path TEXT NULL,
        upload_status VARCHAR(40) NOT NULL DEFAULT 'received',
        message TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_cloud_packages_order (order_uid),
        KEY idx_cloud_packages_studyint (studyint)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($con, "CREATE TABLE IF NOT EXISTS cloud_report_return_outbox (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        order_uid VARCHAR(80) NOT NULL UNIQUE,
        clinic_id VARCHAR(120) NOT NULL DEFAULT '',
        studyint VARCHAR(255) NOT NULL,
        accession_number VARCHAR(80) NULL,
        payload_json LONGTEXT NULL,
        report_text LONGTEXT NULL,
        reported_by_username VARCHAR(191) NULL,
        status VARCHAR(40) NOT NULL DEFAULT 'queued',
        attempts INT NOT NULL DEFAULT 0,
        last_error TEXT NULL,
        next_retry_at DATETIME NULL,
        sent_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_cloud_return_clinic (clinic_id, status),
        KEY idx_cloud_return_studyint (studyint)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($con, "CREATE TABLE IF NOT EXISTS cloud_audit_log (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        event_type VARCHAR(80) NOT NULL,
        entity_type VARCHAR(80) NOT NULL DEFAULT '',
        entity_id VARCHAR(120) NOT NULL DEFAULT '',
        clinic_id VARCHAR(120) NOT NULL DEFAULT '',
        actor VARCHAR(191) NOT NULL DEFAULT '',
        success TINYINT(1) NOT NULL DEFAULT 1,
        message TEXT NULL,
        context_json MEDIUMTEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_cloud_audit_type (event_type, created_at),
        KEY idx_cloud_audit_clinic (clinic_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $done = true;
}
?>
