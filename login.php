<?php
session_start();
// Database connection
$servername = "localhost";
$db_username = "root";
$db_password = "password";
$database = "movie_app";

$conn = new mysqli($servername, $db_username, $db_password, $database);

// Check DB connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user input
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Lookup user in the database
    $sql = "SELECT user_id, user_password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if ($password === $row['user_password']) { 
            // Save username and password to session for sitewide access
            $_SESSION['username'] = $username;
            $_SESSION['password'] = $password;
            header("Location: http://localhost:8080/profile.php");
            exit();
        } else {
            // Login fail
            echo "Incorrect password.";
        }
    } else {
        // Login fail
        echo "User not found.";
    }

    $stmt->close();
}
$conn->close();
?>
