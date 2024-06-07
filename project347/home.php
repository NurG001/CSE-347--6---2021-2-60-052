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

$userSql = $conn->prepare("SELECT * FROM users_information WHERE email = ?");
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

if (isset($_GET['delete_case'])) {
    $caseID = intval($_GET['delete_case']);
    $deleteSql = $conn->prepare("DELETE FROM Cases WHERE CaseNo = ? AND UserID = ?");
    $deleteSql->bind_param("ii", $caseID, $userID);
    $deleteSql->execute();
    $deleteSql->close();
    header("Location: home.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_case'])) {
        $caseNo = intval($_POST['case_no']);
        $clientName = $_POST['client_name'];
        $courtName = $_POST['court_name'];
        $courtNo = $_POST['court_no'];
        $act = $_POST['act'];
        $hearingDates = $_POST['hearing_dates'];
        $completed = isset($_POST['completed']) ? 1 : 0;
        $caseFilePath = '';

        if (isset($_FILES['case_file']) && $_FILES['case_file']['error'] == UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['case_file']['tmp_name'];
            $fileName = $_FILES['case_file']['name'];
            $dest_path = './uploads/' . $fileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $caseFilePath = $dest_path;
            }
        }

        $updateSql = $conn->prepare("UPDATE Cases SET ClientName = ?, CourtName = ?, CourtNo = ?, Act = ?, HearingDates = ?, Completed = ?, CaseFilePath = IF(?, ?, CaseFilePath) WHERE CaseNo = ? AND UserID = ?");
        $updateSql->bind_param("sssssisisi", $clientName, $courtName, $courtNo, $act, $hearingDates, $completed, $caseFilePath, $caseFilePath, $caseNo, $userID);
        $updateSql->execute();
        $updateSql->close();
        header("Location: home.php");
        exit;
    } elseif (isset($_POST['hearing_date']) && isset($_POST['case_no'])) {
        $caseNo = intval($_POST['case_no']);
        $hearingDate = $_POST['hearing_date'];
        $updateSql = $conn->prepare("UPDATE Cases SET HearingDates = ? WHERE CaseNo = ? AND UserID = ?");
        $updateSql->bind_param("sii", $hearingDate, $caseNo, $userID);
        $updateSql->execute();
        $updateSql->close();
        header("Location: home.php");
        exit;
    }
}

$clients = [];
$clientSql = $conn->prepare("SELECT * FROM Clients WHERE UserID = ?");
$clientSql->bind_param("i", $userID);
$clientSql->execute();
$clientResult = $clientSql->get_result();

if ($clientResult) {
    while ($row = $clientResult->fetch_assoc()) {
        $clients[] = $row;
    }
}
$clientSql->close();

$cases = [];
$casesSql = $conn->prepare("SELECT * FROM Cases WHERE UserID = ?");
$casesSql->bind_param("i", $userID);
$casesSql->execute();
$casesResult = $casesSql->get_result();

if ($casesResult) {
    while ($row = $casesResult->fetch_assoc()) {
        $cases[] = $row;
    }
}
$casesSql->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
    <style>
    body, html {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('bg9.png') no-repeat center center fixed; /* Set your background image here */
            background-size: cover; /* Cover the entire background */
            width: 100%;
            color: white;
        }

        .navbar {
            background-color: #0e2333;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 100%;
        }

        .navbar a, .navbar button {
            color: #fcc674;
            padding: 20px 45px;
            text-decoration: none;
            background: none;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
            border-radius: 5px;
        }

        .navbar a:hover, .navbar button:hover {
            background-color: #bd852f;
        }

        .content, .form-container, .edit-case-form {
            display: none;
            padding: 30px;
            background-color: rgba(26, 71, 87, 0.88); /* White background with slight transparency */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin: 20px auto;
            width: 95%;
            max-width: 1300px;
        }

        .active {
            display: block;
            transform: translateY(0);
            opacity: 1;
        }

        .client-list, .cases-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .client-item, .case-item {
            background-color: rgba(255, 255, 255, 0.75); /* White background with slight transparency */
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.3s ease;
        }

        .client-item:hover, .case-item:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .client-item h2, .case-item h2 {
            margin-top: 0;
            color: #333;
        }

        .client-item p, .case-item p {
            margin-bottom: 0;
            color: #666;
        }

        .cases-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .cases-table th, .cases-table td {
            padding: 47px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .cases-table th {
            background-color: rgb(13, 16, 23, 0.75);
            color: #ffff;
        }

        .file-link {
            text-decoration: none;
            color: #007bff;
            transition: color 0.3s ease;
        }

        .file-link:hover {
            color: #0056b3;
        }

        .completed {
            color: green;
        }

        .incomplete {
            color: red;
        }

        .edit-button, .delete-button {
            background-color: #faa83c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s ease;
            margin: 5px;
        }

        .edit-button:hover {
            background-color: #6b4711;
        }

        .delete-button {
            background-color: #050505;
        }

        .delete-button:hover {
            background-color: #e60000;
        }

        /* Feedback Form Styling */
        .feedback-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }

        .feedback-form label {
            font-weight: bold;
        }

        .feedback-form textarea {
            resize: vertical;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .feedback-form button {
            padding: 10px;
            background-color: #faa83c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .feedback-form button:hover {
            background-color: #6b4711;
        }

        .edit-case-form {
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.75); /* White background with slight transparency */
            color: black;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin: 20px auto;
            width: 95%;
            max-width: 1300px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="javascript:void(0)" onclick="showSection('home');">Home</a>
        <a href="javascript:void(0)" onclick="showSection('user-profile');">User Profile</a>
        <a href="javascript:void(0)" onclick="showSection('cases-list');">Cases</a>
        <a href="javascript:void(0)" onclick="showSection('hearing-schedule');">Hearing Schedule</a>
        <a href="javascript:void(0)" onclick="showSection('clients-list');">Clients List</a>
        <a href="javascript:void(0)" onclick="showSection('add-client-form');">Add Clients</a>
        <a href="javascript:void(0)" onclick="showSection('feedback');">Feedback</a>
        <a href="logout.php">Log out</a>
    </div>

    <div class="content" id="home">
        <h1><b>Lawyer_Hub</b></h1>
        <p>This is a platform for managing your legal cases and clients.</p>
        <p>Where lawyers can securely store, access, and manage their case files, client details, schedules, billing information, and more.</p>
    </div>

    <div class="content" id="user-profile">
        <h1>User Profile</h1>
        <p>Name: <?= htmlspecialchars($userData['name']) ?></p>
        <p>Email: <?= htmlspecialchars($userData['email']) ?></p>
        <p>Date of Birth: <?= htmlspecialchars($userData['dob']) ?></p>
        <p>Blood Group: <?= htmlspecialchars($userData['blood_group']) ?></p>
        <p>Designation: <?= htmlspecialchars($userData['designation']) ?></p>
        <p>Chamber Address: <?= htmlspecialchars($userData['chamber_address']) ?></p>
        <p>Chamber Phone: <?= htmlspecialchars($userData['chamber_phone']) ?></p>
        <p>Membership No: <?= htmlspecialchars($userData['membership_no']) ?></p>
        <p>Graduation Year: <?= htmlspecialchars($userData['graduation']) ?></p>
        <p>Phone: <?= htmlspecialchars($userData['phone']) ?></p>
        <button class="edit-button" onclick="editProfile()">Edit Profile</button>
    </div>

    <div id="cases-list" class="content">
        <h1>Cases List</h1>
        <div class="cases-list">
            <table class="cases-table">
                <thead>
                    <tr>
                        <th>Case No</th>
                        <th>Client Name</th>
                        <th>Court Name</th>
                        <th>Court No</th>
                        <th>Act</th>
                        <th>Hearing Dates</th>
                        <th>Files</th>
                        <th>Completed</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cases as $case): ?>
                        <tr>
                            <td><?= htmlspecialchars($case['CaseNo']) ?></td>
                            <td><?= htmlspecialchars($case['ClientName']) ?></td>
                            <td><?= htmlspecialchars($case['CourtName']) ?></td>
                            <td><?= htmlspecialchars($case['CourtNo']) ?></td>
                            <td><?= htmlspecialchars($case['Act']) ?></td>
                            <td><?= htmlspecialchars($case['HearingDates']) ?></td>
                            <td><a href="<?= htmlspecialchars($case['CaseFilePath']) ?>" class="file-link" target="_blank">View</a></td>
                            <td class="<?= $case['Completed'] ? 'completed' : 'incomplete' ?>"><?= $case['Completed'] ? 'Yes' : 'No' ?></td>
                            <td>
                                <button class="edit-button" onclick="editCase(<?= $case['CaseNo'] ?>)">Edit</button>
                                <button class="delete-button" onclick="deleteCase(<?= $case['CaseNo'] ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="edit-case-form" class="edit-case-form">
        <h2>Edit Case</h2>
        <form id="case-form" action="home.php" method="post" enctype="multipart/form-data">
            <input type="hidden" id="case_no" name="case_no" required>
            <label for="client_name">Client Name:</label>
            <input type="text" id="client_name" name="client_name" required><br>
            
            <label for="court_name">Court Name:</label>
            <input type="text" id="court_name" name="court_name"><br>
            
            <label for="court_no">Court No:</label>
            <input type="text" id="court_no" name="court_no"><br>
            
            <label for="act">Act:</label>
            <input type="text" id="act" name="act"><br>
            
            <label for="hearing_dates">Hearing Dates:</label>
            <input type="text" id="hearing_dates" name="hearing_dates"><br>
            
            <label for="completed">Completed:</label>
            <input type="checkbox" id="completed" name="completed"><br>
            
            <label for="case_file">Case File:</label>
            <input type="file" id="case_file" name="case_file"><br>
            
            <input type="submit" name="update_case" value="Update Case">
        </form>
    </div>

    <div id="hearing-schedule" class="content">
        <h1>Hearing Schedule</h1>
        <div class="cases-list">
            <table class="cases-table">
                <thead>
                    <tr>
                        <th>Case No</th>
                        <th>Client Name</th>
                        <th>Hearing Dates</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cases as $case): ?>
                        <tr>
                            <td><?= htmlspecialchars($case['CaseNo']) ?></td>
                            <td><?= htmlspecialchars($case['ClientName']) ?></td>
                            <td><?= htmlspecialchars($case['HearingDates']) ?></td>
                            <td><button class="edit-button" onclick="openCalendar(<?= $case['CaseNo'] ?>)">Set Hearing Date</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div id="calendar-container" style="display:none;">
            <div id="calendar"></div>
            <form id="date-form" method="POST">
                <input type="hidden" id="selected-date" name="hearing_date">
                <input type="hidden" id="case-no" name="case_no">
                <input type="submit" value="Save Date">
            </form>
        </div>
    </div>

    <div id="clients-list" class="content">
        <h1>Clients List</h1>
        <div class="client-list">
            <?php if (!empty($clients)): ?>
                <?php foreach ($clients as $client): ?>
                    <div class="client-item">
                        <h2><?= htmlspecialchars($client['ClientName']) ?></h2>
                        <p>
                            <strong>Phone:</strong> <?= htmlspecialchars($client['Phone'] ?? 'N/A') ?><br>
                            <strong>Address:</strong> <?= htmlspecialchars($client['Address'] ?? 'N/A') ?><br>
                            <strong>Reference:</strong> <?= htmlspecialchars($client['Reference'] ?? 'N/A') ?><br>
                            <strong>Case Description:</strong> <?= htmlspecialchars($client['CaseDescription'] ?? 'N/A') ?><br>
                            <?php if (isset($client['CaseFilePath'])): ?>
                                <strong>Case File:</strong> <a href="<?= htmlspecialchars($client['CaseFilePath']) ?>" target="_blank">View File</a>
                            <?php else: ?>
                                <strong>Case File:</strong> N/A
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No clients found.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-container" id="add-client-form">
        <h2>Add New Client</h2>
        <form action="add_client.php" method="post" enctype="multipart/form-data">
            <label for="client_name">Client Name:</label>
            <input type="text" id="client_name" name="client_name" required><br>
            
            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" required><br>
            
            <label for="address">Address:</label>
            <input type="text" id="address" name="address" required><br>
            
            <label for="reference">Reference:</label>
            <input type="text" id="reference" name="reference"><br>
            
            <label for="case_description">Case Description:</label>
            <textarea id="case_description" name="case_description"></textarea><br>
            
            <label for="case_file">Case File:</label>
            <input type="file" id="case_file" name="case_file"><br>
            
            <input type="submit" value="Add Client">
        </form>
    </div>

    <div id="feedback" class="content">
        <h1>Feedback</h1>
        <p>This is a platform for any feedback or inquiries you need.</p>
        <!-- Feedback Form -->
        <form class="feedback-form" action="submit_feedback.php" method="post">
            <label for="feedback">Your Feedback:</label>
            <textarea id="feedback" name="feedback" rows="4" required></textarea>
            <button type="submit">Submit Feedback</button>
        </form>
    </div>

    <script>
        function showSection(id) {
            var sections = document.querySelectorAll('.content, .form-container, .edit-case-form');
            sections.forEach(function(section) {
                section.style.display = 'none';
            });

            var selectedSection = document.getElementById(id);
            selectedSection.style.display = 'block';
        }

        function editCase(caseID) {
            var cases = <?php echo json_encode($cases); ?>;
            var caseData = cases.find(c => c.CaseNo == caseID);
            if (caseData) {
                document.getElementById('case_no').value = caseData.CaseNo;
                document.getElementById('client_name').value = caseData.ClientName;
                document.getElementById('court_name').value = caseData.CourtName;
                document.getElementById('court_no').value = caseData.CourtNo;
                document.getElementById('act').value = caseData.Act;
                document.getElementById('hearing_dates').value = caseData.HearingDates;
                document.getElementById('completed').checked = caseData.Completed ? true : false;
            }
            showSection('edit-case-form');
        }

        function deleteCase(caseID) {
            if (confirm("Are you sure you want to delete this case?")) {
                window.location.href = "home.php?delete_case=" + caseID;
            }
        }

        function openCalendar(caseNo) {
            $('#calendar-container').show();
            $('#calendar').fullCalendar({
                selectable: true,
                selectHelper: true,
                select: function(start, end) {
                    $('#selected-date').val(start.format());
                    $('#case-no').val(caseNo);
                    $('#date-form').show();
                    $('#calendar').fullCalendar('unselect');
                }
            });
        }

        function editProfile() {
            window.location.href = "edit_profile.php";
        }
    </script>
</body>
</html>
