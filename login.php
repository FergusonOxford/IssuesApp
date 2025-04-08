<?php
// Include the database class for PDO connection
require '../database/database.php'; // Adjust the path if needed

// Start session to track user login
session_start();

//PROBABLY REMOVE THIS AND ADD SESSION_DESTROY FOR WHEN INVALID INFO IS ENTERED 

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: issue_list.php"); // Redirect to dashboard if logged in
    exit();
}

// Get the PDO connection from the Database class
$conn = Database::connect();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve email and password from form
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
        exit();
    }

    // Prepare and execute the query to check if the email exists
    $sql = "SELECT id, pwd_hash, pwd_salt, `admin` FROM iss_persons WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);

    // Check if any user was found with the provided email
    if ($stmt->rowCount() > 0) {
        // Fetch the result
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $user['id'];
        $stored_hash = $user['pwd_hash'];
        $stored_salt = $user['pwd_salt'];
        $fname = $user['fname'];
        $lname = $user['lname'];
        $admin = $user['admin'];

        // Generate the MD5 hash with the salt
        $hashed_password = md5($password . $stored_salt);

        // Verify if the password matches
        if ($hashed_password == $stored_hash) {
            // Password is correct, set session and redirect to dashboard
            $_SESSION['user_id'] = $user_id;
            // $_SESSION['user_name'] = $fname . $lname;
            // $_SESSION['email'] = $email;
            $_SESSION['admin'] = $admin;
            header("Location: issue_list.php");
            exit();
        } else {
            echo "Invalid credentials!";
        }
    } else {
        echo "No user found with that email!";
    }
}

// Disconnect from the database
Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <form method="POST" action="login.php">
        <label for="email">Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label for="password">Password:</label><br>
        <input type="password" name="password" required><br><br>

        <input type="submit" value="Login">
    </form>
</body>
</html>
