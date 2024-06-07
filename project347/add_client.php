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
    $clientName = $_POST['client_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $reference = $_POST['reference'];
    $caseDescription = $_POST['case_description'];
    $caseFilePath = '';

    // Ensure the uploads directory exists
    $uploadFileDir = './uploads/';
    if (!is_dir($uploadFileDir)) {
        mkdir($uploadFileDir, 0777, true);
    }

    if (isset($_FILES['case_file']) && $_FILES['case_file']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['case_file']['tmp_name'];
        $fileName = $_FILES['case_file']['name'];
        $dest_path = $uploadFileDir . $fileName;

        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $caseFilePath = $dest_path;
        } else {
            echo "Error moving the uploaded file.";
            exit;
        }
    }

    $insertClientSql = $conn->prepare("INSERT INTO Clients (UserID, ClientName, phone, address, Reference, CaseDescription, CaseFilePath) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $insertClientSql->bind_param("issssss", $userID, $clientName, $phone, $address, $reference, $caseDescription, $caseFilePath);
    if ($insertClientSql->execute()) {
        // Insert a new case for the client
        $courtName = '';
        $courtNo = '';
        $act = '';
        $hearingDates = '';

        $insertCaseSql = $conn->prepare("INSERT INTO Cases (UserID, ClientName, CourtName, CourtNo, Act, HearingDates, CaseFilePath) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insertCaseSql->bind_param("issssss", $userID, $clientName, $courtName, $courtNo, $act, $hearingDates, $caseFilePath);
        $insertCaseSql->execute();
        $insertCaseSql->close();

        header("Location: home.php");
        exit;
    } else {
        echo "Error: " . $insertClientSql->error;
    }
    $insertClientSql->close();
}
$conn->close();
?>
