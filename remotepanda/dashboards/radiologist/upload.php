<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['zipFile'])) {
    $uploadDir = 'uploads/';
    $zipFile = $_FILES['zipFile']['tmp_name'];
    $zipName = $_FILES['zipFile']['name'];

    // Move the uploaded file
    $targetPath = $uploadDir . basename($zipName);
    move_uploaded_file($zipFile, $targetPath);

    // Extract the zip file
    $zip = new ZipArchive();
    if ($zip->open($targetPath) === TRUE) {
        $zip->extractTo($uploadDir);
        $zip->close();
        echo json_encode(['status' => 'success', 'message' => 'Files uploaded and extracted.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to extract zip file.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded.']);
}
?>
