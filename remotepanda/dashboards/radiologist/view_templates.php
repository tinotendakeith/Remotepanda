<?php
session_start();
error_reporting(0);
include('../../includes/dbconnection.php');
include('../../functions.php');
include('../../includes/report_template_helper.php');

if (!isLoggedIn()) {
    $_SESSION['msg'] = "You must log in first";
    header('location: index.php');
    exit;
}

$currentUser = isset($_SESSION['user']['username']) ? trim((string) $_SESSION['user']['username']) : '';
$templatePlaceholderCatalog = rp_template_placeholder_catalog();
$templateDir = __DIR__ . DIRECTORY_SEPARATOR . 'Templates';
if (!is_dir($templateDir)) {
    @mkdir($templateDir, 0777, true);
}

function rp_alert($type, $message) {
    $_SESSION['template_flash'] = array('type' => $type, 'message' => $message);
}

function rp_safe_template_base($name) {
    $base = preg_replace('/[^A-Za-z0-9 _-]/', '', (string) $name);
    $base = trim(preg_replace('/\s+/', ' ', $base));
    if ($base === '') {
        $base = 'Template';
    }
    return str_replace(' ', '_', $base);
}

function rp_unique_template_file($dir, $user, $name, $ext) {
    $userPart = rp_safe_template_base($user);
    $base = rp_safe_template_base($name);
    $filename = $userPart . '_' . $base . '.' . $ext;
    $target = $dir . DIRECTORY_SEPARATOR . $filename;
    $i = 1;
    while (file_exists($target)) {
        $filename = $userPart . '_' . $base . '_' . $i . '.' . $ext;
        $target = $dir . DIRECTORY_SEPARATOR . $filename;
        $i++;
    }
    return array($filename, $target);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_template'])) {
    $name = trim((string) ($_POST['name'] ?? ''));
    $file = $_FILES['pdf_file'] ?? null;

    if ($currentUser === '') {
        rp_alert('danger', 'Your session is missing a username. Please log in again.');
    } elseif ($name === '') {
        rp_alert('danger', 'Template name is required.');
    } elseif (!$file || empty($file['name']) || empty($file['tmp_name'])) {
        rp_alert('danger', 'Choose a template file first.');
    } else {
        $originalName = (string) $file['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, rp_template_supported_upload_extensions(), true)) {
            rp_alert('danger', 'Only .htm, .html, and .docx template files are supported here.');
        } else {
            $importError = '';
            $htmlContent = rp_template_import_upload_to_html($file['tmp_name'], $originalName, $importError);
            if ($htmlContent === '' && $importError !== '') {
                rp_alert('danger', $importError);
            } else {
                list($storedFile, $target) = rp_unique_template_file($templateDir, $currentUser, $name, 'html');
                if (@file_put_contents($target, $htmlContent, LOCK_EX) !== false) {
                    if ($hasTemplateOwnerColumns) {
                        $stmt = mysqli_prepare($con, 'INSERT INTO Templates (Name, Author, temp_file, owner_type, owner_username) VALUES (?, ?, ?, ?, ?)');
                    } else {
                        $stmt = mysqli_prepare($con, 'INSERT INTO Templates (Name, Author, temp_file) VALUES (?, ?, ?)');
                    }
                    if ($stmt) {
                        if ($hasTemplateOwnerColumns) {
                            mysqli_stmt_bind_param($stmt, 'sssss', $name, $currentUser, $storedFile, $templateOwnerType, $templateOwnerUsername);
                        } else {
                            mysqli_stmt_bind_param($stmt, 'sss', $name, $currentUser, $storedFile);
                        }
                        if (mysqli_stmt_execute($stmt)) {
                            rp_alert('success', 'Template uploaded successfully.');
                        } else {
                            @unlink($target);
                            rp_alert('danger', 'Template uploaded, but could not be saved to the database.');
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        @unlink($target);
                        rp_alert('danger', 'Could not prepare the template save request.');
                    }
                } else {
                    rp_alert('danger', 'Could not save the uploaded template file.');
                }
            }
        }
    }

    header('Location: view_templates.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template_changes'])) {
    $tempId = isset($_POST['edit_temp_id']) ? (int) $_POST['edit_temp_id'] : 0;
    $name = trim((string) ($_POST['edit_template_name'] ?? ''));
    $content = (string) ($_POST['edit_template_content'] ?? '');

    if ($currentUser === '') {
        rp_alert('danger', 'Your session is missing a username. Please log in again.');
    } elseif ($tempId <= 0) {
        rp_alert('danger', 'Invalid template selected.');
    } elseif ($name === '') {
        rp_alert('danger', 'Template name is required.');
    } else {
        if ($hasTemplateOwnerColumns) {
            $stmt = mysqli_prepare($con, 'SELECT temp_file FROM Templates WHERE tempID = ? AND owner_type = ? AND owner_username = ? LIMIT 1');
        } else {
            $stmt = mysqli_prepare($con, 'SELECT temp_file FROM Templates WHERE tempID = ? AND Author = ? LIMIT 1');
        }
        if ($stmt) {
            if ($hasTemplateOwnerColumns) {
                mysqli_stmt_bind_param($stmt, 'iss', $tempId, $templateOwnerType, $templateOwnerUsername);
            } else {
                mysqli_stmt_bind_param($stmt, 'is', $tempId, $currentUser);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);

            if ($row) {
                $file = basename((string) $row['temp_file']);
                $path = $templateDir . DIRECTORY_SEPARATOR . $file;
                if (@file_put_contents($path, $content, LOCK_EX) !== false) {
                    if ($hasTemplateOwnerColumns) {
                        $update = mysqli_prepare($con, 'UPDATE Templates SET Name = ? WHERE tempID = ? AND owner_type = ? AND owner_username = ? LIMIT 1');
                    } else {
                        $update = mysqli_prepare($con, 'UPDATE Templates SET Name = ? WHERE tempID = ? AND Author = ? LIMIT 1');
                    }
                    if ($update) {
                        if ($hasTemplateOwnerColumns) {
                            mysqli_stmt_bind_param($update, 'siss', $name, $tempId, $templateOwnerType, $templateOwnerUsername);
                        } else {
                            mysqli_stmt_bind_param($update, 'sis', $name, $tempId, $currentUser);
                        }
                        mysqli_stmt_execute($update);
                        mysqli_stmt_close($update);
                        rp_alert('success', 'Template updated successfully.');
                    } else {
                        rp_alert('danger', 'Template content saved, but the name could not be updated.');
                    }
                } else {
                    rp_alert('danger', 'Could not write the template file.');
                }
            } else {
                rp_alert('danger', 'Template not found for this account.');
            }
        } else {
            rp_alert('danger', 'Could not prepare the template update request.');
        }
    }

    header('Location: view_templates.php');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'template-content') {
    header('Content-Type: application/json; charset=UTF-8');
    $tempId = isset($_GET['temp_id']) ? (int) $_GET['temp_id'] : 0;

    if ($currentUser === '' || $tempId <= 0) {
        echo json_encode(array('ok' => false, 'message' => 'Invalid template request.'));
        exit;
    }

    if ($hasTemplateOwnerColumns) {
        $stmt = mysqli_prepare($con, 'SELECT tempID, Name, temp_file FROM Templates WHERE tempID = ? AND owner_type = ? AND owner_username = ? LIMIT 1');
    } else {
        $stmt = mysqli_prepare($con, 'SELECT tempID, Name, temp_file FROM Templates WHERE tempID = ? AND Author = ? LIMIT 1');
    }
    if (!$stmt) {
        echo json_encode(array('ok' => false, 'message' => 'Could not load template.'));
        exit;
    }

    if ($hasTemplateOwnerColumns) {
        mysqli_stmt_bind_param($stmt, 'iss', $tempId, $templateOwnerType, $templateOwnerUsername);
    } else {
        mysqli_stmt_bind_param($stmt, 'is', $tempId, $currentUser);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$row) {
        echo json_encode(array('ok' => false, 'message' => 'Template not found for this account.'));
        exit;
    }

    $file = basename((string) $row['temp_file']);
    $path = $templateDir . DIRECTORY_SEPARATOR . $file;
    if (!is_file($path)) {
        echo json_encode(array('ok' => false, 'message' => 'Template file was not found.'));
        exit;
    }

    echo json_encode(array(
        'ok' => true,
        'temp_id' => (int) $row['tempID'],
        'name' => (string) $row['Name'],
        'file' => $file,
        'content' => (string) file_get_contents($path)
    ));
    exit;
}

$searchTerm = trim((string) ($_GET['q'] ?? ''));
$templates = array();
$totalTemplates = 0;

if ($currentUser !== '') {
    if ($hasTemplateOwnerColumns) {
        $countStmt = mysqli_prepare($con, 'SELECT COUNT(*) AS total FROM Templates WHERE owner_type = ? AND owner_username = ?');
    } else {
        $countStmt = mysqli_prepare($con, 'SELECT COUNT(*) AS total FROM Templates WHERE Author = ?');
    }
    if ($countStmt) {
        if ($hasTemplateOwnerColumns) {
            mysqli_stmt_bind_param($countStmt, 'ss', $templateOwnerType, $templateOwnerUsername);
        } else {
            mysqli_stmt_bind_param($countStmt, 's', $currentUser);
        }
        mysqli_stmt_execute($countStmt);
        $countResult = mysqli_stmt_get_result($countStmt);
        if ($countResult && ($countRow = mysqli_fetch_assoc($countResult))) {
            $totalTemplates = (int) $countRow['total'];
        }
        mysqli_stmt_close($countStmt);
    }

    if ($searchTerm !== '') {
        $like = '%' . $searchTerm . '%';
        if ($hasTemplateOwnerColumns) {
            $stmt = mysqli_prepare($con, 'SELECT tempID, Name, Author, temp_file FROM Templates WHERE owner_type = ? AND owner_username = ? AND (Name LIKE ? OR temp_file LIKE ?) ORDER BY Name ASC');
        } else {
            $stmt = mysqli_prepare($con, 'SELECT tempID, Name, Author, temp_file FROM Templates WHERE Author = ? AND (Name LIKE ? OR temp_file LIKE ?) ORDER BY Name ASC');
        }
        if ($stmt) {
            if ($hasTemplateOwnerColumns) {
                mysqli_stmt_bind_param($stmt, 'ssss', $templateOwnerType, $templateOwnerUsername, $like, $like);
            } else {
                mysqli_stmt_bind_param($stmt, 'sss', $currentUser, $like, $like);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($result && ($row = mysqli_fetch_assoc($result))) {
                $templates[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        if ($hasTemplateOwnerColumns) {
            $stmt = mysqli_prepare($con, 'SELECT tempID, Name, Author, temp_file FROM Templates WHERE owner_type = ? AND owner_username = ? ORDER BY Name ASC');
        } else {
            $stmt = mysqli_prepare($con, 'SELECT tempID, Name, Author, temp_file FROM Templates WHERE Author = ? ORDER BY Name ASC');
        }
        if ($stmt) {
            if ($hasTemplateOwnerColumns) {
                mysqli_stmt_bind_param($stmt, 'ss', $templateOwnerType, $templateOwnerUsername);
            } else {
                mysqli_stmt_bind_param($stmt, 's', $currentUser);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($result && ($row = mysqli_fetch_assoc($result))) {
                $templates[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$flash = $_SESSION['template_flash'] ?? null;
unset($_SESSION['template_flash']);
?>
<!DOCTYPE HTML>
<html>
<head>
<title>RemotePanda | Radiologist Templates</title>
<link rel="stylesheet" type="text/css" href="../../extensions/font-awesome-4.7.0/css/font-awesome.min.css">
<link href="../../extensions/css/bootstrap.css" rel='stylesheet' type='text/css' />
<link href="../../extensions/css/style.css" rel='stylesheet' type='text/css' />
<link href="../../extensions/css/animate.css" rel="stylesheet" type="text/css" media="all">
<link href="../../extensions/css/custom.css" rel="stylesheet">
<link rel="icon" type="image/x-icon" href="../images/favicon.png">
<script src="../../extensions/js/jquery-1.11.1.min.js"></script>
<script src="../../extensions/js/modernizr.custom.js"></script>
<script src="../../extensions/js/wow.min.js"></script>
<script>new WOW().init();</script>
<script src="../../extensions/js/metisMenu.min.js"></script>
<script src="../../extensions/js/custom.js"></script>
<style>
:root {
    --rp-navy: #01152a;
    --rp-ink: #07182f;
    --rp-muted: #50627d;
    --rp-red: #ed1b24;
    --rp-blue: #2f7fbd;
    --rp-line: #d9e4f2;
    --rp-soft: #f3f7fc;
    --rp-card: #ffffff;
}
body { background: #eef4fb; color: var(--rp-ink); }
.template-shell { padding: 34px 28px 60px; }
.template-hero {
    display: grid;
    grid-template-columns: minmax(0, 1.25fr) minmax(280px, .75fr);
    gap: 18px;
    margin-bottom: 18px;
}
.template-card {
    background: var(--rp-card);
    border: 1px solid var(--rp-line);
    border-radius: 18px;
    box-shadow: 0 16px 42px rgba(1, 21, 42, .08);
    overflow: hidden;
}
.template-card-header {
    padding: 18px 22px;
    border-bottom: 1px solid var(--rp-line);
    background: linear-gradient(180deg, #fff, #f8fbff);
}
.template-card-header h4, .template-page-title h2 { margin: 0; font-weight: 800; letter-spacing: .02em; }
.template-page-title { margin-bottom: 16px; }
.template-page-title p, .template-card-header p, .template-small { color: var(--rp-muted); margin: 6px 0 0; }
.template-card-body { padding: 20px 22px; }
.template-form-grid { display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: end; }
.template-input, .template-select {
    width: 100%;
    height: 42px;
    border: 1px solid #cfdceb;
    border-radius: 11px;
    padding: 0 13px;
    background: #fff;
    color: var(--rp-ink);
}
.template-input:focus { border-color: var(--rp-blue); outline: none; box-shadow: 0 0 0 3px rgba(47,127,189,.12); }
.template-label { display: block; font-weight: 700; margin-bottom: 7px; }
.template-btn {
    border: 0;
    border-radius: 11px;
    min-height: 42px;
    padding: 0 18px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
}
.template-btn-primary { background: var(--rp-red); color: #fff; }
.template-btn-dark { background: var(--rp-navy); color: #fff; }
.template-btn-light { background: #fff; border: 1px solid #cfdceb; color: var(--rp-ink); }
.template-metric { display: grid; gap: 12px; }
.template-metric-row {
    border: 1px solid var(--rp-line);
    border-radius: 14px;
    background: #f8fbff;
    padding: 14px 16px;
}
.template-metric-row span { color: var(--rp-muted); display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .08em; }
.template-metric-row strong { font-size: 26px; line-height: 1.15; }
.template-toolbar { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin: 18px 0; flex-wrap: wrap; }
.template-search { display: flex; gap: 10px; min-width: 320px; flex: 1; }
.template-table-card { background: #fff; border: 1px solid var(--rp-line); border-radius: 18px; overflow: hidden; box-shadow: 0 16px 42px rgba(1,21,42,.06); }
.template-table { width: 100%; border-collapse: collapse; margin: 0; }
.template-table thead th { background: var(--rp-navy); color: #fff; padding: 14px 16px; font-size: 12px; letter-spacing: .06em; text-transform: uppercase; }
.template-table tbody td { padding: 16px; border-bottom: 1px solid #edf2f8; vertical-align: middle; }
.template-table tbody tr:hover { background: #f8fbff; }
.template-file-pill { display: inline-flex; align-items: center; gap: 7px; border-radius: 999px; padding: 6px 10px; background: #edf5ff; color: #174c84; font-weight: 700; font-size: 12px; }
.template-actions { display: flex; gap: 8px; align-items: center; }
.template-icon-btn { width: 38px; height: 34px; border-radius: 999px; border: 0; display: inline-flex; align-items: center; justify-content: center; }
.template-icon-view { background: var(--rp-red); color: #fff; }
.template-icon-edit { background: var(--rp-blue); color: #fff; }
.template-icon-delete { background: var(--rp-navy); color: #fff; }
.template-empty { padding: 44px 20px; text-align: center; color: var(--rp-muted); }
.template-alert { border-radius: 14px; padding: 13px 16px; margin-bottom: 18px; border: 1px solid; }
.template-alert-success { background: #eaf8ef; color: #176033; border-color: #ccebd6; }
.template-alert-danger { background: #fff1f1; color: #9d1d22; border-color: #ffd0d3; }
.template-tags-card { margin-bottom: 18px; }
.template-chip-wrap { display: flex; flex-wrap: wrap; gap: 8px; }
.template-chip {
    border: 1px solid #d7e4f2;
    background: #f8fbff;
    color: var(--rp-ink);
    border-radius: 999px;
    padding: 7px 11px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    cursor: pointer;
}
.template-chip span { font: 700 12px Consolas, monospace; color: #174c84; }
.template-chip strong { font-size: 12px; }
.template-edit-modal .modal-dialog {
    width: min(1480px, calc(100vw - 24px));
    max-width: min(1480px, calc(100vw - 24px));
    margin: 14px auto;
}
.template-edit-modal .modal-content {
    border: 0;
    border-radius: 16px;
    overflow: hidden;
    max-height: calc(100vh - 28px);
}
.template-edit-modal .modal-header {
    border-bottom: 1px solid var(--rp-line);
    background: linear-gradient(180deg, #fff, #f8fbff);
    padding: 16px 20px;
}
.template-edit-modal .modal-title { font-weight: 800; }
.template-edit-modal .modal-body {
    max-height: calc(100vh - 170px);
    overflow: auto;
    padding: 18px 20px;
}
.template-editor-meta {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 14px;
}
.template-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 20px;
    border-top: 1px solid var(--rp-line);
    background: #fff;
}
@media (max-width: 980px) {
    .template-hero, .template-form-grid, .template-editor-meta { grid-template-columns: 1fr; }
    .template-shell { padding: 22px 14px; }
    .template-search { min-width: 100%; }
}
</style>
</head>
<body class="cbp-spmenu-push">
<div class="main-content">
    <?php
    include_once('../../includes/radiologists-sidebar.php');
    include_once('../../includes/radiographer-heading.php');
    ?>
    <div id="page-wrapper">
        <div class="main-page template-shell">
            <div class="template-page-title">
                <h2>Manage Templates</h2>
                <p>Your reporting templates are private to this radiologist account. Upload or edit templates here, then load them inside your reporting workflow.</p>
            </div>

            <?php if ($flash && !empty($flash['message'])): ?>
                <div class="template-alert template-alert-<?php echo $flash['type'] === 'danger' ? 'danger' : 'success'; ?>">
                    <?php echo htmlentities($flash['message']); ?>
                </div>
            <?php endif; ?>

            <div class="template-hero">
                <div class="template-card">
                    <div class="template-card-header">
                        <h4>Add Template</h4>
                        <p>Upload a template for <?php echo htmlentities($currentUser); ?> only.</p>
                    </div>
                    <div class="template-card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="template-form-grid">
                                <div>
                                    <label class="template-label" for="name">Template name</label>
                                    <input class="template-input" placeholder="e.g. Chest X-ray Report" name="name" id="name" required>
                                </div>
                                <div>
                                    <label class="template-label" for="pdf_file">Template file</label>
                                    <input class="template-input" type="file" name="pdf_file" id="pdf_file" accept=".htm,.html,.docx" required>
                                </div>
                                <button class="template-btn template-btn-primary" type="submit" name="upload_template">
                                    <i class="fa fa-upload"></i> Upload
                                </button>
                            </div>
                            <p class="template-small">Accepted formats: .htm, .html, .docx. DOCX files are converted to editable HTML. Templates uploaded here will not appear for other radiologists.</p>
                        </form>
                    </div>
                </div>

                <div class="template-card">
                    <div class="template-card-header">
                        <h4>Template Library</h4>
                        <p>Current account scope</p>
                    </div>
                    <div class="template-card-body template-metric">
                        <div class="template-metric-row">
                            <span>Your Templates</span>
                            <strong><?php echo (int) $totalTemplates; ?></strong>
                        </div>
                        <div class="template-metric-row">
                            <span>Signed in as</span>
                            <strong style="font-size:18px;"><?php echo htmlentities($currentUser !== '' ? $currentUser : 'Unknown'); ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="template-card template-tags-card">
                <div class="template-card-header">
                    <h4>Supported Autofill Tags</h4>
                    <p>Use these placeholders in your templates. The reporting screen replaces them with live study and patient details when the template is loaded.</p>
                </div>
                <div class="template-card-body template-chip-wrap">
                    <?php foreach ($templatePlaceholderCatalog as $placeholder): ?>
                        <button type="button" class="template-chip js-copy-template-tag" data-tag="<?php echo htmlentities($placeholder['tag']); ?>">
                            <span><?php echo htmlentities($placeholder['tag']); ?></span>
                            <strong><?php echo htmlentities($placeholder['label']); ?></strong>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="template-toolbar">
                <form class="template-search" method="get" action="view_templates.php">
                    <input class="template-input" type="text" name="q" value="<?php echo htmlentities($searchTerm); ?>" placeholder="Search your templates by name or file">
                    <button class="template-btn template-btn-dark" type="submit"><i class="fa fa-search"></i> Search</button>
                    <?php if ($searchTerm !== ''): ?>
                        <a class="template-btn template-btn-light" href="view_templates.php">Reset</a>
                    <?php endif; ?>
                </form>
                <div class="template-small">
                    <?php echo count($templates); ?> shown<?php echo $searchTerm !== '' ? ' for "' . htmlentities($searchTerm) . '"' : ''; ?>.
                </div>
            </div>

            <div class="template-table-card">
                <table class="template-table">
                    <thead>
                        <tr>
                            <th style="width:70px;">#</th>
                            <th>Name</th>
                            <th>File</th>
                            <th style="width:190px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($templates)): ?>
                        <?php $cnt = 1; foreach ($templates as $row): ?>
                            <?php
                                $file = basename((string) $row['temp_file']);
                                $fileUrl = 'Templates/' . rawurlencode($file);
                            ?>
                            <tr>
                                <td><?php echo $cnt++; ?></td>
                                <td><strong><?php echo htmlentities($row['Name']); ?></strong></td>
                                <td><span class="template-file-pill"><i class="fa fa-file-code-o"></i><?php echo htmlentities($file); ?></span></td>
                                <td>
                                    <div class="template-actions">
                                        <a class="template-icon-btn template-icon-view" href="<?php echo htmlentities($fileUrl); ?>" target="_blank" title="Preview template"><i class="fa fa-eye"></i></a>
                                        <button class="template-icon-btn template-icon-edit js-edit-template" type="button" data-temp-id="<?php echo (int) $row['tempID']; ?>" title="Edit template"><i class="fa fa-pencil"></i></button>
                                        <button class="template-icon-btn template-icon-delete" type="button" onclick="deleteItem(<?php echo (int) $row['tempID']; ?>)" title="Delete template"><i class="fa fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">
                                <div class="template-empty">
                                    <strong>No templates found for your account.</strong><br>
                                    Upload your first template above<?php echo $searchTerm !== '' ? ' or clear the search filter' : ''; ?>.
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade template-edit-modal" id="editTemplateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="editTemplateForm" class="modal-content" method="post">
            <input type="hidden" name="save_template_changes" value="1">
            <input type="hidden" name="edit_temp_id" id="edit_temp_id">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Edit Template</h4>
                <p class="template-small">Edit only templates owned by <?php echo htmlentities($currentUser); ?>. Click an autofill tag to insert it into the report body.</p>
            </div>
            <div class="modal-body">
                <div class="template-editor-meta">
                    <div>
                        <label class="template-label" for="edit_template_name">Template name</label>
                        <input class="template-input" name="edit_template_name" id="edit_template_name" required>
                    </div>
                    <div>
                        <label class="template-label" for="edit_template_file">Template file</label>
                        <input class="template-input" id="edit_template_file" disabled>
                    </div>
                </div>

                <div class="template-card" style="box-shadow:none;margin-bottom:14px;">
                    <div class="template-card-header">
                        <h4>Supported Autofill Tags</h4>
                        <p>Click to insert a placeholder into the editor.</p>
                    </div>
                    <div class="template-card-body template-chip-wrap">
                        <?php foreach ($templatePlaceholderCatalog as $placeholder): ?>
                            <button type="button" class="template-chip js-insert-template-tag" data-editor="edit_template_editor" data-tag="<?php echo htmlentities($placeholder['tag']); ?>">
                                <span><?php echo htmlentities($placeholder['tag']); ?></span>
                                <strong><?php echo htmlentities($placeholder['label']); ?></strong>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <textarea id="edit_template_editor" name="edit_template_content"></textarea>
            </div>
            <div class="template-modal-footer">
                <button type="button" class="template-btn template-btn-light" data-dismiss="modal">Close</button>
                <button type="submit" class="template-btn template-btn-primary"><i class="fa fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script src="../../extensions/js/classie.js"></script>
<script src="../../extensions/js/tinymce/js/tinymce/tinymce.min.js"></script>
<script>
function deleteItem(tempID) {
    if (confirm('Delete this template from your account?')) {
        window.location.href = 'delete_template.php?tempID=' + encodeURIComponent(tempID);
    }
}

function initTemplateEditor() {
    if (!window.tinymce) {
        return;
    }
    tinymce.init({
        selector: '#edit_template_editor',
        branding: false,
        height: Math.max(380, window.innerHeight - 470),
        menubar: true,
        plugins: 'lists link table code',
        toolbar: 'undo redo | styleselect | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | code'
    });
}

function setTemplateEditorContent(content) {
    if (window.tinymce && tinymce.get('edit_template_editor')) {
        tinymce.get('edit_template_editor').setContent(content || '');
        return;
    }
    $('#edit_template_editor').val(content || '');
}

function insertTemplateTag(editorId, tag) {
    if (window.tinymce && tinymce.get(editorId)) {
        tinymce.get(editorId).focus();
        tinymce.get(editorId).execCommand('mceInsertContent', false, tag);
        return;
    }
    var target = document.getElementById(editorId);
    if (!target) {
        return;
    }
    var start = target.selectionStart || target.value.length;
    var end = target.selectionEnd || target.value.length;
    target.value = target.value.substring(0, start) + tag + target.value.substring(end);
}

initTemplateEditor();

$(document).on('click', '.js-edit-template', function () {
    var tempId = $(this).data('temp-id');
    $.ajax({
        url: 'view_templates.php',
        method: 'GET',
        dataType: 'json',
        data: { action: 'template-content', temp_id: tempId },
        success: function (resp) {
            if (!resp || !resp.ok) {
                alert((resp && resp.message) ? resp.message : 'Could not load template.');
                return;
            }
            $('#edit_temp_id').val(resp.temp_id);
            $('#edit_template_name').val(resp.name || '');
            $('#edit_template_file').val(resp.file || '');
            setTemplateEditorContent(resp.content || '');
            $('#editTemplateModal').modal('show');
        },
        error: function () {
            alert('Could not load template.');
        }
    });
});

$(document).on('click', '.js-insert-template-tag', function () {
    insertTemplateTag($(this).data('editor'), $(this).data('tag'));
});

$(document).on('click', '.js-copy-template-tag', function () {
    var tag = $(this).data('tag') || '';
    if (window.navigator && navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(tag);
    }
});

$('#editTemplateForm').on('submit', function () {
    if (window.tinymce && tinymce.get('edit_template_editor')) {
        tinymce.get('edit_template_editor').save();
    }
});

(function () {
    var menuLeft = document.getElementById('cbp-spmenu-s1');
    var showLeftPush = document.getElementById('showLeftPush');
    var body = document.body;
    if (showLeftPush && menuLeft) {
        showLeftPush.onclick = function() {
            classie.toggle(this, 'active');
            classie.toggle(body, 'cbp-spmenu-push-toright');
            classie.toggle(menuLeft, 'cbp-spmenu-open');
            classie.toggle(showLeftPush, 'disabled');
        };
    }
})();
</script>
<script src="../../extensions/js/jquery.nicescroll.js"></script>
<script src="../../extensions/js/scripts.js"></script>
<script src="../../extensions/js/bootstrap.js"></script>
</body>
</html>
