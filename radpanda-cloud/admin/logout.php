<?php
require_once __DIR__ . '/../includes/admin_auth.php';
rp_cloud_admin_logout();
header('Location: ' . rp_cloud_admin_path('login.php'));
exit;
?>
