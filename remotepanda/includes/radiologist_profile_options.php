<?php

function rp_profile_options_column_exists($con, $table, $column)
{
    $table = mysqli_real_escape_string($con, $table);
    $column = mysqli_real_escape_string($con, $column);
    $result = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function rp_profile_options_ensure_schema($con)
{
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS radiologist_profile_options (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        option_type VARCHAR(40) NOT NULL,
        option_label VARCHAR(191) NOT NULL,
        option_value VARCHAR(191) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_type_value (option_type, option_value),
        KEY idx_type_active (option_type, is_active, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $defaults = array(
        'specialty' => array(
            'General Radiology',
            'Chest',
            'MSK',
            'Neuro',
            'Paediatric',
            'Obstetric',
            'Breast',
            'Cardiac',
            'Interventional',
            'Emergency / Trauma',
        ),
        'modality' => array(
            'X-ray',
            'CT',
            'MRI',
            'Ultrasound',
            'Mammography',
            'Fluoroscopy',
            'DEXA',
            'Nuclear Medicine',
        ),
    );

    foreach ($defaults as $type => $labels) {
        $typeEsc = mysqli_real_escape_string($con, $type);
        $result = mysqli_query($con, "SELECT COUNT(*) AS total FROM radiologist_profile_options WHERE option_type = '$typeEsc'");
        $row = $result ? mysqli_fetch_assoc($result) : array('total' => 0);
        if ((int)($row['total'] ?? 0) > 0) {
            continue;
        }

        $sort = 10;
        foreach ($labels as $label) {
            rp_profile_options_upsert($con, $type, $label, $sort, 1, null);
            $sort += 10;
        }
    }
}

function rp_profile_options_normalize_type($type)
{
    $type = strtolower(trim((string)$type));
    return in_array($type, array('specialty', 'modality'), true) ? $type : '';
}

function rp_profile_options_upsert($con, $type, $label, $sortOrder = 0, $isActive = 1, $id = null)
{
    $type = rp_profile_options_normalize_type($type);
    $label = trim((string)$label);
    if ($type === '' || $label === '') {
        return false;
    }

    $value = $label;
    $sortOrder = (int)$sortOrder;
    $isActive = $isActive ? 1 : 0;

    if ($id !== null && (int)$id > 0) {
        $id = (int)$id;
        $stmt = mysqli_prepare($con, "UPDATE radiologist_profile_options SET option_type = ?, option_label = ?, option_value = ?, sort_order = ?, is_active = ? WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'sssiii', $type, $label, $value, $sortOrder, $isActive, $id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }

    $stmt = mysqli_prepare($con, "INSERT INTO radiologist_profile_options (option_type, option_label, option_value, sort_order, is_active)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE option_label = VALUES(option_label), sort_order = VALUES(sort_order), is_active = VALUES(is_active)");
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 'sssii', $type, $label, $value, $sortOrder, $isActive);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function rp_profile_options_list($con, $type, $activeOnly = true)
{
    $type = rp_profile_options_normalize_type($type);
    if ($type === '') {
        return array();
    }

    $sql = "SELECT * FROM radiologist_profile_options WHERE option_type = ?";
    if ($activeOnly) {
        $sql .= " AND is_active = 1";
    }
    $sql .= " ORDER BY sort_order ASC, option_label ASC";

    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        return array();
    }
    mysqli_stmt_bind_param($stmt, 's', $type);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = array();
    while ($result && ($row = mysqli_fetch_assoc($result))) {
        $rows[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $rows;
}

function rp_profile_options_selected_array($value)
{
    if (is_array($value)) {
        $parts = $value;
    } else {
        $parts = explode(',', (string)$value);
    }

    $selected = array();
    foreach ($parts as $part) {
        $part = trim((string)$part);
        if ($part !== '' && !in_array($part, $selected, true)) {
            $selected[] = $part;
        }
    }
    return $selected;
}

function rp_profile_options_clean_selection($con, $type, $input)
{
    $selected = rp_profile_options_selected_array($input);
    if (!$selected) {
        return '';
    }

    $allowedRows = rp_profile_options_list($con, $type, true);
    $allowed = array();
    foreach ($allowedRows as $row) {
        $allowed[(string)$row['option_label']] = true;
    }

    $clean = array();
    foreach ($selected as $label) {
        if (isset($allowed[$label]) && !in_array($label, $clean, true)) {
            $clean[] = $label;
        }
    }

    return implode(', ', $clean);
}
