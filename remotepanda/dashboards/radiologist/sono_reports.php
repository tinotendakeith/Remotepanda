<?php
session_start();
error_reporting(0);
include('../../includes/dbconnection.php');
include('../../functions.php');
include_once('../../includes/remote_reporting_service.php');

if (!isLoggedIn()) {
    $_SESSION['msg'] = 'You must log in first';
    header('location: ../../index.php');
    exit;
}

function report_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function report_label($value): string
{
    $value = trim((string) $value);
    return $value === '' ? 'Not set' : ucwords(str_replace('_', ' ', $value));
}

function report_badge_class(string $status): string
{
    $status = strtolower(trim($status));
    if (in_array($status, array('finalized', 'reported'), true)) return 'report-badge-final';
    if (in_array($status, array('returned', 'sent'), true)) return 'report-badge-returned';
    if (in_array($status, array('queued', 'return queued'), true)) return 'report-badge-queued';
    if (in_array($status, array('in progress', 'in_progress'), true)) return 'report-badge-progress';
    if (in_array($status, array('pending verification'), true)) return 'report-badge-verify';
    if ($status === 'failed') return 'report-badge-failed';
    return 'report-badge-draft';
}

function report_case_status(array $row): string
{
    $orderStatus = strtolower(trim((string) ($row['report_order_status'] ?? '')));
    $studyStatus = trim((string) ($row['study_status'] ?? ''));
    $returnStatus = strtolower(trim((string) ($row['return_status'] ?? '')));

    if ($returnStatus === 'queued') return 'Return Queued';
    if ($returnStatus === 'sent' || $orderStatus === 'returned') return 'Returned';
    if ($returnStatus === 'failed') return 'Return Failed';
    if ($orderStatus === 'reported' || strtolower($studyStatus) === 'finalized') return 'Finalized';
    if ($orderStatus === 'in_progress' || strtolower($studyStatus) === 'in progress') return 'In Progress';
    if (strtolower($studyStatus) === 'pending verification') return 'Pending Verification';
    return $studyStatus !== '' ? $studyStatus : report_label($orderStatus ?: 'Draft');
}

function report_infer_modality(array $row): string
{
    $raw = trim((string) (($row['study_modality'] ?? '') ?: ($row['order_modality'] ?? '')));
    if ($raw === '') {
        $raw = trim((string) (($row['study_name'] ?? '') ?: ($row['requested_procedure'] ?? '')));
    }
    if ($raw === '') return '-';
    $upper = strtoupper($raw);
    if (strpos($upper, 'CT') !== false) return 'CT';
    if (strpos($upper, 'MRI') !== false || strpos($upper, 'MR ') !== false) return 'MRI';
    if (strpos($upper, 'US') !== false || strpos($upper, 'ULTRASOUND') !== false || strpos($upper, 'SONO') !== false) return 'Ultrasound';
    if (strpos($upper, 'XR') !== false || strpos($upper, 'X-RAY') !== false || strpos($upper, 'XRAY') !== false || strpos($upper, 'CXR') !== false) return 'X-ray';
    if (strpos($upper, 'MAMMO') !== false) return 'Mammography';
    if (strpos($upper, 'DOPPLER') !== false) return 'Doppler';
    return $raw;
}

rp_remote_reporting_ensure_schema($con);
$currentReporter = rp_remote_reporting_current_user();
$assignment = rp_remote_reporting_assignment_sql($currentReporter, 's', 'r');
$search = trim((string) ($_GET['q'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? 'All'));
$allowedStatuses = array('All', 'Drafts', 'In Progress', 'Pending Verification', 'Finalized', 'Returned', 'Return Queued', 'Return Failed');
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'All';
}

$hasStudyTextarea = rp_remote_reporting_has_column($con, 'study', 'textarea');
$studyReportSelect = $hasStudyTextarea ? 's.textarea AS study_report_text,' : "'' AS study_report_text,";
$studyReportCondition = $hasStudyTextarea ? "OR COALESCE(s.textarea, '') <> ''" : '';

$latestOrderJoin = "LEFT JOIN remote_report_orders r ON r.id = (
    SELECT rr.id FROM remote_report_orders rr
    WHERE rr.studyint = s.studyint OR rr.accession_number = CAST(s.accession_number AS CHAR)
    ORDER BY rr.id DESC LIMIT 1
)";

$baseReportWhere = "(
    LOWER(COALESCE(r.status, '')) IN ('in_progress','reported','returned','finalized')
    OR s.status IN ('In Progress','Pending Verification','Finalized','Reported','Returned','Completed')
    OR COALESCE(r.final_report_text, '') <> ''
    {$studyReportCondition}
)";

$where = array($assignment['sql'], $baseReportWhere);
$types = $assignment['types'];
$params = $assignment['params'];

if ($statusFilter === 'Drafts') {
    $where[] = "(COALESCE(r.final_report_text, '') <> '' {$studyReportCondition} OR LOWER(COALESCE(r.status, '')) = 'in_progress' OR s.status IN ('In Progress','Pending Verification')) AND NOT (s.status = 'Finalized' OR LOWER(COALESCE(r.status, '')) IN ('reported','returned'))";
} elseif ($statusFilter === 'In Progress') {
    $where[] = "(s.status = 'In Progress' OR LOWER(COALESCE(r.status, '')) = 'in_progress')";
} elseif ($statusFilter === 'Pending Verification') {
    $where[] = "s.status = 'Pending Verification'";
} elseif ($statusFilter === 'Finalized') {
    $where[] = "(s.status = 'Finalized' OR LOWER(COALESCE(r.status, '')) IN ('reported','finalized'))";
} elseif ($statusFilter === 'Returned') {
    $where[] = "(LOWER(COALESCE(r.status, '')) = 'returned' OR LOWER(COALESCE(ro.status, '')) = 'sent')";
} elseif ($statusFilter === 'Return Queued') {
    $where[] = "LOWER(COALESCE(ro.status, '')) = 'queued'";
} elseif ($statusFilter === 'Return Failed') {
    $where[] = "LOWER(COALESCE(ro.status, '')) = 'failed'";
}

if ($search !== '') {
    $where[] = "(s.Name LIKE ? OR r.patient_name LIKE ? OR s.accession_number LIKE ? OR s.studyint LIKE ? OR s.study LIKE ? OR s.requested_procedure LIKE ? OR s.modality LIKE ? OR r.order_uid LIKE ? OR r.clinic_id LIKE ? OR r.branch LIKE ?)";
    $like = '%' . $search . '%';
    $types .= 'ssssssssss';
    for ($i = 0; $i < 10; $i++) {
        $params[] = $like;
    }
}

$whereSql = 'WHERE ' . implode(' AND ', $where);
$sql = "SELECT
        s.study_id,
        s.accession_number,
        s.Name,
        s.studyint,
        s.study AS study_name,
        s.requested_procedure,
        s.modality AS study_modality,
        s.status AS study_status,
        s.creation_time,
        s.assignment_updated_at,
        {$studyReportSelect}
        r.order_uid,
        r.clinic_id,
        r.branch AS remote_branch,
        r.patient_name AS order_patient_name,
        r.modality AS order_modality,
        r.procedure_name,
        r.status AS report_order_status,
        r.final_report_text,
        r.received_at,
        r.viewed_at,
        r.started_at,
        r.reported_at,
        r.returned_at,
        r.updated_at AS order_updated_at,
        ro.status AS return_status,
        ro.attempts AS return_attempts,
        ro.last_error AS return_last_error,
        ro.sent_at AS return_sent_at,
        ro.updated_at AS return_updated_at
    FROM study s
    {$latestOrderJoin}
    LEFT JOIN remote_report_return_outbox ro ON ro.order_uid = r.order_uid
    {$whereSql}
    ORDER BY COALESCE(ro.updated_at, r.reported_at, r.updated_at, s.assignment_updated_at, s.creation_time) DESC
    LIMIT 300";

$rows = array();
$stmt = mysqli_prepare($con, $sql);
if ($stmt) {
    rp_remote_reporting_bind($stmt, $types, $params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

$totalReports = count($rows);
$draftCount = 0;
$finalizedCount = 0;
$returnCount = 0;
$queuedCount = 0;
foreach ($rows as $row) {
    $caseStatus = strtolower(report_case_status($row));
    if (in_array($caseStatus, array('finalized', 'reported'), true)) $finalizedCount++;
    if (in_array($caseStatus, array('returned', 'return failed'), true)) $returnCount++;
    if ($caseStatus === 'return queued') $queuedCount++;
    if (in_array($caseStatus, array('in progress', 'pending verification', 'draft'), true)) $draftCount++;
}
?>
<!DOCTYPE HTML>
<html>
<head>
<title>RemotePanda | Reports</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="../../extensions/css/bootstrap.css" rel="stylesheet" type="text/css" />
<link href="../../extensions/css/style.css" rel="stylesheet" type="text/css" />
<link href="../../extensions/css/custom.css" rel="stylesheet">
<link href="../../extensions/css/font-awesome.css" rel="stylesheet">
<link rel="icon" type="image/x-icon" href="../../extensions/images/favicon.png">
<script src="../../extensions/js/jquery-1.11.1.min.js"></script>
<script src="../../extensions/js/modernizr.custom.js"></script>
<script src="../../extensions/js/metisMenu.min.js"></script>
<script src="../../extensions/js/custom.js"></script>
<style>
body { background:#f3f8ff; color:#001b36; }
#page-wrapper { padding-top:92px; background:#f3f8ff; min-height:100vh; }
.report-shell { max-width:1490px; margin:0 auto; padding:0 10px 34px; }
.report-heading,
.report-card { background:#fff; border:1px solid #cfe0f5; border-radius:8px; box-shadow:0 12px 30px rgba(1,21,42,.06); }
.report-heading { display:flex; align-items:center; justify-content:space-between; gap:20px; padding:24px 28px; margin-bottom:18px; }
.report-eyebrow { color:#4c6687; font-size:13px; font-weight:900; letter-spacing:1.6px; text-transform:uppercase; }
.report-heading h1,
.report-card h3 { color:#001f3e; font-weight:900; letter-spacing:0; margin:0; }
.report-heading h1 { font-size:31px; line-height:1.1; margin-top:4px; }
.report-subtext { color:#284977; margin:6px 0 0; font-size:16px; }
.report-stats { display:grid; grid-template-columns:repeat(4, minmax(120px, 1fr)); gap:12px; min-width:620px; }
.report-stat { background:#f6fbff; border:1px solid #d8e7f8; border-radius:8px; padding:12px 14px; }
.report-stat span { display:block; color:#4c6687; font-size:12px; font-weight:900; letter-spacing:.7px; text-transform:uppercase; }
.report-stat strong { display:block; color:#001f3e; font-size:25px; line-height:1.1; margin-top:5px; }
.report-card { margin-bottom:18px; overflow:hidden; }
.report-card-header { padding:18px 20px; border-bottom:1px solid #d9e7f7; display:flex; align-items:flex-start; justify-content:space-between; gap:18px; }
.report-card-body { padding:20px; }
.report-filter { display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; }
.report-field { flex:1 1 330px; }
.report-field-small { flex:0 1 230px; }
.report-field label { color:#001f3e; display:block; font-size:14px; font-weight:900; margin-bottom:7px; }
.report-field .form-control { border:1px solid #bdd4ef; border-radius:8px; box-shadow:none; min-height:42px; }
.report-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
.report-tab { background:#f6fbff; border:1px solid #cfe0f5; border-radius:999px; color:#001f3e; display:inline-flex; align-items:center; min-height:36px; padding:8px 14px; font-weight:900; text-decoration:none; }
.report-tab.active { background:#011f3d; border-color:#011f3d; color:#fff; }
.report-card .btn { border-radius:999px; display:inline-flex; align-items:center; justify-content:center; gap:7px; font-weight:900; line-height:1; min-height:38px; padding:10px 17px; }
.btn-primary { background:#011f3d; border-color:#011f3d; color:#fff !important; }
.btn-danger { background:#ed1b24; border-color:#ed1b24; color:#fff !important; }
.btn-default { background:#f6fbff; border:1px solid #cfe0f5; color:#001f3e !important; }
.report-table-wrap { overflow:auto; }
.report-table { width:100%; min-width:1180px; margin:0; border-collapse:collapse; }
.report-table th { background:#f0f5fd; border-bottom:1px solid #d9e7f7 !important; color:#001f3e; font-size:13px; font-weight:900; letter-spacing:.4px; padding:13px 12px !important; text-transform:uppercase; }
.report-table td { border-top:0 !important; border-bottom:1px solid #e2ebf6 !important; color:#001f3e; padding:14px 12px !important; vertical-align:middle !important; }
.report-table tbody tr:hover { background:#f8fbff; }
.report-title { font-weight:900; color:#001f3e; }
.report-muted { color:#4c6687; font-size:12px; line-height:1.45; }
.report-badge { display:inline-flex; align-items:center; border-radius:999px; font-size:12px; font-weight:900; padding:6px 10px; white-space:nowrap; }
.report-badge-draft { background:#eef6ff; color:#244b78; }
.report-badge-progress { background:#fef3c7; color:#92400e; }
.report-badge-verify { background:#ede9fe; color:#5b21b6; }
.report-badge-final { background:#e7f7ed; color:#1f7a3e; }
.report-badge-returned { background:#e0f2fe; color:#075985; }
.report-badge-queued { background:#fff7ed; color:#9a3412; }
.report-badge-failed { background:#fee2e2; color:#991b1b; }
.report-actions { display:flex; gap:8px; flex-wrap:wrap; }
.report-action { border-radius:999px; display:inline-flex; align-items:center; justify-content:center; gap:7px; font-weight:900; line-height:1; min-height:38px; padding:10px 15px; text-decoration:none; }
.report-action-primary { background:#011f3d; color:#fff; }
.report-action-secondary { background:#f6fbff; border:1px solid #cfe0f5; color:#001f3e; }
.report-empty { color:#4c6687; padding:28px !important; text-align:center; }
@media (max-width: 1000px) { .report-heading { flex-direction:column; align-items:flex-start; } .report-stats { min-width:0; width:100%; grid-template-columns:repeat(2, 1fr); } }
@media (max-width: 640px) { .report-stats { grid-template-columns:1fr; } .report-heading h1 { font-size:26px; } }
</style>
</head>
<body class="cbp-spmenu-push">
<div class="main-content">
<?php include_once('../../includes/radiographer-sidebar.php'); ?>
<?php include_once('../../includes/radiographer-heading.php'); ?>
<div id="page-wrapper">
  <div class="report-shell">
    <section class="report-heading">
      <div>
        <div class="report-eyebrow">Reporting Desk</div>
        <h1>Manage Reports</h1>
        <p class="report-subtext">Find drafts, finalized reports, and return status without opening patient administration pages.</p>
      </div>
      <div class="report-stats">
        <div class="report-stat"><span>Total</span><strong><?php echo number_format($totalReports); ?></strong></div>
        <div class="report-stat"><span>Drafts</span><strong><?php echo number_format($draftCount); ?></strong></div>
        <div class="report-stat"><span>Finalized</span><strong><?php echo number_format($finalizedCount); ?></strong></div>
        <div class="report-stat"><span>Returning</span><strong><?php echo number_format($returnCount + $queuedCount); ?></strong></div>
      </div>
    </section>

    <section class="report-card">
      <div class="report-card-header">
        <div>
          <h3>Find Reports</h3>
          <p class="report-subtext">Search by accession, study UID, procedure, modality, clinic, or report order.</p>
        </div>
      </div>
      <div class="report-card-body">
        <div class="report-tabs">
          <?php foreach ($allowedStatuses as $tab) {
              $tabUrl = 'sono_reports.php?status=' . urlencode($tab);
              if ($search !== '') $tabUrl .= '&q=' . urlencode($search);
          ?>
            <a class="report-tab <?php echo $statusFilter === $tab ? 'active' : ''; ?>" href="<?php echo report_h($tabUrl); ?>"><?php echo report_h($tab); ?></a>
          <?php } ?>
        </div>
        <form method="get" class="report-filter">
          <input type="hidden" name="status" value="<?php echo report_h($statusFilter); ?>">
          <div class="report-field">
            <label>Search</label>
            <input type="text" name="q" class="form-control" value="<?php echo report_h($search); ?>" placeholder="Accession, study UID, procedure, clinic">
          </div>
          <div class="report-field report-field-small">
            <label>Status</label>
            <select name="status" class="form-control">
              <?php foreach ($allowedStatuses as $status) { ?>
                <option value="<?php echo report_h($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo report_h($status); ?></option>
              <?php } ?>
            </select>
          </div>
          <button class="btn btn-danger" type="submit"><i class="fa fa-filter"></i> Apply</button>
          <a class="btn btn-default" href="sono_reports.php">Reset</a>
        </form>
      </div>
    </section>

    <section class="report-card">
      <div class="report-card-header">
        <div>
          <h3>Report Register</h3>
          <p class="report-subtext">A reporting activity log for cases assigned to this radiologist.</p>
        </div>
      </div>
      <div class="report-card-body">
        <div class="report-table-wrap">
          <table class="table report-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Patient</th>
                <th>Study</th>
                <th>Clinic</th>
                <th>Report Status</th>
                <th>Return</th>
                <th>Last Activity</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
            <?php if (!empty($rows)) { $count = 1; foreach ($rows as $row) {
                $accession = (string) ($row['accession_number'] ?? '');
                $patientName = trim((string) (($row['Name'] ?? '') ?: ($row['order_patient_name'] ?? '')));
                $studyName = (string) (($row['requested_procedure'] ?? '') ?: (($row['procedure_name'] ?? '') ?: ($row['study_name'] ?? 'Study')));
                $modality = report_infer_modality($row);
                $clinic = trim((string) (($row['remote_branch'] ?? '') ?: ($row['clinic_id'] ?? '')));
                $caseStatus = report_case_status($row);
                $returnStatus = trim((string) ($row['return_status'] ?? ''));
                $lastActivity = (string) (($row['return_updated_at'] ?? '') ?: (($row['reported_at'] ?? '') ?: (($row['order_updated_at'] ?? '') ?: (($row['assignment_updated_at'] ?? '') ?: ($row['creation_time'] ?? '')))));
                $hasFinalText = trim((string) (($row['final_report_text'] ?? '') ?: ($row['study_report_text'] ?? ''))) !== '';
                $actionLabel = in_array(strtolower($caseStatus), array('finalized', 'returned', 'return queued'), true) ? 'Review Report' : ($hasFinalText ? 'Continue Report' : 'Open Report');
            ?>
              <tr>
                <td><?php echo $count; ?></td>
                <td>
                  <div class="report-title"><?php echo report_h($patientName !== '' ? $patientName : 'Unnamed patient'); ?></div>
                  <div class="report-muted">Accession: <?php echo report_h($accession !== '' ? $accession : 'Not recorded'); ?></div>
                  <div class="report-muted"><?php echo report_h($row['studyint'] ?? ''); ?></div>
                  <?php if (!empty($row['order_uid'])) { ?><div class="report-muted"><?php echo report_h($row['order_uid']); ?></div><?php } ?>
                </td>
                <td>
                  <div class="report-title"><?php echo report_h($studyName); ?></div>
                  <div class="report-muted"><?php echo report_h($modality); ?></div>
                </td>
                <td><?php echo report_h($clinic !== '' ? $clinic : 'Local'); ?></td>
                <td><span class="report-badge <?php echo report_h(report_badge_class($caseStatus)); ?>"><?php echo report_h(report_label($caseStatus)); ?></span></td>
                <td>
                  <?php if ($returnStatus !== '') { ?>
                    <span class="report-badge <?php echo report_h(report_badge_class($returnStatus)); ?>"><?php echo report_h(report_label($returnStatus)); ?></span>
                    <?php if (!empty($row['return_attempts'])) { ?><div class="report-muted"><?php echo (int) $row['return_attempts']; ?> attempt(s)</div><?php } ?>
                    <?php if (!empty($row['return_last_error'])) { ?><div class="report-muted"><?php echo report_h($row['return_last_error']); ?></div><?php } ?>
                  <?php } else { ?>
                    <span class="report-muted">Not queued</span>
                  <?php } ?>
                </td>
                <td><?php echo report_h($lastActivity !== '' ? $lastActivity : 'Not recorded'); ?></td>
                <td>
                  <div class="report-actions">
                    <?php if ($accession !== '') { ?>
                      <a class="report-action report-action-primary" href="patient-details.php?accession=<?php echo urlencode($accession); ?>"><i class="fa fa-file-text-o"></i> <?php echo report_h($actionLabel); ?></a>
                    <?php } ?>
                    <a class="report-action report-action-secondary" href="index.php?status=<?php echo urlencode($caseStatus === 'Return Queued' ? 'Return Queued' : ($caseStatus === 'Finalized' ? 'Finalized' : 'All')); ?>"><i class="fa fa-list"></i> Worklist</a>
                  </div>
                </td>
              </tr>
            <?php $count++; } } else { ?>
              <tr><td colspan="8" class="report-empty">No reports found for this filter.</td></tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
</div>
</div>
<script src="js/classie.js"></script>
<script>
var menuLeft = document.getElementById('cbp-spmenu-s1'),
    showLeftPush = document.getElementById('showLeftPush'),
    body = document.body;
if (showLeftPush && menuLeft) {
    showLeftPush.onclick = function() {
        classie.toggle(this, 'active');
        classie.toggle(body, 'cbp-spmenu-push-toright');
        classie.toggle(menuLeft, 'cbp-spmenu-open');
    };
}
</script>
<script src="../../extensions/js/jquery.nicescroll.js"></script>
<script src="../../extensions/js/scripts.js"></script>
<script src="../../extensions/js/bootstrap.js"></script>
</body>
</html>
