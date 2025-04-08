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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_issue'])) 
{
    $newFileName = null;


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
  
    $short_description = $_POST['short_description'];
    $long_description = $_POST['long_description'];
    $open_date = $_POST['open_date'];
    $close_date = $_POST['close_date'];
    $priority = $_POST['priority'];
    $org = $_POST['org'];
    $project = $_POST['project'];
    $per_id = $_POST['per_id']; // Get the selected person ID from the dropdown
    // $newFileName is PDF attachment
    // $attachmentPath is entire path
    $pdf_attachment = $_POST['pdf_attachment'];

    // Insert new issue into the database
    $conn->query("INSERT INTO iss_issues (short_description, long_description, open_date, close_date, priority, org, project, per_id, pdf_attachment) 
                  VALUES ('$short_description', '$long_description', '$open_date', '$close_date', '$priority', '$org', '$project', '$per_id', '$newFileName')");

    // Redirect back to the issue list page after submitting the form
    header("Location: issue_list.php");
    exit();
}

// Fetch list of users to populate the person ID dropdown
$user_result = $conn->query("SELECT id, email FROM iss_persons"); // Assuming users table with id and username
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Issue</title>
    <!-- Bootstrap CSS for styling -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center mb-4">Add a New Issue</h2>

    <form action="add_issue.php" method="POST" enctype="multipart/form-data">
        <!-- Short Description -->
        <div class="form-group">
            <label for="short_description">Short Description:</label>
            <input type="text" class="form-control" name="short_description" placeholder="Enter short description" required>
        </div>

        <!-- Long Description -->
        <div class="form-group">
            <label for="long_description">Long Description:</label>
            <textarea class="form-control" name="long_description" placeholder="Enter long description" required></textarea>
        </div>

        <!-- Open Date -->
        <div class="form-group">
            <label for="open_date">Open Date:</label>
            <input type="date" class="form-control" name="open_date" required>
        </div>

        <!-- Close Date -->
        <div class="form-group">
            <label for="close_date">Close Date:</label>
            <input type="date" class="form-control" name="close_date">
        </div>

        <!-- Priority -->
        <div class="form-group">
            <label for="priority">Priority:</label>
            <select class="form-control" name="priority" required>
                <option value="Low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">High</option>
            </select>
        </div>

        <!-- Organization -->
        <div class="form-group">
            <label for="org">Organization:</label>
            <input type="text" class="form-control" name="org" placeholder="Enter organization" required>
        </div>

        <!-- Project -->
        <div class="form-group">
            <label for="project">Project:</label>
            <input type="text" class="form-control" name="project" placeholder="Enter project" required>
        </div>

        <!-- Person ID dropdown -->
        <div class="form-group">
            <label for="per_id">Assigned Person:</label>
            <select class="form-control" name="per_id" required>
                <option value="" disabled selected>Select a Person</option>
                <?php while ($user = $user_result->fetch_assoc()): ?>
                    <option value="<?php echo $user['id']; ?>"><?php echo $user['email']; ?></option>
                <?php endwhile; ?>
            </select>

        </div>

        <div class="form-group">
            <label for="pdf_attachment">PDF:</label>
            <input type="file" class="form-control" name="pdf_attachment" accept="application/pdf" />
        </div>
        <!-- Submit Button -->
        <button type="submit" name="add_issue" class="btn btn-primary btn-block">Add Issue</button>
    </form>

    <br>
    <a href="issue_list.php" class="btn btn-secondary btn-block">Back to Issue List</a>
</div>

<!-- Bootstrap JS and dependencies (jQuery and Popper) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>

<?php
// Close connection
$conn->close();
?>
