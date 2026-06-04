<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../../includes/dbconnection.php');
include('../../functions.php');
include('../../includes/typist_workflow_service.php');

if (!isLoggedIn()) {
    $_SESSION['msg'] = "You must log in first";
    header('location: ../../index.php');
    exit;
}

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$user = rp_typist_workflow_user();
$userType = strtolower((string)($user['type'] ?? ''));
if (!rp_typist_workflow_is_typist($user) && !rp_remote_reporting_user_is_admin($user)) {
    header('location: ../radiologist/index.php');
    exit;
}

rp_typist_workflow_ensure_schema($con);
$status = trim((string)($_GET['status'] ?? 'Active'));
$search = trim((string)($_GET['q'] ?? ''));
$allowed = array('Active', 'With Typist', 'Needs Edits', 'Ready for Radiologist', 'All');
if (!in_array($status, $allowed, true)) {
    $status = 'Active';
}

$where = array("r.status IN ('dictated','with_typist','needs_typist_edits','typed_draft_ready')");
if ($status === 'With Typist') {
    $where[] = "r.status IN ('dictated','with_typist')";
} elseif ($status === 'Needs Edits') {
    $where[] = "r.status = 'needs_typist_edits'";
} elseif ($status === 'Ready for Radiologist') {
    $where[] = "r.status = 'typed_draft_ready'";
} elseif ($status === 'All') {
    $where = array("r.status NOT IN ('reported','returned')");
}

$types = '';
$params = array();
if (rp_typist_workflow_is_typist($user)) {
    $allowedRadiologists = rp_typist_workflow_radiologists_for_typist($user);
    if (empty($allowedRadiologists)) {
        $where[] = '0=1';
    } else {
        $placeholders = implode(',', array_fill(0, count($allowedRadiologists), '?'));
        $where[] = "r.radiologist_username IN ({$placeholders})";
        $types .= str_repeat('s', count($allowedRadiologists));
        foreach ($allowedRadiologists as $radiologistUsername) {
            $params[] = $radiologistUsername;
        }
    }
}
if ($search !== '') {
    $where[] = "(s.Name LIKE ? OR s.accession_number LIKE ? OR s.studyint LIKE ? OR s.requested_procedure LIKE ? OR r.radiologist_username LIKE ?)";
    $like = '%' . $search . '%';
    $types .= 'sssss';
    $params = array_merge($params, array($like, $like, $like, $like, $like));
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT r.order_uid, r.studyint, r.accession_number, r.patient_name, r.procedure_name, r.modality,
               r.radiologist_username, r.status AS order_status, r.received_at, r.updated_at,
               s.Name, s.requested_procedure, s.requesting_physician,
               d.status AS draft_status, d.typist_username, d.updated_at AS draft_updated_at,
               (SELECT COUNT(*) FROM report_dictations rd WHERE rd.studyint = r.studyint) AS dictation_count
        FROM remote_report_orders r
        LEFT JOIN study s ON s.studyint = r.studyint
        LEFT JOIN report_typist_drafts d ON d.studyint = r.studyint
        {$whereSql}
        ORDER BY
            CASE r.status
                WHEN 'needs_typist_edits' THEN 1
                WHEN 'dictated' THEN 2
                WHEN 'with_typist' THEN 3
                WHEN 'typed_draft_ready' THEN 4
                ELSE 5
            END,
            r.updated_at ASC
        LIMIT 300";

$stmt = mysqli_prepare($con, $sql);
$res = false;
if ($stmt) {
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE HTML>
<html>
<head>
<title>RADPANDA | Typist Queue</title>
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
.rp-page{padding:18px}.rp-title{margin:0;color:#0b1f3a;font-size:28px;font-weight:700}.rp-sub{margin:0;color:#4b5d77;font-size:14px}
.rp-card{background:#fff;border:1px solid #d9e7ff;border-radius:18px;box-shadow:0 10px 20px rgba(7,33,66,.06);margin-top:16px}
.rp-card-h{padding:14px 16px;border-bottom:1px solid #e3ecfb;display:flex;align-items:center;justify-content:space-between}
.rp-card-t{font-size:17px;color:#0a244a;font-weight:700;margin:0}.rp-filter{padding:12px 16px;border-bottom:1px solid #edf3ff;display:flex;gap:8px;flex-wrap:wrap}
.rp-input,.rp-select{height:38px;border-radius:10px;border:1px solid #c7d9f5;padding:0 10px;background:#fff;color:#0a244a}.rp-btn{height:38px;border:none;border-radius:10px;padding:0 14px;font-weight:700;display:inline-flex;align-items:center;gap:6px;text-decoration:none}
.rp-btn-primary{background:#ed1b24;color:#fff}.rp-btn-secondary{background:#0a2a57;color:#fff}.rp-table-wrap{overflow:auto;padding:0 10px 10px 10px}
.rp-table{width:100%;border-collapse:separate;border-spacing:0 8px;min-width:900px}.rp-table th{font-size:12px;color:#516b90;font-weight:700;padding:8px}
.rp-table td{background:#f8fbff;border-top:1px solid #e5efff;border-bottom:1px solid #e5efff;padding:10px 8px;color:#0c294f;font-size:13px;vertical-align:middle}.rp-table td:first-child{border-left:1px solid #e5efff;border-radius:10px 0 0 10px}.rp-table td:last-child{border-right:1px solid #e5efff;border-radius:0 10px 10px 0}
.rp-badge{display:inline-flex;align-items:center;gap:4px;padding:4px 9px;border-radius:999px;font-size:11px;font-weight:700}.rp-progress{background:#fef3c7;color:#92400e}.rp-ready{background:#dcfce7;color:#166534}.rp-edits{background:#fee2e2;color:#991b1b}
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
  <h1 class="rp-title">Typist Queue</h1>
  <p class="rp-sub">Dictated studies waiting for typed draft preparation and radiologist review.</p>

  <section class="rp-card">
    <div class="rp-card-h"><h2 class="rp-card-t">Assigned Typing Work</h2></div>
    <form class="rp-filter" method="get" action="">
      <input class="rp-input" type="text" name="q" placeholder="Search patient, accession, radiologist" value="<?php echo h($search); ?>" style="min-width:320px;flex:1 1 320px;">
      <select class="rp-select" name="status">
        <?php foreach ($allowed as $s): ?>
          <option value="<?php echo h($s); ?>" <?php echo $status === $s ? 'selected' : ''; ?>><?php echo h($s); ?></option>
        <?php endforeach; ?>
      </select>
      <button class="rp-btn rp-btn-primary" type="submit"><i class="fa fa-filter"></i> Apply</button>
      <a class="rp-btn rp-btn-secondary" href="index.php">Reset</a>
    </form>
    <div class="rp-table-wrap">
      <table class="rp-table">
        <thead>
          <tr>
            <th>#</th><th>Patient</th><th>Accession</th><th>Procedure</th><th>Radiologist</th><th>Dictations</th><th>Status</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php $i = 1; if ($res && mysqli_num_rows($res) > 0): while ($row = mysqli_fetch_assoc($res)):
            $caseStatus = strtolower((string)($row['order_status'] ?? ''));
            $badge = $caseStatus === 'typed_draft_ready' ? 'rp-ready' : ($caseStatus === 'needs_typist_edits' ? 'rp-edits' : 'rp-progress');
        ?>
          <tr>
            <td><?php echo $i++; ?></td>
            <td><strong><?php echo h(($row['Name'] ?? '') ?: ($row['patient_name'] ?? '')); ?></strong><br><small><?php echo h($row['studyint'] ?? ''); ?></small></td>
            <td><?php echo h($row['accession_number'] ?? ''); ?></td>
            <td><?php echo h(($row['requested_procedure'] ?? '') ?: ($row['procedure_name'] ?? '')); ?></td>
            <td><?php echo h($row['radiologist_username'] ?? ''); ?></td>
            <td><?php echo (int)($row['dictation_count'] ?? 0); ?></td>
            <td><span class="rp-badge <?php echo h($badge); ?>"><?php echo h(strtoupper(str_replace('_', ' ', (string)($row['order_status'] ?? '')))); ?></span></td>
            <td><a class="rp-btn rp-btn-secondary" href="case.php?studyint=<?php echo urlencode((string)$row['studyint']); ?>">Open Case</a></td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="8" style="text-align:center;background:#fff;border:1px dashed #c7d9f5;border-radius:12px;padding:24px;">No typist work is waiting right now.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
</div>
</div>
<script src="../../extensions/js/bootstrap.js"></script>
</body>
</html>
