<?php
session_start();
require_once __DIR__ . '/database_config.php';
// insert.php

// Start or resume the session


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submitForm"])) {
if (!isset($_SESSION['user']['username'])) {
    http_response_code(401);
    exit('Unauthorized');
}
$connect = rp_remote_database_pdo();

    // Handle file upload
    $uploadDir = '../extensions/pdf/'; // Replace with your actual upload directory path
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    if (!isset($_FILES['filename']) || $_FILES['filename']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed.');
    }
    $tempName = $_FILES['filename']['tmp_name'];
    $originalName = $_FILES['filename']['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        throw new Exception('Only PDF uploads are allowed.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tempName);
    if ($mimeType !== 'application/pdf') {
        throw new Exception('Invalid file content. Expected a PDF.');
    }
    $filename = bin2hex(random_bytes(16)) . '.pdf';
    $targetFile = $uploadDir . $filename;
    if (!move_uploaded_file($tempName, $targetFile)) {
        throw new Exception('Unable to store uploaded file.');
    }

    // Extract and sanitize form data
    $Name = $_POST['Name'];
    $modality = $_POST['modality'];
    $study = $_POST['study'];
    $scanned_by = $_POST['scanned_by'];
    $room = $_POST['room'];

    // Retrieve the logged-in user's username from the session
    $booked_by = $_SESSION['user']['username'];

    $start = $_POST['start'];
    $end = $_POST['end'];

            // Retrieve the patient's ID based on patient information from the "patients" table
        $patientName = $_POST['Name']; // Assuming you have the patient's name in the form
        $patientID = 0; // Initialize with a default value
        
        // Query the "patients" table to find the patient's ID based on their name
        $selectPatientIDQuery = "SELECT id FROM patients WHERE patient_name = :patientName";
        $patientIDStmt = $connect->prepare($selectPatientIDQuery);
        $patientIDStmt->bindParam(':patientName', $patientName);
        $patientIDStmt->execute();
        
        $result = $patientIDStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
          // Throw an error if the patient ID cannot be found
          throw new Exception('Patient ID not found.');
        }
        
        $patientID = $result['id'];

    $status = "Not Scanned";
    $location = "George Silundika";
    // Checkboxes (Urgency)
    $color = "blue";

    try {
        $sql = "INSERT INTO events (Name, modality, study, room, scanned_by, booked_by, filename, color, status, location, start, end, patient_id) 
                VALUES (:Name, :modality, :study, :room, :scanned_by, :booked_by, :filename, :color, :status, :location, :start, :end, :patientID)";

        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':Name', $Name);
        $stmt->bindParam(':modality', $modality);
        $stmt->bindParam(':study', $study);
        $stmt->bindParam(':room', $room);
        $stmt->bindParam(':scanned_by', $scanned_by);
        $stmt->bindParam(':booked_by', $booked_by);
        $stmt->bindParam(':filename', $filename);
        $stmt->bindParam(':color', $color);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':start', $start);
        $stmt->bindParam(':end', $end);
        $stmt->bindParam(':patientID', $patientID, PDO::PARAM_INT); // Bind the patient ID

        $stmt->execute();
        
         // Retrieve the last inserted event's ID (assuming you have an auto-increment primary key)
        $lastEventID = $connect->lastInsertId();
        
        // Query the "events" table to retrieve the data you just inserted
        $selectEventQuery = "SELECT * FROM events WHERE id = :lastEventID";
        $eventStmt = $connect->prepare($selectEventQuery);
        $eventStmt->bindParam(':lastEventID', $lastEventID, PDO::PARAM_INT);
        $eventStmt->execute();
        $eventData = $eventStmt->fetch(PDO::FETCH_ASSOC);
        // Query the "patients" table to retrieve the corresponding patient's data
        $selectPatientQuery = "SELECT * FROM patients WHERE id = :patientID"; // Replace with the appropriate common identifier
        $patientStmt = $connect->prepare($selectPatientQuery);
        $patientStmt->bindParam(':patientID', $eventData['patient_id'], PDO::PARAM_INT); // Use 'patient_id' from event data
        $patientStmt->execute();
        $patientData = $patientStmt->fetch(PDO::FETCH_ASSOC);

        
        // Combine the data to create the study record
        $studyData = [
            'patient_id' => $patientData['id'],
            'study_id' => $eventData['id'],
            'Name' => $patientData['patient_name'],
            'date_of_birth' => $patientData['date_of_birth'],
            'gender' => $patientData['gender'],
            'requesting_physician' => $patientData['referring_doctor'], // Example: Use scanned_by from the event data
            'requested_procedure' => $eventData['study'], // Example: Use study from the event data
            'modality' => $eventData['modality'],
            'start_date' => $eventData['start'],
            'technician_name' => $eventData['scanned_by'],
            'status' => $eventData['status'],
            'study' => $eventData['study']
            // Add other fields as needed
        ];
        
        
        // Insert data into the "study" table
        $insertStudyQuery = "INSERT INTO study (patient_id, study_id, Name, date_of_birth, gender, requesting_physician, requested_procedure, modality, start_date, technician_name, status, study) 
        VALUES (:patientID, :study_id, :Name, :date_of_birth, :gender, :requestingPhysician, :requestedProcedure, :modality, :startDate, :technicianName, :status, :study)";
        $studyStmt = $connect->prepare($insertStudyQuery);
        
        // Bind parameters and values
        $studyStmt->bindParam(':patientID', $studyData['patient_id'], PDO::PARAM_INT);
        $studyStmt->bindParam(':study_id', $studyData['study_id'], PDO::PARAM_INT);
        $studyStmt->bindParam(':Name', $studyData['Name'], PDO::PARAM_STR);
        $studyStmt->bindParam(':date_of_birth', $studyData['date_of_birth'], PDO::PARAM_STR);
        $studyStmt->bindParam(':gender', $studyData['gender'], PDO::PARAM_STR);
        $studyStmt->bindParam(':requestingPhysician', $studyData['requesting_physician'], PDO::PARAM_STR);
        $studyStmt->bindParam(':requestedProcedure', $studyData['requested_procedure'], PDO::PARAM_STR);
        $studyStmt->bindParam(':modality', $studyData['modality'], PDO::PARAM_STR);
        $studyStmt->bindParam(':startDate', $studyData['start_date'], PDO::PARAM_STR);
        $studyStmt->bindParam(':technicianName', $studyData['technician_name'], PDO::PARAM_STR);
         $studyStmt->bindParam(':status', $studyData['status'], PDO::PARAM_STR);
          $studyStmt->bindParam(':study', $studyData['study'], PDO::PARAM_STR);
        
        // Execute the insert statement
        $studyStmt->execute();
               // $stmt->execute();
        

        // Retrieve the last inserted ID from the study table
        $lastStudyID = $connect->lastInsertId();

        $selectEventQuery = "SELECT * FROM study WHERE accession_number = :accessionNumber";
        $hl7Stmt = $connect->prepare($selectEventQuery);
        $hl7Stmt->bindParam(':accessionNumber', $lastStudyID, PDO::PARAM_INT);
        $hl7Stmt->execute();

        $hl7Data = $hl7Stmt->fetch(PDO::FETCH_ASSOC);



        $hl7Data = [
            'accession_number' => $hl7Data['accession_number'],
            'patient_id' => $hl7Data['patient_id'],
            'study_id' => $hl7Data['study_id'],
            'Name' => $hl7Data['Name'],
            'date_of_birth' => $hl7Data['date_of_birth'],
            'gender' => $hl7Data['gender'],
            'requesting_physician' => $hl7Data['requesting_physician'], // Example: Use scanned_by from the event data
            'requested_procedure' => $hl7Data['study'], // Example: Use study from the event data
            'modality' => $hl7Data['modality'],
            'status' => $hl7Data['status'],
            'start_date' => $hl7Data['start'],
            'technician_name' => $hl7Data['technician_name']
            // Add other fields as needed
        ];
            
            $dateOfBirthDateTime = new DateTime($hl7Data['date_of_birth']);
            $formattedDateOfBirth = $dateOfBirthDateTime->format('Ymd');
            // Now, you can use $formattedDateOfBirth in your HL7 message
            $hl7Data['date_of_birth'] = $formattedDateOfBirth;

            $startDateTime = new DateTime($hl7Data['start']);
            $formattedStartDate = $startDateTime->format('YmdHis');
            // Now, you can use $formattedStartDate in your HL7 message
            $hl7Data['start_date'] = $formattedStartDate;

            
            
            // Generate HL7 message (replace this with your HL7 generation logic)
            try {
              $hl7Message = "MSH|^~\&|Radpanda Ris|Sante Worklist Server|||$formattedStartDate|||ORM^O01|MPMC23420|P|2.3|||AL|NE\n";
                $hl7Message .= "PID|1|3|{$hl7Data['patient_id']}|$study_id|{$hl7Data['Name']}||$formattedDateOfBirth|F|||ADDRESS|PHONENUMBER|EMAIL|||||||||$modality|\n";
                $hl7Message .= "PV1||O||||||2593^^George Silundika Avenue^^||||||||||||\n";
               $hl7Message .= "ORC|NW|{$hl7Data['accession_number']}|HD2939||{$hl7Data['status']}|N|||20200312121800|MRSHUMBA||{$hl7Data['requesting_physician']}||||||||||||\n";
                $hl7Message .= "OBR|1||HD2939|{$hl7Data['requested_procedure']}||20200312121800|20200312121800|||||||||2593^^GEORGE SILUNDIKA AVENUES^^||CR||||||FCR-CSL3|||1^once^^20200312121800^^R||||^|||{$hl7Data['technician_name']}|^^^^CR2";


            
              // Add more segments as needed
            
              // Create HL7 text file
              $fileName = "hl7_patient_" . $lastEventID . ".hl7";
              $filePath = "C:/ProgramData/iQ-WORKLIST/exchange/incoming/text/" . $fileName; // Make sure 'hl7_files' directory exists
            
              // Write HL7 message to the file
              file_put_contents($filePath, $hl7Message);
            } catch (Exception $e) {
              // Handle the error gracefully
              // For example, you could log the error and send an email notification
            }
        

        // Set the appropriate headers for download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $fileName);
            header('Content-Length: ' . filesize($filePath));
        
            // Output the file content
            readfile($filePath);
            

            // DB Experiment Starts

            // DB Expreiment ends


             // Send HL7 data to the DICOM worklist server
                $dicomServerIP = '192.168.1.11'; // Replace with the actual IP address of the DICOM worklist server
                $dicomServerPort = 787; // Replace with the actual port used by the DICOM worklist server

                // Use a dedicated function to handle DICOM communication
                function sendToDicomServer($hl7Message, $dicomServerIP, $dicomServerPort) {
                    $socket = fsockopen($dicomServerIP, $dicomServerPort, $errno, $errstr, 10);

                    if ($socket) {
                        fwrite($socket, $hl7Message);
                        fclose($socket);
                    } else {
                        // Log or handle the error
                        echo "Error connecting to DICOM server: $errstr ($errno)";
                    }
                }

                // Call the function to send HL7 data to the DICOM server
                sendToDicomServer($hl7Message, $dicomServerIP, $dicomServerPort);

            

         //Set a success message
        $_SESSION['success_message'] = "$Name has been Booked for  $start."; 
            
        
            //Redirect to the desired URL after data insertion
        header("Location: //192.168.1.11/radpanda/dashboards/reception/index.php");
        
            // Redirect to the desired URL after data insertion
        // header("Location: generate_db.php");
        
        exit();
        
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
