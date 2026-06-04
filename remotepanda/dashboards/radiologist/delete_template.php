<?php
session_start();
error_reporting(0);
include('../../includes/dbconnection.php');
include('../../functions.php');

if (!isLoggedIn()) {
    $_SESSION['msg'] = 'You must log in first';
    header('Location: ../../index.php');
    exit;
}

$currentUser = isset($_SESSION['user']['username']) ? trim((string) $_SESSION['user']['username']) : '';
$tempId = isset($_GET['tempID']) ? (int) $_GET['tempID'] : 0;
$templateDir = __DIR__ . DIRECTORY_SEPARATOR . 'Templates';

function rp_template_flash($type, $message) {
    $_SESSION['template_flash'] = array('type' => $type, 'message' => $message);
}

function rp_templates_column_exists($con, $column) {
    $column = mysqli_real_escape_string($con, $column);
    $result = mysqli_query($con, "SHOW COLUMNS FROM Templates LIKE '{$column}'");
    return $result && mysqli_num_rows($result) > 0;
}

function rp_ensure_template_owner_columns($con) {
    if (!rp_templates_column_exists($con, 'owner_type')) {
        @mysqli_query($con, "ALTER TABLE Templates ADD COLUMN owner_type VARCHAR(50) NULL");
    }
    if (!rp_templates_column_exists($con, 'owner_username')) {
        @mysqli_query($con, "ALTER TABLE Templates ADD COLUMN owner_username VARCHAR(191) NULL");
    }
    return rp_templates_column_exists($con, 'owner_type') && rp_templates_column_exists($con, 'owner_username');
}

function rp_migrate_current_template_owner($con, $ownerType, $ownerUsername) {
    if ($ownerUsername === '') {
        return;
    }

    $stmt = mysqli_prepare($con, "UPDATE Templates SET owner_type = ?, owner_username = ? WHERE Author = ? AND (owner_type IS NULL OR owner_type = '') AND (owner_username IS NULL OR owner_username = '')");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sss', $ownerType, $ownerUsername, $ownerUsername);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

$templateOwnerType = 'radiologist';
$templateOwnerUsername = $currentUser;
$hasTemplateOwnerColumns = rp_ensure_template_owner_columns($con);
if ($hasTemplateOwnerColumns) {
    rp_migrate_current_template_owner($con, $templateOwnerType, $templateOwnerUsername);
}

if ($currentUser === '' || $tempId <= 0) {
    rp_template_flash('danger', 'Invalid template delete request.');
    header('Location: view_templates.php');
    exit;
}

if ($hasTemplateOwnerColumns) {
    $stmt = mysqli_prepare($con, 'SELECT temp_file FROM Templates WHERE tempID = ? AND owner_type = ? AND owner_username = ? LIMIT 1');
} else {
    $stmt = mysqli_prepare($con, 'SELECT temp_file FROM Templates WHERE tempID = ? AND Author = ? LIMIT 1');
}
if (!$stmt) {
    rp_template_flash('danger', 'Could not verify template ownership.');
    header('Location: view_templates.php');
    exit;
}

if ($hasTemplateOwnerColumns) {
    mysqli_stmt_bind_param($stmt, 'iss', $tempId, $templateOwnerType, $templateOwnerUsername);
} else {
    mysqli_stmt_bind_param($stmt, 'is', $tempId, $currentUser);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$template = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$template) {
    rp_template_flash('danger', 'Template not found for your account.');
    header('Location: view_templates.php');
    exit;
}

$fileName = basename((string) $template['temp_file']);
if ($hasTemplateOwnerColumns) {
    $deleteStmt = mysqli_prepare($con, 'DELETE FROM Templates WHERE tempID = ? AND owner_type = ? AND owner_username = ? LIMIT 1');
} else {
    $deleteStmt = mysqli_prepare($con, 'DELETE FROM Templates WHERE tempID = ? AND Author = ? LIMIT 1');
}
if (!$deleteStmt) {
    rp_template_flash('danger', 'Could not prepare template delete.');
    header('Location: view_templates.php');
    exit;
}

if ($hasTemplateOwnerColumns) {
    mysqli_stmt_bind_param($deleteStmt, 'iss', $tempId, $templateOwnerType, $templateOwnerUsername);
} else {
    mysqli_stmt_bind_param($deleteStmt, 'is', $tempId, $currentUser);
}
$deleted = mysqli_stmt_execute($deleteStmt);
mysqli_stmt_close($deleteStmt);

if ($deleted) {
    $count = 0;
    $countStmt = mysqli_prepare($con, 'SELECT COUNT(*) AS total FROM Templates WHERE temp_file = ?');
    if ($countStmt) {
        mysqli_stmt_bind_param($countStmt, 's', $fileName);
        mysqli_stmt_execute($countStmt);
        $countResult = mysqli_stmt_get_result($countStmt);
        if ($countResult && ($countRow = mysqli_fetch_assoc($countResult))) {
            $count = (int) $countRow['total'];
        }
        mysqli_stmt_close($countStmt);
    }

    $filePath = $templateDir . DIRECTORY_SEPARATOR . $fileName;
    if ($count === 0 && is_file($filePath)) {
        @unlink($filePath);
    }

    rp_template_flash('success', 'Template deleted.');
} else {
    rp_template_flash('danger', 'Could not delete template.');
}

header('Location: view_templates.php');
exit;
?>
