<?php
include('../../includes/dbconnection.php');
include('../../functions.php');

if (isset($_POST['submit'])) {
    // Retrieve form data
    $title = $_POST['title'];
    $patient_name = $_POST['patient_name'];
    $address = $_POST['address'];
    $date_of_birth = $_POST['date_of_birth'];
    $phone_number = $_POST['phone_number'];
    $gender = $_POST['gender'];
    $email_address = $_POST['email_address'];
    $employer_name = $_POST['employer_name'];
    $business_phone = $_POST['business_phone'];
    $referring_doctor = $_POST['referring_doctor'];

    // Insert data into the database
    $query = "INSERT INTO patients (title, patient_name, address, date_of_birth, phone_number, gender, email_address, employer_name, business_phone, referring_doctor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($con, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssssssss", $title, $patient_name, $address, $date_of_birth, $phone_number, $gender, $email_address, $employer_name, $business_phone, $referring_doctor);

        if (mysqli_stmt_execute($stmt)) {
            echo "Patient added successfully!";
        } else {
            echo "Error: " . mysqli_error($con);
        }

        mysqli_stmt_close($stmt);
    } else {
        echo "Error preparing statement: " . mysqli_error($con);
    }
}
?>
