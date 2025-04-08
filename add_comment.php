<?php
// Start session
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

$servername = "localhost";
$username = "root"; // Update with your MySQL username
$password = ""; // Update with your MySQL password
$dbname = "cis355"; // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle adding a new comment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $short_comment = $_POST['short_comment'];
    $long_comment = $_POST['long_comment'];
    $iss_id = $_POST['iss_id'];
    $per_id = $_SESSION['user_id']; // The person ID will come from the logged-in session

    // Insert the comment into the database
    $stmt = $conn->prepare("INSERT INTO iss_comments (short_comment, long_comment, iss_id, per_id) 
                            VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $short_comment, $long_comment, $iss_id, $per_id);
    $stmt->execute();

    // Redirect back to the issue page
    header("Location: issue_details.php?iss_id=" . $iss_id); // Assuming you're on an issue details page
    exit();
}

$conn->close();
?>
