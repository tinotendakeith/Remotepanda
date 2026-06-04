<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Include your database connection or any necessary files here
include('dbconnection.php');
include('../functions.php');

if (isset($_GET['scan_id'])) { // Remove the space here
    $itemId = $_GET['scan_id'];
    echo  $itemId;
    // Perform the delete operation in your database
    // Replace this with your actual database delete code
    $deleteQuery = "DELETE FROM clinic_scans WHERE scan_id = $itemId";
    if (mysqli_query($con, $deleteQuery)) {
        // Record deleted successfully, you can set a success message
        $_SESSION['success_message'] = "Record deleted successfully";
        // Handle errors if the record wasn't deleted
        $_SESSION['error_message'] = "Error deleting record: " . mysqli_error($con);
    }

    // Redirect back to the page where you list the items
    header("Location:../dashboards/admin/add_scan.php");
} else {
    // Handle any errors or invalid requests here
}
?>


