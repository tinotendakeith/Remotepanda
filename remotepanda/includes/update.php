<?php
// update.php
require_once __DIR__ . '/database_config.php';

$connect = rp_remote_database_pdo();
$response = array(); // Create a response array

if (isset($_POST["id"])) {
    $query = "
        UPDATE events 
        SET Name = :Name, modality = :modality, color = :color, location = :location, start = :start, end = :end 
        WHERE id = :id
    ";
    $statement = $connect->prepare($query);
    
    if ($statement->execute(array(
        ':Name' => $_POST['Name'],
        ':modality' => $_POST['modality'],
        ':color' => $_POST['color'],
        ':location' => $_POST['location'],
        ':start' => $_POST['start'],
        ':end' => $_POST['end'],
        ':id' => $_POST['id']
    ))) {
       
    }
}

    $statement->execute();
   // Set a success message
        $_SESSION['success_message'] = "Data inserted successfully.";
// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
