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
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if username already exists
    $check_sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        header("Location: http://localhost:8080/register.html");
        exit();
    } else {
        // Insert new user
        $insert_sql = "INSERT INTO users (username, user_password) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ss", $username, $password);

        if ($insert_stmt->execute()) {
            // Save username and password to session for sitewide access
            $_SESSION['username'] = $username;
            $_SESSION['password'] = $password;
            echo "Account created! <a href='login.php'>Log in</a>";
            header("Location: http://localhost:8080/profile.php");
            exit();
        } else {
            echo "Error: " . $conn->error;
        }

        $insert_stmt->close();
    }

    $stmt->close();
}

$conn->close();
?>
