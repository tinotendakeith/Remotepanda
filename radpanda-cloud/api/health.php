<?php
require_once __DIR__ . '/../includes/api.php';
rp_cloud_ensure_schema($con);
rp_cloud_json(array(
    'success' => true,
    'service' => 'radpanda-cloud',
    'status' => 'ok',
    'time' => date('c')
));
?>
