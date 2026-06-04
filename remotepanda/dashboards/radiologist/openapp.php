<?php

// Ensure that this script can only be accessed from localhost
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    http_response_code(403); // Forbidden
    echo "Forbidden: This script can only be accessed from localhost.";
    exit;
}

// Command to open the desktop application
$command = 'C:\\Program Files\\Santesoft\\Sante DICOM Viewer Pro\\Sante DICOM Viewer Pro64.exe';

// Execute the command
exec($command, $output, $returnCode);

// Check if the command was executed successfully
if ($returnCode === 0) {
    echo "Desktop application opened successfully.";
} else {
    echo "Error: Unable to open the desktop application.";
}

?>
