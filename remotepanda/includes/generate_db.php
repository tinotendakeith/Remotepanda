<?php
session_start();
require_once __DIR__ . '/database_config.php';

// Connect to the database and fetch study data
$connect = rp_remote_database_pdo();

// Fetch all data from the study table
$selectAllQuery = "SELECT * FROM study";
$selectAllStmt = $connect->prepare($selectAllQuery);
$selectAllStmt->execute();

// Fetch all study data
$allStudyData = $selectAllStmt->fetchAll(PDO::FETCH_ASSOC);

// Specify the SQLite database file path
$dbFilePath = "C:/ProgramData/iQ-WORKLIST/exchange/incoming/text/all_study_data.db";

// Create or open the SQLite database
$db = new SQLite3($dbFilePath);







// CREATE TABLE IF NOT EXISTS "WORKLIST" (
// 	"WORKLIST_PRKEY"	INTEGER,
// 	"PATIENT_ID"	VARCHAR(128),
// 	"PATIENT_NAME"	VARCHAR(128) NOT NULL,
// 	"PATIENT_BIRTHDATE"	VARCHAR(128),
// 	"PATIENT_SEX"	VARCHAR(4),
// 	"PATIENT_ADDRESS"	VARCHAR(4),
// 	"ACCESSION_NUMBER"	VARCHAR(32),
// 	"CHARACTER_SET"	VARCHAR(32),
// 	"MEDICAL_ALERTS"	VARCHAR(128),
// 	"ALLERGIES"	VARCHAR(128),
// 	"STUDY_INSTANCE_UID"	VARCHAR(128),
// 	"REQUESTING_PHYSICIAN_NAME"	VARCHAR(128),
// 	"REQUESTED_PROCEDURE_DESCRIPTION"	VARCHAR(128),
// 	"MODALITY"	VARCHAR(32),
// 	"REQUESTED_CONTRAST_AGENT"	VARCHAR(128),
// 	"SCHEDULED_STATION_AE_TITLE"	VARCHAR(32),
// 	"START_DATE"	VARCHAR(20),
// 	"START_TIME"	VARCHAR(32),
// 	"TECHNICIAN_NAME"	VARCHAR(128),
// 	"SCHEDULED_PROCEDURE_STEP_DESCRIPTION"	VARCHAR(128),
// 	"SCHEDULED_PROCEDURE_STEP_ID"	VARCHAR(32),
// 	"SCHEDULED_STATION_NAME"	VARCHAR(32),
// 	"SCHEDULED_PROCEDURE_STEP_LOCATION"	VARCHAR(32),
// 	"PRE_MEDICATION"	VARCHAR(128),
// 	"SCHEDULED_PROCEDURE_STEP_COMMENTS"	VARCHAR(128),
// 	"SCHEDULED_PROCEDURE_STATUS"	VARCHAR(8),
// 	"REQUESTED_PROCEDURE_ID"	VARCHAR(32),
// 	"REQUESTED_PROCEDURE_PRIORITY"	VARCHAR(32),
// 	"MESSAGE"	VARCHAR(2048),
// 	PRIMARY KEY("WORKLIST_PRKEY" AUTOINCREMENT)
// );





// Create a table in the database
$db->exec("CREATE TABLE IF NOT EXISTS study (
    patient_id INTEGER,
    study_id INTEGER,
    PATIENT_NAME TEXT,
    date_of_birth TEXT,
    gender TEXT,
    requesting_physician TEXT,
    requested_procedure TEXT,
    modality TEXT,
    start_date TEXT,
    technician_name TEXT,
    status TEXT,
    study TEXT
)");

// Insert data into the SQLite database
$insertStmt = $db->prepare("INSERT INTO study (patient_id, study_id, Name, date_of_birth, gender, requesting_physician, requested_procedure, modality, start_date, technician_name, status, study) 
    VALUES (:patientID, :study_id, :Name, :date_of_birth, :gender, :requestingPhysician, :requestedProcedure, :modality, :startDate, :technicianName, :status, :study)");

foreach ($allStudyData as $studyRow) {
    $insertStmt->bindValue(':patientID', $studyRow['patient_id'], SQLITE3_INTEGER);
    $insertStmt->bindValue(':study_id', $studyRow['study_id'], SQLITE3_INTEGER);
    $insertStmt->bindValue(':Name', $studyRow['Name'], SQLITE3_TEXT);
    $insertStmt->bindValue(':date_of_birth', $studyRow['date_of_birth'], SQLITE3_TEXT);
    $insertStmt->bindValue(':gender', $studyRow['gender'], SQLITE3_TEXT);
    $insertStmt->bindValue(':requestingPhysician', $studyRow['requesting_physician'], SQLITE3_TEXT);
    $insertStmt->bindValue(':requestedProcedure', $studyRow['requested_procedure'], SQLITE3_TEXT);
    $insertStmt->bindValue(':modality', $studyRow['modality'], SQLITE3_TEXT);
    $insertStmt->bindValue(':startDate', $studyRow['start_date'], SQLITE3_TEXT);
    $insertStmt->bindValue(':technicianName', $studyRow['technician_name'], SQLITE3_TEXT);
    $insertStmt->bindValue(':status', $studyRow['status'], SQLITE3_TEXT);
    $insertStmt->bindValue(':study', $studyRow['study'], SQLITE3_TEXT);

    $insertStmt->execute();
}

// Close the SQLite database
$db->close();

// Set appropriate headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=all_study_data.db');
header('Content-Length: ' . filesize($dbFilePath));

// Clean the output buffer
ob_clean();

// Output the file content
readfile($dbFilePath);

// Set a success message
$_SESSION['success_message'] = "Patient has been Booked Successfully.";

// Redirect to the desired URL
header("Location: //192.168.1.11/radpanda/dashboards/reception/index.php");

exit();
?>
