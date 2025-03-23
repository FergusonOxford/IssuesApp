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

// Create the issues table if it doesn't exist
$table_creation_query = "
    CREATE TABLE IF NOT EXISTS iss_issues (
        id INT AUTO_INCREMENT PRIMARY KEY,
        short_description VARCHAR(255) NOT NULL,
        long_description TEXT NOT NULL,
        open_date DATE NOT NULL,
        close_date DATE,
        priority VARCHAR(50) NOT NULL,
        org VARCHAR(100),
        project VARCHAR(100),
        per_id INT NOT NULL
    );
";
$conn->query($table_creation_query);

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $conn->query("DELETE FROM iss_issues WHERE id=$delete_id");
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

    $conn->query("INSERT INTO iss_issues (short_description, long_description, open_date, close_date, priority, org, project, per_id) 
                  VALUES ('$short_description', '$long_description', '$open_date', '$close_date', '$priority', '$org', '$project', $per_id)");
}

// Handle update issue form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_issue'])) {
    $id = $_POST['id'];
    $short_description = $_POST['short_description'];
    $long_description = $_POST['long_description'];
    $open_date = $_POST['open_date'];
    $close_date = $_POST['close_date'];
    $priority = $_POST['priority'];
    $org = $_POST['org'];
    $project = $_POST['project'];

    $conn->query("UPDATE iss_issues SET short_description='$short_description', long_description='$long_description', 
                  open_date='$open_date', close_date='$close_date', priority='$priority', org='$org', project='$project' 
                  WHERE id=$id");
}

// Fetch all issues
$result = $conn->query("SELECT * FROM iss_issues WHERE per_id = {$_SESSION['user_id']}"); // Filter by logged-in user
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Management</title>
</head>
<body>
<!-- Add New Issue Button -->
<a href="add_issue.php"><button>Add New Issue</button></a>
<h2>Issue List</h2>
<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Short Description</th>
            <th>Priority</th>
            <th>Organization</th>
            <th>Project</th>
            <th>Open Date</th>
            <th>Close Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['short_description']; ?></td>
            <td><?php echo $row['priority']; ?></td>
            <td><?php echo $row['org']; ?></td>
            <td><?php echo $row['project']; ?></td>
            <td><?php echo $row['open_date']; ?></td>
            <td><?php echo $row['close_date']; ?></td>
            <td>
                <a href="issue_list.php?edit_id=<?php echo $row['id']; ?>">Edit</a> |
                <a href="issue_list.php?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<h2>Add a New Issue</h2>
<form action="issue_list.php" method="POST">
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

<?php
// Handle edit operation
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $edit_result = $conn->query("SELECT * FROM iss_issues WHERE id=$edit_id");
    $edit_row = $edit_result->fetch_assoc();
}?>
    


</body>
</html>

<?php
// Close connection
$conn->close();
?>
