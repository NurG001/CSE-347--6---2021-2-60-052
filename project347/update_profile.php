<?php
session_start();

if (!isset($_SESSION['email'])) {
    echo "No session email set. Please log in.";
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lawyer_hub";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_SESSION['email'];

$name = $_POST['name'];
$email = $_POST['email'];
$dob = $_POST['dob'];
$blood_group = $_POST['blood_group'];
$designation = $_POST['designation'];
$chamber_address = $_POST['chamber_address'];
$chamber_phone = $_POST['chamber_phone'];
$graduation = $_POST['graduation'];
$phone = $_POST['phone'];

$sql = $conn->prepare("UPDATE users_information SET name=?, email=?, dob=?, blood_group=?, designation=?, chamber_address=?, chamber_phone=?, graduation=?, phone=? WHERE email=?");
$sql->bind_param("ssssssssss", $name, $email, $dob, $blood_group, $designation, $chamber_address, $chamber_phone, $graduation, $phone, $email);
if ($sql->execute()) {
    // Redirect to home.php after successful update
    header("Location: home.php");
    exit;
} else {
    echo "Error updating profile: " . $conn->error;
}

$sql->close();
$conn->close();
?>
