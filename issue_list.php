<?php
// Start session to check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

require '../database/database.php'; // Ensure this includes the PDO database connection

// Connect to the database
$pdo = Database::connect();

// Handle comment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $short_comment = $_POST['short_comment'];
    $long_comment = $_POST['long_comment'];
    $iss_id = $_POST['iss_id'];
    $per_id = $_SESSION['user_id']; // Get the logged-in user's ID

    // Prepare the SQL query with placeholders
    $stmt = $pdo->prepare("INSERT INTO iss_comments (per_id, iss_id, short_comment, long_comment, posted_date) 
    VALUES (?, ?, ?, ?, NOW())");

    // Bind the parameters to the placeholders
    $stmt->bindValue(1, $per_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $iss_id, PDO::PARAM_INT);
    $stmt->bindValue(3, $short_comment, PDO::PARAM_STR);
    $stmt->bindValue(4, $long_comment, PDO::PARAM_STR);

    // Execute the statement
    $stmt->execute();

    // Redirect after success
    header("Location: issue_list.php");
    exit();
}

// Edit comment handling
if (isset($_POST['edit_comment_submit'])) {
    $comment_id = intval($_POST['comment_id']);
    $short_comment = $_POST['short_comment'];
    $long_comment = $_POST['long_comment'];

    // Check if the logged-in user owns the comment
    $check = $pdo->prepare("SELECT per_id FROM iss_comments WHERE id = ?");
    $check->execute([$comment_id]);
    
    if ($check->rowCount() > 0) {
        $owner = $check->fetch(PDO::FETCH_ASSOC);
        if ($owner['per_id'] == $_SESSION['user_id']) {
            $update = $pdo->prepare("UPDATE iss_comments SET short_comment = ?, long_comment = ? WHERE id = ?");
            $update->execute([$short_comment, $long_comment, $comment_id]);
        } else {
            echo "Unauthorized action.";
        }
    }

    // Redirect after success
    header("Location: issue_list.php");
    exit;
}

// Handle delete comment request
if (isset($_GET['delete_comment_id'])) {
    $delete_comment_id = $_GET['delete_comment_id'];
    $stmt = $pdo->prepare("DELETE FROM iss_comments WHERE id = ?");
    $stmt->execute([$delete_comment_id]);

    // Redirect after deletion
    header("Location: issue_list.php");
    exit();
}

// Handle delete issue request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_issue'])) {
    $delete_issue_id = $_POST['id'];

    if ($_SESSION['admin'] != 'Y' && $_SESSION['user_id'] != $delete_issue_id) {
        header("Location: issue_list.php");
        exit();
    }

    $stmt = $pdo->prepare("DELETE FROM iss_issues WHERE id = ?");
    $stmt->execute([$delete_issue_id]);

    // Redirect after deletion
    header("Location: issue_list.php");
    exit();
}

// Handle issue update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_issue'])) {
    $newFileName = null;

    // Handle file upload
    if (isset($_FILES['pdf_attachment']) && $_FILES['pdf_attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        $fileTmpPath = $_FILES['pdf_attachment']['tmp_name'];
        $fileName = $_FILES['pdf_attachment']['name'];
        $fileSize = $_FILES['pdf_attachment']['size'];
        $fileType = $_FILES['pdf_attachment']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        if ($fileExtension !== 'pdf') {
            die("Only PDF files allowed");
        }

        if ($fileSize > 2 * 1024 * 1024) {
            die("File size exceeds 2MB limit");
        }

        $newFileName = MD5(time() . $fileName) . "." . $fileExtension;
        $uploadFileDir = './uploads/';
        $dest_path = $uploadFileDir . $newFileName;

        // If the upload directory doesn't exist, create it
        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0755, true);
        }

        if (!move_uploaded_file($fileTmpPath, $dest_path)) {
            die("Error moving file");
        }
    }

    // Get form data
    $id = $_POST['id'];
    $short_description = $_POST['short_description'];
    $long_description = $_POST['long_description'];
    $priority = $_POST['priority'];
    $org = $_POST['org'];
    $project = $_POST['project'];
    $open_date = $_POST['open_date'];
    $close_date = $_POST['close_date'];
    $assigned_person = $_POST['assigned_person'];

    // Validate user permissions for updating the issue
    if ($_SESSION['admin'] != 'Y' && $_SESSION['user_id'] != $assigned_person) {
        header("Location: issue_list.php");
        exit();
    }

    if ($newFileName !== null) {
        // Update issue with attachment
        $stmt = $pdo->prepare("UPDATE iss_issues SET short_description = ?, long_description = ?, priority = ?, org = ?, project = ?, open_date = ?, close_date = ?, per_id = ?, pdf_attachment = ? WHERE id = ?");
        $stmt->execute([$short_description, $long_description, $priority, $org, $project, $open_date, $close_date, $assigned_person, $newFileName, $id]);
    } else {
        // Update issue without attachment
        $stmt = $pdo->prepare("UPDATE iss_issues SET short_description = ?, long_description = ?, priority = ?, org = ?, project = ?, open_date = ?, close_date = ?, per_id = ? WHERE id = ?");
        $stmt->execute([$short_description, $long_description, $priority, $org, $project, $open_date, $close_date, $assigned_person, $id]);
    }

    // Redirect after update
    header("Location: issue_list.php");
    exit();
}

// Set up pagination
$limit = 5; // Issues per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Get filter from query string or default to 'open'
$filter = isset($_GET['issue_filter']) ? $_GET['issue_filter'] : 'open';

// Count total number of issues (without LIMIT) to calculate total pages
if ($filter == 'all') {
    $count_query = "SELECT COUNT(*) AS total FROM iss_issues";
} else {
    $count_query = "SELECT COUNT(*) AS total FROM iss_issues WHERE close_date IS NULL OR close_date > NOW()";
}

$stmt = $pdo->query($count_query);
$count_row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_issues = $count_row['total'];
$total_pages = ceil($total_issues / $limit);

// Main SELECT query to fetch issues (with LIMIT + OFFSET)
if ($filter == 'all') {
    $sql = "SELECT iss_issues.*, iss_persons.fname AS assigned_person_fname, iss_persons.lname AS assigned_person_lname
            FROM iss_issues
            INNER JOIN iss_persons ON iss_issues.per_id = iss_persons.id
            ORDER BY iss_issues.close_date DESC
            LIMIT :limit OFFSET :offset";
} else {
    $sql = "SELECT iss_issues.*, iss_persons.fname AS assigned_person_fname, iss_persons.lname AS assigned_person_lname
            FROM iss_issues
            INNER JOIN iss_persons ON iss_issues.per_id = iss_persons.id
            WHERE close_date IS NULL OR close_date > NOW()
            ORDER BY iss_issues.close_date DESC
            LIMIT :limit OFFSET :offset";
}

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

// Fetch all issues
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Management</title>
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<!-- Logout Button -->
<a href="logout.php"><button class="btn btn-danger">Logout</button></a>

<!-- Add New Issue Button -->
<a href="add_issue.php"><button class="btn btn-primary">Add New Issue</button></a>

<!-- Send to persons_list Button -->
<a href="persons_list.php">
    <button class="btn btn-primary">View Persons List</button>
</a>

<!-- Send to comment_list Button -->
<a href="comment_list.php">
    <button class="btn btn-primary">View Comment List</button>
</a>





<h2>Issue List</h2>

<!-- Issue Filter -->
<form method="GET" class="mb-3">
    <select name="issue_filter" class="form-control" style="width: 200px; display: inline-block;">
        <option value="open" <?php echo isset($_GET['issue_filter']) && $_GET['issue_filter'] == 'open' ? 'selected' : ''; ?>>Open Issues</option>
        <option value="all" <?php echo isset($_GET['issue_filter']) && $_GET['issue_filter'] == 'all' ? 'selected' : ''; ?>>All Issues</option>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
</form>
<table class="table table-bordered">
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
    <?php foreach ($issues as $row): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['short_description']; ?></td>
                <td><?php echo $row['priority']; ?></td>
                <td><?php echo $row['org']; ?></td>
                <td><?php echo $row['project']; ?></td>
                <td><?php echo $row['open_date']; ?></td>
                <td><?php echo $row['close_date']; ?></td>
                <td>
                    <button class="btn btn-info" data-toggle="modal" data-target="#readModal<?php echo $row['id']; ?>">R</button>

                    <?php if($_SESSION['admin'] == 'Y' || $_SESSION['user_id'] == $row['per_id']) { ?>

                        

                    <button class="btn btn-warning" data-toggle="modal" data-target="#updateModal<?php echo $row['id']; ?>">U</button>
                    <button class="btn btn-danger" data-toggle="modal" data-target="#deleteModal<?php echo $row['id']; ?>">D</button>
                    <?php }?>
                </td>
            </tr>

<!-- Read Modal -->
<div class="modal" id="readModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Issue Details (Read)</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p><strong>Short Description:</strong> <?php echo $row['short_description']; ?></p>
                <p><strong>Long Description:</strong> <?php echo $row['long_description']; ?></p>
                <p><strong>Priority:</strong> <?php echo $row['priority']; ?></p>
                <p><strong>Organization:</strong> <?php echo $row['org']; ?></p>
                <p><strong>Project:</strong> <?php echo $row['project']; ?></p>
                <p><strong>Open Date:</strong> <?php echo $row['open_date']; ?></p>
                <p><strong>Close Date:</strong> <?php echo $row['close_date']; ?></p>
                <p><strong>Assigned Person:</strong> <?php echo $row['assigned_person_fname'] . " " . $row['assigned_person_lname'] ?></p>

                <!-- Comments Section -->
                <h5>Comments</h5>
                <div class="comments-list">
                <?php
// Fetch comments for the current issue using PDO
$stmt = $pdo->prepare("SELECT c.*, p.fname, p.lname FROM iss_comments c 
                        INNER JOIN iss_persons p ON c.per_id = p.id 
                        WHERE c.iss_id = :iss_id");
$stmt->bindValue(':iss_id', $row['id'], PDO::PARAM_INT); // Bind the issue ID dynamically
$stmt->execute();

while ($comment = $stmt->fetch(PDO::FETCH_ASSOC)):
?>
   <div class="comment" id="comment-<?php echo $comment['id']; ?>">
        <p><strong><?php echo $comment['fname'] . " " . $comment['lname']; ?>:</strong> 
             <span class="comment-text" id="short-comment-<?php echo $comment['id']; ?>"><?php echo htmlspecialchars($comment['short_comment']); ?></span>
        </p>
        <p><span class="comment-text" id="long-comment-<?php echo $comment['id']; ?>"><?php echo htmlspecialchars($comment['long_comment']); ?></span></p>
        <p><small>Posted on: <?php echo $comment['posted_date']; ?></small></p>

        <?php
        // Before your main HTML, near the top of the PHP section
        $editing_comment_id = isset($_POST['edit_comment']) ? intval($_POST['edit_comment']) : null;
        ?>

        <?php if ($comment['per_id'] == $_SESSION['user_id']): ?>
            <!-- Edit Button as form -->
            <form method="POST" style="display:inline;">
                <input type="hidden" name="iss_id" value="<?php echo $row['id']; ?>">
                <input type="hidden" name="open_modal_id" value="<?php echo $row['id']; ?>">
                <button type="submit" name="edit_comment" value="<?php echo $comment['id']; ?>" class="btn btn-warning btn-sm">Edit</button>
            </form>

            <!-- Delete Button -->
            <a href="issue_list.php?delete_comment_id=<?php echo $comment['id']; ?>" class="btn btn-danger btn-sm">Delete</a>

            <!-- Only show the edit form if this comment is being edited -->
            <?php if ($editing_comment_id == $comment['id']): ?>
                <form action="issue_list.php" method="POST" style="margin-top:10px;">
                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                    <input type="hidden" name="iss_id" value="<?php echo $row['id']; ?>">

                    <div class="form-group">
                        <label for="short_comment">Title:</label>
                        <input type="text" class="form-control" name="short_comment" value="<?php echo htmlspecialchars($comment['short_comment']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="long_comment">Body:</label>
                        <textarea class="form-control" name="long_comment" required><?php echo htmlspecialchars($comment['long_comment']); ?></textarea>
                    </div>

                    <button type="submit" name="edit_comment_submit" class="btn btn-primary">Save Changes</button>
                    <a href="issue_list.php?id=<?php echo $row['id']; ?>" class="btn btn-secondary">Cancel</a>
                </form>
            <?php endif; ?>
        <?php endif; ?>
   </div>
<?php endwhile; ?>

                </div>

                <!-- Add Comment Form -->
                <h5>Add Comment</h5>
                <form action="issue_list.php" method="POST">
                    <input type="hidden" name="iss_id" value="<?php echo $row['id']; ?>">
                    <div class="form-group">
                        <label for="short_comment">Title:</label>
                        <input type="text" class="form-control" name="short_comment" placeholder="Enter short comment" required>
                    </div>
                    <div class="form-group">
                        <label for="long_comment">Body:</label>
                        <textarea class="form-control" name="long_comment" placeholder="Enter long comment" required></textarea>
                    </div>
                    <button type="submit" name="add_comment" class="btn btn-primary">Add Comment</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_POST['open_modal_id'])): ?>
<script>
    $(document).ready(function(){
        $('#readModal<?php echo $_POST['open_modal_id']; ?>').modal('show');
    });
</script>
<?php endif; ?>
<script>
    // Function to toggle edit form visibility when "Edit" button is clicked
    function editComment(commentId) {
        
        // Hide the current comment text and show the edit form
        document.getElementById('short-comment-' + commentId).style.display = 'none';
        document.getElementById('long-comment-' + commentId).style.display = 'none';
        document.getElementById('edit-comment-form-' + commentId).style.display = 'block';
    }

    // Function to cancel editing and hide the edit form
    function cancelEdit(commentId) {
        // Hide the edit form and show the comment text again
        document.getElementById('short-comment-' + commentId).style.display = 'inline';
        document.getElementById('long-comment-' + commentId).style.display = 'inline';
        document.getElementById('edit-comment-form-' + commentId).style.display = 'none';
     }
</script>




  <!-- Edit Modal (Update) -->
<div class="modal fade" id="updateModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateModalLabel">Edit Issue Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="issue_list.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

                    <!-- Full Text Fields -->
                    <div class="form-group">
                        <label for="short_description">Short Description:</label>
                        <input type="text" class="form-control" name="short_description" value="<?php echo htmlspecialchars($row['short_description']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="long_description">Long Description:</label>
                        <textarea class="form-control" name="long_description"><?php echo htmlspecialchars($row['long_description']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="open_date">Open Date:</label>
                        <input type="date" class="form-control" name="open_date" value="<?php echo $row['open_date']; ?>">
                    </div>
                    <div class="form-group">
                        <label for="close_date">Close Date:</label>
                        <input type="date" class="form-control" name="close_date" value="<?php echo $row['close_date']; ?>">
                    </div>
                    <div class="form-group">
                        <label for="priority">Priority:</label>
                        <select class="form-control" name="priority">
                            <option value="Low" <?php echo $row['priority'] == 'Low' ? 'selected' : ''; ?>>Low</option>
                            <option value="Medium" <?php echo $row['priority'] == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="High" <?php echo $row['priority'] == 'High' ? 'selected' : ''; ?>>High</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="org">Organization:</label>
                        <input type="text" class="form-control" name="org" value="<?php echo htmlspecialchars($row['org']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="project">Project:</label>
                        <input type="text" class="form-control" name="project" value="<?php echo htmlspecialchars($row['project']); ?>">
                    </div>

                    <!-- Assigned Person's Name (Dropdown) -->
                    <div class="form-group">
                        <label for="assigned_person">Assigned Person:</label>
                        <select class="form-control" name="assigned_person">
                        <?php
// Query to get all persons for the dropdown
$stmt = $pdo->prepare("SELECT id, fname, lname FROM iss_persons");
$stmt->execute();

// Populate the dropdown with all persons
while ($person = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $selected = $row['per_id'] == $person['id'] ? 'selected' : '';
    echo '<option value="' . $person['id'] . '" ' . $selected . '>' . $person['fname'] . ' ' . $person['lname'] . '</option>';
}
?>

                        </select>
                    </div>

                    <!-- PDF Attachment -->
                    <div class="form-group">
                        <label for="pdf_attachment">PDF Attachment:</label>
                        <input type="file" class="form-control" name="pdf_attachment", value="<?php echo htmlspecialchars($row['pdf_attachment'])?>">
                        <?php if ($row['pdf_attachment']): ?>
                            <p>Current PDF: <a href="uploads/<?php echo htmlspecialchars($row['pdf_attachment']); ?>" target="_blank">View Current PDF</a></p>
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="update_issue" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>



            <!-- Delete Modal -->
            <div class="modal fade" id="deleteModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteModalLabel">Delete Person</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this issue?</p>
                        </div>
                        <div class="modal-footer">
                            <form action="issue_list.php" method="POST">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="delete_issue" class="btn btn-danger">Delete</button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php endforeach; ?>
    </tbody>
</table>

<!-- Pagination Links -->
<div class="pagination">
    <ul class="pagination">
        <?php if ($page > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>&issue_filter=<?php echo $filter; ?>">Previous</a></li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&issue_filter=<?php echo $filter; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&issue_filter=<?php echo $filter; ?>">Next</a></li>
        <?php endif; ?>
    </ul>
</div>

<!-- Include jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>


