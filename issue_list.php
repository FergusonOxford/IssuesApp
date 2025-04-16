<?php
// Start session to check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

require '../database/database.php';

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

// Handle comment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $short_comment = $_POST['short_comment'];
    $long_comment = $_POST['long_comment'];
    $iss_id = $_POST['iss_id'];
    $per_id = $_SESSION['user_id']; // Get the logged-in user's ID

   
    
    // Prepare the SQL query with placeholders
    $stmt = $conn->prepare("INSERT INTO iss_comments (per_id, iss_id, short_comment, long_comment, posted_date) 
    VALUES (?, ?, ?, ?, NOW())");

    // Bind the parameters to the placeholders
    $stmt->bind_param("iiss", $per_id, $iss_id, $short_comment, $long_comment);

    // Execute the statement
    $stmt->execute();

    // Close the prepared statement
    $stmt->close();


    header("Location: issue_list.php");
    exit();
    

}

// Handle delete comment request
if (isset($_GET['delete_comment_id'])) {
    $delete_comment_id = $_GET['delete_comment_id'];
    $stmt = $conn->prepare("DELETE FROM iss_comments WHERE id = ?");
    $stmt->bind_param("i", $delete_comment_id);
     // Execute the query
     $stmt->execute();

     // Close the prepared statement
     $stmt->close();
    header("Location: issue_list.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_issue'])) 
{

    $delete_issue_id = $_POST['id'];

 // this is for deleting an issue will have to work on
 if($_SESSION['admin'] != 'Y' && $_SESSION['user_id'] != $delete_issue_id)
 {
    header("Location: issue_list.php");
   exit();

 }

   
    $stmt = $conn->prepare("DELETE FROM iss_issues WHERE id = ?");
    $stmt->bind_param("i", $delete_issue_id);
     // Execute the query
     $stmt->execute();

     // Close the prepared statement
     $stmt->close();
    header("Location: issue_list.php");
    exit();

}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_issue'])) {
    
    
    if(isset($_FILES['pdf_attachment']))
    {
        $fileTmpPath = $_FILES['pdf_attachment']['tmp_name'];
        $fileName = $_FILES['pdf_attachment']['name'];
        $fileSize = $_FILES['pdf_attachment']['size'];
        $fileType = $_FILES['pdf_attachment']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        if($fileExtension !== 'pdf')
        {
            die("Only PDF files allowed");

        }

        if($fileSize > 2 * 1024 * 1024)
        {
            die("File size exceeds 2MB limit");
        }
        $newFileName = MD5(time() . $fileName) . "." . $fileExtension;
        $uploadFileDir = './uploads/';
        $dest_path = $uploadFileDir.$newFileName;

        #if no exist create dir 
        if(!is_dir($uploadFileDir))
        {
            #files directory, permissions, idk
            mkdir($uploadFileDir, 0755, true);
        }

        if(move_uploaded_file($fileTmpPath, $dest_path))
        {
                $attachmentPath = $dest_path;

        }
        else
        {
            die("error moving file");
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
 
 

    // this is for updating an issue will have to work on
    if($_SESSION['admin'] != 'Y' && $_SESSION['user_id'] != $assigned_person)
    {
       header("Location: issue_list.php");
      exit();

    }


    // Update SQL query
    $stmt = $conn->prepare("UPDATE iss_issues SET short_description = ?, long_description = ?, priority = ?, org = ?, project = ?, open_date = ?, close_date = ?, per_id = ?, pdf_attachment = ? WHERE id = ?");
    $stmt->bind_param("sssssssssi", $short_description, $long_description, $priority, $org, $project, $open_date, $close_date, $assigned_person, $newFileName, $id);

    // Execute the query
    if ($stmt->execute()) {
        echo "Record updated successfully.";
    } else {
        echo "Error updating record: " . $stmt->error;
    }
    // Close the prepared statement
    $stmt->close();

}
// Fetch all issues
$result = $conn->query("SELECT iss_issues.*, iss_persons.fname AS assigned_person_fname, iss_persons.lname AS assigned_person_lname
FROM iss_issues
INNER JOIN iss_persons ON iss_issues.per_id = iss_persons.id;
"); // Join to get assigned person's name
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
        <?php while ($row = $result->fetch_assoc()): ?>
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
                        // Fetch comments for the current issue
                        $comments_result = $conn->query("SELECT c.*, p.fname, p.lname FROM iss_comments c 
                                                        INNER JOIN iss_persons p ON c.per_id = p.id 
                                                        WHERE c.iss_id = " . $row['id']);
                        while ($comment = $comments_result->fetch_assoc()):
                    ?>
                        <div class="comment" id="comment-<?php echo $comment['id']; ?>">
                            <p><strong><?php echo $comment['fname'] . " " . $comment['lname']; ?>:</strong> 
                                <span class="comment-text" id="short-comment-<?php echo $comment['id']; ?>"><?php echo $comment['short_comment']; ?></span>
                            </p>
                            <p><span class="comment-text" id="long-comment-<?php echo $comment['id']; ?>"><?php echo $comment['long_comment']; ?></span></p>
                            <p><small>Posted on: <?php echo $comment['posted_date']; ?></small></p>

                            <?php if ($comment['per_id'] == $_SESSION['user_id']): ?>
                                <!-- Edit Button -->
                                <button class="btn btn-warning btn-sm" onclick="editComment(<?php echo $comment['id']; ?>)">Edit</button>
                                <!-- Delete Button -->
                                <a href="issue_list.php?delete_comment_id=<?php echo $comment['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                            <?php endif; ?>

                            <!-- Edit Comment Form (hidden by default) -->
                            <form action="issue_list.php" method="POST" id="edit-comment-form-<?php echo $comment['id']; ?>" style="display:none;">
                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                <div class="form-group">
                                    <label for="short_comment">Short Comment:</label>
                                    <input type="text" class="form-control" name="short_comment" id="edit-short-comment-<?php echo $comment['id']; ?>" value="<?php echo $comment['short_comment']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="long_comment">Long Comment:</label>
                                    <textarea class="form-control" name="long_comment" id="edit-long-comment-<?php echo $comment['id']; ?>" required><?php echo $comment['long_comment']; ?></textarea>
                                </div>
                                <button type="submit" name="edit_comment" class="btn btn-primary">Save Changes</button>
                                <button type="button" class="btn btn-secondary" onclick="cancelEdit(<?php echo $comment['id']; ?>)">Cancel</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Add Comment Form -->
                <h5>Add Comment</h5>
                <form action="issue_list.php" method="POST">
                    <input type="hidden" name="iss_id" value="<?php echo $row['id']; ?>">
                    <div class="form-group">
                        <label for="short_comment">Short Comment:</label>
                        <input type="text" class="form-control" name="short_comment" placeholder="Enter short comment" required>
                    </div>
                    <div class="form-group">
                        <label for="long_comment">Long Comment:</label>
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
                            $persons_result = $conn->query("SELECT id, fname, lname FROM iss_persons");

                            // Populate the dropdown with all persons
                            while ($person = $persons_result->fetch_assoc()) {
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

        <?php endwhile; ?>
    </tbody>
</table>

<!-- Include jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>

<?php
// Close connection
$conn->close();
?>
