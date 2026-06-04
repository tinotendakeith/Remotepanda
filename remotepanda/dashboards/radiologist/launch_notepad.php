<?php
// Ensure safe mode is disabled in php.ini for exec() to function
if (ini_get('safe_mode')) {
    die('Safe mode is enabled. Please disable it to use exec()');
}

// Retrieve AE title from your data source (replace this with your actual retrieval logic)
$aeTitle = "SANTESRVPG1"; // Replace with the actual AE title

// Retrieve the accession number from the request parameter
$accessionNumber = $_GET['accession_number'] ?? '';

// Check if the accession number is provided
if ($accessionNumber === "") {
    // If the accession number is not provided, handle it appropriately
    die('Accession number is not provided.');
}

// Command to launch Sante DICOM Viewer Pro with a specific study
$command = 'start /B cmd /c "C:\\Program Files\\Santesoft\\Sante DICOM Viewer Pro\\Sante DICOM Viewer Pro64.exe" -a ' . $aeTitle . ' -N ' . $accessionNumber;

// Log the command to a file++
$logFile = 'C:\\xampp\\htdocs\\radpanda\\logs\\log.txt'; // Replace with the actual path
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Command: $command\n", FILE_APPEND);
// Attempt to execute the command
$output = shell_exec($command);

// Log the output to the same file
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Output: $output\n", FILE_APPEND);

// Provide feedback to the client-side script
echo "Sante DICOM Viewer Pro launch attempted with AE title: $aeTitle and accession number: $accessionNumber";
?>
