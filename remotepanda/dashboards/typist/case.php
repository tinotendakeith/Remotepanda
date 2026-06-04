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

function rp_typist_template_column_exists($con, $column)
{
    $column = mysqli_real_escape_string($con, $column);
    $result = mysqli_query($con, "SHOW COLUMNS FROM Templates LIKE '{$column}'");
    return $result && mysqli_num_rows($result) > 0;
}

function rp_typist_templates_have_owner_columns($con)
{
    return rp_typist_template_column_exists($con, 'owner_type') && rp_typist_template_column_exists($con, 'owner_username');
}

function rp_typist_template_file_record($row, $type, $dir, $context, $catalog)
{
    $file = basename((string)($row['temp_file'] ?? ''));
    $path = $dir . DIRECTORY_SEPARATOR . $file;
    if ($file === '' || !is_file($path)) {
        return null;
    }

    $content = (string)file_get_contents($path);
    $content = rp_render_template_placeholders($content, $context);

    return array(
        'id' => (int)($row['tempID'] ?? 0),
        'name' => (string)($row['Name'] ?? $file),
        'file' => $file,
        'owner_type' => $type,
        'author' => (string)($row['Author'] ?? ''),
        'content' => $content,
    );
}

$user = rp_typist_workflow_user();
if (!rp_typist_workflow_is_typist($user) && !rp_remote_reporting_user_is_admin($user)) {
    header('location: ../radiologist/index.php');
    exit;
}

$studyint = trim((string)($_GET['studyint'] ?? ''));
if ($studyint === '' || !rp_typist_workflow_can_typist_access($con, $studyint, $user)) {
    http_response_code(403);
    echo 'This case is not available to the typist queue.';
    exit;
}

$state = rp_typist_workflow_get_case_state($con, $studyint);
$order = $state['order'] ?: array();
$draft = $state['latest_draft'] ?: array();
$draftText = (string)($draft['draft_text'] ?? '');

$patientName = (string)(($order['Name'] ?? '') ?: ($order['patient_name'] ?? 'Patient'));
$procedure = (string)(($order['procedure_name'] ?? '') ?: ($order['requested_procedure'] ?? ''));
$accession = (string)(($order['accession_number'] ?? '') ?: ($order['study_accession'] ?? ''));
$radiologistUsername = trim((string)($order['radiologist_username'] ?? ''));
$templatePlaceholderCatalog = rp_template_placeholder_catalog();
$templateContext = array(
    'patient_name' => $patientName,
    'exam_name' => $procedure,
    'exam_date' => (string)(($order['study_date'] ?? '') ?: ($order['scheduled_at'] ?? '') ?: ($order['start_date'] ?? '')),
    'date_of_birth' => (string)($order['date_of_birth'] ?? ''),
    'referrer' => (string)($order['requesting_physician'] ?? ''),
    'gender' => (string)($order['gender'] ?? ''),
    'study_id' => $studyint,
    'radiographer_name' => (string)(($order['radiographer_name'] ?? '') ?: ($order['technician_name'] ?? '')),
    'radiologist_name' => $radiologistUsername,
    'today_date' => date('Y-m-d'),
);

$templateRecords = array();
$hasOwnerColumns = rp_typist_templates_have_owner_columns($con);
$typistTemplateDir = __DIR__ . DIRECTORY_SEPARATOR . 'Templates';
$radiologistTemplateDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'radiologist' . DIRECTORY_SEPARATOR . 'Templates';

if ($user !== '' && $hasOwnerColumns) {
    $stmt = mysqli_prepare($con, "SELECT tempID, Name, Author, temp_file FROM Templates WHERE owner_type = 'typist' AND owner_username = ? ORDER BY Name ASC LIMIT 80");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $user);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($result && ($row = mysqli_fetch_assoc($result))) {
            $record = rp_typist_template_file_record($row, 'typist', $typistTemplateDir, $templateContext, $templatePlaceholderCatalog);
            if ($record) {
                $templateRecords[] = $record;
            }
        }
        mysqli_stmt_close($stmt);
    }
}

if ($radiologistUsername !== '' && $hasOwnerColumns) {
    $stmt = mysqli_prepare($con, "SELECT tempID, Name, Author, temp_file FROM Templates WHERE owner_type = 'radiologist' AND owner_username = ? ORDER BY Name ASC LIMIT 80");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $radiologistUsername);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($result && ($row = mysqli_fetch_assoc($result))) {
            $record = rp_typist_template_file_record($row, 'radiologist', $radiologistTemplateDir, $templateContext, $templatePlaceholderCatalog);
            if ($record) {
                $templateRecords[] = $record;
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE HTML>
<html>
<head>
<title>RADPANDA | Typist Case</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link href="../../extensions/css/bootstrap.css" rel="stylesheet" type="text/css" />
<link href="../../extensions/css/style.css" rel="stylesheet" type="text/css" />
<link href="../../extensions/css/custom.css" rel="stylesheet" type="text/css" />
<script src="../../extensions/js/jquery-1.11.1.min.js"></script>
<script src="../../extensions/js/tinymce/js/tinymce/tinymce.min.js"></script>
<style>
@import url('https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700;800&display=swap');
body{background:#f2f7ff !important;font-family:'Barlow',sans-serif}.rp-page{padding:18px}.rp-title{margin:0;color:#0b1f3a;font-size:28px;font-weight:800}.rp-sub{margin:0;color:#4b5d77;font-size:14px}
.rp-card{background:#fff;border:1px solid #d9e7ff;border-radius:18px;box-shadow:0 10px 20px rgba(7,33,66,.06);margin-top:16px;padding:18px}.rp-grid{display:grid;grid-template-columns:.8fr 1.35fr;gap:16px}.rp-facts{display:grid;grid-template-columns:150px 1fr;gap:7px 12px}.rp-facts .k{font-weight:700;color:#0f172a}.rp-facts .v{color:#0f172a}
.rp-list{display:grid;gap:10px;margin:0;padding:0;list-style:none}.rp-item{border:1px solid #e2e8f0;border-radius:12px;background:#f8fbff;padding:10px}.rp-item small{display:block;color:#64748b;margin-top:3px}
.rp-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}.rp-btn{border:none;border-radius:999px;padding:9px 16px;font-weight:800;display:inline-flex;gap:6px;align-items:center;text-decoration:none;cursor:pointer}.rp-btn-primary{background:#ed1b24;color:#fff}.rp-btn-secondary{background:#0a2a57;color:#fff}.rp-btn-ghost{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe}.rp-status{font-size:13px;font-weight:700;color:#475569}.rp-message{border-left:4px solid #bfdbfe;padding:8px 10px;background:#fff;border-radius:8px;margin-bottom:8px}
.rp-editor-shell{display:grid;grid-template-columns:250px minmax(0,1fr);gap:14px}.rp-template-rail{border:1px solid #d8e6f7;border-radius:14px;background:#f8fbff;padding:12px;min-height:540px}.rp-template-rail h4{margin:0 0 10px;font-weight:800;color:#07182f}.rp-template-search{width:100%;height:36px;border:1px solid #cbd5e1;border-radius:10px;padding:0 10px;margin-bottom:10px}
.rp-template-list{display:grid;gap:8px;max-height:270px;overflow:auto;padding-right:3px}.rp-template-item{border:1px solid #cfe0f2;background:#fff;border-radius:12px;padding:9px 10px;text-align:left;font-weight:800;color:#07182f;cursor:pointer}.rp-template-item small{display:block;font-weight:600;color:#64748b;margin-top:2px}.rp-template-item:hover,.rp-template-item.active{border-color:#2f7fbd;background:#edf6ff}.rp-template-empty{border:1px dashed #cbd5e1;border-radius:12px;padding:14px;color:#64748b;background:#fff}
.rp-template-tools{border-top:1px solid #d8e6f7;margin-top:12px;padding-top:12px}.rp-template-tags{display:flex;flex-wrap:wrap;gap:7px;margin-top:8px}.rp-template-chip{border:1px solid #cfe0f2;background:#fff;border-radius:999px;padding:6px 9px;font-size:12px;font-weight:800;color:#174c84;cursor:pointer}
.rp-editor-area textarea{width:100%;min-height:520px}.rp-editor-hint{display:flex;justify-content:space-between;gap:10px;align-items:center;color:#50627d;font-size:13px;margin-bottom:8px}.rp-editor-hint strong{color:#0f172a}.rp-muted-link{display:inline-flex;margin-top:10px;color:#1d4ed8;font-weight:800;text-decoration:none}.rp-mode-pill{display:inline-flex;border-radius:999px;padding:5px 10px;background:#e0f2fe;color:#075985;font-weight:800;font-size:12px}
@media(max-width:1200px){.rp-grid,.rp-editor-shell{grid-template-columns:1fr}.rp-template-rail{min-height:auto}.rp-template-list{max-height:220px}}
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
  <h1 class="rp-title"><?php echo h($patientName); ?></h1>
  <p class="rp-sub"><?php echo h($procedure); ?> &middot; Accession <?php echo h($accession); ?></p>

  <div class="rp-grid">
    <section class="rp-card">
      <h3>Case Context</h3>
      <div class="rp-facts">
        <div class="k">Study UID</div><div class="v"><?php echo h($studyint); ?></div>
        <div class="k">Radiologist</div><div class="v"><?php echo h($radiologistUsername); ?></div>
        <div class="k">Referrer</div><div class="v"><?php echo h($order['requesting_physician'] ?? ''); ?></div>
        <div class="k">Status</div><div class="v"><?php echo h(strtoupper(str_replace('_', ' ', (string)($order['status'] ?? '')))); ?></div>
      </div>
      <h3 style="margin-top:18px;">Dictations</h3>
      <?php if (!empty($state['dictations'])): ?>
        <ul class="rp-list">
          <?php foreach ($state['dictations'] as $dictation): ?>
            <li class="rp-item">
              <strong><?php echo h($dictation['radiologist_username'] ?? 'Radiologist'); ?></strong>
              <small><?php echo h($dictation['created_at'] ?? ''); ?> &middot; <?php echo number_format(((int)($dictation['file_size'] ?? 0)) / 1024, 1); ?> KB</small>
              <?php if (trim((string)($dictation['note_text'] ?? '')) !== ''): ?><small><?php echo h($dictation['note_text']); ?></small><?php endif; ?>
              <audio controls preload="none" src="/remotepanda/api/download-dictation.php?id=<?php echo (int)$dictation['id']; ?>" style="width:100%;margin-top:8px;"></audio>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div class="rp-item">No dictations are saved for this case yet.</div>
      <?php endif; ?>
      <h3 style="margin-top:18px;">Review Messages</h3>
      <?php if (!empty($state['messages'])): ?>
        <?php foreach ($state['messages'] as $message): ?>
          <div class="rp-message">
            <strong><?php echo h($message['from_username'] ?? 'System'); ?></strong>
            <small><?php echo h($message['created_at'] ?? ''); ?> &middot; <?php echo h($message['message_type'] ?? 'comment'); ?></small>
            <div><?php echo h($message['message_text'] ?? ''); ?></div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="rp-item">No review messages yet.</div>
      <?php endif; ?>
    </section>

    <section class="rp-card">
      <div class="rp-editor-hint">
        <div>
          <h3 style="margin:0;">Typed Report Draft</h3>
          <span>Use templates, letterheads, and quick patient fields while typing.</span>
        </div>
        <span class="rp-mode-pill">Word processor</span>
      </div>
      <div class="rp-editor-shell">
        <aside class="rp-template-rail">
          <h4>Templates</h4>
          <input id="typistTemplateSearch" class="rp-template-search" type="search" placeholder="Search templates">
          <div id="typistTemplateList" class="rp-template-list">
            <?php if (!empty($templateRecords)): ?>
              <?php foreach ($templateRecords as $index => $template): ?>
                <button type="button" class="rp-template-item js-template-load" data-template-index="<?php echo (int)$index; ?>" data-template-name="<?php echo h(strtolower($template['name'] . ' ' . $template['owner_type'])); ?>">
                  <?php echo h($template['name']); ?>
                  <small><?php echo $template['owner_type'] === 'radiologist' ? 'Radiologist template' : 'My template'; ?></small>
                </button>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="rp-template-empty">No typist or radiologist templates are available yet.</div>
            <?php endif; ?>
          </div>
          <a class="rp-muted-link" href="view_templates.php"><i class="fa fa-file-text-o"></i>&nbsp; Manage my templates</a>
          <div class="rp-template-tools">
            <h4>Quick Fields</h4>
            <div class="rp-template-tags">
              <?php foreach ($templatePlaceholderCatalog as $placeholder): ?>
                <button type="button" class="rp-template-chip js-insert-field" data-field-key="<?php echo h($placeholder['key']); ?>"><?php echo h($placeholder['label']); ?></button>
              <?php endforeach; ?>
            </div>
          </div>
        </aside>
        <div class="rp-editor-area">
          <textarea id="typistDraftTextarea" placeholder="Type the report from the radiologist dictation..."><?php echo h($draftText); ?></textarea>
        </div>
      </div>
      <div class="rp-actions">
        <button class="rp-btn rp-btn-ghost" type="button" id="saveTypistDraftBtn"><i class="fa fa-save"></i> Save Draft</button>
        <button class="rp-btn rp-btn-primary" type="button" id="submitTypistDraftBtn"><i class="fa fa-paper-plane"></i> Send to Radiologist for Approval</button>
        <span id="typistCaseStatus" class="rp-status"></span>
      </div>
    </section>
  </div>
</div>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const textarea = document.getElementById('typistDraftTextarea');
  const saveBtn = document.getElementById('saveTypistDraftBtn');
  const submitBtn = document.getElementById('submitTypistDraftBtn');
  const status = document.getElementById('typistCaseStatus');
  const studyint = <?php echo json_encode($studyint); ?>;
  const templates = <?php echo json_encode($templateRecords); ?>;
  const templateContext = <?php echo json_encode($templateContext); ?>;

  if (window.tinymce && textarea) {
    tinymce.init({
      selector: '#typistDraftTextarea',
      branding: false,
      height: 540,
      menubar: 'file edit view insert format tools table',
      plugins: 'lists link table code autoresize',
      toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | code',
      content_style: 'body{font-family:Barlow,Arial,sans-serif;font-size:14px;line-height:1.55;color:#07182f;}'
    });
  }

  function editor() {
    return window.tinymce ? tinymce.get('typistDraftTextarea') : null;
  }

  function getDraftText() {
    const ed = editor();
    if (ed) {
      ed.save();
      return ed.getContent();
    }
    return textarea ? textarea.value : '';
  }

  function setDraftText(content) {
    const ed = editor();
    if (ed) {
      ed.setContent(content || '');
      ed.focus();
      return;
    }
    if (textarea) textarea.value = content || '';
  }

  function insertContent(content) {
    const ed = editor();
    if (ed) {
      ed.focus();
      ed.execCommand('mceInsertContent', false, content || '');
      return;
    }
    if (!textarea) return;
    const start = textarea.selectionStart || 0;
    const end = textarea.selectionEnd || 0;
    textarea.value = textarea.value.slice(0, start) + (content || '') + textarea.value.slice(end);
  }

  function setStatus(text, color) {
    if (status) {
      status.textContent = text || '';
      status.style.color = color || '#475569';
    }
  }

  async function send(action) {
    const text = getDraftText();
    const res = await fetch('/remotepanda/api/typist-workflow.php', {
      cache: 'no-store',
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: action, studyint: studyint, draft_text: text })
    });
    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.error || 'Could not save draft.');
    }
    return data;
  }

  document.querySelectorAll('.js-template-load').forEach(function (button) {
    button.addEventListener('click', function () {
      const index = parseInt(button.getAttribute('data-template-index') || '-1', 10);
      const record = templates[index];
      if (!record) return;
      setDraftText(record.content || '');
      document.querySelectorAll('.js-template-load').forEach(function (item) { item.classList.remove('active'); });
      button.classList.add('active');
      setStatus('Template loaded.', '#0f766e');
    });
  });

  document.querySelectorAll('.js-insert-field').forEach(function (button) {
    button.addEventListener('click', function () {
      const key = button.getAttribute('data-field-key') || '';
      insertContent(templateContext[key] || '');
    });
  });

  const search = document.getElementById('typistTemplateSearch');
  if (search) {
    search.addEventListener('input', function () {
      const q = search.value.trim().toLowerCase();
      document.querySelectorAll('.js-template-load').forEach(function (button) {
        button.style.display = !q || (button.getAttribute('data-template-name') || '').indexOf(q) !== -1 ? '' : 'none';
      });
    });
  }

  if (saveBtn) {
    saveBtn.addEventListener('click', async function () {
      saveBtn.disabled = true;
      setStatus('Saving draft...', '#475569');
      try {
        const data = await send('save_draft');
        setStatus(data.message || 'Draft saved.', '#0f766e');
      } catch (err) {
        setStatus((err && err.message ? err.message : 'Could not save draft.').toString().slice(0, 180), '#b91c1c');
      }
      saveBtn.disabled = false;
    });
  }
  if (submitBtn) {
    submitBtn.addEventListener('click', async function () {
      submitBtn.disabled = true;
      setStatus('Sending to radiologist...', '#475569');
      try {
        const data = await send('submit_draft');
        setStatus(data.message || 'Draft sent to radiologist.', '#0f766e');
      } catch (err) {
        setStatus((err && err.message ? err.message : 'Could not submit draft.').toString().slice(0, 180), '#b91c1c');
        submitBtn.disabled = false;
      }
    });
  }
});
</script>
<script src="../../extensions/js/bootstrap.js"></script>
</body>
</html>
