<?php
require_once __DIR__ . '/../../includes/dbconnection.php';

// Function to retrieve the accession number from the database
function your_get_accession_number_function() {
    global $con; // Assuming $con is your database connection variable
    
    // Modify this query to retrieve the accession number from your database
    $query = "SELECT accession_number FROM study";
    $result = mysqli_query($con, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['accession_number'];
    } else {
        return ""; // Return empty string if no accession number is found
    }
}

// Assuming you have a function to retrieve the accession number from the database
// Replace 'your_get_accession_number_function' with your actual function
$accessionNumber = your_get_accession_number_function(); 

// Check if the accession number is retrieved successfully
if ($accessionNumber === "") {
    // If the accession number retrieval failed, you may want to handle it appropriately
    die('Failed to retrieve accession number from the database.');
}

// Echo the accession number back as the response
echo $accessionNumber;
?>
