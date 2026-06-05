<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../../includes/dbconnection.php');
include('../../functions.php');
include('../../includes/remote_reporting_service.php');
include('../../includes/cloud_bridge_service.php');

if (!isLoggedIn()) {
    $_SESSION['msg'] = "You must log in first";
    header('location: ../../index.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['user']);
    header("location: ../../index.php");
    exit;
}

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function inferModality($row)
{
    $raw = '';
    if (isset($row['modality']) && trim((string)$row['modality']) !== '') {
        $raw = trim((string)$row['modality']);
    } elseif (isset($row['study']) && trim((string)$row['study']) !== '') {
        $raw = trim((string)$row['study']);
    } elseif (isset($row['requested_procedure'])) {
        $raw = trim((string)$row['requested_procedure']);
    }

    $u = strtoupper($raw);
    if ($u === '') return '-';

    if (strpos($u, 'CT') !== false) return 'CT';
    if (strpos($u, 'MRI') !== false || strpos($u, 'MR ') !== false) return 'MRI';
    if (strpos($u, 'US') !== false || strpos($u, 'ULTRASOUND') !== false || strpos($u, 'SONO') !== false) return 'Ultrasound';
    if (strpos($u, 'XR') !== false || strpos($u, 'X-RAY') !== false || strpos($u, 'XRAY') !== false || strpos($u, 'CXR') !== false) return 'X-ray';
    if (strpos($u, 'MAMMO') !== false) return 'Mammography';
    if (strpos($u, 'DOPPLER') !== false) return 'Doppler';
    if (strpos($u, 'PET') !== false) return 'PET';
    if (strpos($u, 'NM') !== false || strpos($u, 'NUCLEAR') !== false) return 'Nuclear';

    return $raw;
}

$search = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$statusFilter = isset($_GET['status']) ? trim((string)$_GET['status']) : 'Awaiting Report';
rp_remote_reporting_ensure_schema($con);
$cloudImportSummary = null;
$cloudReturnSummary = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_cloud_orders'])) {
    $cloudImportSummary = rp_remote_cloud_bridge_import_orders($con, 50);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['push_cloud_returns'])) {
    $cloudReturnSummary = rp_remote_cloud_push_returned_reports($con, 25);
}
$currentReporter = rp_remote_reporting_current_user();
$assignment = rp_remote_reporting_assignment_sql($currentReporter, 's', 'r');

$allowedStatuses = [
    'Awaiting Report',
    'In Progress',
    'Pending Verification',
    'Finalized',
    'Reported',
    'Returned',
    'Return Queued',
    'All'
];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'Awaiting Report';
}

$kpiAssigned = 0;
$kpiUrgent = 0;
$kpiInProgress = 0;
$kpiFinalizedToday = 0;

$latestOrderJoin = "LEFT JOIN remote_report_orders r ON r.id = (
    SELECT rr.id FROM remote_report_orders rr
    WHERE rr.studyint = s.studyint OR rr.accession_number = CAST(s.accession_number AS CHAR)
    ORDER BY rr.id DESC LIMIT 1
)";

$kpiSql = "
SELECT
  SUM(CASE WHEN COALESCE(r.status, s.status) IN ('received','sent_to_cloud','Awaiting Report','In Progress','Pending Verification','in_progress') THEN 1 ELSE 0 END) AS assigned_count,
  SUM(CASE WHEN r.viewed_at IS NULL AND COALESCE(r.status, s.status) IN ('received','sent_to_cloud','Awaiting Report') THEN 1 ELSE 0 END) AS urgent_count,
  SUM(CASE WHEN s.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress_count,
  SUM(CASE WHEN (s.status = 'Finalized' OR r.status = 'reported') AND DATE(COALESCE(r.reported_at, s.assignment_updated_at, s.creation_time)) = CURDATE() THEN 1 ELSE 0 END) AS finalized_today_count
FROM study s
{$latestOrderJoin}
WHERE {$assignment['sql']}
";
$kpiStmt = mysqli_prepare($con, $kpiSql);
$kpiRes = false;
if ($kpiStmt) {
    rp_remote_reporting_bind($kpiStmt, $assignment['types'], $assignment['params']);
    mysqli_stmt_execute($kpiStmt);
    $kpiRes = mysqli_stmt_get_result($kpiStmt);
}
if ($kpiRes && ($kpiRow = mysqli_fetch_assoc($kpiRes))) {
    $kpiAssigned = (int)$kpiRow['assigned_count'];
    $kpiUrgent = (int)$kpiRow['urgent_count'];
    $kpiInProgress = (int)$kpiRow['in_progress_count'];
    $kpiFinalizedToday = (int)$kpiRow['finalized_today_count'];
}
if ($kpiStmt) {
    mysqli_stmt_close($kpiStmt);
}

$where = [$assignment['sql']];
$types = $assignment['types'];
$params = $assignment['params'];
if ($statusFilter !== 'All') {
    if ($statusFilter === 'Reported') {
        $where[] = "r.status = 'reported'";
    } elseif ($statusFilter === 'Returned') {
        $where[] = "r.status = 'returned'";
    } elseif ($statusFilter === 'Return Queued') {
        $where[] = "ro.status = 'queued'";
    } elseif ($statusFilter === 'In Progress') {
        $where[] = "(s.status = 'In Progress' OR r.status = 'in_progress')";
    } else {
        $where[] = "s.status = ?";
        $types .= 's';
        $params[] = $statusFilter;
    }
}
if ($search !== '') {
    $where[] = "(s.Name LIKE ? OR s.accession_number LIKE ? OR s.studyint LIKE ? OR s.requesting_physician LIKE ? OR r.order_uid LIKE ?)";
    $like = '%' . $search . '%';
    $types .= 'sssss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
$whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

$listSql = "
SELECT
    s.accession_number,
    s.Name,
    s.studyint,
    s.study,
    s.modality,
    s.requested_procedure,
    s.requesting_physician,
    s.technician_name,
    s.gender,
    s.date_of_birth,
    s.status,
    s.creation_time,
    r.order_uid,
    r.clinic_id,
    r.branch AS remote_branch,
    r.status AS report_order_status,
    r.reported_at,
    r.received_at,
    r.viewed_at,
    r.started_at,
    r.returned_at,
    ro.status AS return_status,
    ro.attempts AS return_attempts,
    ro.last_error AS return_last_error,
    ro.sent_at AS return_sent_at,
    CASE
      WHEN COALESCE(r.received_at, s.creation_time) IS NULL THEN NULL
      ELSE TIMESTAMPDIFF(MINUTE, COALESCE(r.received_at, s.creation_time), NOW())
    END AS wait_minutes
FROM study s
{$latestOrderJoin}
LEFT JOIN remote_report_return_outbox ro ON ro.order_uid = r.order_uid
{$whereSql}
ORDER BY
    CASE
      WHEN s.status = 'STAT' THEN 1
      WHEN s.status = 'Urgent' THEN 2
      WHEN s.status = 'Awaiting Report' THEN 3
      WHEN s.status = 'In Progress' THEN 4
      WHEN s.status = 'Pending Verification' THEN 5
      ELSE 6
    END,
    s.creation_time ASC
LIMIT 300
";
$listStmt = mysqli_prepare($con, $listSql);
$listRes = false;
if ($listStmt) {
    rp_remote_reporting_bind($listStmt, $types, $params);
    mysqli_stmt_execute($listStmt);
    $listRes = mysqli_stmt_get_result($listStmt);
}
?>
<!DOCTYPE HTML>
<html>
<head>
<title>RADPANDA | Radiologist Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link href="../../extensions/css/bootstrap.css" rel="stylesheet" type="text/css" />
<link href="../../extensions/css/style.css" rel="stylesheet" type="text/css" />
<link href="../../extensions/css/custom.css" rel="stylesheet" type="text/css" />
<link rel="icon" type="image/x-icon" href="../../extensions/images/favicon.png">
<script src="../../extensions/js/jquery-1.11.1.min.js"></script>
<style>
@import url('https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&display=swap');
body{background:#f2f7ff !important;font-family:'Barlow',sans-serif}
.rp-page{padding:18px}
.rp-header{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px}
.rp-title{margin:0;color:#0b1f3a;font-size:28px;font-weight:700}
.rp-sub{margin:0;color:#4b5d77;font-size:14px}
.rp-chip-row{display:grid;grid-template-columns:repeat(4,minmax(160px,1fr));gap:12px;margin-bottom:14px}
.rp-kpi{background:#fff;border:1px solid #d9e7ff;border-radius:16px;padding:14px 16px;box-shadow:0 8px 18px rgba(7,33,66,.06)}
.rp-kpi .k{font-size:12px;color:#5f7390;text-transform:uppercase;letter-spacing:.4px}
.rp-kpi .v{font-size:26px;color:#082248;font-weight:700;line-height:1.2}
.rp-kpi.urgent{background:linear-gradient(135deg,#fee2e2,#fff);border-color:#fecaca}
.rp-grid{display:grid;grid-template-columns:2.3fr 1fr;gap:14px}
.rp-card{background:#fff;border:1px solid #d9e7ff;border-radius:18px;box-shadow:0 10px 20px rgba(7,33,66,.06)}
.rp-card-h{padding:14px 16px;border-bottom:1px solid #e3ecfb;display:flex;align-items:center;justify-content:space-between;gap:10px}
.rp-card-t{font-size:17px;color:#0a244a;font-weight:700;margin:0}
.rp-filter{padding:12px 16px;border-bottom:1px solid #edf3ff;display:flex;gap:8px;flex-wrap:wrap}
.rp-input,.rp-select{height:38px;border-radius:10px;border:1px solid #c7d9f5;padding:0 10px;background:#fff;color:#0a244a}
.rp-btn{height:38px;border:none;border-radius:10px;padding:0 14px;font-weight:600}
.rp-btn-primary{background:#ed1b24;color:#fff}
.rp-btn-secondary{background:#0a2a57;color:#fff}
.rp-btn-cloud{background:#0f766e;color:#fff}
.rp-alert{margin:0 0 14px 0;border-radius:12px;padding:12px 14px;font-weight:600;border:1px solid #b7e4ce;background:#ecfdf5;color:#065f46}
.rp-alert.warn{border-color:#fed7aa;background:#fff7ed;color:#9a3412}
.rp-table-wrap{overflow:auto;padding:0 10px 10px 10px}
.rp-table{width:100%;border-collapse:separate;border-spacing:0 8px;min-width:900px}
.rp-table th{font-size:12px;color:#516b90;font-weight:700;padding:8px}
.rp-table td{background:#f8fbff;border-top:1px solid #e5efff;border-bottom:1px solid #e5efff;padding:10px 8px;color:#0c294f;font-size:13px;vertical-align:middle}
.rp-table td:first-child{border-left:1px solid #e5efff;border-radius:10px 0 0 10px}
.rp-table td:last-child{border-right:1px solid #e5efff;border-radius:0 10px 10px 0}
.rp-badge{display:inline-flex;align-items:center;gap:4px;padding:4px 9px;border-radius:999px;font-size:11px;font-weight:700}
.rp-awaiting{background:#dbeafe;color:#1e3a8a}
.rp-progress{background:#fef3c7;color:#92400e}
.rp-verify{background:#ede9fe;color:#5b21b6}
.rp-final{background:#dcfce7;color:#166534}
.rp-urgent{background:#fee2e2;color:#991b1b}
.rp-new{background:#fff7ed;color:#9a3412}
.rp-return{background:#e0f2fe;color:#075985}
.rp-return.sent{background:#dcfce7;color:#166534}
.rp-return.failed{background:#fee2e2;color:#991b1b}
.rp-actions{display:flex;gap:8px;flex-wrap:wrap}
.rp-link-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:8px 12px;border-radius:10px;background:#0a2a57;color:#fff;text-decoration:none;font-weight:700;font-size:12px;min-width:128px}
.rp-link-btn.alt{background:#1d4ed8}
.rp-link-btn.light{background:#f1f5ff;color:#123665;border:1px solid #c7d9f5}
.rp-side{padding:14px}
.rp-tool-list{display:grid;gap:10px}
.rp-tool{display:flex;justify-content:space-between;align-items:center;padding:10px;border:1px solid #d8e6ff;border-radius:12px;background:#f8fbff}
.rp-tool .n{font-weight:700;color:#0f2d56}
.rp-tool .d{font-size:12px;color:#617897}
.rp-tool a{font-size:12px;font-weight:700;color:#1d4ed8;text-decoration:none}
@media (max-width:1280px){.rp-chip-row{grid-template-columns:repeat(2,minmax(180px,1fr))}.rp-grid{grid-template-columns:1fr}}
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
  <div class="rp-header">
    <div>
      <h1 class="rp-title">Radiologist Worklist</h1>
      <p class="rp-sub">Prioritized reporting queue with quick actions and clinical context.</p>
    </div>
    <form method="post" action="" style="margin:0;">
      <button class="rp-btn rp-btn-cloud" type="submit" name="sync_cloud_orders" value="1"><i class="fa fa-cloud-arrow-down"></i> Sync Cloud Orders</button>
      <button class="rp-btn rp-btn-secondary" type="submit" name="push_cloud_returns" value="1"><i class="fa fa-cloud-arrow-up"></i> Push Final Reports</button>
    </form>
  </div>

  <?php if (is_array($cloudImportSummary)): ?>
    <?php
      $cloudImportOk = (int)($cloudImportSummary['failed'] ?? 0) === 0;
      $cloudImportText = 'Cloud sync checked ' . (int)($cloudImportSummary['checked'] ?? 0) . ' order(s), imported ' . (int)($cloudImportSummary['imported'] ?? 0) . '.';
      if (!$cloudImportOk && !empty($cloudImportSummary['errors'])) {
          $cloudImportText .= ' ' . implode(' ', array_map('strval', $cloudImportSummary['errors']));
      }
    ?>
    <div class="rp-alert <?php echo $cloudImportOk ? '' : 'warn'; ?>"><?php echo h($cloudImportText); ?></div>
  <?php endif; ?>

  <?php if (is_array($cloudReturnSummary)): ?>
    <?php
      $cloudReturnOk = (int)($cloudReturnSummary['failed'] ?? 0) === 0;
      $cloudReturnText = 'Cloud return push checked ' . (int)($cloudReturnSummary['checked'] ?? 0) . ' report(s), sent ' . (int)($cloudReturnSummary['sent'] ?? 0) . '.';
      if (!$cloudReturnOk && !empty($cloudReturnSummary['errors'])) {
          $cloudReturnText .= ' ' . implode(' ', array_map('strval', $cloudReturnSummary['errors']));
      }
    ?>
    <div class="rp-alert <?php echo $cloudReturnOk ? '' : 'warn'; ?>"><?php echo h($cloudReturnText); ?></div>
  <?php endif; ?>

  <div class="rp-chip-row">
    <div class="rp-kpi"><div class="k">Assigned</div><div class="v"><?php echo (int)$kpiAssigned; ?></div></div>
    <div class="rp-kpi urgent"><div class="k">New / Unopened</div><div class="v"><?php echo (int)$kpiUrgent; ?></div></div>
    <div class="rp-kpi"><div class="k">In Progress</div><div class="v"><?php echo (int)$kpiInProgress; ?></div></div>
    <div class="rp-kpi"><div class="k">Finalized Today</div><div class="v"><?php echo (int)$kpiFinalizedToday; ?></div></div>
  </div>

  <div class="rp-grid">
    <section class="rp-card">
      <div class="rp-card-h">
        <h2 class="rp-card-t">Reporting Queue</h2>
      </div>
      <form class="rp-filter" method="get" action="">
        <input class="rp-input" type="text" name="q" placeholder="Search patient, accession, study UID, referrer" value="<?php echo h($search); ?>" style="min-width:320px;flex:1 1 320px;">
        <select class="rp-select" name="status">
          <?php foreach ($allowedStatuses as $s): ?>
            <option value="<?php echo h($s); ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?>><?php echo h($s); ?></option>
          <?php endforeach; ?>
        </select>
        <button class="rp-btn rp-btn-primary" type="submit"><i class="fa fa-filter"></i> Apply</button>
        <a class="rp-btn rp-btn-secondary" href="index.php" style="display:inline-flex;align-items:center;text-decoration:none;">Reset</a>
      </form>

      <div class="rp-table-wrap">
        <table class="rp-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Patient</th>
              <th>Accession</th>
              <th>Modality</th>
              <th>Procedure</th>
              <th>Referrer</th>
              <th>Wait</th>
              <th>Status</th>
              <th>Order</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $cnt = 1;
            if ($listRes && mysqli_num_rows($listRes) > 0):
              while ($row = mysqli_fetch_assoc($listRes)):
                $status = (string)$row['status'];
                $orderStatus = trim((string)($row['report_order_status'] ?? ''));
                $badgeClass = 'rp-awaiting';
                if ($status === 'In Progress') $badgeClass = 'rp-progress';
                elseif ($status === 'Pending Verification') $badgeClass = 'rp-verify';
                elseif ($status === 'Finalized') $badgeClass = 'rp-final';
                elseif ($status === 'Urgent' || $status === 'STAT') $badgeClass = 'rp-urgent';

                $waitMinutes = isset($row['wait_minutes']) ? (int)$row['wait_minutes'] : 0;
                $waitLabel = $waitMinutes > 0 ? floor($waitMinutes / 60) . 'h ' . ($waitMinutes % 60) . 'm' : '-';
                $isNew = empty($row['viewed_at']) && in_array(strtolower($orderStatus !== '' ? $orderStatus : $status), ['received', 'sent_to_cloud', 'awaiting report'], true);
                $returnStatus = trim((string)($row['return_status'] ?? ''));
                $returnClass = 'rp-return';
                $returnLabel = '';
                if ($returnStatus !== '') {
                    $returnLabel = $returnStatus;
                    if (in_array(strtolower($returnStatus), ['sent', 'returned'], true)) {
                        $returnClass .= ' sent';
                    } elseif (in_array(strtolower($returnStatus), ['failed', 'error'], true) || trim((string)($row['return_last_error'] ?? '')) !== '') {
                        $returnClass .= ' failed';
                    }
                } elseif ($orderStatus === 'reported') {
                    $returnLabel = 'queued';
                } elseif ($orderStatus === 'returned') {
                    $returnLabel = 'returned';
                    $returnClass .= ' sent';
                }
            ?>
            <tr>
              <td><?php echo $cnt; ?></td>
              <td>
                <div style="font-weight:700"><?php echo h($row['Name']); ?></div>
                <div style="font-size:11px;color:#5f7390"><?php echo h($row['studyint']); ?></div>
                <?php if ($isNew): ?><div style="margin-top:5px;"><span class="rp-badge rp-new"><i class="fa fa-circle"></i> New</span></div><?php endif; ?>
              </td>
              <td><?php echo h($row['accession_number']); ?></td>
              <td><?php echo h(inferModality($row)); ?></td>
              <td><?php echo h($row['requested_procedure']); ?></td>
              <td><?php echo h($row['requesting_physician']); ?></td>
              <td><?php echo h($waitLabel); ?></td>
              <td><span class="rp-badge <?php echo $badgeClass; ?>"><?php echo h($status); ?></span></td>
              <td>
                <div style="font-weight:700"><?php echo h($orderStatus !== '' ? $orderStatus : 'local'); ?></div>
                <div style="font-size:11px;color:#5f7390"><?php echo h($row['order_uid'] ?? ''); ?></div>
                <?php if ($returnLabel !== ''): ?>
                  <div style="margin-top:5px;"><span class="rp-badge <?php echo h($returnClass); ?>">Return: <?php echo h($returnLabel); ?></span></div>
                <?php endif; ?>
              </td>
              <td>
                <div class="rp-actions">
                  <?php
                    $caseStatus = strtolower(trim((string)($row['status'] ?? '')));
                    $orderStatusForAction = strtolower(trim((string)($row['report_order_status'] ?? '')));
                    $actionLabel = ($caseStatus === 'in progress' || $orderStatusForAction === 'in_progress') ? 'Resume Reporting' : 'Start Reporting';
                  ?>
                  <a class="rp-link-btn" href="patient-details.php?accession=<?php echo urlencode((string)$row['accession_number']); ?>"><i class="fa fa-play"></i> <?php echo h($actionLabel); ?></a>
                </div>
              </td>
            </tr>
            <?php
                $cnt++;
              endwhile;
            else:
            ?>
            <tr>
              <td colspan="10" style="text-align:center;background:#fff;border:1px dashed #c7d9f5;border-radius:12px;padding:24px;">No studies match your current filter.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <aside class="rp-card">
      <div class="rp-card-h"><h2 class="rp-card-t">Quick Tools</h2></div>
      <div class="rp-side">
        <div class="rp-tool-list">
          <div class="rp-tool">
            <div><div class="n">Unread / Awaiting</div><div class="d">Prioritize unread studies first</div></div>
            <a href="index.php?status=Awaiting+Report">Open</a>
          </div>
          <div class="rp-tool">
            <div><div class="n">In Progress</div><div class="d">Resume draft interpretations</div></div>
            <a href="index.php?status=In+Progress">Open</a>
          </div>
          <div class="rp-tool">
            <div><div class="n">Pending Verification</div><div class="d">Finalize and sign off reports</div></div>
            <a href="index.php?status=Pending+Verification">Open</a>
          </div>
          <div class="rp-tool">
            <div><div class="n">Return Queue</div><div class="d">Reports finalized and waiting for clinic pickup</div></div>
            <a href="index.php?status=Return+Queued">Open</a>
          </div>
          <div class="rp-tool">
            <div><div class="n">Template Library</div><div class="d">Create/use reporting templates</div></div>
            <a href="view_templates.php">Open</a>
          </div>
          <div class="rp-tool">
            <div><div class="n">Scanned Patients</div><div class="d">Review completed scan queue</div></div>
            <a href="scanned-patients.php">Open</a>
          </div>
          <div class="rp-tool">
            <div><div class="n">Profile & Preferences</div><div class="d">Update account settings</div></div>
            <a href="profile.php">Open</a>
          </div>
        </div>
      </div>
    </aside>
  </div>
</div>
</div>
</div>

<div class="success-message" id="successMessage" style="background:#16a34a;color:#fff;padding:10px;position:fixed;top:0;width:100%;text-align:center;z-index:9999;display:none;">
<?php
if (isset($_SESSION['success_message'])) {
    echo h($_SESSION['success_message']);
    unset($_SESSION['success_message']);
}
?>
</div>

<script src="../../extensions/js/bootstrap.js"></script>
<script>
(function(){
  var m = document.getElementById('successMessage');
  if (m && m.textContent.trim() !== '') {
    m.style.display = 'block';
    setTimeout(function(){ m.style.display = 'none'; }, 3000);
  }
})();

(function(){
  var busy = false;
  var lastReloadAt = 0;
  var remoteBaseUrl = (function(){
    var marker = '/dashboards/';
    var path = window.location.pathname || '';
    var pos = path.indexOf(marker);
    return pos >= 0 ? path.slice(0, pos) : '';
  })();

  function canRefresh() {
    var active = document.activeElement;
    if (active && /^(INPUT|TEXTAREA|SELECT)$/.test(active.tagName || '')) return false;
    return Date.now() - lastReloadAt > 45000;
  }

  function autoCloudSync() {
    if (busy || document.hidden) return;
    busy = true;
    Promise.all([
      fetch(remoteBaseUrl + '/api/cloud-import-orders.php?limit=25', { cache: 'no-store', credentials: 'same-origin' }).then(function(r){ return r.json(); }),
      fetch(remoteBaseUrl + '/api/cloud-push-returned-reports.php?limit=10', { cache: 'no-store', credentials: 'same-origin' }).then(function(r){ return r.json(); })
    ]).then(function(results){
      var imported = results[0] && results[0].summary ? parseInt(results[0].summary.imported || 0, 10) : 0;
      var pushed = results[1] && results[1].summary ? parseInt(results[1].summary.sent || 0, 10) : 0;
      if ((imported > 0 || pushed > 0) && canRefresh()) {
        lastReloadAt = Date.now();
        window.location.reload();
      }
    }).catch(function(){
      // The scheduled worker will keep retrying; keep the dashboard quiet.
    }).finally(function(){
      busy = false;
    });
  }

  setTimeout(autoCloudSync, 4000);
  setInterval(autoCloudSync, 45000);
  document.addEventListener('visibilitychange', function(){
    if (!document.hidden) autoCloudSync();
  });
})();
</script>
</body>
</html>

