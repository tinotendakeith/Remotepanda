<?php

/**
 * Canonical reporting workflow states.
 *
 * The UI still contains legacy labels (for example "In Progress"). This service
 * keeps those aliases compatible while giving all workflow writers one guarded
 * transition path.
 */
function rp_report_workflow_canonical_state(string $status): string
{
    $value = strtolower(trim($status));
    $value = preg_replace('/[\s-]+/', '_', $value);

    $aliases = array(
        '' => 'awaiting_report',
        'received' => 'awaiting_report',
        'assigned' => 'awaiting_report',
        'awaiting_report' => 'awaiting_report',
        'sent_to_remotepanda' => 'awaiting_report',
        'in_progress' => 'direct_draft',
        'direct_draft' => 'direct_draft',
        'dictated' => 'pending_transcription',
        'pending_transcription' => 'pending_transcription',
        'with_typist' => 'transcription_in_progress',
        'transcription_in_progress' => 'transcription_in_progress',
        'needs_typist_edits' => 'transcription_in_progress',
        'typed_draft_ready' => 'pending_signoff',
        'pending_verification' => 'pending_signoff',
        'pending_signoff' => 'pending_signoff',
        'finalized' => 'finalized',
        'reported' => 'finalized',
        'return_queued' => 'return_queued',
        'returned' => 'returned',
        'return_failed' => 'return_failed',
    );

    return $aliases[$value] ?? $value;
}

function rp_report_workflow_allowed_transitions(): array
{
    return array(
        'awaiting_report' => array('direct_draft', 'pending_transcription', 'finalized'),
        'direct_draft' => array('pending_transcription', 'finalized'),
        'pending_transcription' => array('transcription_in_progress', 'direct_draft'),
        'transcription_in_progress' => array('pending_transcription', 'pending_signoff', 'direct_draft'),
        'pending_signoff' => array('transcription_in_progress', 'finalized'),
        'finalized' => array('return_queued'),
        'return_queued' => array('returned', 'return_failed'),
        'return_failed' => array('return_queued', 'returned'),
        'returned' => array(),
    );
}

function rp_report_workflow_can_transition(string $from, string $to): bool
{
    $fromCanonical = rp_report_workflow_canonical_state($from);
    $toCanonical = rp_report_workflow_canonical_state($to);
    if ($fromCanonical === $toCanonical) {
        return true;
    }

    $allowed = rp_report_workflow_allowed_transitions();
    return in_array($toCanonical, $allowed[$fromCanonical] ?? array(), true);
}

/**
 * Update the newest order for a study without allowing terminal cases to move
 * backwards. The stored value remains the existing database-compatible status.
 */
function rp_report_workflow_transition_order(mysqli $con, string $studyint, string $nextStatus): array
{
    $stmt = mysqli_prepare($con, "SELECT id, status FROM remote_report_orders WHERE studyint = ? ORDER BY id DESC LIMIT 1");
    if (!$stmt) {
        return array('ok' => false, 'error' => 'Could not read the report workflow state.');
    }

    mysqli_stmt_bind_param($stmt, 's', $studyint);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$row) {
        return array('ok' => false, 'error' => 'Report order not found.');
    }

    $currentStatus = (string)($row['status'] ?? '');
    if (!rp_report_workflow_can_transition($currentStatus, $nextStatus)) {
        return array(
            'ok' => false,
            'error' => 'Invalid report workflow transition.',
            'from' => rp_report_workflow_canonical_state($currentStatus),
            'to' => rp_report_workflow_canonical_state($nextStatus),
        );
    }

    $id = (int)$row['id'];
    $stmt = mysqli_prepare($con, "UPDATE remote_report_orders SET status = ?, updated_at = NOW() WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return array('ok' => false, 'error' => 'Could not prepare the workflow update.');
    }

    mysqli_stmt_bind_param($stmt, 'si', $nextStatus, $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return array(
        'ok' => (bool)$ok,
        'from' => rp_report_workflow_canonical_state($currentStatus),
        'to' => rp_report_workflow_canonical_state($nextStatus),
        'stored_status' => $nextStatus,
    );
}
?>