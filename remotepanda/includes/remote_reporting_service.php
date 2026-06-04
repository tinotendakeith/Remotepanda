<?php

function rp_remote_reporting_has_column(mysqli $con, string $table, string $column): bool
{
    $tableEsc = mysqli_real_escape_string($con, $table);
    $columnEsc = mysqli_real_escape_string($con, $column);
    $res = @mysqli_query($con, "SHOW COLUMNS FROM `{$tableEsc}` LIKE '{$columnEsc}'");
    return $res instanceof mysqli_result && mysqli_num_rows($res) > 0;
}

function rp_remote_reporting_ensure_schema(mysqli $con): void
{
    @mysqli_query($con, "CREATE TABLE IF NOT EXISTS remote_report_orders (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        order_uid VARCHAR(80) NOT NULL UNIQUE,
        clinic_id VARCHAR(120) NOT NULL DEFAULT '',
        branch VARCHAR(120) NULL,
        studyint VARCHAR(255) NOT NULL,
        accession_number VARCHAR(80) NULL,
        patient_id INT NULL,
        patient_name VARCHAR(255) NOT NULL DEFAULT '',
        modality VARCHAR(80) NULL,
        procedure_name VARCHAR(255) NULL,
        radiologist_id INT NULL,
        radiologist_username VARCHAR(191) NULL,
        local_invoice_id BIGINT NULL,
        package_path TEXT NULL,
        package_size BIGINT NOT NULL DEFAULT 0,
        payload_json MEDIUMTEXT NULL,
        status VARCHAR(40) NOT NULL DEFAULT 'received',
        received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_remote_orders_studyint (studyint),
        KEY idx_remote_orders_radiologist (radiologist_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $orderColumns = array(
        'final_report_text' => "ALTER TABLE remote_report_orders ADD COLUMN final_report_text LONGTEXT NULL",
        'reported_by_user_id' => "ALTER TABLE remote_report_orders ADD COLUMN reported_by_user_id INT NULL",
        'reported_by_username' => "ALTER TABLE remote_report_orders ADD COLUMN reported_by_username VARCHAR(191) NULL",
        'reported_at' => "ALTER TABLE remote_report_orders ADD COLUMN reported_at DATETIME NULL",
        'returned_at' => "ALTER TABLE remote_report_orders ADD COLUMN returned_at DATETIME NULL",
        'last_return_error' => "ALTER TABLE remote_report_orders ADD COLUMN last_return_error TEXT NULL",
        'viewed_at' => "ALTER TABLE remote_report_orders ADD COLUMN viewed_at DATETIME NULL",
        'started_at' => "ALTER TABLE remote_report_orders ADD COLUMN started_at DATETIME NULL"
    );
    foreach ($orderColumns as $column => $sql) {
        if (!rp_remote_reporting_has_column($con, 'remote_report_orders', $column)) {
            @mysqli_query($con, $sql);
        }
    }

    @mysqli_query($con, "CREATE TABLE IF NOT EXISTS remote_report_return_outbox (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        order_uid VARCHAR(80) NOT NULL,
        studyint VARCHAR(255) NOT NULL,
        clinic_id VARCHAR(120) NOT NULL DEFAULT '',
        accession_number VARCHAR(80) NULL,
        payload_json LONGTEXT NULL,
        status VARCHAR(40) NOT NULL DEFAULT 'queued',
        attempts INT NOT NULL DEFAULT 0,
        last_error TEXT NULL,
        next_retry_at DATETIME NULL,
        sent_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_return_order (order_uid),
        KEY idx_return_status (status, next_retry_at),
        KEY idx_return_studyint (studyint)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function rp_remote_reporting_current_user(): array
{
    $user = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : array();
    return array(
        'id' => (int) ($user['id'] ?? 0),
        'username' => (string) ($user['username'] ?? ($_SESSION['username'] ?? '')),
        'type' => strtolower((string) ($user['user_type'] ?? ($_SESSION['user_type'] ?? '')))
    );
}

function rp_remote_reporting_user_is_admin(array $user): bool
{
    return in_array((string) ($user['type'] ?? ''), array('admin', 'superadmin', 'owner'), true);
}

function rp_remote_reporting_assignment_sql(array $user, string $studyAlias = 's', string $orderAlias = 'r'): array
{
    if (rp_remote_reporting_user_is_admin($user)) {
        return array('sql' => '1=1', 'types' => '', 'params' => array());
    }

    $conditions = array();
    $types = '';
    $params = array();
    $userId = (int) ($user['id'] ?? 0);
    $username = trim((string) ($user['username'] ?? ''));

    if ($userId > 0) {
        $conditions[] = "{$studyAlias}.assigned_radiologist_id = ?";
        $conditions[] = "{$orderAlias}.radiologist_id = ?";
        $types .= 'ii';
        $params[] = $userId;
        $params[] = $userId;
    }
    if ($username !== '') {
        $conditions[] = "{$studyAlias}.reporting_radiologist = ?";
        $conditions[] = "{$orderAlias}.radiologist_username = ?";
        $types .= 'ss';
        $params[] = $username;
        $params[] = $username;
    }

    if (empty($conditions)) {
        return array('sql' => '0=1', 'types' => '', 'params' => array());
    }

    return array('sql' => '(' . implode(' OR ', $conditions) . ')', 'types' => $types, 'params' => $params);
}

function rp_remote_reporting_bind(mysqli_stmt $stmt, string $types, array $params): void
{
    if ($types === '') {
        return;
    }
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

function rp_remote_reporting_create_local_reception_alert(mysqli $con, array $study, string $orderUid, string $radiologistName): void
{
    $notificationService = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'radpanda' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'system_notification_service.php';
    if (!is_file($notificationService)) {
        return;
    }

    require_once $notificationService;
    if (!function_exists('rp_system_notifications_create_report_finalized_notification')) {
        return;
    }

    $studyId = (int) ($study['study_id'] ?? 0);
    $branch = trim((string) ($study['branch'] ?? ''));
    if ($studyId <= 0 || $branch === '') {
        return;
    }

    rp_system_notifications_create_report_finalized_notification($con, array(
        'study_id' => $studyId,
        'branch' => $branch,
        'patient_name' => (string) ($study['Name'] ?? 'Patient'),
        'study_name' => (string) (($study['requested_procedure'] ?? '') ?: ($study['study'] ?? 'Study')),
        'radiologist_name' => $radiologistName !== '' ? $radiologistName : 'Remote radiologist',
        'order_uid' => $orderUid,
    ));
}

function rp_remote_reporting_get_case(mysqli $con, string $accession, array $user): ?array
{
    rp_remote_reporting_ensure_schema($con);
    $acl = rp_remote_reporting_assignment_sql($user);
    $sql = "SELECT s.*, r.order_uid, r.clinic_id, r.branch AS remote_branch, r.status AS report_order_status,
                   r.final_report_text, r.reported_at, r.returned_at, r.viewed_at, r.started_at,
                   ro.status AS return_status, ro.attempts AS return_attempts, ro.last_error AS return_last_error,
                   ro.sent_at AS return_sent_at, ro.updated_at AS return_updated_at
            FROM study s
            LEFT JOIN remote_report_orders r ON r.id = (
                SELECT rr.id FROM remote_report_orders rr
                WHERE rr.studyint = s.studyint OR rr.accession_number = CAST(s.accession_number AS CHAR)
                ORDER BY rr.id DESC LIMIT 1
            )
            LEFT JOIN remote_report_return_outbox ro ON ro.order_uid = r.order_uid
            WHERE s.accession_number = ? AND {$acl['sql']}
            LIMIT 1";
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        return null;
    }
    $types = 's' . $acl['types'];
    $params = array_merge(array($accession), $acl['params']);
    rp_remote_reporting_bind($stmt, $types, $params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function rp_remote_reporting_mark_case_opened(mysqli $con, string $studyint, array $user): array
{
    rp_remote_reporting_ensure_schema($con);
    $studyint = trim($studyint);
    if ($studyint === '') {
        return array('ok' => false, 'message' => 'Missing study identifier.');
    }

    $acl = rp_remote_reporting_assignment_sql($user);
    $stmt = mysqli_prepare($con, "SELECT s.accession_number, s.status, r.id AS order_id, r.status AS order_status
        FROM study s
        LEFT JOIN remote_report_orders r ON r.id = (
            SELECT rr.id FROM remote_report_orders rr
            WHERE rr.studyint = s.studyint OR rr.accession_number = CAST(s.accession_number AS CHAR)
            ORDER BY rr.id DESC LIMIT 1
        )
        WHERE s.studyint = ? AND {$acl['sql']}
        LIMIT 1");
    if (!$stmt) {
        return array('ok' => false, 'message' => 'Could not prepare case lookup.');
    }

    $types = 's' . $acl['types'];
    $params = array_merge(array($studyint), $acl['params']);
    rp_remote_reporting_bind($stmt, $types, $params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $study = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    if (!$study) {
        return array('ok' => false, 'message' => 'Study not found or not assigned to this radiologist.');
    }

    $studyStatus = trim((string) ($study['status'] ?? ''));
    $orderStatus = trim((string) ($study['order_status'] ?? ''));
    $canStart = in_array(strtolower($studyStatus), array('awaiting report', 'assigned', 'received'), true)
        || in_array(strtolower($orderStatus), array('received', 'sent_to_cloud', 'assigned'), true);

    if (!empty($study['order_id'])) {
        $orderId = (int) $study['order_id'];
        if ($canStart) {
            $up = mysqli_prepare($con, "UPDATE remote_report_orders
                SET status = 'in_progress', viewed_at = COALESCE(viewed_at, NOW()), started_at = COALESCE(started_at, NOW()), updated_at = NOW()
                WHERE id = ? LIMIT 1");
        } else {
            $up = mysqli_prepare($con, "UPDATE remote_report_orders
                SET viewed_at = COALESCE(viewed_at, NOW()), updated_at = NOW()
                WHERE id = ? LIMIT 1");
        }
        if ($up) {
            mysqli_stmt_bind_param($up, 'i', $orderId);
            mysqli_stmt_execute($up);
            mysqli_stmt_close($up);
        }
    }

    if ($canStart) {
        $upStudy = mysqli_prepare($con, "UPDATE study SET status = 'In Progress', assignment_updated_at = NOW() WHERE studyint = ? LIMIT 1");
        if ($upStudy) {
            mysqli_stmt_bind_param($upStudy, 's', $studyint);
            mysqli_stmt_execute($upStudy);
            mysqli_stmt_close($upStudy);
        }
    }

    return array('ok' => true, 'started' => $canStart);
}

function rp_remote_reporting_finalize_report(mysqli $con, string $studyint, string $reportText, array $user): array
{
    rp_remote_reporting_ensure_schema($con);

    $studyint = trim($studyint);
    $reportText = trim($reportText);
    if ($studyint === '') {
        return array('ok' => false, 'error' => 'Missing study identifier.');
    }
    if ($reportText === '') {
        return array('ok' => false, 'error' => 'Final report cannot be blank.');
    }

    $acl = rp_remote_reporting_assignment_sql($user);
    $stmt = mysqli_prepare($con, "SELECT s.accession_number, s.study_id, s.Name, s.patient_id, s.requested_procedure, s.study, s.modality,
               r.branch,
               r.id AS order_id, r.order_uid, r.clinic_id
        FROM study s
        LEFT JOIN remote_report_orders r ON r.id = (
            SELECT rr.id FROM remote_report_orders rr
            WHERE rr.studyint = s.studyint OR rr.accession_number = CAST(s.accession_number AS CHAR)
            ORDER BY rr.id DESC LIMIT 1
        )
        WHERE s.studyint = ? AND {$acl['sql']}
        LIMIT 1");
    if (!$stmt) {
        return array('ok' => false, 'error' => 'Could not prepare study lookup.');
    }
    $types = 's' . $acl['types'];
    $params = array_merge(array($studyint), $acl['params']);
    rp_remote_reporting_bind($stmt, $types, $params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $study = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    if (!$study) {
        return array('ok' => false, 'error' => 'Study not found or not assigned to this radiologist.');
    }

    $username = (string) ($user['username'] ?? '');
    $userId = (int) ($user['id'] ?? 0);
    $orderUid = trim((string) ($study['order_uid'] ?? ''));
    if ($orderUid === '') {
        $orderUid = 'LOCAL-' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $studyint);
    }
    $clinicId = trim((string) ($study['clinic_id'] ?? ''));
    $accession = (string) ($study['accession_number'] ?? '');

    mysqli_begin_transaction($con);
    try {
        $upStudy = mysqli_prepare($con, "UPDATE study
            SET textarea = ?, radiologist_notes = ?, radiologist_notes_updated_by = ?, radiologist_notes_updated_at = NOW(),
                status = 'Finalized', assignment_updated_at = NOW()
            WHERE studyint = ? LIMIT 1");
        if (!$upStudy) {
            throw new Exception('Could not prepare study report update.');
        }
        mysqli_stmt_bind_param($upStudy, 'ssss', $reportText, $reportText, $username, $studyint);
        if (!mysqli_stmt_execute($upStudy)) {
            throw new Exception('Could not update study report.');
        }
        mysqli_stmt_close($upStudy);

        if (!empty($study['order_id'])) {
            $orderId = (int) $study['order_id'];
            $upOrder = mysqli_prepare($con, "UPDATE remote_report_orders
                SET status = 'reported', final_report_text = ?, reported_by_user_id = ?, reported_by_username = ?, reported_at = NOW(), updated_at = NOW()
                WHERE id = ? LIMIT 1");
            if (!$upOrder) {
                throw new Exception('Could not prepare report order update.');
            }
            mysqli_stmt_bind_param($upOrder, 'sisi', $reportText, $userId, $username, $orderId);
            if (!mysqli_stmt_execute($upOrder)) {
                throw new Exception('Could not update report order.');
            }
            mysqli_stmt_close($upOrder);
        }

        $payload = array(
            'order_uid' => $orderUid,
            'clinic_id' => $clinicId,
            'studyint' => $studyint,
            'accession_number' => $accession,
            'patient_id' => (int) ($study['patient_id'] ?? 0),
            'patient_name' => (string) ($study['Name'] ?? ''),
            'modality' => (string) ($study['modality'] ?? ''),
            'procedure_name' => (string) ($study['requested_procedure'] ?? ''),
            'report_text' => $reportText,
            'reported_by_user_id' => $userId,
            'reported_by_username' => $username,
            'reported_at' => date('c')
        );
        $payloadJson = json_encode($payload);

        $outbox = mysqli_prepare($con, "INSERT INTO remote_report_return_outbox
                (order_uid, studyint, clinic_id, accession_number, payload_json, status, next_retry_at)
            VALUES (?, ?, ?, ?, ?, 'queued', NOW())
            ON DUPLICATE KEY UPDATE payload_json = VALUES(payload_json), status = 'queued', last_error = NULL, next_retry_at = NOW(), updated_at = NOW()");
        if (!$outbox) {
            throw new Exception('Could not prepare report return outbox.');
        }
        mysqli_stmt_bind_param($outbox, 'sssss', $orderUid, $studyint, $clinicId, $accession, $payloadJson);
        if (!mysqli_stmt_execute($outbox)) {
            throw new Exception('Could not queue report return.');
        }
        mysqli_stmt_close($outbox);

        rp_remote_reporting_create_local_reception_alert($con, $study, $orderUid, $username);

        mysqli_commit($con);
    } catch (Exception $e) {
        mysqli_rollback($con);
        return array('ok' => false, 'error' => $e->getMessage());
    }

    return array(
        'ok' => true,
        'order_uid' => $orderUid,
        'studyint' => $studyint,
        'status' => 'reported',
        'message' => 'Report finalized and queued for clinic return.'
    );
}
?>
