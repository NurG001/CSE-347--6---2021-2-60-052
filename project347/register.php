<?php
// Connection variables
$host = 'localhost'; // Host name
$username = 'root'; // Database username
$password = ''; // Database password
$dbname = 'lawyer_hub'; // Database name

// Connect to the database
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user input from the form
$name = $_POST['name'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
$dob = $_POST['dob'];
$blood_group = $_POST['blood_group'];
$designation = $_POST['designation'];
$chamber_address = $_POST['chamber_address'];
$chamber_phone = $_POST['chamber_phone'];
$membership_no = $_POST['membership_no'];
$graduation = $_POST['graduation'];
$phone = $_POST['phone'];

// SQL query to insert data into the users_information table
$sql = "INSERT INTO users_information (name, email, password, dob, blood_group, designation, chamber_address, chamber_phone, membership_no, graduation, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

// Prepare and bind
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("sssssssssis", $name, $email, $password, $dob, $blood_group, $designation, $chamber_address, $chamber_phone, $membership_no, $graduation, $phone);

// Execute the statement
if ($stmt->execute()) {
    // Redirect to login page after successful registration
    echo "<script>
        alert('Registration successful! Please login.');
        window.location.href = 'index.html';
    </script>";
} else {
    echo "Error: " . $stmt->error;
}

// Close statement and connection
$stmt->close();
$conn->close();
?>
