<?php
// Include your database connection file
include('dbconnection.php');
include('../functions.php');

// Function to check if the patient exists in the database
function checkIfPatientExists($con, $patientName) {
    // Sanitize the input to prevent SQL injection (replace this with your sanitation method)
    $patientName = mysqli_real_escape_string($con, $patientName);

    // Query to check if the patient exists
    $query = "SELECT COUNT(*) as count FROM patients WHERE patient_name = '$patientName'";
    $result = mysqli_query($con, $query);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $count = $row['count'];

        // If count is greater than 0, patient exists; otherwise, patient does not exist
        return ($count > 0);
    } else {
        // Handle query error
        return false;
    }
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the posted patient_name
    $patientName = isset($_POST['patient_name']) ? $_POST['patient_name'] : '';

    // Check if the patient exists
    $patientExists = checkIfPatientExists($con, $patientName);

    // Return a response
    echo $patientExists ? 'exists' : 'not_exists';
} else {
    // Handle non-POST requests
    echo 'Invalid request';
}

// Close the database connection
mysqli_close($con);
?>
