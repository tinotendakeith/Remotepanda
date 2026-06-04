<?php
$command = $_GET['cmd']; // Get the command from the query parameter

// Validate and sanitize the command to prevent security risks
$allowedCommands = ['dir', 'your_custom_command'];
if (in_array($command, $allowedCommands)) {
    // Execute the command and echo the result
    echo shell_exec($command);
} else {
    echo "Invalid command";
}
?>
