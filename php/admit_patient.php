<!-- admit_patient.php -->
<?php
// Include database connection file
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $patient_name = $_POST['patient_name'];
    $gender = $_POST['report_type'];
    $dob = $_POST['report_date'];
    $admit_date = $_POST['report_date'];
    $blood_group = $_POST['report_type'];
    $disease_description = $_POST['disease'];
    $phone_number = $_POST['phno'];
    $address = $_POST['address'];
    // Generate a random unique user ID
    $user_id = mt_rand(100000, 999999);;

    // Prepare SQL statement
    $sql = "INSERT INTO patients ( user_id,patient_name, gender, dob, admit_date, blood_group, disease_description, phone_number, address) 
            VALUES ('$user_id','$patient_name', '$gender', '$dob', '$admit_date', '$blood_group', '$disease_description', '$phone_number', '$address')";

    // Execute the query
    if ($conn->query($sql) === TRUE) {
        echo "Patient admitted successfully! your patient id is:$user_id";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    // Close the database connection
    $conn->close();
}
?>
