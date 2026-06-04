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

$currentUser = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : array();
$userId = (int)($currentUser['id'] ?? 0);

if ($userId <= 0) {
    http_response_code(403);
    exit('Profile could not be loaded for this session. Please log out and sign in again.');
}

function rp_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rp_profile_column_exists($con, $table, $column)
{
    $table = mysqli_real_escape_string($con, $table);
    $column = mysqli_real_escape_string($con, $column);
    $result = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function rp_radiologist_profile_ensure_schema($con)
{
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS radiologist_profiles (
        id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        display_name VARCHAR(191) NOT NULL DEFAULT '',
        headline VARCHAR(255) NOT NULL DEFAULT '',
        specialties VARCHAR(255) NOT NULL DEFAULT '',
        modalities VARCHAR(255) NOT NULL DEFAULT '',
        location VARCHAR(191) NOT NULL DEFAULT '',
        turnaround_hours INT NOT NULL DEFAULT 24,
        currency_code VARCHAR(10) NOT NULL DEFAULT 'USD',
        default_fee DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        bio TEXT DEFAULT NULL,
        is_available TINYINT(1) NOT NULL DEFAULT 1,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $profileAdds = array(
        'qualifications' => "ALTER TABLE radiologist_profiles ADD COLUMN qualifications VARCHAR(255) NOT NULL DEFAULT ''",
        'license_number' => "ALTER TABLE radiologist_profiles ADD COLUMN license_number VARCHAR(120) NOT NULL DEFAULT ''",
        'default_workflow' => "ALTER TABLE radiologist_profiles ADD COLUMN default_workflow VARCHAR(40) NOT NULL DEFAULT 'type_own'",
        'signature_text' => "ALTER TABLE radiologist_profiles ADD COLUMN signature_text VARCHAR(255) NOT NULL DEFAULT ''",
    );
    foreach ($profileAdds as $column => $sql) {
        if (!rp_profile_column_exists($con, 'radiologist_profiles', $column)) {
            mysqli_query($con, $sql);
        }
    }

    if (!rp_profile_column_exists($con, 'users', 'profile_image')) {
        mysqli_query($con, "ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL");
    }
}

function rp_fetch_current_radiologist($con, $userId)
{
    $stmt = mysqli_prepare($con, "SELECT * FROM users WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

function rp_fetch_radiologist_profile($con, $userId)
{
    $stmt = mysqli_prepare($con, "SELECT * FROM radiologist_profiles WHERE user_id = ? LIMIT 1");
    if (!$stmt) {
        return array();
    }
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : array();
    mysqli_stmt_close($stmt);
    return $row ?: array();
}

function rp_profile_image_url($path)
{
    $path = trim((string)$path);
    if ($path === '') {
        return '../../extensions/images/download (1).png';
    }
    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }
    return '../../' . ltrim(str_replace('\\', '/', $path), '/');
}

rp_radiologist_profile_ensure_schema($con);
rp_profile_options_ensure_schema($con);

$alerts = array();
$user = rp_fetch_current_radiologist($con, $userId);
if (!$user) {
    http_response_code(404);
    exit('User not found.');
}

$profile = rp_fetch_radiologist_profile($con, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['profile_action'] ?? '');

    if ($action === 'save_profile') {
        $username = trim((string)($_POST['username'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $displayName = trim((string)($_POST['display_name'] ?? ''));
        $specialties = rp_profile_options_clean_selection($con, 'specialty', $_POST['specialty'] ?? array());
        $qualifications = trim((string)($_POST['qualifications'] ?? ''));
        $licenseNumber = trim((string)($_POST['license_number'] ?? ''));
        $reportingRate = (float)($_POST['reporting_rate'] ?? 0);
        $defaultWorkflow = (string)($_POST['default_workflow'] ?? 'type_own');
        $bio = trim((string)($_POST['bio'] ?? ''));
        $signatureText = trim((string)($_POST['signature_text'] ?? ''));

        if ($username === '') {
            $alerts[] = array('type' => 'error', 'text' => 'Name is required.');
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $alerts[] = array('type' => 'error', 'text' => 'Please enter a valid email address.');
        } else {
            $stmt = mysqli_prepare($con, "UPDATE users SET username = ?, email = ?, MobileNumber = ? WHERE id = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sssi', $username, $email, $phone, $userId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            $headline = $specialties !== '' ? $specialties : 'Radiologist';
            $modalities = rp_profile_options_clean_selection($con, 'modality', $_POST['modalities'] ?? array());
            $location = trim((string)($_POST['location'] ?? ''));
            $turnaroundHours = max(1, (int)($_POST['turnaround_hours'] ?? 24));
            $currencyCode = strtoupper(trim((string)($_POST['currency_code'] ?? 'USD')));
            if ($currencyCode === '') {
                $currencyCode = 'USD';
            }

            $stmt = mysqli_prepare($con, "INSERT INTO radiologist_profiles
                (user_id, display_name, headline, specialties, modalities, location, turnaround_hours, currency_code, default_fee, qualifications, license_number, default_workflow, bio, signature_text)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    display_name = VALUES(display_name),
                    headline = VALUES(headline),
                    specialties = VALUES(specialties),
                    modalities = VALUES(modalities),
                    location = VALUES(location),
                    turnaround_hours = VALUES(turnaround_hours),
                    currency_code = VALUES(currency_code),
                    default_fee = VALUES(default_fee),
                    qualifications = VALUES(qualifications),
                    license_number = VALUES(license_number),
                    default_workflow = VALUES(default_workflow),
                    bio = VALUES(bio),
                    signature_text = VALUES(signature_text)");
            if ($stmt) {
                mysqli_stmt_bind_param(
                    $stmt,
                    'isssssisdsssss',
                    $userId,
                    $displayName,
                    $headline,
                    $specialties,
                    $modalities,
                    $location,
                    $turnaroundHours,
                    $currencyCode,
                    $reportingRate,
                    $qualifications,
                    $licenseNumber,
                    $defaultWorkflow,
                    $bio,
                    $signatureText
                );
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            $_SESSION['user']['username'] = $username;
            $_SESSION['user']['email'] = $email;
            $alerts[] = array('type' => 'success', 'text' => 'Profile updated.');
        }
    }

    if ($action === 'upload_photo') {
        if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
            $alerts[] = array('type' => 'error', 'text' => 'Please choose a profile image to upload.');
        } else {
            $file = $_FILES['profile_image'];
            $imageInfo = @getimagesize($file['tmp_name']);
            $allowed = array(
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
            );

            if (!$imageInfo || !isset($allowed[$imageInfo['mime']])) {
                $alerts[] = array('type' => 'error', 'text' => 'Profile image must be JPG, PNG, WEBP, or GIF.');
            } elseif ((int)$file['size'] > 3 * 1024 * 1024) {
                $alerts[] = array('type' => 'error', 'text' => 'Profile image must be smaller than 3 MB.');
            } else {
                $uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profile-images';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }
                $extension = $allowed[$imageInfo['mime']];
                $fileName = 'radiologist-' . $userId . '-' . date('YmdHis') . '.' . $extension;
                $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
                $relativePath = 'uploads/profile-images/' . $fileName;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $stmt = mysqli_prepare($con, "UPDATE users SET profile_image = ? WHERE id = ? LIMIT 1");
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, 'si', $relativePath, $userId);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                    $_SESSION['user']['profile_image'] = $relativePath;
                    $alerts[] = array('type' => 'success', 'text' => 'Profile photo updated.');
                } else {
                    $alerts[] = array('type' => 'error', 'text' => 'Could not save the uploaded image.');
                }
            }
        }
    }

    if ($action === 'change_password') {
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($newPassword === '' || strlen($newPassword) < 8) {
            $alerts[] = array('type' => 'error', 'text' => 'New password must be at least 8 characters.');
        } elseif ($newPassword !== $confirmPassword) {
            $alerts[] = array('type' => 'error', 'text' => 'The new passwords do not match.');
        } elseif (!function_exists('verifyUserPassword') || !verifyUserPassword($currentPassword, (string)$user['password'])) {
            $alerts[] = array('type' => 'error', 'text' => 'Current password is incorrect.');
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($con, "UPDATE users SET password = ?, last_password_reset_at = NOW() WHERE id = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'si', $hash, $userId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $alerts[] = array('type' => 'success', 'text' => 'Password updated.');
            } else {
                $alerts[] = array('type' => 'error', 'text' => 'Could not update password.');
            }
        }
    }

    $user = rp_fetch_current_radiologist($con, $userId);
    $profile = rp_fetch_radiologist_profile($con, $userId);
}

$displayName = (string)($profile['display_name'] ?? '');
if ($displayName === '') {
    $displayName = (string)($user['username'] ?? '');
}
$profileImage = rp_profile_image_url($user['profile_image'] ?? '');
$email = (string)($user['email'] ?? '');
$phone = (string)($user['MobileNumber'] ?? '');
$workflow = (string)($profile['default_workflow'] ?? 'type_own');
$specialtyOptions = rp_profile_options_list($con, 'specialty', true);
$modalityOptions = rp_profile_options_list($con, 'modality', true);
$selectedSpecialties = rp_profile_options_selected_array($profile['specialties'] ?? '');
$selectedModalities = rp_profile_options_selected_array($profile['modalities'] ?? '');
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>RADPANDA | Radiologist Profile</title>
    <link rel="stylesheet" type="text/css" href="../../extensions/font-awesome-4.7.0/css/font-awesome.css">
    <link href="../../extensions/css/bootstrap.css" rel="stylesheet" type="text/css">
    <link href="../../extensions/css/style.css" rel="stylesheet" type="text/css">
    <link href="../../extensions/css/animate.css" rel="stylesheet" type="text/css" media="all">
    <link href="../../extensions/css/custom.css" rel="stylesheet">
    <script src="../../extensions/js/jquery-1.11.1.min.js"></script>
    <script src="../../extensions/js/modernizr.custom.js"></script>
    <script src="../../extensions/js/metisMenu.min.js"></script>
    <script src="../../extensions/js/custom.js"></script>
    <link rel="icon" type="image/x-icon" href="../../extensions/images/favicon.png">
    <style>
        .rp-profile-wrap {
            max-width: 1220px;
            margin: 0 auto;
            padding: 28px 18px 60px;
        }
        .rp-profile-hero,
        .rp-profile-card {
            background: #fff;
            border: 1px solid #d7e7fb;
            border-radius: 10px;
            box-shadow: 0 12px 28px rgba(1, 21, 42, 0.08);
        }
        .rp-profile-hero {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: center;
            padding: 24px;
            margin-bottom: 18px;
        }
        .rp-profile-person {
            display: flex;
            gap: 18px;
            align-items: center;
        }
        .rp-profile-avatar {
            width: 94px;
            height: 94px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #d9fff0;
            background: #f1f6fd;
        }
        .rp-profile-title h1 {
            margin: 0 0 6px;
            font-size: 32px;
            color: #01152a;
            font-weight: 800;
        }
        .rp-profile-title p {
            margin: 0;
            color: #49617f;
        }
        .rp-profile-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 8px 12px;
            background: #e8f7ff;
            color: #005e9f;
            font-weight: 700;
            margin-top: 10px;
        }
        .rp-profile-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(300px, 1fr);
            gap: 18px;
        }
        .rp-profile-card {
            margin-bottom: 18px;
            overflow: hidden;
        }
        .rp-profile-card h2 {
            margin: 0;
            padding: 16px 20px;
            font-size: 18px;
            background: #01152a;
            color: #fff;
            font-weight: 800;
        }
        .rp-profile-card-body {
            padding: 20px;
        }
        .rp-field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }
        .rp-field-full {
            grid-column: 1 / -1;
        }
        .rp-profile-card label {
            display: block;
            margin-bottom: 7px;
            color: #01152a;
            font-weight: 800;
            font-size: 13px;
        }
        .rp-profile-card input,
        .rp-profile-card select,
        .rp-profile-card textarea {
            width: 100%;
            border: 1px solid #cbdcf0;
            border-radius: 8px;
            padding: 11px 12px;
            color: #01152a;
            background: #fff;
        }
        .rp-profile-card textarea {
            min-height: 116px;
            resize: vertical;
        }
        .rp-profile-card select[multiple] {
            min-height: 126px;
            padding: 8px;
        }
        .rp-profile-card select[multiple] option {
            padding: 7px 8px;
            border-radius: 6px;
        }
        .rp-profile-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
            border-top: 1px solid #e6eef8;
            padding-top: 16px;
            margin-top: 18px;
        }
        .rp-btn {
            border: 0;
            border-radius: 999px;
            padding: 10px 18px;
            font-weight: 800;
            cursor: pointer;
        }
        .rp-btn-primary {
            background: #01152a;
            color: #fff;
        }
        .rp-btn-red {
            background: #ed1b24;
            color: #fff;
        }
        .rp-btn-light {
            background: #eef5ff;
            color: #0453b5;
            border: 1px solid #cde1fb;
        }
        .rp-alert {
            border-radius: 9px;
            padding: 13px 15px;
            margin-bottom: 14px;
            font-weight: 700;
        }
        .rp-alert-success {
            background: #e8f8ee;
            color: #006b2b;
            border: 1px solid #bfe9cd;
        }
        .rp-alert-error {
            background: #fff1f1;
            color: #b00020;
            border: 1px solid #ffc9c9;
        }
        .rp-profile-summary {
            display: grid;
            gap: 12px;
        }
        .rp-summary-item {
            border: 1px solid #dbe9fb;
            background: #f8fbff;
            border-radius: 9px;
            padding: 13px;
        }
        .rp-summary-item span {
            display: block;
            color: #6a7f9c;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .rp-summary-item strong {
            color: #01152a;
        }
        .rp-help-text {
            color: #5d7190;
            font-size: 12px;
            margin-top: 7px;
        }
        @media (max-width: 900px) {
            .rp-profile-hero,
            .rp-profile-person {
                align-items: flex-start;
                flex-direction: column;
            }
            .rp-profile-grid,
            .rp-field-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="cbp-spmenu-push">
<div class="main-content">
    <?php
    include_once('../../includes/radiographer-sidebar.php');
    include_once('../../includes/radiographer-heading.php');
    ?>
    <div id="page-wrapper">
        <div class="main-page rp-profile-wrap">
            <?php foreach ($alerts as $alert): ?>
                <div class="rp-alert rp-alert-<?php echo rp_h($alert['type']); ?>">
                    <?php echo rp_h($alert['text']); ?>
                </div>
            <?php endforeach; ?>

            <section class="rp-profile-hero">
                <div class="rp-profile-person">
                    <img class="rp-profile-avatar" src="<?php echo rp_h($profileImage); ?>" alt="Profile image">
                    <div class="rp-profile-title">
                        <h1><?php echo rp_h($displayName); ?></h1>
                        <p><?php echo rp_h($email !== '' ? $email : 'No email added'); ?></p>
                        <span class="rp-profile-badge"><i class="fa fa-user-md" style="margin-right: 7px;"></i> Radiologist</span>
                    </div>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="profile_action" value="upload_photo">
                    <label for="profile_image">Profile photo</label>
                    <input type="file" name="profile_image" id="profile_image" accept="image/*">
                    <div class="rp-profile-actions" style="border-top:0;margin-top:10px;padding-top:0;">
                        <button class="rp-btn rp-btn-light" type="submit"><i class="fa fa-camera"></i> Update Photo</button>
                    </div>
                </form>
            </section>

            <div class="rp-profile-grid">
                <div>
                    <section class="rp-profile-card">
                        <h2>Account & Professional Profile</h2>
                        <div class="rp-profile-card-body">
                            <form method="post">
                                <input type="hidden" name="profile_action" value="save_profile">
                                <div class="rp-field-grid">
                                    <div>
                                        <label for="username">Account Name</label>
                                        <input type="text" id="username" name="username" value="<?php echo rp_h($user['username'] ?? ''); ?>" required>
                                    </div>
                                    <div>
                                        <label for="display_name">Display Name</label>
                                        <input type="text" id="display_name" name="display_name" value="<?php echo rp_h($profile['display_name'] ?? ''); ?>" placeholder="e.g. Dr Mark Nyathi">
                                    </div>
                                    <div>
                                        <label for="email">Email</label>
                                        <input type="email" id="email" name="email" value="<?php echo rp_h($email); ?>">
                                    </div>
                                    <div>
                                        <label for="phone">Mobile Number</label>
                                        <input type="text" id="phone" name="phone" value="<?php echo rp_h($phone); ?>" placeholder="+263 ...">
                                    </div>
                                    <div>
                                        <label for="specialty">Specialty</label>
                                        <select id="specialty" name="specialty[]" multiple size="5">
                                            <?php foreach ($specialtyOptions as $option): ?>
                                                <?php $label = (string)$option['option_label']; ?>
                                                <option value="<?php echo rp_h($label); ?>" <?php echo in_array($label, $selectedSpecialties, true) ? 'selected' : ''; ?>>
                                                    <?php echo rp_h($label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="rp-help-text">Pick one or more. Admins manage this list under System > Profile Options.</p>
                                    </div>
                                    <div>
                                        <label for="modalities">Modalities</label>
                                        <select id="modalities" name="modalities[]" multiple size="5">
                                            <?php foreach ($modalityOptions as $option): ?>
                                                <?php $label = (string)$option['option_label']; ?>
                                                <option value="<?php echo rp_h($label); ?>" <?php echo in_array($label, $selectedModalities, true) ? 'selected' : ''; ?>>
                                                    <?php echo rp_h($label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="rp-help-text">Hold Ctrl to select more than one modality.</p>
                                    </div>
                                    <div>
                                        <label for="qualifications">Qualifications</label>
                                        <input type="text" id="qualifications" name="qualifications" value="<?php echo rp_h($profile['qualifications'] ?? ''); ?>" placeholder="MBChB, MMed Radiology...">
                                    </div>
                                    <div>
                                        <label for="license_number">License / Registration Number</label>
                                        <input type="text" id="license_number" name="license_number" value="<?php echo rp_h($profile['license_number'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label for="reporting_rate">Default Reporting Fee</label>
                                        <input type="number" step="0.01" min="0" id="reporting_rate" name="reporting_rate" value="<?php echo rp_h($profile['default_fee'] ?? '0.00'); ?>">
                                    </div>
                                    <div>
                                        <label for="currency_code">Currency</label>
                                        <input type="text" id="currency_code" name="currency_code" value="<?php echo rp_h($profile['currency_code'] ?? 'USD'); ?>" placeholder="USD">
                                    </div>
                                    <div>
                                        <label for="location">Location</label>
                                        <input type="text" id="location" name="location" value="<?php echo rp_h($profile['location'] ?? ''); ?>" placeholder="City / country">
                                    </div>
                                    <div>
                                        <label for="turnaround_hours">Default Turnaround Hours</label>
                                        <input type="number" min="1" id="turnaround_hours" name="turnaround_hours" value="<?php echo rp_h($profile['turnaround_hours'] ?? '24'); ?>">
                                    </div>
                                    <div>
                                        <label for="default_workflow">Preferred Reporting Workflow</label>
                                        <select id="default_workflow" name="default_workflow">
                                            <option value="type_own" <?php echo $workflow === 'type_own' ? 'selected' : ''; ?>>Type my own reports</option>
                                            <option value="with_typist" <?php echo $workflow === 'with_typist' ? 'selected' : ''; ?>>Dictate and use my typist pool</option>
                                            <option value="either" <?php echo $workflow === 'either' ? 'selected' : ''; ?>>Either workflow</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="signature_text">Report Signature</label>
                                        <input type="text" id="signature_text" name="signature_text" value="<?php echo rp_h($profile['signature_text'] ?? ''); ?>" placeholder="Name and credentials for reports">
                                    </div>
                                    <div class="rp-field-full">
                                        <label for="bio">Profile Notes</label>
                                        <textarea id="bio" name="bio" placeholder="Short profile for clinics and your internal team."><?php echo rp_h($profile['bio'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <div class="rp-profile-actions">
                                    <button class="rp-btn rp-btn-primary" type="submit"><i class="fa fa-save"></i> Save Profile</button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <section class="rp-profile-card">
                        <h2>Security</h2>
                        <div class="rp-profile-card-body">
                            <form method="post">
                                <input type="hidden" name="profile_action" value="change_password">
                                <div class="rp-field-grid">
                                    <div>
                                        <label for="current_password">Current Password</label>
                                        <input type="password" id="current_password" name="current_password" autocomplete="current-password">
                                    </div>
                                    <div>
                                        <label for="new_password">New Password</label>
                                        <input type="password" id="new_password" name="new_password" autocomplete="new-password">
                                    </div>
                                    <div>
                                        <label for="confirm_password">Confirm New Password</label>
                                        <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
                                    </div>
                                </div>
                                <p class="rp-help-text">Use at least 8 characters. Existing sessions stay active until logout.</p>
                                <div class="rp-profile-actions">
                                    <button class="rp-btn rp-btn-red" type="submit"><i class="fa fa-lock"></i> Update Password</button>
                                </div>
                            </form>
                        </div>
                    </section>
                </div>

                <aside>
                    <section class="rp-profile-card">
                        <h2>Profile Summary</h2>
                        <div class="rp-profile-card-body rp-profile-summary">
                            <div class="rp-summary-item">
                                <span>Specialty</span>
                                <strong><?php echo rp_h(($profile['specialties'] ?? '') !== '' ? $profile['specialties'] : 'Not set'); ?></strong>
                            </div>
                            <div class="rp-summary-item">
                                <span>Reporting Fee</span>
                                <strong><?php echo rp_h($profile['currency_code'] ?? 'USD'); ?> <?php echo number_format((float)($profile['default_fee'] ?? 0), 2); ?></strong>
                            </div>
                            <div class="rp-summary-item">
                                <span>Workflow</span>
                                <strong><?php echo rp_h($workflow === 'with_typist' ? 'Dictation + typist pool' : ($workflow === 'either' ? 'Flexible' : 'Types own reports')); ?></strong>
                            </div>
                            <div class="rp-summary-item">
                                <span>Last Password Update</span>
                                <strong><?php echo rp_h(($user['last_password_reset_at'] ?? '') !== '' ? $user['last_password_reset_at'] : 'Not recorded'); ?></strong>
                            </div>
                        </div>
                    </section>

                    <section class="rp-profile-card">
                        <h2>What This Controls</h2>
                        <div class="rp-profile-card-body">
                            <p style="color:#49617f; line-height:1.6; margin:0;">
                                This profile is the foundation for the radiologist marketplace, reporting fees,
                                typist workflow preference, and report signature defaults.
                            </p>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</div>

<script src="../../extensions/js/bootstrap.js"></script>
</body>
</html>
