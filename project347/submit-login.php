<?php
session_start(); // Start the session

// Database connection setup
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lawyer_hub";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_POST['email'];
$password = $_POST['password']; // This is the plaintext password

// Prepare SQL statement to prevent SQL injection
$sql = $conn->prepare("SELECT * FROM users_information WHERE email = ?");
$sql->bind_param("s", $email);
$sql->execute();
$result = $sql->get_result();

if ($row = $result->fetch_assoc()) {
    // Verify the password
    if (password_verify($password, $row['password'])) {
        // Set session variables
        $_SESSION['loggedin'] = true;
        $_SESSION['email'] = $row['email'];
        $_SESSION['name'] = $row['name']; // Assume you have a name field in your database

        // Redirect to home.php
        header("Location: home.php");
        exit();
    } else {
        echo "Invalid email or password.";
    }
} else {
    echo "Invalid email or password.";
}

$sql->close();
$conn->close();
?>
