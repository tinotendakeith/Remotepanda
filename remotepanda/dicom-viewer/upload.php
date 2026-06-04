<?php
// Directory to store uploaded files
$target_dir = "uploads/";

// Get the uploaded file
$target_file = $target_dir . basename($_FILES["dicomFile"]["name"]);

// Ensure the uploads directory exists
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// Check if the file is a valid DICOM file (you can add more validation if needed)
$fileType = pathinfo($target_file, PATHINFO_EXTENSION);

if (move_uploaded_file($_FILES["dicomFile"]["tmp_name"], $target_file)) {
    // If the file was successfully uploaded
    echo "File uploaded successfully: " . basename($_FILES["dicomFile"]["name"]);

    // Redirect to the index.html page and pass the uploaded file name as a GET parameter
    header("Location: index.html?filename=" . urlencode(basename($_FILES["dicomFile"]["name"])));
    exit(); // Ensure the script stops after the redirection
} else {
    echo "Error moving the uploaded file.";
}
?>
