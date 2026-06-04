<?php
error_reporting(0);
include('dbconnection.php');
include('../functions.php');

if (!isLoggedIn()) {
    // Redirect or handle unauthorized access
    exit('Unauthorized Access');
}

if (isset($_POST['submit'])) 
{
    $name = $_POST['name'];
    $specialty = $_POST['specialty'];
    $address = $_POST['address'];
    $phone_number = $_POST['phone_number'];
    $email_address = $_POST['email_address'];

    $query=mysqli_query($con,"insert into referring_doctor(name,specialty,address,phone_number,email_address) 
    value('$name','$specialty','$address','$phone_number','$email_address')");

    if ($query) {
        echo "New Doctor has been added.";
    } else {
        echo "Something Went Wrong :( Don't fret, Let's try again.";
    }
} else {
    // Handle the case where the form was not submitted properly
    echo "Form submission error.";
}
?>
