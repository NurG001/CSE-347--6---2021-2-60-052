<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    // Redirect to the login page if not logged in
    header("Location: login.php");
    exit;
}

// Database connection setup
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lawyer_hub";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve logged-in user's email from session
$email = $_SESSION['email'];

// Prepare and execute SQL statement to fetch user's data
$sql = $conn->prepare("SELECT name, email, dob, blood_group, designation, chamber_address, chamber_phone, graduation, phone FROM users_information WHERE email = ?");
$sql->bind_param("s", $email);
$sql->execute();
$result = $sql->get_result();

// Check if user's data exists
if ($result->num_rows > 0) {
    // Fetch user's data
    $userData = $result->fetch_assoc();

    // Assign user's data to variables
    $name = $userData['name'];
    $email = $userData['email'];
    $dob = $userData['dob'];
    $blood_group = $userData['blood_group'];
    $designation = $userData['designation'];
    $chamber_address = $userData['chamber_address'];
    $chamber_phone = $userData['chamber_phone'];
    $graduation = $userData['graduation'];
    $phone = $userData['phone'];
} else {
    echo "Error: User data not found.";
    exit;
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <style>
        /* Your CSS styles here */
        body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 500px;
    margin: 50px auto;
    padding: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

h2 {
    margin-top: 0;
    margin-bottom: 20px;
}

form {
    display: grid;
    grid-row-gap: 20px;
}

label {
    font-weight: bold;
}

input[type="text"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;
}

input[type="submit"] {
    background-color: #007bff;
    color: #fff;
    border: none;
    border-radius: 5px;
    padding: 10px 20px;
    cursor: pointer;
}

input[type="submit"]:hover {
    background-color: #0056b3;
}

    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Profile</h2>
        <form action="update_profile.php" method="post">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="<?php echo $name; ?>" required>

            <label for="email">Email:</label>
            <input type="text" id="email" name="email" value="<?php echo $email; ?>" required>

            <label for="dob">Date of Birth:</label>
            <input type="text" id="dob" name="dob" value="<?php echo $dob; ?>" required>

            <label for="blood_group">Blood Group:</label>
            <input type="text" id="blood_group" name="blood_group" value="<?php echo $blood_group; ?>" required>

            <label for="designation">Designation:</label>
            <input type="text" id="designation" name="designation" value="<?php echo $designation; ?>" required>

            <label for="chamber_address">Chamber Address:</label>
            <input type="text" id="chamber_address" name="chamber_address" value="<?php echo $chamber_address; ?>" required>

            <label for="chamber_phone">Chamber Phone:</label>
            <input type="text" id="chamber_phone" name="chamber_phone" value="<?php echo $chamber_phone; ?>" required>

            <label for="graduation">Graduation Year:</label>
            <input type="text" id="graduation" name="graduation" value="<?php echo $graduation; ?>" required>

            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" value="<?php echo $phone; ?>" required>

            <input type="submit" value="Save Changes">
        </form>

    </div>
</body>
</html>
