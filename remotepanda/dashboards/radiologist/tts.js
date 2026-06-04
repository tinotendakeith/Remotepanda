<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transcription = $_POST['transcription'];
    
    // Process the transcription as needed (e.g., store in a database, perform further actions)
    // Your additional logic goes here
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
