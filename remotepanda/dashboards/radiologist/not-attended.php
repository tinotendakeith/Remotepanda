<?php
session_start();
error_reporting(0);
include('../../includes/dbconnection.php');
include('../../functions.php');
include_once('../../includes/remote_reporting_service.php');

if (!isLoggedIn()) {
    $_SESSION['msg'] = 'You must log in first';
    header('location: index.php');
    exit;
}

function rp_case_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rp_case_label($value): string
{
    $value = trim((string) $value);
    return $value === '' ? 'Not set' : ucwords(str_replace('_', ' ', $value));
}

rp_remote_reporting_ensure_schema($con);
$user = rp_remote_reporting_current_user();
$acl = rp_remote_reporting_assignment_sql($user, 's', 'r');
$q = trim((string) ($_GET['q'] ?? ''));

$where = "(
    LOWER(COALESCE(r.status, '')) IN ('received','sent_to_cloud','assigned','new')
    OR LOWER(COALESCE(s.status, '')) IN ('awaiting report','assigned','received','new')
)";
$where .= " AND {$acl['sql']}";
$types = $acl['types'];
$params = $acl['params'];
if ($q !== '') {
    $where .= " AND (
        r.accession_number LIKE ?
        OR r.studyint LIKE ?
        OR r.procedure_name LIKE ?
        OR r.modality LIKE ?
        OR r.clinic_id LIKE ?
        OR s.accession_number LIKE ?
        OR s.requested_procedure LIKE ?
        OR s.study LIKE ?
    )";
    $needle = '%' . $q . '%';
    $types .= 'ssssssss';
    for ($i = 0; $i < 8; $i++) {
        $params[] = $needle;
    }
}

$sql = "SELECT r.*, s.study_id, s.accession_number AS study_accession, s.study AS study_name, s.requested_procedure,
               s.modality AS study_modality, s.status AS study_status, s.scheduled_date
        FROM remote_report_orders r
        LEFT JOIN study s ON s.studyint = r.studyint OR CAST(s.accession_number AS CHAR) = r.accession_number
        WHERE {$where}
        ORDER BY COALESCE(r.received_at, r.updated_at) DESC
        LIMIT 200";
$stmt = mysqli_prepare($con, $sql);
if ($stmt) {
    rp_remote_reporting_bind($stmt, $types, $params);
    mysqli_stmt_execute($stmt);
    $caseRes = mysqli_stmt_get_result($stmt);
} else {
    $caseRes = false;
}

$cases = array();
if ($caseRes) {
    while ($row = mysqli_fetch_assoc($caseRes)) {
        $cases[] = $row;
    }
}
if ($stmt) {
    mysqli_stmt_close($stmt);
}

$inProgressRes = mysqli_query($con, "SELECT COUNT(*) AS total FROM remote_report_orders r LEFT JOIN study s ON s.studyint = r.studyint OR CAST(s.accession_number AS CHAR) = r.accession_number WHERE LOWER(COALESCE(r.status,''))='in_progress' OR LOWER(COALESCE(s.status,''))='in progress'");
$inProgressRow = $inProgressRes ? mysqli_fetch_assoc($inProgressRes) : array('total' => 0);
?>
<!DOCTYPE HTML>
<html>
<head>
<title>RemotePanda | New Studies</title>
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
.case-shell { max-width:1490px; margin:0 auto; padding:0 10px 34px; }
.case-heading,
.case-card { background:#fff; border:1px solid #cfe0f5; border-radius:8px; box-shadow:0 12px 30px rgba(1,21,42,.06); }
.case-heading { display:flex; align-items:center; justify-content:space-between; gap:20px; padding:24px 28px; margin-bottom:18px; }
.case-eyebrow { color:#4c6687; font-size:13px; font-weight:900; letter-spacing:1.6px; text-transform:uppercase; }
.case-heading h1,
.case-card h3 { color:#001f3e; font-weight:900; letter-spacing:0; margin:0; }
.case-heading h1 { font-size:31px; line-height:1.1; margin-top:4px; }
.case-subtext { color:#284977; margin:6px 0 0; font-size:16px; }
.case-stats { display:grid; grid-template-columns:repeat(2, minmax(140px, 1fr)); gap:12px; min-width:320px; }
.case-stat { background:#f6fbff; border:1px solid #d8e7f8; border-radius:8px; padding:12px 14px; }
.case-stat span { display:block; color:#4c6687; font-size:12px; font-weight:900; letter-spacing:.7px; text-transform:uppercase; }
.case-stat strong { display:block; color:#001f3e; font-size:25px; line-height:1.1; margin-top:5px; }
.case-card { margin-bottom:18px; overflow:hidden; }
.case-card-header { padding:18px 20px; border-bottom:1px solid #d9e7f7; display:flex; align-items:flex-start; justify-content:space-between; gap:18px; }
.case-card-body { padding:20px; }
.case-filter { display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; }
.case-field { flex:1 1 360px; }
.case-field label { color:#001f3e; display:block; font-size:14px; font-weight:900; margin-bottom:7px; }
.case-field .form-control { border:1px solid #bdd4ef; border-radius:8px; box-shadow:none; min-height:42px; }
.case-card .btn { border-radius:999px; display:inline-flex; align-items:center; justify-content:center; gap:7px; font-weight:900; line-height:1; min-height:38px; padding:10px 17px; }
.btn-primary { background:#011f3d; border-color:#011f3d; color:#fff !important; }
.btn-default { background:#f6fbff; border:1px solid #cfe0f5; color:#001f3e !important; }
.case-table-wrap { overflow:auto; }
.case-table { width:100%; min-width:1100px; margin:0; border-collapse:collapse; }
.case-table th { background:#f0f5fd; border-bottom:1px solid #d9e7f7 !important; color:#001f3e; font-size:13px; font-weight:900; letter-spacing:.4px; padding:13px 12px !important; text-transform:uppercase; }
.case-table td { border-top:0 !important; border-bottom:1px solid #e2ebf6 !important; color:#001f3e; padding:14px 12px !important; vertical-align:middle !important; }
.case-table tbody tr:hover { background:#f8fbff; }
.case-title { font-weight:900; color:#001f3e; }
.case-muted { color:#4c6687; font-size:12px; line-height:1.45; }
.case-pill { display:inline-flex; align-items:center; background:#eef6ff; color:#244b78; border-radius:999px; font-size:12px; font-weight:900; padding:6px 10px; }
.case-empty { color:#4c6687; padding:28px !important; text-align:center; }
@media (max-width: 900px) { .case-heading { flex-direction:column; align-items:flex-start; } .case-stats { min-width:0; width:100%; } }
</style>
</head>
<body class="cbp-spmenu-push">
<div class="main-content">
<?php include_once('../../includes/radiographer-sidebar.php'); ?>
<?php include_once('../../includes/radiographer-heading.php'); ?>
<div id="page-wrapper">
  <div class="case-shell">
    <section class="case-heading">
      <div>
        <div class="case-eyebrow">Remote Worklist</div>
        <h1>New Studies</h1>
        <p class="case-subtext">Assigned cases waiting to be opened, reviewed, and reported.</p>
      </div>
      <div class="case-stats">
        <div class="case-stat"><span>New</span><strong><?php echo number_format(count($cases)); ?></strong></div>
        <div class="case-stat"><span>In Progress</span><strong><?php echo number_format((int) ($inProgressRow['total'] ?? 0)); ?></strong></div>
      </div>
    </section>

    <section class="case-card">
      <div class="case-card-header">
        <div><h3>Find Studies</h3><p class="case-subtext">Search by accession, procedure, modality, or clinic.</p></div>
      </div>
      <div class="case-card-body">
        <form method="get" class="case-filter">
          <div class="case-field"><label>Search</label><input type="text" name="q" class="form-control" value="<?php echo rp_case_h($q); ?>" placeholder="Accession, study, modality, clinic"></div>
          <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> Search</button>
          <a class="btn btn-default" href="not-attended.php">Reset</a>
        </form>
      </div>
    </section>

    <section class="case-card">
      <div class="case-card-header">
        <div><h3>Case Queue</h3><p class="case-subtext">Patient demographics are intentionally minimized here. Open the case for clinical context and images.</p></div>
      </div>
      <div class="case-card-body">
        <div class="case-table-wrap">
          <table class="table case-table">
            <thead><tr><th>#</th><th>Accession</th><th>Study</th><th>Modality</th><th>Clinic</th><th>Received</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php if (!empty($cases)) { $i = 1; foreach ($cases as $row) {
                $accession = (string) (($row['study_accession'] ?? '') ?: ($row['accession_number'] ?? ''));
                $studyName = (string) (($row['requested_procedure'] ?? '') ?: (($row['procedure_name'] ?? '') ?: ($row['study_name'] ?? 'Study')));
                $modality = (string) (($row['study_modality'] ?? '') ?: ($row['modality'] ?? ''));
                $status = (string) (($row['status'] ?? '') ?: ($row['study_status'] ?? ''));
            ?>
              <tr>
                <td><?php echo $i; ?></td>
                <td><div class="case-title"><?php echo rp_case_h($accession); ?></div><div class="case-muted"><?php echo rp_case_h($row['studyint'] ?? ''); ?></div></td>
                <td><?php echo rp_case_h($studyName); ?></td>
                <td><span class="case-pill"><?php echo rp_case_h(rp_case_label($modality)); ?></span></td>
                <td><div class="case-title"><?php echo rp_case_h($row['clinic_id'] ?? ''); ?></div><div class="case-muted"><?php echo rp_case_h($row['branch'] ?? ''); ?></div></td>
                <td><?php echo rp_case_h($row['received_at'] ?? ''); ?></td>
                <td><span class="case-pill"><?php echo rp_case_h(rp_case_label($status)); ?></span></td>
                <td><a class="btn btn-primary" href="view-appointment.php?viewid=<?php echo urlencode($accession); ?>"><i class="fa fa-folder-open"></i> Open Case</a></td>
              </tr>
            <?php $i++; }} else { ?>
              <tr><td colspan="8" class="case-empty">No new studies assigned right now.</td></tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
</div>
</div>
<script src="../../extensions/js/classie.js"></script>
<script src="../../extensions/js/jquery.nicescroll.js"></script>
<script src="../../extensions/js/scripts.js"></script>
<script src="../../extensions/js/bootstrap.js"></script>
</body>
</html>
