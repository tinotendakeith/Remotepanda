<?php
// load.php
require_once __DIR__ . '/database_config.php';

$connect = rp_remote_database_pdo();

$data = array();

$query = "SELECT * FROM events ORDER BY id";

$statement = $connect->prepare($query);

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
