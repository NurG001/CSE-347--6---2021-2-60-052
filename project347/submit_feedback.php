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

$userSql = $conn->prepare("SELECT id FROM users_information WHERE email = ?");
$userSql->bind_param("s", $email);
$userSql->execute();
$userResult = $userSql->get_result();

if ($userResult->num_rows === 0) {
    echo "No user found with email: $email";
    $conn->close();
    exit;
}

$userData = $userResult->fetch_assoc();
$userID = $userData['id'];
$userSql->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $feedback = $_POST['feedback'];

    $insertFeedbackSql = $conn->prepare("INSERT INTO Feedback (UserID, FeedbackText) VALUES (?, ?)");
    $insertFeedbackSql->bind_param("is", $userID, $feedback);
    $insertFeedbackSql->execute();
    $insertFeedbackSql->close();

    echo "Thank you for your feedback!";
    header("Location: home.php");
    exit;
}

$conn->close();
?>
