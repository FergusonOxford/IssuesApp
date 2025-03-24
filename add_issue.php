<?php
// Start session to check if user is logged in
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

// Handle add issue form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_issue'])) {
    $short_description = $_POST['short_description'];
    $long_description = $_POST['long_description'];
    $open_date = $_POST['open_date'];
    $close_date = $_POST['close_date'];
    $priority = $_POST['priority'];
    $org = $_POST['org'];
    $project = $_POST['project'];
    $per_id = $_SESSION['user_id']; // Assuming user_id is saved in session

    // Insert new issue into the database
    $conn->query("INSERT INTO iss_issues (short_description, long_description, open_date, close_date, priority, org, project, per_id) 
                  VALUES ('$short_description', '$long_description', '$open_date', '$close_date', '$priority', '$org', '$project', $per_id)");

    // Redirect back to the issue list page after submitting the form
    header("Location: issue_list.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Issue</title>
</head>
<body>

<h2>Add a New Issue</h2>

<form action="add_issue.php" method="POST">
    <input type="text" name="short_description" placeholder="Short Description" required><br>
    <textarea name="long_description" placeholder="Long Description" required></textarea><br>
    <input type="date" name="open_date" required><br>
    <input type="date" name="close_date"><br>
    <select name="priority" required>
        <option value="Low">Low</option>
        <option value="Medium">Medium</option>
        <option value="High">High</option>
    </select><br>
    <input type="text" name="org" placeholder="Organization" required><br>
    <input type="text" name="project" placeholder="Project" required><br>
    <button type="submit" name="add_issue">Add Issue</button>
</form>

<a href="issue_list.php">Back to Issue List</a>

</body>
</html>

<?php
// Close connection
$conn->close();
?>
