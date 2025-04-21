<?php
// Start session to check if the user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

// Check if user is admin or allow them to add a comment only if they are logged in
if ($_SESSION['admin'] != 'Y' && !isset($_SESSION['user_id'])) {
    header("Location: comment_list.php"); // Redirect to comment list page if not authorized
    exit();
}

// Include the PDO connection
require '../database/database.php'; 
$pdo = Database::connect(); // Get the PDO connection

// Handle add comment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $short_comment = $_POST['short_comment'];
    $long_comment = $_POST['long_comment'];
    $per_id = $_SESSION['user_id']; // Get the logged-in user's ID
    $iss_id = $_POST['iss_id']; // Get the issue ID from the form

    try {
        // Prepare the insert statement
        $stmt = $pdo->prepare("INSERT INTO iss_comments (short_comment, long_comment, per_id, iss_id, posted_date) 
                                VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$short_comment, $long_comment, $per_id, $iss_id]);

        // Redirect to the comments list page after adding the comment
        header("Location: comment_list.php");
        exit();
    } catch (PDOException $e) {
        // Handle errors (e.g., database issues)
        echo "Error: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Comment</title>
    <!-- Include Bootstrap CSS for styling -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center mb-4">Add a New Comment</h2>

    <!-- Form to Add New Comment -->
    <form action="add_comment.php" method="POST">
        <!-- Short Comment -->
        <div class="form-group">
            <label for="short_comment">Short Comment:</label>
            <input type="text" class="form-control" name="short_comment" placeholder="Enter short comment" required>
        </div>

        <!-- Long Comment -->
        <div class="form-group">
            <label for="long_comment">Long Comment:</label>
            <textarea class="form-control" name="long_comment" placeholder="Enter detailed comment" rows="4" required></textarea>
        </div>

        <!-- Issue ID (Hidden or from a selection) -->
        <div class="form-group">
            <label for="iss_id">Select Issue:</label>
            <select class="form-control" name="iss_id" required>
                <?php
                // Fetch issues from the database for selection
                $stmt = $pdo->query("SELECT id, short_description FROM iss_issues ORDER BY id ASC");
                while ($issue = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<option value='" . $issue['id'] . "'>" . $issue['short_description'] . "</option>";
                }
                ?>
            </select>
        </div>

        <!-- Submit Button -->
        <button type="submit" name="add_comment" class="btn btn-primary btn-block">Add Comment</button>
    </form>

    <br>
    <!-- Back to Comments List Button -->
    <a href="comment_list.php" class="btn btn-secondary btn-block">Back to Comment List</a>
</div>

<!-- Include Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>

<?php
// Close the PDO connection
$pdo = null;
?>
