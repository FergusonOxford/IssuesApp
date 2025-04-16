<?php
// Start session to check if the user is logged in
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

// Handle add person form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_person'])) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'];
    $password = $_POST['password']; // Plain password
    $admin = $_POST['admin']; // 'Y' for admin, 'N' for non-admin

    
    

    // Salt ranodmly generated, but can be added if required
    $pwd_salt = bin2hex(random_bytes(32));; // You can leave this empty or add a custom salt

    // Generate the MD5 hash with the salt
    $pwd_hash = md5($password . $pwd_salt);

    // Insert new person into the database
    $stmt = $conn->prepare("INSERT INTO iss_persons (fname, lname, mobile, email, pwd_hash, pwd_salt, admin) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $fname, $lname, $mobile, $email, $pwd_hash, $pwd_salt, $admin);
    $stmt->execute();

    // Redirect to the persons list page after adding
    header("Location: persons_list.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Person</title>
    <!-- Include Bootstrap CSS for styling -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center mb-4">Add a New Person</h2>

    <!-- Form to Add New Person -->
    <form action="add_person.php" method="POST">
        <!-- First Name -->
        <div class="form-group">
            <label for="fname">First Name:</label>
            <input type="text" class="form-control" name="fname" placeholder="Enter first name" required>
        </div>

        <!-- Last Name -->
        <div class="form-group">
            <label for="lname">Last Name:</label>
            <input type="text" class="form-control" name="lname" placeholder="Enter last name" required>
        </div>

        <!-- Mobile Number -->
        <div class="form-group">
            <label for="mobile">Mobile:</label>
            <input type="text" class="form-control" name="mobile" placeholder="Enter mobile number" required>
        </div>

        <!-- Email -->
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" class="form-control" name="email" placeholder="Enter email" required>
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" class="form-control" name="password" placeholder="Enter password" required>
        </div>

        <!-- Admin Status -->
        <div class="form-group">
            <label for="admin">Admin Status:</label>
            <select class="form-control" name="admin" required>
                <option value="N">Non-Admin</option>
                <option value="Y">Admin</option>
            </select>
        </div>

        <!-- Submit Button -->
        <button type="submit" name="add_person" class="btn btn-primary btn-block">Add Person</button>
    </form>

    <br>
    <!-- Back to List Button -->
    <a href="persons_list.php" class="btn btn-secondary btn-block">Back to Person List</a>
</div>

<!-- Include Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>

<?php
// Close connection
$conn->close();
?>
