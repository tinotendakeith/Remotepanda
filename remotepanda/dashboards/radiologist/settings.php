<?php
session_start();
error_reporting(0);

include('../../includes/dbconnection.php');
include('../../functions.php');
include('../../includes/platform_settings.php');

if (!isLoggedIn()) {
    $_SESSION['msg'] = 'You must log in first';
    header('location: ../../index.php');
    exit;
}

rp_remote_settings_ensure($con);

$userType = strtolower((string)($_SESSION['user']['user_type'] ?? $_SESSION['user_type'] ?? ''));
$isAdmin = in_array($userType, ['admin', 'superadmin', 'owner'], true);
$currentUserId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals((string)$_SESSION['csrf_token'], $token)) {
        $flash = 'Invalid request token. Please refresh and try again.';
        $flashType = 'danger';
    } elseif (!$isAdmin) {
        $flash = 'Only admin users can change system settings.';
        $flashType = 'danger';
    } else {
        $pacsBase = trim((string)($_POST['pacs_base_directory'] ?? rp_remote_default_pacs_base_directory()));
        if ($pacsBase === '') {
            $pacsBase = rp_remote_default_pacs_base_directory();
        }

        $aclMode = strtolower(trim((string)($_POST['feature_remote_strict_study_acl_mode'] ?? 'off')));
        if (!in_array($aclMode, ['off', 'monitor', 'enforce'], true)) {
            $aclMode = 'off';
        }

        $flags = [
            'feature_remote_api_enabled',
            'feature_remote_dicom_stream_enabled',
            'feature_remote_zip_export_enabled',
            'feature_remote_study_notes_enabled',
            'pacs_allow_recursive_lookup',
            'feature_remote_strict_study_acl_fail_open',
        ];

        $okAll = true;
        $okAll = rp_remote_setting_set($con, 'pacs_base_directory', $pacsBase, $currentUserId) && $okAll;
        $okAll = rp_remote_setting_set($con, 'feature_remote_strict_study_acl_mode', $aclMode, $currentUserId) && $okAll;

        // Legacy compatibility flag kept in sync.
        $legacyStrict = $aclMode === 'enforce' ? '1' : '0';
        $okAll = rp_remote_setting_set($con, 'feature_remote_strict_study_acl', $legacyStrict, $currentUserId) && $okAll;

        foreach ($flags as $flag) {
            $value = isset($_POST[$flag]) ? '1' : '0';
            $okAll = rp_remote_setting_set($con, $flag, $value, $currentUserId) && $okAll;
        }

        if ($okAll) {
            $flash = 'System settings saved successfully.';
            $flashType = 'success';
        } else {
            $flash = 'Some settings failed to save. Please retry.';
            $flashType = 'danger';
        }
    }
}

$pacsBaseDirectory = rp_remote_get_pacs_base_directory($con);
$featureRemoteApi = rp_remote_feature_enabled($con, 'feature_remote_api_enabled', true);
$featureDicom = rp_remote_feature_enabled($con, 'feature_remote_dicom_stream_enabled', true);
$featureZip = rp_remote_feature_enabled($con, 'feature_remote_zip_export_enabled', true);
$featureNotes = rp_remote_feature_enabled($con, 'feature_remote_study_notes_enabled', true);
$featureRecursiveLookup = rp_remote_allow_recursive_lookup($con);
$aclMode = rp_remote_acl_mode($con);
$aclFailOpen = rp_remote_acl_fail_open($con);

function checkedAttr(bool $value): string
{
    return $value ? 'checked' : '';
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE HTML>
<html>
<head>
<title>RADPANDA | Remote System Settings</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="../../extensions/css/bootstrap.css" rel="stylesheet" type="text/css" />
<link href="../../extensions/css/style.css" rel="stylesheet" type="text/css" />
<link href="../../extensions/css/custom.css" rel="stylesheet" type="text/css" />
<link rel="icon" type="image/x-icon" href="../../extensions/images/favicon.png">
<script src="../../extensions/js/jquery-1.11.1.min.js"></script>
<style>
  .rp-wrap { padding: 16px; }
  .rp-card { background: #fff; border: 1px solid #dce7f7; border-radius: 12px; padding: 16px; margin-bottom: 14px; }
  .rp-title { margin-top: 0; color: #0a244a; font-weight: 700; }
  .rp-muted { color: #5f7390; }
  .rp-row { margin-bottom: 10px; }
  .rp-switch { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
  .rp-note { background:#eff6ff; border:1px solid #bfdbfe; color:#1e3a8a; border-radius:8px; padding:10px; margin-top:10px; }
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
    <h3 class="rp-title">Remote Platform Settings</h3>
    <p class="rp-muted">Configure PACS storage and feature flags without changing code. Defaults preserve current behavior.</p>

    <?php if ($flash !== '') { ?>
      <div class="alert alert-<?php echo h($flashType); ?>"><?php echo h($flash); ?></div>
    <?php } ?>

    <?php if (!$isAdmin) { ?>
      <div class="alert alert-info">You have read-only access. Contact an admin account to modify these settings.</div>
    <?php } ?>

    <form method="post" action="">
      <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">

      <div class="rp-card">
        <h4 class="rp-title">PACS Storage</h4>
        <div class="rp-row">
          <label for="pacs_base_directory">PACS base directory</label>
          <input type="text" class="form-control" id="pacs_base_directory" name="pacs_base_directory" value="<?php echo h($pacsBaseDirectory); ?>" <?php echo $isAdmin ? '' : 'disabled'; ?> >
          <small class="rp-muted">Default on this server: <?php echo h(rp_remote_default_pacs_base_directory()); ?></small>
        </div>
        <label class="rp-switch">
          <input type="checkbox" name="pacs_allow_recursive_lookup" <?php echo checkedAttr($featureRecursiveLookup); ?> <?php echo $isAdmin ? '' : 'disabled'; ?>>
          <span>Allow recursive folder lookup (slower, useful when studies are nested)</span>
        </label>
      </div>

      <div class="rp-card">
        <h4 class="rp-title">Remote API Flags</h4>
        <label class="rp-switch"><input type="checkbox" name="feature_remote_api_enabled" <?php echo checkedAttr($featureRemoteApi); ?> <?php echo $isAdmin ? '' : 'disabled'; ?>> <span>Enable Remote API</span></label>
        <label class="rp-switch"><input type="checkbox" name="feature_remote_dicom_stream_enabled" <?php echo checkedAttr($featureDicom); ?> <?php echo $isAdmin ? '' : 'disabled'; ?>> <span>Enable DICOM file streaming/list endpoints</span></label>
        <label class="rp-switch"><input type="checkbox" name="feature_remote_zip_export_enabled" <?php echo checkedAttr($featureZip); ?> <?php echo $isAdmin ? '' : 'disabled'; ?>> <span>Enable study ZIP exports</span></label>
        <label class="rp-switch"><input type="checkbox" name="feature_remote_study_notes_enabled" <?php echo checkedAttr($featureNotes); ?> <?php echo $isAdmin ? '' : 'disabled'; ?>> <span>Enable study notes API</span></label>
      </div>

      <div class="rp-card">
        <h4 class="rp-title">Study Access Control Rollout</h4>
        <div class="rp-row">
          <label for="feature_remote_strict_study_acl_mode">ACL Mode</label>
          <select class="form-control" id="feature_remote_strict_study_acl_mode" name="feature_remote_strict_study_acl_mode" <?php echo $isAdmin ? '' : 'disabled'; ?>>
            <option value="off" <?php echo $aclMode === 'off' ? 'selected' : ''; ?>>Off (no assignment enforcement)</option>
            <option value="monitor" <?php echo $aclMode === 'monitor' ? 'selected' : ''; ?>>Monitor (log violations, allow access)</option>
            <option value="enforce" <?php echo $aclMode === 'enforce' ? 'selected' : ''; ?>>Enforce (block unassigned access)</option>
          </select>
        </div>
        <label class="rp-switch">
          <input type="checkbox" name="feature_remote_strict_study_acl_fail_open" <?php echo checkedAttr($aclFailOpen); ?> <?php echo $isAdmin ? '' : 'disabled'; ?>>
          <span>Fail-open compatibility (in enforce mode, allow access if ACL columns/checks are unavailable)</span>
        </label>
        <div class="rp-note">
          Recommended rollout: start in <strong>Monitor</strong>, verify `study_acl_monitor_allow` events in API Audit, then move to <strong>Enforce</strong> per clinic.
        </div>
      </div>

      <?php if ($isAdmin) { ?>
        <button type="submit" class="btn btn-primary">Save Settings</button>
      <?php } ?>
    </form>
  </div>
</div>
</div>
</body>
</html>
