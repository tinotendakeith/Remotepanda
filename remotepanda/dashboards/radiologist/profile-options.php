<?php
session_start();
error_reporting(0);

include('../../includes/dbconnection.php');
include('../../functions.php');
include_once('../../includes/radiologist_profile_options.php');

if (!isLoggedIn()) {
    $_SESSION['msg'] = 'You must log in first';
    header('location: ../../index.php');
    exit;
}

$userType = strtolower((string)($_SESSION['user']['user_type'] ?? $_SESSION['user_type'] ?? ''));
$isAdmin = in_array($userType, array('admin', 'superadmin', 'owner'), true);
if (!$isAdmin) {
    http_response_code(403);
    exit('Only Remotepanda admins can manage profile options.');
}

rp_profile_options_ensure_schema($con);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function rp_po_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals((string)$_SESSION['csrf_token'], $token)) {
        $flash = 'Invalid request token. Please refresh and try again.';
        $flashType = 'danger';
    } else {
        $action = (string)($_POST['option_action'] ?? '');

        if ($action === 'save_option') {
            $id = isset($_POST['option_id']) && $_POST['option_id'] !== '' ? (int)$_POST['option_id'] : null;
            $type = rp_profile_options_normalize_type($_POST['option_type'] ?? '');
            $label = trim((string)($_POST['option_label'] ?? ''));
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if ($type === '' || $label === '') {
                $flash = 'Choose a type and enter an option name.';
                $flashType = 'danger';
            } elseif (rp_profile_options_upsert($con, $type, $label, $sortOrder, $isActive, $id)) {
                $flash = 'Profile option saved.';
            } else {
                $flash = 'Could not save that option. It may already exist under another row.';
                $flashType = 'danger';
            }
        }

        if ($action === 'toggle_option') {
            $id = (int)($_POST['option_id'] ?? 0);
            $active = (int)($_POST['is_active'] ?? 0);
            $stmt = mysqli_prepare($con, "UPDATE radiologist_profile_options SET is_active = ? WHERE id = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ii', $active, $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $flash = $active ? 'Option activated.' : 'Option hidden from radiologist profiles.';
            } else {
                $flash = 'Could not update option status.';
                $flashType = 'danger';
            }
        }
    }
}

$specialties = rp_profile_options_list($con, 'specialty', false);
$modalities = rp_profile_options_list($con, 'modality', false);
?>
<!DOCTYPE HTML>
<html>
<head>
<title>RADPANDA | Profile Options</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="../../extensions/css/bootstrap.css" rel="stylesheet" type="text/css" />
<link href="../../extensions/css/style.css" rel="stylesheet" type="text/css" />
<link href="../../extensions/css/custom.css" rel="stylesheet" type="text/css" />
<link rel="icon" type="image/x-icon" href="../../extensions/images/favicon.png">
<script src="../../extensions/js/jquery-1.11.1.min.js"></script>
<style>
    .rp-options-wrap { padding: 18px; }
    .rp-options-hero {
        background: #fff;
        border: 1px solid #dce7f7;
        border-radius: 14px;
        padding: 20px;
        margin-bottom: 16px;
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: center;
    }
    .rp-options-title { margin: 0 0 5px; color: #01152a; font-weight: 800; }
    .rp-muted { color: #5f7390; }
    .rp-card {
        background: #fff;
        border: 1px solid #dce7f7;
        border-radius: 14px;
        margin-bottom: 16px;
        overflow: hidden;
    }
    .rp-card-head {
        padding: 14px 16px;
        border-bottom: 1px solid #e3eefc;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .rp-card-head h3 { margin: 0; color: #01152a; font-size: 18px; font-weight: 800; }
    .rp-card-body { padding: 16px; }
    .rp-form-grid {
        display: grid;
        grid-template-columns: 170px minmax(220px, 1fr) 120px 110px auto;
        gap: 10px;
        align-items: end;
    }
    .rp-form-grid label {
        color: #0b2545;
        font-weight: 800;
        font-size: 12px;
    }
    .rp-form-grid input,
    .rp-form-grid select {
        width: 100%;
        border: 1px solid #cbdcf0;
        border-radius: 8px;
        padding: 9px 10px;
    }
    .rp-btn {
        border: 0;
        border-radius: 999px;
        padding: 9px 15px;
        font-weight: 800;
        cursor: pointer;
    }
    .rp-btn-primary { background: #01152a; color: #fff; }
    .rp-btn-light { background: #eef5ff; color: #0453b5; border: 1px solid #cde1fb; }
    .rp-btn-warn { background: #fff4d6; color: #925600; border: 1px solid #ffe0a3; }
    .rp-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    .rp-option-list { display: grid; gap: 9px; }
    .rp-option-row {
        border: 1px solid #dce7f7;
        background: #f8fbff;
        border-radius: 10px;
        padding: 12px;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 12px;
        align-items: center;
    }
    .rp-option-name { color: #01152a; font-weight: 900; }
    .rp-badge {
        display: inline-block;
        border-radius: 999px;
        padding: 4px 9px;
        font-size: 11px;
        font-weight: 900;
        margin-left: 6px;
    }
    .rp-badge-on { background: #dcfce7; color: #06712f; }
    .rp-badge-off { background: #f1f5f9; color: #64748b; }
    @media (max-width: 980px) {
        .rp-grid, .rp-form-grid { grid-template-columns: 1fr; }
        .rp-options-hero { align-items: flex-start; flex-direction: column; }
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
    <div class="rp-options-wrap">
        <section class="rp-options-hero">
            <div>
                <h2 class="rp-options-title">Radiologist Profile Options</h2>
                <p class="rp-muted">Control the specialties and modalities radiologists can select on their profiles.</p>
            </div>
            <a class="rp-btn rp-btn-light" href="settings.php">Back to Settings</a>
        </section>

        <?php if ($flash !== '') { ?>
            <div class="alert alert-<?php echo rp_po_h($flashType); ?>"><?php echo rp_po_h($flash); ?></div>
        <?php } ?>

        <section class="rp-card">
            <div class="rp-card-head">
                <h3>Add or Update Option</h3>
            </div>
            <div class="rp-card-body">
                <form method="post" class="rp-form-grid">
                    <input type="hidden" name="csrf_token" value="<?php echo rp_po_h($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="option_action" value="save_option">
                    <div>
                        <label for="option_type">Type</label>
                        <select id="option_type" name="option_type" required>
                            <option value="specialty">Specialty</option>
                            <option value="modality">Modality</option>
                        </select>
                    </div>
                    <div>
                        <label for="option_label">Option name</label>
                        <input type="text" id="option_label" name="option_label" placeholder="e.g. Abdominal Ultrasound" required>
                    </div>
                    <div>
                        <label for="sort_order">Sort</label>
                        <input type="number" id="sort_order" name="sort_order" value="100">
                    </div>
                    <label style="padding-bottom:9px;">
                        <input type="checkbox" name="is_active" checked> Active
                    </label>
                    <button class="rp-btn rp-btn-primary" type="submit">Save Option</button>
                </form>
            </div>
        </section>

        <div class="rp-grid">
            <?php
            $groups = array(
                'Specialties' => $specialties,
                'Modalities' => $modalities,
            );
            foreach ($groups as $title => $rows) {
            ?>
            <section class="rp-card">
                <div class="rp-card-head">
                    <h3><?php echo rp_po_h($title); ?></h3>
                    <span class="rp-muted"><?php echo count($rows); ?> options</span>
                </div>
                <div class="rp-card-body">
                    <div class="rp-option-list">
                        <?php if (!$rows) { ?>
                            <div class="rp-muted">No options yet.</div>
                        <?php } ?>
                        <?php foreach ($rows as $row) { ?>
                            <div class="rp-option-row">
                                <div>
                                    <span class="rp-option-name"><?php echo rp_po_h($row['option_label']); ?></span>
                                    <span class="rp-badge <?php echo (int)$row['is_active'] ? 'rp-badge-on' : 'rp-badge-off'; ?>">
                                        <?php echo (int)$row['is_active'] ? 'Active' : 'Hidden'; ?>
                                    </span>
                                    <div class="rp-muted">Sort <?php echo (int)$row['sort_order']; ?></div>
                                </div>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo rp_po_h($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="option_action" value="toggle_option">
                                    <input type="hidden" name="option_id" value="<?php echo (int)$row['id']; ?>">
                                    <input type="hidden" name="is_active" value="<?php echo (int)$row['is_active'] ? 0 : 1; ?>">
                                    <button class="rp-btn <?php echo (int)$row['is_active'] ? 'rp-btn-warn' : 'rp-btn-light'; ?>" type="submit">
                                        <?php echo (int)$row['is_active'] ? 'Hide' : 'Activate'; ?>
                                    </button>
                                </form>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </section>
            <?php } ?>
        </div>
    </div>
</div>
</div>
<script src="../../extensions/js/bootstrap.js"></script>
</body>
</html>
