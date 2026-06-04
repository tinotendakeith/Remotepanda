<?php
// load.php
require_once __DIR__ . '/database_config.php';

session_start(); // Start the session to access session variables

$connect = rp_remote_database_pdo();

$data = array();

// Assuming you have the current username stored in the session
$currentUsername = $_SESSION['user']['username'];

$query = "SELECT * FROM events WHERE scanned_by = :username AND color != 'black' ORDER BY id";

$statement = $connect->prepare($query);
$statement->bindParam(':username', $currentUsername, PDO::PARAM_STR);
$statement->execute();

$result = $statement->fetchAll();

foreach ($result as $row) {
    $eventColor = $row["color"]; // Assuming you have a 'color' column in your events table

    $data[] = array(
        'id' => $row["id"],
        'Name' => $row["Name"],
        'modality' => $row["modality"],
        'study' => $row["study"],
        'room' => $row["room"],
        'filename' => $row["filename"],
        'status' => $row["status"],
        'location' => $row["location"],
        'start' => $row["start"],
        'end' => $row["end"],
        'color' => $eventColor // Set the background color for the event
    );
}

echo json_encode($data);
?>
