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

// Get the issue ID from the query string (assuming you're on an issue detail page)
$iss_id = $_POST['iss_id']; // You may need to validate this

// Fetch comments for the issue from the iss_comments table
$sql = "SELECT iss_comments.id, iss_comments.short_comment, iss_comments.long_comment, iss_comments.posted_date, 
               iss_persons.fname, iss_persons.lname
        FROM iss_comments
        LEFT JOIN iss_persons ON iss_comments.per_id = iss_persons.id
        WHERE iss_comments.iss_id = $iss_id
        ORDER BY iss_comments.posted_date DESC"; // Fetch comments for the issue
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments for Issue</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-5">
    <h2>Comments for Issue #<?php echo $iss_id; ?></h2>

    <!-- Display comments -->
    <div class="comments-list">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="comment">
                    <h5><?php echo $row['fname'] . ' ' . $row['lname']; ?> <small>(Posted on <?php echo $row['posted_date']; ?>)</small></h5>
                    <p><strong>Short Comment:</strong> <?php echo $row['short_comment']; ?></p>
                    <p><strong>Long Comment:</strong> <?php echo $row['long_comment']; ?></p>
                    <hr>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No comments yet.</p>
        <?php endif; ?>
    </div>

    <!-- Add a new comment -->
    <h3>Add a Comment</h3>
    <form action="add_comment.php" method="POST">
        <input type="hidden" name="iss_id" value="<?php echo $iss_id; ?>">
        <div class="form-group">
            <label for="short_comment">Short Comment</label>
            <input type="text" class="form-control" name="short_comment" required>
        </div>
        <div class="form-group">
            <label for="long_comment">Long Comment</label>
            <textarea class="form-control" name="long_comment" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Add Comment</button>
    </form>

</div>

<!-- Bootstrap JS and dependencies (jQuery and Popper) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
