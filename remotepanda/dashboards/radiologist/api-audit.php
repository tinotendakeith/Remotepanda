<?php
session_start();
error_reporting(0);

include('../../includes/dbconnection.php');
include('../../functions.php');

if (!isLoggedIn()) {
    $_SESSION['msg'] = 'You must log in first';
    header('location: ../../index.php');
    exit;
}

$userType = strtolower((string)($_SESSION['user']['user_type'] ?? $_SESSION['user_type'] ?? ''));
$isAdmin = in_array($userType, ['admin', 'superadmin', 'owner'], true);
if (!$isAdmin) {
    $_SESSION['msg'] = 'Access denied.';
    header('location: index.php');
    exit;
}

@mysqli_query($con, "CREATE TABLE IF NOT EXISTS remote_api_audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    request_id VARCHAR(64) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    http_method VARCHAR(12) NOT NULL,
    studyint VARCHAR(128) NULL,
    user_id INT NULL,
    username VARCHAR(191) NULL,
    user_type VARCHAR(64) NULL,
    status_code INT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    message VARCHAR(500) NULL,
    client_ip VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    meta_json LONGTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_request_id (request_id),
    KEY idx_studyint (studyint),
    KEY idx_created_at (created_at),
    KEY idx_endpoint (endpoint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function getParam(string $key, string $default = ''): string
{
    return isset($_GET[$key]) ? trim((string)$_GET[$key]) : $default;
}

function eventSeverity(string $eventType, int $success, int $statusCode): array
{
    $ev = strtolower($eventType);

    if ($success === 0 || $statusCode >= 500 || strpos($ev, 'failed') !== false || strpos($ev, 'denied') !== false || strpos($ev, 'blocked') !== false) {
        return ['High', 'sev-high'];
    }

    if ($statusCode >= 400 || strpos($ev, 'missing') !== false || strpos($ev, 'not_found') !== false) {
        return ['Medium', 'sev-medium'];
    }

    if (strpos($ev, 'request_received') !== false || strpos($ev, 'read') !== false) {
        return ['Info', 'sev-info'];
    }

    return ['Low', 'sev-low'];
}

function firstCellInt(mysqli $con, string $sql): int
{
    $res = mysqli_query($con, $sql);
    if (!$res) {
        return 0;
    }
    $row = mysqli_fetch_row($res);
    return $row ? (int)$row[0] : 0;
}

function getTopByField(mysqli $con, string $interval, string $field, int $limit = 5): array
{
    $allowedFields = ['username', 'studyint'];
    if (!in_array($field, $allowedFields, true)) {
        return [];
    }

    $sql = "SELECT {$field} AS label, COUNT(*) AS cnt
            FROM remote_api_audit_logs
            WHERE created_at >= (NOW() - INTERVAL {$interval})
              AND event_type IN ('study_acl_monitor_allow', 'study_access_denied', 'study_acl_fail_open_allow')
              AND {$field} IS NOT NULL
              AND {$field} <> ''
            GROUP BY {$field}
            ORDER BY cnt DESC, {$field} ASC
            LIMIT " . (int)$limit;

    $res = mysqli_query($con, $sql);
    $rows = [];
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $rows[] = [
                'label' => (string)($r['label'] ?? ''),
                'count' => (int)($r['cnt'] ?? 0),
            ];
        }
    }
    return $rows;
}

$fromDate = getParam('from_date', '');
$toDate = getParam('to_date', '');
$endpoint = getParam('endpoint', '');
$eventType = getParam('event_type', '');
$username = getParam('username', '');
$studyint = getParam('studyint', '');
$requestId = getParam('request_id', '');
$success = getParam('success', 'all');
$page = max(1, (int)getParam('page', '1'));
$pageSize = (int)getParam('page_size', '50');
$export = strtolower(getParam('export', ''));

if (!in_array($pageSize, [25, 50, 100, 200], true)) {
    $pageSize = 50;
}

$where = [];
if ($fromDate !== '') {
    $fromEsc = mysqli_real_escape_string($con, $fromDate);
    $where[] = "created_at >= '{$fromEsc} 00:00:00'";
}
if ($toDate !== '') {
    $toEsc = mysqli_real_escape_string($con, $toDate);
    $where[] = "created_at <= '{$toEsc} 23:59:59'";
}
if ($endpoint !== '') {
    $endpointEsc = mysqli_real_escape_string($con, $endpoint);
    $where[] = "endpoint LIKE '%{$endpointEsc}%'";
}
if ($eventType !== '') {
    $eventEsc = mysqli_real_escape_string($con, $eventType);
    $where[] = "event_type LIKE '%{$eventEsc}%'";
}
if ($username !== '') {
    $usernameEsc = mysqli_real_escape_string($con, $username);
    $where[] = "username LIKE '%{$usernameEsc}%'";
}
if ($studyint !== '') {
    $studyEsc = mysqli_real_escape_string($con, $studyint);
    $where[] = "studyint LIKE '%{$studyEsc}%'";
}
if ($requestId !== '') {
    $reqEsc = mysqli_real_escape_string($con, $requestId);
    $where[] = "request_id = '{$reqEsc}'";
}
if ($success === '1' || $success === '0') {
    $where[] = 'success = ' . (int)$success;
}

$whereSql = count($where) > 0 ? ('WHERE ' . implode(' AND ', $where)) : '';

$filterBase = [
    'from_date' => $fromDate,
    'to_date' => $toDate,
    'endpoint' => $endpoint,
    'event_type' => $eventType,
    'username' => $username,
    'studyint' => $studyint,
    'request_id' => $requestId,
    'success' => $success,
    'page_size' => (string)$pageSize,
];

if ($export === 'csv') {
    $csvSql = "SELECT id, created_at, request_id, endpoint, event_type, http_method, studyint, username, user_type, status_code, success, client_ip, message
               FROM remote_api_audit_logs
               {$whereSql}
               ORDER BY id DESC";
    $csvRes = mysqli_query($con, $csvSql);

    $filename = 'api-audit-' . date('Ymd-His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $out = fopen('php://output', 'w');
    fputcsv($out, ['id', 'created_at', 'request_id', 'endpoint', 'event_type', 'severity', 'http_method', 'studyint', 'username', 'user_type', 'status_code', 'success', 'client_ip', 'message']);

    if ($csvRes) {
        while ($r = mysqli_fetch_assoc($csvRes)) {
            $sev = eventSeverity((string)($r['event_type'] ?? ''), (int)($r['success'] ?? 0), (int)($r['status_code'] ?? 0));
            fputcsv($out, [
                $r['id'] ?? '',
                $r['created_at'] ?? '',
                $r['request_id'] ?? '',
                $r['endpoint'] ?? '',
                $r['event_type'] ?? '',
                $sev[0],
                $r['http_method'] ?? '',
                $r['studyint'] ?? '',
                $r['username'] ?? '',
                $r['user_type'] ?? '',
                $r['status_code'] ?? '',
                ((int)($r['success'] ?? 0) === 1 ? 'YES' : 'NO'),
                $r['client_ip'] ?? '',
                $r['message'] ?? '',
            ]);
        }
    }

    fclose($out);
    exit;
}

$acl24Monitor = firstCellInt($con, "SELECT COUNT(*) FROM remote_api_audit_logs WHERE created_at >= (NOW() - INTERVAL 24 HOUR) AND event_type = 'study_acl_monitor_allow'");
$acl24Denied = firstCellInt($con, "SELECT COUNT(*) FROM remote_api_audit_logs WHERE created_at >= (NOW() - INTERVAL 24 HOUR) AND event_type = 'study_access_denied'");
$acl24FailOpen = firstCellInt($con, "SELECT COUNT(*) FROM remote_api_audit_logs WHERE created_at >= (NOW() - INTERVAL 24 HOUR) AND event_type = 'study_acl_fail_open_allow'");

$acl7Monitor = firstCellInt($con, "SELECT COUNT(*) FROM remote_api_audit_logs WHERE created_at >= (NOW() - INTERVAL 7 DAY) AND event_type = 'study_acl_monitor_allow'");
$acl7Denied = firstCellInt($con, "SELECT COUNT(*) FROM remote_api_audit_logs WHERE created_at >= (NOW() - INTERVAL 7 DAY) AND event_type = 'study_access_denied'");
$acl7FailOpen = firstCellInt($con, "SELECT COUNT(*) FROM remote_api_audit_logs WHERE created_at >= (NOW() - INTERVAL 7 DAY) AND event_type = 'study_acl_fail_open_allow'");

$topUsers24 = getTopByField($con, '24 HOUR', 'username', 5);
$topStudies24 = getTopByField($con, '24 HOUR', 'studyint', 5);
$topUsers7 = getTopByField($con, '7 DAY', 'username', 5);
$topStudies7 = getTopByField($con, '7 DAY', 'studyint', 5);

$countSql = "SELECT COUNT(*) AS cnt FROM remote_api_audit_logs {$whereSql}";
$countRes = mysqli_query($con, $countSql);
$totalRows = 0;
if ($countRes && ($row = mysqli_fetch_assoc($countRes))) {
    $totalRows = (int)($row['cnt'] ?? 0);
}

$totalPages = max(1, (int)ceil($totalRows / $pageSize));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $pageSize;

$listSql = "SELECT id, created_at, request_id, endpoint, event_type, http_method, studyint, username, user_type, status_code, success, message, client_ip
            FROM remote_api_audit_logs
            {$whereSql}
            ORDER BY id DESC
            LIMIT {$offset}, {$pageSize}";
$listRes = mysqli_query($con, $listSql);

function buildPageUrl(array $base, int $targetPage): string
{
    $base['page'] = (string)$targetPage;
    return '?' . http_build_query($base);
}

$csvUrl = '?' . http_build_query(array_merge($filterBase, ['export' => 'csv']));
$clearRequestUrl = '?' . http_build_query(array_merge($filterBase, ['request_id' => '', 'page' => 1]));
?>
<!DOCTYPE HTML>
<html>
<head>
<title>RADPANDA | API Audit</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link href="../../extensions/css/bootstrap.css" rel="stylesheet" type="text/css" />
<link href="../../extensions/css/style.css" rel="stylesheet" type="text/css" />
<link href="../../extensions/css/custom.css" rel="stylesheet" type="text/css" />
<link rel="icon" type="image/x-icon" href="../../extensions/images/favicon.png">
<script src="../../extensions/js/jquery-1.11.1.min.js"></script>
<style>
body { background: #f2f7ff !important; }
.rp-wrap { padding: 16px; }
.rp-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom: 12px; }
.rp-title { margin:0; color:#0a244a; font-size:28px; font-weight:700; }
.rp-sub { color:#5f7390; margin:4px 0 0 0; }
.rp-card { background:#fff; border:1px solid #dce7f7; border-radius:12px; padding:14px; margin-bottom:14px; }
.rp-filters { display:grid; grid-template-columns: repeat(4, minmax(160px,1fr)); gap:10px; }
.rp-table-wrap { overflow:auto; }
.rp-table { width:100%; border-collapse: collapse; min-width: 1320px; }
.rp-table th { background:#f1f6ff; color:#39567e; font-size:12px; padding:8px; border:1px solid #e2ecfb; }
.rp-table td { padding:8px; border:1px solid #e9effb; color:#16365c; font-size:12px; vertical-align:top; }
.rp-badge-ok { background:#dcfce7; color:#166534; padding:3px 8px; border-radius:999px; font-weight:700; font-size:11px; }
.rp-badge-fail { background:#fee2e2; color:#991b1b; padding:3px 8px; border-radius:999px; font-weight:700; font-size:11px; }
.rp-sev { padding:3px 8px; border-radius:999px; font-weight:700; font-size:11px; display:inline-block; }
.rp-sev.sev-high { background:#fee2e2; color:#991b1b; }
.rp-sev.sev-medium { background:#fef3c7; color:#92400e; }
.rp-sev.sev-low { background:#dcfce7; color:#166534; }
.rp-sev.sev-info { background:#dbeafe; color:#1e3a8a; }
.rp-pager { display:flex; justify-content:space-between; align-items:center; margin-top:10px; }
.rp-pager a { text-decoration:none; }
.rp-chip { background:#eff6ff; color:#1e3a8a; border:1px solid #bfdbfe; padding:4px 10px; border-radius:999px; font-size:12px; }
.rp-link { color:#1d4ed8; text-decoration:none; }
.rp-link:hover { text-decoration:underline; }
.rp-readiness-grid { display:grid; grid-template-columns:repeat(6,minmax(120px,1fr)); gap:10px; margin-bottom:10px; }
.rp-kpi { background:#f8fbff; border:1px solid #dce7f7; border-radius:10px; padding:10px; }
.rp-kpi .k { font-size:11px; color:#587295; text-transform:uppercase; }
.rp-kpi .v { font-size:22px; color:#0a244a; font-weight:700; line-height:1.2; }
.rp-impact-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.rp-mini-table { width:100%; border-collapse: collapse; }
.rp-mini-table th,.rp-mini-table td { border:1px solid #e9effb; padding:6px; font-size:12px; }
.rp-mini-table th { background:#f4f8ff; color:#39567e; }
@media (max-width: 1200px) {
  .rp-filters { grid-template-columns: repeat(2, minmax(160px,1fr)); }
  .rp-readiness-grid { grid-template-columns:repeat(3,minmax(120px,1fr)); }
  .rp-impact-grid { grid-template-columns:1fr; }
}
</style>
</head>
<body class="cbp-spmenu-push">
<div class="main-content">
<?php
include_once('../../includes/radiographer-heading.php');
include_once('../../includes/radiographer-sidebar.php');
?>
<div id="page-wrapper">
  <div class="rp-wrap">
    <div class="rp-head">
      <div>
        <h1 class="rp-title">API Audit Logs</h1>
        <p class="rp-sub">Track access, failures, and user actions across remote reporting endpoints.</p>
      </div>
      <a href="<?php echo h($csvUrl); ?>" class="btn btn-default"><i class="fa fa-download"></i> Export CSV</a>
    </div>

    <div class="rp-card">
      <h4 style="margin-top:0; color:#0a244a;">ACL Readiness</h4>
      <p class="rp-sub" style="margin-bottom:10px;">Use monitor/fail-open metrics to decide when to move safely to full enforce mode.</p>
      <div class="rp-readiness-grid">
        <div class="rp-kpi"><div class="k">24h Monitor Allow</div><div class="v"><?php echo (int)$acl24Monitor; ?></div></div>
        <div class="rp-kpi"><div class="k">24h Denied</div><div class="v"><?php echo (int)$acl24Denied; ?></div></div>
        <div class="rp-kpi"><div class="k">24h Fail-Open</div><div class="v"><?php echo (int)$acl24FailOpen; ?></div></div>
        <div class="rp-kpi"><div class="k">7d Monitor Allow</div><div class="v"><?php echo (int)$acl7Monitor; ?></div></div>
        <div class="rp-kpi"><div class="k">7d Denied</div><div class="v"><?php echo (int)$acl7Denied; ?></div></div>
        <div class="rp-kpi"><div class="k">7d Fail-Open</div><div class="v"><?php echo (int)$acl7FailOpen; ?></div></div>
      </div>
      <div class="rp-impact-grid">
        <div>
          <h5 style="margin:8px 0; color:#16365c;">Top impacted users (24h)</h5>
          <table class="rp-mini-table">
            <thead><tr><th>User</th><th>Count</th></tr></thead>
            <tbody>
            <?php if (!empty($topUsers24)) { foreach ($topUsers24 as $r) { ?>
              <tr><td><?php echo h($r['label']); ?></td><td><?php echo (int)$r['count']; ?></td></tr>
            <?php }} else { ?>
              <tr><td colspan="2">No ACL impact events in last 24h.</td></tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
        <div>
          <h5 style="margin:8px 0; color:#16365c;">Top impacted studies (24h)</h5>
          <table class="rp-mini-table">
            <thead><tr><th>Study</th><th>Count</th></tr></thead>
            <tbody>
            <?php if (!empty($topStudies24)) { foreach ($topStudies24 as $r) { ?>
              <tr><td><?php echo h($r['label']); ?></td><td><?php echo (int)$r['count']; ?></td></tr>
            <?php }} else { ?>
              <tr><td colspan="2">No ACL impact events in last 24h.</td></tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="rp-impact-grid" style="margin-top:12px;">
        <div>
          <h5 style="margin:8px 0; color:#16365c;">Top impacted users (7d)</h5>
          <table class="rp-mini-table">
            <thead><tr><th>User</th><th>Count</th></tr></thead>
            <tbody>
            <?php if (!empty($topUsers7)) { foreach ($topUsers7 as $r) { ?>
              <tr><td><?php echo h($r['label']); ?></td><td><?php echo (int)$r['count']; ?></td></tr>
            <?php }} else { ?>
              <tr><td colspan="2">No ACL impact events in last 7 days.</td></tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
        <div>
          <h5 style="margin:8px 0; color:#16365c;">Top impacted studies (7d)</h5>
          <table class="rp-mini-table">
            <thead><tr><th>Study</th><th>Count</th></tr></thead>
            <tbody>
            <?php if (!empty($topStudies7)) { foreach ($topStudies7 as $r) { ?>
              <tr><td><?php echo h($r['label']); ?></td><td><?php echo (int)$r['count']; ?></td></tr>
            <?php }} else { ?>
              <tr><td colspan="2">No ACL impact events in last 7 days.</td></tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="rp-card">
      <form method="get" action="">
        <div class="rp-filters">
          <div>
            <label>From date</label>
            <input type="date" class="form-control" name="from_date" value="<?php echo h($fromDate); ?>">
          </div>
          <div>
            <label>To date</label>
            <input type="date" class="form-control" name="to_date" value="<?php echo h($toDate); ?>">
          </div>
          <div>
            <label>Success</label>
            <select class="form-control" name="success">
              <option value="all" <?php echo $success === 'all' ? 'selected' : ''; ?>>All</option>
              <option value="1" <?php echo $success === '1' ? 'selected' : ''; ?>>Success</option>
              <option value="0" <?php echo $success === '0' ? 'selected' : ''; ?>>Failed</option>
            </select>
          </div>
          <div>
            <label>Page size</label>
            <select class="form-control" name="page_size">
              <option value="25" <?php echo $pageSize === 25 ? 'selected' : ''; ?>>25</option>
              <option value="50" <?php echo $pageSize === 50 ? 'selected' : ''; ?>>50</option>
              <option value="100" <?php echo $pageSize === 100 ? 'selected' : ''; ?>>100</option>
              <option value="200" <?php echo $pageSize === 200 ? 'selected' : ''; ?>>200</option>
            </select>
          </div>
          <div>
            <label>Endpoint</label>
            <input type="text" class="form-control" name="endpoint" value="<?php echo h($endpoint); ?>" placeholder="/remotepanda/api/...">
          </div>
          <div>
            <label>Event type</label>
            <input type="text" class="form-control" name="event_type" value="<?php echo h($eventType); ?>" placeholder="study_access_denied">
          </div>
          <div>
            <label>Username</label>
            <input type="text" class="form-control" name="username" value="<?php echo h($username); ?>" placeholder="radiologist">
          </div>
          <div>
            <label>Study ID</label>
            <input type="text" class="form-control" name="studyint" value="<?php echo h($studyint); ?>" placeholder="studyint">
          </div>
          <div>
            <label>Request ID</label>
            <input type="text" class="form-control" name="request_id" value="<?php echo h($requestId); ?>" placeholder="click any request id below">
          </div>
        </div>
        <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;">
          <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Apply</button>
          <a href="api-audit.php" class="btn btn-default">Reset</a>
          <?php if ($requestId !== '') { ?>
            <a href="<?php echo h($clearRequestUrl); ?>" class="btn btn-warning btn-sm">Clear Request Drill-down</a>
          <?php } ?>
        </div>
      </form>
    </div>

    <div class="rp-card">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; gap:10px; flex-wrap:wrap;">
        <strong>Total rows: <?php echo (int)$totalRows; ?></strong>
        <span>Page <?php echo (int)$page; ?> of <?php echo (int)$totalPages; ?></span>
        <?php if ($requestId !== '') { ?>
          <span class="rp-chip">Request drill-down: <?php echo h($requestId); ?></span>
        <?php } ?>
      </div>
      <div class="rp-table-wrap">
        <table class="rp-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Timestamp</th>
              <th>Endpoint</th>
              <th>Event</th>
              <th>Severity</th>
              <th>Method</th>
              <th>Study</th>
              <th>User</th>
              <th>Status</th>
              <th>Success</th>
              <th>IP</th>
              <th>Request ID</th>
              <th>Message</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($listRes && mysqli_num_rows($listRes) > 0) { ?>
            <?php while ($row = mysqli_fetch_assoc($listRes)) { ?>
              <?php
                $rowReqId = (string)($row['request_id'] ?? '');
                $rowSuccess = (int)($row['success'] ?? 0);
                $rowStatus = (int)($row['status_code'] ?? 0);
                $sev = eventSeverity((string)($row['event_type'] ?? ''), $rowSuccess, $rowStatus);
                $drillUrl = '?' . http_build_query(array_merge($filterBase, ['request_id' => $rowReqId, 'page' => 1]));
              ?>
              <tr>
                <td><?php echo (int)$row['id']; ?></td>
                <td><?php echo h($row['created_at']); ?></td>
                <td><?php echo h($row['endpoint']); ?></td>
                <td><?php echo h($row['event_type']); ?></td>
                <td><span class="rp-sev <?php echo h($sev[1]); ?>"><?php echo h($sev[0]); ?></span></td>
                <td><?php echo h($row['http_method']); ?></td>
                <td><?php echo h($row['studyint']); ?></td>
                <td><?php echo h($row['username']); ?><br><small><?php echo h($row['user_type']); ?></small></td>
                <td><?php echo $rowStatus; ?></td>
                <td>
                  <?php if ($rowSuccess === 1) { ?>
                    <span class="rp-badge-ok">YES</span>
                  <?php } else { ?>
                    <span class="rp-badge-fail">NO</span>
                  <?php } ?>
                </td>
                <td><?php echo h($row['client_ip']); ?></td>
                <td><a class="rp-link" href="<?php echo h($drillUrl); ?>"><small><?php echo h($rowReqId); ?></small></a></td>
                <td><?php echo h($row['message']); ?></td>
              </tr>
            <?php } ?>
          <?php } else { ?>
            <tr><td colspan="13">No audit log rows found for the selected filters.</td></tr>
          <?php } ?>
          </tbody>
        </table>
      </div>

      <div class="rp-pager">
        <div>
          <?php if ($page > 1) { ?>
            <a class="btn btn-default btn-sm" href="<?php echo h(buildPageUrl($filterBase, $page - 1)); ?>">Previous</a>
          <?php } ?>
        </div>
        <div>
          <?php if ($page < $totalPages) { ?>
            <a class="btn btn-default btn-sm" href="<?php echo h(buildPageUrl($filterBase, $page + 1)); ?>">Next</a>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
</body>
</html>
