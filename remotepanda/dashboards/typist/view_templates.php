<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../../includes/dbconnection.php');
include('../../functions.php');
include('../../includes/typist_workflow_service.php');
include('../../includes/report_template_helper.php');

if (!isLoggedIn()) {
    $_SESSION['msg'] = "You must log in first";
    header('location: ../../index.php');
    exit;
}

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function rp_typist_template_alert($type, $message)
{
    $_SESSION['typist_template_flash'] = array('type' => $type, 'message' => $message);
}

function rp_typist_template_safe_base($name)
{
    $base = preg_replace('/[^A-Za-z0-9 _-]/', '', (string)$name);
    $base = trim(preg_replace('/\s+/', ' ', $base));
    return str_replace(' ', '_', $base !== '' ? $base : 'Template');
}

function rp_typist_template_unique_file($dir, $user, $name)
{
    $userPart = rp_typist_template_safe_base($user);
    $base = rp_typist_template_safe_base($name);
    $filename = $userPart . '_' . $base . '.html';
    $target = $dir . DIRECTORY_SEPARATOR . $filename;
    $i = 1;
    while (file_exists($target)) {
        $filename = $userPart . '_' . $base . '_' . $i . '.html';
        $target = $dir . DIRECTORY_SEPARATOR . $filename;
        $i++;
    }
    return array($filename, $target);
}

function rp_typist_template_column_exists($con, $column)
{
    $column = mysqli_real_escape_string($con, $column);
    $result = mysqli_query($con, "SHOW COLUMNS FROM Templates LIKE '{$column}'");
    return $result && mysqli_num_rows($result) > 0;
}

function rp_typist_template_ensure_owner_columns($con)
{
    if (!rp_typist_template_column_exists($con, 'owner_type')) {
        @mysqli_query($con, "ALTER TABLE Templates ADD COLUMN owner_type VARCHAR(50) NULL");
    }
    if (!rp_typist_template_column_exists($con, 'owner_username')) {
        @mysqli_query($con, "ALTER TABLE Templates ADD COLUMN owner_username VARCHAR(191) NULL");
    }
    return rp_typist_template_column_exists($con, 'owner_type') && rp_typist_template_column_exists($con, 'owner_username');
}

$user = rp_typist_workflow_user();
if (!rp_typist_workflow_is_typist($user) && !rp_remote_reporting_user_is_admin($user)) {
    header('location: ../radiologist/index.php');
    exit;
}

$templateDir = __DIR__ . DIRECTORY_SEPARATOR . 'Templates';
if (!is_dir($templateDir)) {
    @mkdir($templateDir, 0777, true);
}

$hasOwnerColumns = rp_typist_template_ensure_owner_columns($con);
$placeholderCatalog = rp_template_placeholder_catalog();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_template'])) {
    $name = trim((string)($_POST['name'] ?? ''));
    $file = $_FILES['template_file'] ?? null;
    if ($name === '') {
        rp_typist_template_alert('danger', 'Template name is required.');
    } elseif (!$file || empty($file['name']) || empty($file['tmp_name'])) {
        rp_typist_template_alert('danger', 'Choose a template file first.');
    } else {
        $originalName = (string)$file['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, rp_template_supported_upload_extensions(), true)) {
            rp_typist_template_alert('danger', 'Only .htm, .html, and .docx template files are supported.');
        } else {
            $importError = '';
            $htmlContent = rp_template_import_upload_to_html($file['tmp_name'], $originalName, $importError);
            if ($htmlContent === '' && $importError !== '') {
                rp_typist_template_alert('danger', $importError);
            } else {
                list($storedFile, $target) = rp_typist_template_unique_file($templateDir, $user, $name);
                if (@file_put_contents($target, $htmlContent, LOCK_EX) === false) {
                    rp_typist_template_alert('danger', 'Could not save the uploaded template file.');
                } else {
                    if ($hasOwnerColumns) {
                        $stmt = mysqli_prepare($con, "INSERT INTO Templates (Name, Author, temp_file, owner_type, owner_username) VALUES (?, ?, ?, 'typist', ?)");
                    } else {
                        $stmt = mysqli_prepare($con, "INSERT INTO Templates (Name, Author, temp_file) VALUES (?, ?, ?)");
                    }
                    if ($stmt) {
                        if ($hasOwnerColumns) {
                            mysqli_stmt_bind_param($stmt, 'ssss', $name, $user, $storedFile, $user);
                        } else {
                            mysqli_stmt_bind_param($stmt, 'sss', $name, $user, $storedFile);
                        }
                        if (mysqli_stmt_execute($stmt)) {
                            rp_typist_template_alert('success', 'Template uploaded successfully.');
                        } else {
                            @unlink($target);
                            rp_typist_template_alert('danger', 'Template uploaded, but could not be saved to the database.');
                        }
                        mysqli_stmt_close($stmt);
                    } else {
                        @unlink($target);
                        rp_typist_template_alert('danger', 'Could not prepare the template save request.');
                    }
                }
            }
        }
    }
    header('Location: view_templates.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template_changes'])) {
    $tempId = (int)($_POST['edit_temp_id'] ?? 0);
    $name = trim((string)($_POST['edit_template_name'] ?? ''));
    $content = (string)($_POST['edit_template_content'] ?? '');
    if ($tempId <= 0 || $name === '') {
        rp_typist_template_alert('danger', 'Template name is required.');
    } else {
        if ($hasOwnerColumns) {
            $stmt = mysqli_prepare($con, "SELECT temp_file FROM Templates WHERE tempID = ? AND owner_type = 'typist' AND owner_username = ? LIMIT 1");
        } else {
            $stmt = mysqli_prepare($con, "SELECT temp_file FROM Templates WHERE tempID = ? AND Author = ? LIMIT 1");
        }
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'is', $tempId, $user);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);
            if ($row) {
                $path = $templateDir . DIRECTORY_SEPARATOR . basename((string)$row['temp_file']);
                if (@file_put_contents($path, $content, LOCK_EX) !== false) {
                    if ($hasOwnerColumns) {
                        $update = mysqli_prepare($con, "UPDATE Templates SET Name = ? WHERE tempID = ? AND owner_type = 'typist' AND owner_username = ? LIMIT 1");
                    } else {
                        $update = mysqli_prepare($con, "UPDATE Templates SET Name = ? WHERE tempID = ? AND Author = ? LIMIT 1");
                    }
                    if ($update) {
                        mysqli_stmt_bind_param($update, 'sis', $name, $tempId, $user);
                        mysqli_stmt_execute($update);
                        mysqli_stmt_close($update);
                    }
                    rp_typist_template_alert('success', 'Template updated successfully.');
                } else {
                    rp_typist_template_alert('danger', 'Could not write the template file.');
                }
            } else {
                rp_typist_template_alert('danger', 'Template not found for this typist account.');
            }
        }
    }
    header('Location: view_templates.php');
    exit;
}

if (isset($_GET['delete'])) {
    $tempId = (int)$_GET['delete'];
    if ($tempId > 0) {
        if ($hasOwnerColumns) {
            $stmt = mysqli_prepare($con, "SELECT temp_file FROM Templates WHERE tempID = ? AND owner_type = 'typist' AND owner_username = ? LIMIT 1");
        } else {
            $stmt = mysqli_prepare($con, "SELECT temp_file FROM Templates WHERE tempID = ? AND Author = ? LIMIT 1");
        }
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'is', $tempId, $user);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);
            if ($row) {
                @unlink($templateDir . DIRECTORY_SEPARATOR . basename((string)$row['temp_file']));
                if ($hasOwnerColumns) {
                    $del = mysqli_prepare($con, "DELETE FROM Templates WHERE tempID = ? AND owner_type = 'typist' AND owner_username = ? LIMIT 1");
                } else {
                    $del = mysqli_prepare($con, "DELETE FROM Templates WHERE tempID = ? AND Author = ? LIMIT 1");
                }
                if ($del) {
                    mysqli_stmt_bind_param($del, 'is', $tempId, $user);
                    mysqli_stmt_execute($del);
                    mysqli_stmt_close($del);
                    rp_typist_template_alert('success', 'Template deleted.');
                }
            }
        }
    }
    header('Location: view_templates.php');
    exit;
}

$templates = array();
if ($hasOwnerColumns) {
    $stmt = mysqli_prepare($con, "SELECT tempID, Name, Author, temp_file FROM Templates WHERE owner_type = 'typist' AND owner_username = ? ORDER BY Name ASC");
} else {
    $stmt = mysqli_prepare($con, "SELECT tempID, Name, Author, temp_file FROM Templates WHERE Author = ? ORDER BY Name ASC");
}
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($result && ($row = mysqli_fetch_assoc($result))) {
        $file = basename((string)$row['temp_file']);
        $path = $templateDir . DIRECTORY_SEPARATOR . $file;
        $row['content'] = is_file($path) ? (string)file_get_contents($path) : '';
        $templates[] = $row;
    }
    mysqli_stmt_close($stmt);
}

$flash = $_SESSION['typist_template_flash'] ?? null;
unset($_SESSION['typist_template_flash']);
?>
<!DOCTYPE HTML>
<html>
<head>
<title>RemotePanda | Typist Templates</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link href="../../extensions/css/bootstrap.css" rel="stylesheet" type="text/css" />
<link href="../../extensions/css/style.css" rel="stylesheet" type="text/css" />
<link href="../../extensions/css/custom.css" rel="stylesheet" type="text/css" />
<script src="../../extensions/js/jquery-1.11.1.min.js"></script>
<script src="../../extensions/js/tinymce/js/tinymce/tinymce.min.js"></script>
<style>
@import url('https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700;800&display=swap');
body{background:#eef4fb !important;font-family:'Barlow',sans-serif;color:#07182f}.rp-page{padding:28px}.rp-title{font-size:30px;font-weight:800;margin:0}.rp-sub{color:#50627d;margin:6px 0 0}.rp-grid{display:grid;grid-template-columns:380px minmax(0,1fr);gap:18px;margin-top:18px}.rp-card{background:#fff;border:1px solid #d9e7ff;border-radius:18px;box-shadow:0 14px 34px rgba(7,33,66,.07);overflow:hidden}.rp-card h3{margin:0;padding:16px 18px;border-bottom:1px solid #d9e7ff;background:#f8fbff;font-weight:800}.rp-card-body{padding:18px}.rp-field{margin-bottom:12px}.rp-field label{display:block;font-weight:800;margin-bottom:6px}.rp-input{width:100%;height:42px;border:1px solid #cbd5e1;border-radius:11px;padding:0 12px;background:#fff}.rp-btn{border:0;border-radius:999px;padding:10px 16px;font-weight:800;display:inline-flex;align-items:center;gap:7px;text-decoration:none;cursor:pointer}.rp-btn-primary{background:#ed1b24;color:#fff}.rp-btn-dark{background:#01152a;color:#fff}.rp-btn-ghost{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe}.rp-alert{border-radius:14px;padding:12px 14px;margin:14px 0;border:1px solid}.rp-alert-success{background:#eaf8ef;color:#176033;border-color:#ccebd6}.rp-alert-danger{background:#fff1f1;color:#9d1d22;border-color:#ffd0d3}.rp-template-list{display:grid;gap:10px}.rp-template-row{border:1px solid #d9e7ff;border-radius:14px;padding:14px;background:#fff;display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center}.rp-template-row strong{display:block}.rp-template-row small{color:#64748b}.rp-actions{display:flex;gap:8px;flex-wrap:wrap}.rp-tags{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}.rp-tag{border:1px solid #cfe0f2;background:#fff;border-radius:999px;padding:7px 10px;font-size:12px;font-weight:800;color:#174c84}.rp-editor-wrap{display:none;margin-top:18px}.rp-editor-wrap.active{display:block}.rp-editor-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:12px}@media(max-width:1100px){.rp-grid{grid-template-columns:1fr}.rp-page{padding:18px}}
</style>
</head>
<body class="cbp-spmenu-push">
<div class="main-content">
<?php
include_once('../../includes/radiographer-heading.php');
include_once('../../includes/radiographer-sidebar.php');
?>
<div id="page-wrapper">
<div class="rp-page">
  <a class="rp-btn rp-btn-ghost" href="index.php"><i class="fa fa-arrow-left"></i> Back to Typist Queue</a>
  <h1 class="rp-title">Typist Templates</h1>
  <p class="rp-sub">Upload and maintain report templates for your typing workflow. Tags are replaced when a case loads.</p>
  <?php if ($flash && !empty($flash['message'])): ?>
    <div class="rp-alert rp-alert-<?php echo $flash['type'] === 'danger' ? 'danger' : 'success'; ?>"><?php echo h($flash['message']); ?></div>
  <?php endif; ?>
  <div class="rp-grid">
    <section class="rp-card">
      <h3>Add Template</h3>
      <div class="rp-card-body">
        <form method="post" enctype="multipart/form-data">
          <div class="rp-field">
            <label>Template Name</label>
            <input class="rp-input" name="name" type="text" placeholder="e.g. Chest X-ray Standard">
          </div>
          <div class="rp-field">
            <label>Template File</label>
            <input class="rp-input" name="template_file" type="file" accept=".htm,.html,.docx">
          </div>
          <button class="rp-btn rp-btn-primary" type="submit" name="upload_template"><i class="fa fa-upload"></i> Upload Template</button>
        </form>
        <h4 style="margin-top:22px;font-weight:800;">Available Tags</h4>
        <div class="rp-tags">
          <?php foreach ($placeholderCatalog as $placeholder): ?>
            <span class="rp-tag"><?php echo h($placeholder['tag']); ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <section class="rp-card">
      <h3>My Templates</h3>
      <div class="rp-card-body">
        <?php if (empty($templates)): ?>
          <div style="border:1px dashed #cbd5e1;border-radius:14px;padding:24px;color:#64748b;">No templates uploaded yet.</div>
        <?php else: ?>
          <div class="rp-template-list">
            <?php foreach ($templates as $template): ?>
              <div class="rp-template-row">
                <div>
                  <strong><?php echo h($template['Name']); ?></strong>
                  <small><?php echo h($template['temp_file']); ?></small>
                </div>
                <div class="rp-actions">
                  <button class="rp-btn rp-btn-ghost js-edit-template" type="button" data-id="<?php echo (int)$template['tempID']; ?>"><i class="fa fa-pencil"></i> Edit</button>
                  <a class="rp-btn rp-btn-dark" href="view_templates.php?delete=<?php echo (int)$template['tempID']; ?>" onclick="return confirm('Delete this template?');"><i class="fa fa-trash"></i> Delete</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div id="templateEditorWrap" class="rp-editor-wrap">
          <form method="post" id="templateEditForm">
            <input type="hidden" name="edit_temp_id" id="editTempId">
            <div class="rp-field">
              <label>Template Name</label>
              <input class="rp-input" name="edit_template_name" id="editTemplateName" type="text">
            </div>
            <textarea id="editTemplateContent" name="edit_template_content"></textarea>
            <div class="rp-editor-actions">
              <button class="rp-btn rp-btn-ghost" type="button" id="cancelTemplateEdit">Cancel</button>
              <button class="rp-btn rp-btn-primary" type="submit" name="save_template_changes"><i class="fa fa-save"></i> Save Template</button>
            </div>
          </form>
        </div>
      </div>
    </section>
  </div>
</div>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const templates = <?php echo json_encode($templates); ?>;
  const wrap = document.getElementById('templateEditorWrap');
  const idInput = document.getElementById('editTempId');
  const nameInput = document.getElementById('editTemplateName');
  const cancelBtn = document.getElementById('cancelTemplateEdit');
  if (window.tinymce) {
    tinymce.init({
      selector: '#editTemplateContent',
      branding: false,
      height: 520,
      menubar: 'file edit view insert format tools table',
      plugins: 'lists link table code',
      toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | code'
    });
  }
  function setEditor(content) {
    const editor = window.tinymce ? tinymce.get('editTemplateContent') : null;
    if (editor) editor.setContent(content || '');
    else document.getElementById('editTemplateContent').value = content || '';
  }
  document.querySelectorAll('.js-edit-template').forEach(function (button) {
    button.addEventListener('click', function () {
      const id = parseInt(button.getAttribute('data-id') || '0', 10);
      const record = templates.find(function (item) { return parseInt(item.tempID || item.tempId || 0, 10) === id; });
      if (!record) return;
      idInput.value = id;
      nameInput.value = record.Name || '';
      setEditor(record.content || '');
      wrap.classList.add('active');
      wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });
  if (cancelBtn) {
    cancelBtn.addEventListener('click', function () {
      wrap.classList.remove('active');
      idInput.value = '';
      nameInput.value = '';
      setEditor('');
    });
  }
});
</script>
<script src="../../extensions/js/bootstrap.js"></script>
</body>
</html>
