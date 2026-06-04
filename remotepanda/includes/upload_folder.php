<?php
require_once '../../includes/dbconnection.php';

header('Content-Type: application/json');

// Base directory where Sante stores DICOM studies
$base_directory = "C:/Sante Server DB";

// Get studyint
$studyint = isset($_GET['studyint']) ? trim($_GET['studyint']) : '';

if ($studyint === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Study identifier missing']);
    exit;
}

// Where ZIP files will live (web-accessible)
$zipDirectory = "../../extensions/uploads";
$zipFileName  = $studyint . ".zip";
$zipFilePath  = $zipDirectory . "/" . $zipFileName;

// Ensure upload directory exists
if (!is_dir($zipDirectory)) {
    mkdir($zipDirectory, 0775, true);
}

/**
 * Recursively search for a folder by name
 */
function searchFolder($base_directory, $folder_name) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base_directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isDir() && $file->getFilename() === $folder_name) {
            return $file->getPathname();
        }
    }
    return false;
}

// Locate study folder
$folder_path = searchFolder($base_directory, $studyint);

if (!$folder_path) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Study folder not found']);
    exit;
}

// Create ZIP
$zip = new ZipArchive();
if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Could not create ZIP']);
    exit;
}

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($folder_path, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $file) {
    if (!$file->isDir()) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($folder_path) + 1);
        $zip->addFile($filePath, $relativePath);
    }
}

$zip->close();

/**
 * OPTIONAL BUT RECOMMENDED:
 * Register ZIP in DB so radiologists can download later
 */
$stmt = $con->prepare("UPDATE study SET zip_file = ? WHERE studyint = ?");
if ($stmt) {
    $webPath = "extensions/uploads/" . $zipFileName;
    $stmt->bind_param("ss", $webPath, $studyint);
    $stmt->execute();
    $stmt->close();
}

// Success response
echo json_encode([
    'success' => true,
    'message' => 'Study prepared successfully',
    'download_url' => $webPath
]);

