<?php
session_start();

// DB connection setup
$servername = "localhost";
$db_username = "root";
$db_password = "";
$database = "movie_app";

$conn = new mysqli($servername, $db_username, $db_password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    die("Access denied. Please <a href='login.html'>log in</a>.");
}

// Get user ID from session
$username = $_SESSION['username'];
$stmt_user = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt_user->bind_param("s", $username);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user->num_rows !== 1) {
    die("User not found.");
}
$user = $result_user->fetch_assoc();
$user_id = $user['user_id'];
$stmt_user->close();

// Handle form submission for creating a list
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $list_name = $_POST['list_name'];
    $list_description = $_POST['list_description'];

    // Check if the list name is not empty
    if (!empty($list_name)) {
        // Insert the new list into the movie_list table
        $stmt = $conn->prepare("INSERT INTO movie_list (user_id, list_name) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $list_name);
        $stmt->execute();
        $stmt->close();

        // Redirect to the profile page after creating the list
        header("Location: profile.php");
        exit;
    } else {
        echo "List name is required.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Movie List</title>
</head>
<body>
    <h2>Create New Movie List</h2>
    <form method="POST">
        <label>List Name:
            <input type="text" name="list_name" required>
        </label><br><br>
        <label>List Description:
            <textarea name="list_description" rows="4" cols="50"></textarea>
        </label><br><br>
        <button type="submit">Create List</button>
    </form>
</body>
</html>