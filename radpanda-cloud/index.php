<?php
require_once __DIR__ . '/includes/api.php';
rp_cloud_ensure_schema($con);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Radpanda Cloud</title>
    <style>
        body{font-family:Arial,sans-serif;background:#eef5ff;margin:0;color:#061a33}
        .wrap{max-width:920px;margin:60px auto;background:#fff;border:1px solid #cfe0f5;border-radius:10px;padding:28px}
        h1{margin:0 0 8px;font-size:32px}
        .pill{display:inline-block;background:#dcfce7;color:#166534;padding:6px 10px;border-radius:999px;font-weight:700;font-size:12px}
        code{background:#f3f7fc;padding:2px 5px;border-radius:4px}
        .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:22px}
        .card{border:1px solid #d8e5f5;border-radius:8px;padding:14px}
        .muted{color:#52677f}
    </style>
</head>
<body>
<main class="wrap">
    <span class="pill">Local cloud hub ready</span>
    <h1>Radpanda Cloud</h1>
    <p class="muted">This is the reporting gateway layer between clinic nodes and Remotepanda.</p>
    <div class="grid">
        <div class="card"><strong>Upload endpoint</strong><br><code>/radpanda-cloud/api/report-sync-receiver.php</code></div>
        <div class="card"><strong>Return feed</strong><br><code>/radpanda-cloud/api/report-return-feed.php</code></div>
        <div class="card"><strong>Health check</strong><br><code>/radpanda-cloud/api/health.php</code></div>
        <div class="card"><strong>Database</strong><br><code>radpanda_cloud</code></div>
    </div>
</main>
</body>
</html>
