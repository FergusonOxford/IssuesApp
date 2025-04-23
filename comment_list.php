<?php
// Start session
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

// Include the PDO connection
require '../database/database.php'; 
$pdo = Database::connect(); // Get the PDO connection

// Fetch comments and related data
$sql = "SELECT c.id AS comment_id, c.short_comment, c.long_comment, c.posted_date, 
               p.fname, p.lname, i.short_description AS issue_title, c.per_id
        FROM iss_comments c
        LEFT JOIN iss_persons p ON c.per_id = p.id
        LEFT JOIN iss_issues i ON c.iss_id = i.id
        ORDER BY c.posted_date DESC";

// Update comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_comment'])) {
    $comment_id = $_POST['comment_id'];
    $short_comment = $_POST['short_comment'];
    $long_comment = $_POST['long_comment'];
    $per_id = $_POST['per_id'];

    if ($_SESSION['admin'] != 'Y' && $_SESSION['user_id'] != $per_id) {
        header("Location: comment_list.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE iss_comments SET short_comment = ?, long_comment = ? WHERE id = ?");
        $stmt->execute([$short_comment, $long_comment, $comment_id]);
        echo "Comment updated successfully.";
    } catch (PDOException $e) {
        echo "Error updating comment: " . $e->getMessage();
    }
}

// Delete comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    $comment_id = $_POST['comment_id'];
    $per_id = $_POST['per_id'];

    if ($_SESSION['admin'] != 'Y' && $_SESSION['user_id'] != $per_id) {
        header("Location: comment_list.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM iss_comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        echo "Comment deleted successfully.";
    } catch (PDOException $e) {
        echo "Error deleting comment: " . $e->getMessage();
    }
}

try {
    $result = $pdo->query($sql);
} catch (PDOException $e) {
    echo "Error fetching comments: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Comments</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<!-- Logout Button -->
<a href="logout.php"><button class="btn btn-danger">Logout</button></a>

<!-- Send to issue list Button -->
<a href="issue_list.php">
    <button class="btn btn-primary">View Issue List</button>
</a>

<!-- Send to persons_list Button -->
<a href="persons_list.php">
    <button class="btn btn-primary">View Persons List</button>
</a>

<!-- Send to add comment Button -->
<a href="add_comment.php">
    <button class="btn btn-primary">Add Comment List</button>
</a>

<div class="container mt-5">
    <h2>All Comments</h2>

    <!-- Table to Display Comments -->
    <table class="table table-bordered mt-4">
        <thead>
            <tr>
                <th>Comment ID</th>
                <th>Issue Title</th>
                <th>Commented By</th>
                <th>Short Comment</th>
                <th>Long Comment</th>
                <th>Posted Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch(PDO::FETCH_ASSOC)): 
                
                // checking if name exists for user, puts deleted user if user DNE anymore 
                $name = ($row['fname'] && $row['lname']) 
                ? htmlspecialchars($row['fname'] . ' ' . $row['lname']) 
                : "Deleted User";
                
                ?>
                <tr>
                    <td><?php echo $row['comment_id']; ?></td>
                    <td><?php echo $row['issue_title']; ?></td>
                    <td><?php echo $name; ?></td>
                    <td><?php echo $row['short_comment']; ?></td>
                    <td><?php echo $row['long_comment']; ?></td>
                    <td><?php echo $row['posted_date']; ?></td>
                    <td>

                    <!-- View Button -->
                    <button class="btn btn-info" data-toggle="modal" data-target="#viewModal<?php echo $row['comment_id']; ?>">View</button>
                        <?php if ($_SESSION['admin'] == 'Y' || $_SESSION['user_id'] == $row['per_id']) { ?>
                            

                            <!-- Edit Button -->
                            <button class="btn btn-warning" data-toggle="modal" data-target="#editModal<?php echo $row['comment_id']; ?>">Edit</button>

                            <!-- Delete Button -->
                            <button class="btn btn-danger" data-toggle="modal" data-target="#deleteModal<?php echo $row['comment_id']; ?>">Delete</button>
                        <?php } ?>
                    </td>
                </tr>

                <!-- View Modal -->
                <div class="modal fade" id="viewModal<?php echo $row['comment_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="viewModalLabel">View Comment</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p><strong>Issue Title:</strong> <?php echo $row['issue_title']; ?></p>
                                <p><strong>Commented By:</strong> <?php echo $name; ?></p>
                                <p><strong>Short Comment:</strong> <?php echo $row['short_comment']; ?></p>
                                <p><strong>Long Comment:</strong> <?php echo $row['long_comment']; ?></p>
                                <p><strong>Posted Date:</strong> <?php echo $row['posted_date']; ?></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?php echo $row['comment_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editModalLabel">Edit Comment</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form action="comment_list.php" method="POST">
                                    <input type="hidden" name="comment_id" value="<?php echo $row['comment_id']; ?>">
                                    <input type="hidden" name="per_id" value="<?php echo $row['per_id']; ?>">
                                    <div class="form-group">
                                        <label for="short_comment">Short Comment:</label>
                                        <input type="text" class="form-control" name="short_comment" value="<?php echo $row['short_comment']; ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="long_comment">Long Comment:</label>
                                        <textarea class="form-control" name="long_comment" rows="4" required><?php echo $row['long_comment']; ?></textarea>
                                    </div>
                                    <button type="submit" name="update_comment" class="btn btn-primary">Save Changes</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade" id="deleteModal<?php echo $row['comment_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteModalLabel">Delete Comment</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this comment?</p>
                            </div>
                            <div class="modal-footer">
                                <form action="comment_list.php" method="POST">
                                    <input type="hidden" name="comment_id" value="<?php echo $row['comment_id']; ?>">
                                    <input type="hidden" name="per_id" value="<?php echo $row['per_id']; ?>">
                                    <button type="submit" name="delete_comment" class="btn btn-danger">Delete</button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>

<?php
// Close the PDO connection
$pdo = null;
?>
