<?php
// Start session to check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require '../database/database.php';
$pdo = Database::connect();

// Delete person logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_person'])) {
    $delete_person_id = $_POST['id'];
    $is_admin = $_SESSION['admin'];
    $user_id = $_SESSION['user_id'];

    // Only admins can delete other users
    if ($is_admin !== 'Y' && $user_id != $delete_person_id) {
        session_destroy();
        header("Location: login.php");
        exit();
    }

    $stmt = $pdo->prepare("DELETE FROM iss_persons WHERE id = ?");
    $stmt->execute([$delete_person_id]);

    header("Location: persons_list.php");
    exit();
}

// Update person logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_person'])) {
    $id     = $_POST['id'];
    $fname  = $_POST['fname'];
    $lname  = $_POST['lname'];
    $mobile = $_POST['mobile'];
    $email  = $_POST['email'];
    $admin  = $_SESSION['admin'];

    $user_id = $_SESSION['user_id'];

    // Users can only edit their own info unless they are admin
    if ($admin !== 'Y' && $user_id != $id) {
        header("Location: persons_list.php");
        exit();
    }

    if ($admin === 'Y') {
        $admin_flag = $_POST['admin']; // Make sure this is coming from a form input if admin editing
        $stmt = $pdo->prepare("UPDATE iss_persons SET fname = ?, lname = ?, mobile = ?, email = ?, `admin` = ? WHERE id = ?");
        $stmt->execute([$fname, $lname, $mobile, $email, $admin_flag, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE iss_persons SET fname = ?, lname = ?, mobile = ?, email = ? WHERE id = ?");
        $stmt->execute([$fname, $lname, $mobile, $email, $id]);
    }

    header("Location: persons_list.php");
    exit();
}

// Fetch all persons
$stmt = $pdo->query("SELECT id, fname, lname, mobile, email, pwd_hash, pwd_salt, `admin` FROM iss_persons");
$persons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Don't forget to disconnect
Database::disconnect();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Person List</title>
    <!-- Include Bootstrap for table styling -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<!-- Logout Button -->
<a href="logout.php"><button class="btn btn-danger">Logout</button></a>

<?php if ($_SESSION['admin'] == 'Y') { ?> 
<!-- Add New Person Button -->
<a href="add_person.php"><button class="btn btn-primary">Add New Person</button></a>
<?php } ?>
<a href="issue_list.php">
    <button class="btn btn-primary">View Issues List</button>
</a>

<a href="comment_list.php">
    <button class="btn btn-primary">View Comment List</button>
</a>

<!-- Person List Heading -->
<h2 class="text-center mt-4">Person List</h2>

<!-- Table to Display Person Data -->
<table class="table table-bordered mt-4">
    <thead>
        <tr>
            <th>ID</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Mobile</th>
            <th>Email</th>
            <th>Admin</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($persons as $row): ?>


            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['fname']; ?></td>
                <td><?php echo $row['lname']; ?></td>
                <td><?php echo $row['mobile']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo $row['admin'] == 'Y' ? 'Yes' : 'No'; ?></td>
                <td>
                    <!-- View Details Button -->
                    <button class="btn btn-info" data-toggle="modal" data-target="#viewModal<?php echo $row['id']; ?>">View</button>
                    
                   <!-- to not show the buttons if user doesn't have the proper permissions -->

                    <?php if($_SESSION['admin'] == 'Y' || $_SESSION['user_id'] == $row['id']) { ?>
                    <!-- Edit Button -->
                    <button class="btn btn-warning" data-toggle="modal" data-target="#editModal<?php echo $row['id']; ?>">Edit</button>
                    
                    <!-- Delete Button -->
                    <button class="btn btn-danger" data-toggle="modal" data-target="#deleteModal<?php echo $row['id']; ?>">Delete</button>
                   
                    <?php }?>
                </td>
            </tr>

            <!-- View Modal -->
            <div class="modal fade" id="viewModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewModalLabel">View Person Details</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p><strong>First Name:</strong> <?php echo $row['fname']; ?></p>
                            <p><strong>Last Name:</strong> <?php echo $row['lname']; ?></p>
                            <p><strong>Mobile:</strong> <?php echo $row['mobile']; ?></p>
                            <p><strong>Email:</strong> <?php echo $row['email']; ?></p>
                            <p><strong>Admin:</strong><?php echo $row['admin'] == 'Y' ? 'Yes' : 'No'; ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Modal (Example) -->
            <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel">Edit Person Details</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form action="persons_list.php" method="POST">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <div class="form-group">
                                    <label for="fname">First Name:</label>
                                    <input type="text" class="form-control" name="fname" value="<?php echo $row['fname']; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="lname">Last Name:</label>
                                    <input type="text" class="form-control" name="lname" value="<?php echo $row['lname']; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="mobile">Mobile:</label>
                                    <input type="text" class="form-control" name="mobile" value="<?php echo $row['mobile']; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="email">Email:</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo $row['email']; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="admin">Admin:</label>
                                    <select class="form-control" name="admin">
                                        <option value="Y" <?php echo $row['admin'] == 'Y'? 'selected' : ''; ?>>Yes</option>
                                        <option value="N" <?php echo $row['admin'] == 'N'? 'selected' : ''; ?>>No</option>
                                    </select>
                                </div>
                                <button type="submit" name="update_person" class="btn btn-primary">Save Changes</button>
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
                            <p>Are you sure you want to delete this person?</p>
                        </div>
                        <div class="modal-footer">
                            <form action="persons_list.php" method="POST">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="delete_person" vclass="btn btn-danger">Delete</button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Include Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>


