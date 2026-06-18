<?php

require_once __DIR__ . '/remote_reporting_service.php';

function rp_typist_workflow_ensure_schema(mysqli $con): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $cacheKey = 'rp_typist_workflow_schema_checked_at_v2';
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION[$cacheKey]) && (time() - (int)$_SESSION[$cacheKey]) < 86400) {
        $checked = true;
        return;
    }

    @mysqli_query($con, "CREATE TABLE IF NOT EXISTS report_dictations (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        order_uid VARCHAR(80) NULL,
        studyint VARCHAR(255) NOT NULL,
        accession_number VARCHAR(80) NULL,
        radiologist_id INT NULL,
        radiologist_username VARCHAR(191) NULL,
        audio_path TEXT NOT NULL,
        original_file_name VARCHAR(255) NULL,
        mime_type VARCHAR(120) NULL,
        file_size BIGINT NOT NULL DEFAULT 0,
        note_text TEXT NULL,
        status VARCHAR(40) NOT NULL DEFAULT 'available',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_dictation_studyint (studyint),
        KEY idx_dictation_order (order_uid),
        KEY idx_dictation_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @mysqli_query($con, "CREATE TABLE IF NOT EXISTS report_typist_drafts (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        order_uid VARCHAR(80) NULL,
        studyint VARCHAR(255) NOT NULL,
        accession_number VARCHAR(80) NULL,
        typist_id INT NULL,
        typist_username VARCHAR(191) NULL,
        draft_text LONGTEXT NULL,
        status VARCHAR(40) NOT NULL DEFAULT 'with_typist',
        version_number INT NOT NULL DEFAULT 1,
        submitted_at DATETIME NULL,
        reviewed_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_typist_draft_studyint (studyint),
        KEY idx_typist_draft_order (order_uid),
        KEY idx_typist_draft_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @mysqli_query($con, "CREATE TABLE IF NOT EXISTS report_review_messages (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        order_uid VARCHAR(80) NULL,
        studyint VARCHAR(255) NOT NULL,
        from_user_id INT NULL,
        from_username VARCHAR(191) NULL,
        from_role VARCHAR(64) NULL,
        to_role VARCHAR(64) NULL,
        message_text TEXT NULL,
        message_type VARCHAR(40) NOT NULL DEFAULT 'comment',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_review_studyint (studyint),
        KEY idx_review_order (order_uid),
        KEY idx_review_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION[$cacheKey] = time();
    }
    $checked = true;
}

function rp_typist_workflow_storage_root(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'dictations';
}

function rp_typist_workflow_safe_slug(string $value): string
{
    $safe = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $value);
    $safe = trim((string)$safe, '._-');
    return $safe !== '' ? $safe : 'study';
}

function rp_typist_workflow_user(): array
{
    return rp_remote_reporting_current_user();
}

function rp_typist_workflow_is_typist(array $user): bool
{
    return strtolower((string)($user['type'] ?? '')) === 'typist';
}

function rp_typist_workflow_is_radiologist(array $user): bool
{
    return strtolower((string)($user['type'] ?? '')) === 'radiologist';
}

function rp_typist_workflow_cloud_connection(): ?mysqli
{
    if (!function_exists('rp_remote_database_connect')) {
        return null;
    }

    try {
        return rp_remote_database_connect('radpanda_cloud');
    } catch (Throwable $e) {
        return null;
    }
}

function rp_typist_workflow_radiologists_for_typist(array $user): array
{
    $username = trim((string)($user['username'] ?? ''));
    if ($username === '') {
        return array();
    }

    $cloud = rp_typist_workflow_cloud_connection();
    if (!$cloud) {
        return array();
    }

    $rows = array();
    $stmt = mysqli_prepare($cloud, "SELECT rt.radiologist_username
        FROM cloud_radiologist_typists rt
        INNER JOIN cloud_typists t ON t.username = rt.typist_username
        INNER JOIN cloud_radiologists r ON r.username = rt.radiologist_username
        WHERE rt.typist_username = ?
          AND rt.status = 'active'
          AND t.status = 'active'
          AND r.status = 'active'
        ORDER BY rt.radiologist_username ASC");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($res && ($row = mysqli_fetch_assoc($res))) {
            $radiologist = trim((string)($row['radiologist_username'] ?? ''));
            if ($radiologist !== '') {
                $rows[] = $radiologist;
            }
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($cloud);

    return array_values(array_unique($rows));
}

function rp_typist_workflow_typist_linked_to_radiologist(array $user, string $radiologistUsername): bool
{
    $radiologistUsername = trim($radiologistUsername);
    if ($radiologistUsername === '') {
        return false;
    }

    return in_array($radiologistUsername, rp_typist_workflow_radiologists_for_typist($user), true);
}

function rp_typist_workflow_can_typist_access(mysqli $con, string $studyint, array $user): bool
{
    if (rp_remote_reporting_user_is_admin($user)) {
        return true;
    }
    if (!rp_typist_workflow_is_typist($user)) {
        return false;
    }

    rp_typist_workflow_ensure_schema($con);
    $stmt = mysqli_prepare($con, "SELECT r.radiologist_username
        FROM remote_report_orders r
        LEFT JOIN report_typist_drafts d ON d.studyint = r.studyint
        WHERE r.studyint = ?
          AND (
              r.status IN ('dictated','with_typist','typed_draft_ready','needs_typist_edits')
              OR d.status IN ('with_typist','typed_draft_ready','needs_typist_edits')
          )
          AND COALESCE(r.radiologist_username, '') <> ''
        LIMIT 1");
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_bind_param($stmt, 's', $studyint);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    if (!$row) {
        return false;
    }

    return rp_typist_workflow_typist_linked_to_radiologist($user, (string)($row['radiologist_username'] ?? ''));
}

function rp_typist_workflow_get_case_state(mysqli $con, string $studyint): array
{
    rp_typist_workflow_ensure_schema($con);
    $state = array(
        'order' => null,
        'latest_draft' => null,
        'dictations' => array(),
        'messages' => array(),
    );

    $stmt = mysqli_prepare($con, "SELECT r.*, s.Name, s.accession_number AS study_accession, s.requested_procedure, s.requesting_physician
        FROM remote_report_orders r
        LEFT JOIN study s ON s.studyint = r.studyint
        WHERE r.studyint = ?
        ORDER BY r.id DESC LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $studyint);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $state['order'] = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
    }

    $stmt = mysqli_prepare($con, "SELECT * FROM report_typist_drafts WHERE studyint = ? ORDER BY id DESC LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $studyint);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $state['latest_draft'] = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
    }

    $stmt = mysqli_prepare($con, "SELECT * FROM report_dictations WHERE studyint = ? ORDER BY created_at DESC, id DESC");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $studyint);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($res && ($row = mysqli_fetch_assoc($res))) {
            $state['dictations'][] = $row;
        }
        mysqli_stmt_close($stmt);
    }

    $stmt = mysqli_prepare($con, "SELECT * FROM report_review_messages WHERE studyint = ? ORDER BY created_at ASC, id ASC");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $studyint);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($res && ($row = mysqli_fetch_assoc($res))) {
            $state['messages'][] = $row;
        }
        mysqli_stmt_close($stmt);
    }

    return $state;
}

function rp_typist_workflow_order_for_study(mysqli $con, string $studyint): ?array
{
    rp_typist_workflow_ensure_schema($con);
    $stmt = mysqli_prepare($con, "SELECT r.*, s.accession_number AS study_accession
        FROM remote_report_orders r
        LEFT JOIN study s ON s.studyint = r.studyint
        WHERE r.studyint = ?
        ORDER BY r.id DESC LIMIT 1");
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 's', $studyint);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function rp_typist_workflow_add_message(mysqli $con, string $studyint, string $orderUid, string $message, string $toRole, string $type, array $user): void
{
    rp_typist_workflow_ensure_schema($con);
    $uid = (int)($user['id'] ?? 0);
    $username = (string)($user['username'] ?? '');
    $role = (string)($user['type'] ?? '');
    $stmt = mysqli_prepare($con, "INSERT INTO report_review_messages
        (order_uid, studyint, from_user_id, from_username, from_role, to_role, message_text, message_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ssisssss', $orderUid, $studyint, $uid, $username, $role, $toRole, $message, $type);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function rp_typist_workflow_create_dictation(mysqli $con, string $studyint, string $filePath, string $originalName, string $mimeType, int $fileSize, string $noteText, array $user): array
{
    rp_typist_workflow_ensure_schema($con);
    $order = rp_typist_workflow_order_for_study($con, $studyint);
    $orderUid = (string)($order['order_uid'] ?? '');
    $accession = (string)(($order['accession_number'] ?? '') ?: ($order['study_accession'] ?? ''));
    $userId = (int)($user['id'] ?? 0);
    $username = (string)($user['username'] ?? '');

    $stmt = mysqli_prepare($con, "INSERT INTO report_dictations
        (order_uid, studyint, accession_number, radiologist_id, radiologist_username, audio_path, original_file_name, mime_type, file_size, note_text)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        return array('ok' => false, 'error' => 'Could not prepare dictation save.');
    }
    mysqli_stmt_bind_param($stmt, 'sssissssis', $orderUid, $studyint, $accession, $userId, $username, $filePath, $originalName, $mimeType, $fileSize, $noteText);
    $ok = mysqli_stmt_execute($stmt);
    $id = mysqli_insert_id($con);
    mysqli_stmt_close($stmt);
    if (!$ok) {
        return array('ok' => false, 'error' => 'Could not save dictation.');
    }

    $up = mysqli_prepare($con, "UPDATE remote_report_orders SET status = 'dictated', updated_at = NOW() WHERE studyint = ? AND status NOT IN ('reported','returned') LIMIT 1");
    if ($up) {
        mysqli_stmt_bind_param($up, 's', $studyint);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);
    }
    $up = mysqli_prepare($con, "UPDATE study SET status = 'In Progress', assignment_updated_at = NOW() WHERE studyint = ? AND status <> 'Finalized' LIMIT 1");
    if ($up) {
        mysqli_stmt_bind_param($up, 's', $studyint);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);
    }

    rp_typist_workflow_add_message($con, $studyint, $orderUid, 'New radiologist dictation uploaded.', 'typist', 'dictation_uploaded', $user);
    return array('ok' => true, 'id' => $id);
}

function rp_typist_workflow_send_to_typist(mysqli $con, string $studyint, string $message, array $user): array
{
    rp_typist_workflow_ensure_schema($con);
    $order = rp_typist_workflow_order_for_study($con, $studyint);
    if (!$order) {
        return array('ok' => false, 'error' => 'Report order not found.');
    }
    $orderUid = (string)($order['order_uid'] ?? '');
    $accession = (string)(($order['accession_number'] ?? '') ?: ($order['study_accession'] ?? ''));

    $stmt = mysqli_prepare($con, "INSERT INTO report_typist_drafts (order_uid, studyint, accession_number, status)
        VALUES (?, ?, ?, 'with_typist')
        ON DUPLICATE KEY UPDATE status = 'with_typist', updated_at = NOW()");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sss', $orderUid, $studyint, $accession);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $up = mysqli_prepare($con, "UPDATE remote_report_orders SET status = 'with_typist', updated_at = NOW() WHERE studyint = ? AND status NOT IN ('reported','returned') LIMIT 1");
    if ($up) {
        mysqli_stmt_bind_param($up, 's', $studyint);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);
    }

    $msg = trim($message) !== '' ? trim($message) : 'Case sent to typist for report preparation.';
    rp_typist_workflow_add_message($con, $studyint, $orderUid, $msg, 'typist', 'sent_to_typist', $user);
    return array('ok' => true, 'message' => 'Case sent to typist queue.');
}

function rp_typist_workflow_save_draft(mysqli $con, string $studyint, string $draftText, bool $submit, array $user): array
{
    rp_typist_workflow_ensure_schema($con);
    $order = rp_typist_workflow_order_for_study($con, $studyint);
    if (!$order) {
        return array('ok' => false, 'error' => 'Report order not found.');
    }
    $orderUid = (string)($order['order_uid'] ?? '');
    $accession = (string)(($order['accession_number'] ?? '') ?: ($order['study_accession'] ?? ''));
    $userId = (int)($user['id'] ?? 0);
    $username = (string)($user['username'] ?? '');
    $status = $submit ? 'typed_draft_ready' : 'with_typist';
    $submittedAtSql = $submit ? ', submitted_at = NOW()' : '';

    $stmt = mysqli_prepare($con, "SELECT id, version_number FROM report_typist_drafts WHERE studyint = ? ORDER BY id DESC LIMIT 1");
    $existing = null;
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $studyint);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $existing = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);
    }

    if ($existing) {
        $id = (int)$existing['id'];
        $version = (int)$existing['version_number'] + 1;
        $sql = "UPDATE report_typist_drafts SET typist_id = ?, typist_username = ?, draft_text = ?, status = ?, version_number = ?, updated_at = NOW() {$submittedAtSql} WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) {
            return array('ok' => false, 'error' => 'Could not prepare draft update.');
        }
        mysqli_stmt_bind_param($stmt, 'isssii', $userId, $username, $draftText, $status, $version, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $stmt = mysqli_prepare($con, "INSERT INTO report_typist_drafts
            (order_uid, studyint, accession_number, typist_id, typist_username, draft_text, status, version_number, submitted_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, " . ($submit ? 'NOW()' : 'NULL') . ")");
        if (!$stmt) {
            return array('ok' => false, 'error' => 'Could not prepare draft save.');
        }
        mysqli_stmt_bind_param($stmt, 'sssisss', $orderUid, $studyint, $accession, $userId, $username, $draftText, $status);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $up = mysqli_prepare($con, "UPDATE remote_report_orders SET status = ?, updated_at = NOW() WHERE studyint = ? AND status NOT IN ('reported','returned') LIMIT 1");
    if ($up) {
        mysqli_stmt_bind_param($up, 'ss', $status, $studyint);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);
    }

    if ($submit) {
        rp_typist_workflow_add_message($con, $studyint, $orderUid, 'Typed draft submitted to radiologist for approval.', 'radiologist', 'draft_submitted', $user);
    }
    return array('ok' => true, 'status' => $status, 'message' => $submit ? 'Draft sent to radiologist.' : 'Draft saved.');
}

function rp_typist_workflow_request_edits(mysqli $con, string $studyint, string $message, array $user): array
{
    rp_typist_workflow_ensure_schema($con);
    $order = rp_typist_workflow_order_for_study($con, $studyint);
    if (!$order) {
        return array('ok' => false, 'error' => 'Report order not found.');
    }
    $orderUid = (string)($order['order_uid'] ?? '');
    $msg = trim($message) !== '' ? trim($message) : 'Radiologist requested typist edits.';

    $up = mysqli_prepare($con, "UPDATE report_typist_drafts SET status = 'needs_typist_edits', reviewed_at = NOW(), updated_at = NOW() WHERE studyint = ? ORDER BY id DESC LIMIT 1");
    if ($up) {
        mysqli_stmt_bind_param($up, 's', $studyint);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);
    }
    $up = mysqli_prepare($con, "UPDATE remote_report_orders SET status = 'needs_typist_edits', updated_at = NOW() WHERE studyint = ? AND status NOT IN ('reported','returned') LIMIT 1");
    if ($up) {
        mysqli_stmt_bind_param($up, 's', $studyint);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);
    }
    rp_typist_workflow_add_message($con, $studyint, $orderUid, $msg, 'typist', 'edits_requested', $user);
    return array('ok' => true, 'message' => 'Returned to typist for edits.');
}

function rp_typist_workflow_approve_draft(mysqli $con, string $studyint, array $user): array
{
    rp_typist_workflow_ensure_schema($con);
    $state = rp_typist_workflow_get_case_state($con, $studyint);
    $draft = $state['latest_draft'];
    if (!$draft || trim((string)($draft['draft_text'] ?? '')) === '') {
        return array('ok' => false, 'error' => 'No typist draft is available to approve.');
    }
    $result = rp_remote_reporting_finalize_report($con, $studyint, (string)$draft['draft_text'], $user);
    if (empty($result['ok'])) {
        return $result;
    }
    $up = mysqli_prepare($con, "UPDATE report_typist_drafts SET status = 'approved', reviewed_at = NOW(), updated_at = NOW() WHERE id = ? LIMIT 1");
    if ($up) {
        $id = (int)$draft['id'];
        mysqli_stmt_bind_param($up, 'i', $id);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);
    }
    rp_typist_workflow_add_message($con, $studyint, (string)($draft['order_uid'] ?? ''), 'Radiologist approved the typist draft and finalized the report.', 'clinic', 'draft_approved', $user);
    return $result;
}
?>
