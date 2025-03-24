<?php
// Start session to track user login
session_start();

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: issue_list.php"); // Redirect to dashboard if logged in
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";  // Your DB username
$password = "";      // Your DB password
$dbname = "cis355"; // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve email and password from form
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
        exit();
    }

    // Check if the email exists in the database
    $sql = "SELECT id, pwd_hash, pwd_salt FROM iss_persons WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $stored_hash, $stored_salt);
        $stmt->fetch();

        // Generate the MD5 hash with the salt
        $hashed_password = md5($password . $stored_salt);

        // Verify if the password matches
        if ($hashed_password == $stored_hash) {
            // Password is correct, set session and redirect to dashboard
            $_SESSION['user_id'] = $user_id;
            header("Location: issue_list.php");
            exit();
        } else {
            echo "Invalid credentials!";
        }
    } else {
        echo "No user found with that email!";
    }

    $stmt->close();
}

$conn->close();
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
