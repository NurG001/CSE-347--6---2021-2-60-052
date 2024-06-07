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

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id']) && isset($_GET['userID'])) {
    $clientID = $_GET['id'];
    $userID = $_GET['userID'];

    // Fetch client information from the database
    $clientSql = $conn->prepare("SELECT * FROM Clients WHERE ClientID = ? AND UserID = ?");
    $clientSql->bind_param("ii", $clientID, $userID);
    $clientSql->execute();
    $clientResult = $clientSql->get_result();

    // Check if the client exists
    if ($clientResult->num_rows === 0) {
        echo "No client found with ID: $clientID for User ID: $userID";
        $clientSql->close(); // Close the prepared statement
        $conn->close();
        exit;
    }

    // Fetch client data
    $clientData = $clientResult->fetch_assoc();
    $clientSql->close(); // Close the prepared statement
} else {
    echo "Invalid request.";
    $conn->close();
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Client</title>
    <!-- Add your CSS styles here -->
</head>
<body>
    <h1>Edit Client</h1>
    <form action="update_client.php" method="post">
        <input type="hidden" name="client_id" value="<?= $clientData['ClientID'] ?>">
        <label for="client_name">Client Name:</label>
        <input type="text" id="client_name" name="client_name" value="<?= htmlspecialchars($clientData['ClientName']) ?>" required><br>
        
        <label for="phone">Phone:</label>
        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($clientData['phone']) ?>" required><br>
        
        <label for="address">Address:</label>
        <input type="text" id="address" name="address" value="<?= htmlspecialchars($clientData['address']) ?>" required><br>
        
        <label for="reference">Reference:</label>
        <input type="text" id="reference" name="reference" value="<?= htmlspecialchars($clientData['Reference'] ?? '') ?>"><br>
        
        <label for="case_description">Case Description:</label>
        <textarea id="case_description" name="case_description"><?= htmlspecialchars($clientData['CaseDescription'] ?? '') ?></textarea><br>
        
        <label for="case_file">Case File:</label>
        <input type="text" id="case_file" name="case_file" value="<?= htmlspecialchars($clientData['CaseFilePath'] ?? '') ?>"><br>
        
        <input type="submit" value="Update Client">
    </form>
</body>
</html>
