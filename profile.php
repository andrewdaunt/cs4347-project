<?php
session_start(); // Start the session at the very beginning

// DB connection setup
$servername = "localhost";
$db_username = "root"; // Default Laragon username
$db_password = "password";     // Default Laragon password (often empty initially)
$database = "movie_app";

$conn = new mysqli($servername, $db_username, $db_password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Check if user is logged in ---
if (!isset($_SESSION['username'])) {
    // User is not logged in, redirect them to the login page
    header("Location: login.html"); // Redirect to login or homepage
    exit();
}

// --- Check if the logged-in user is 'admin' ---
// If they are admin, redirect them to the admin dashboard
if ($_SESSION['username'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit(); // Stop processing this page immediately after redirect
}

// --- If the user is logged in AND NOT admin, continue displaying the profile ---

// Get user ID from username (User must be logged in and not admin at this point)
$username = $_SESSION['username'];
$stmt_user = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
// Add error checking for prepare
if ($stmt_user === false) {
    die("Error preparing user query: " . $conn->error);
}
$stmt_user->bind_param("s", $username);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

// Add check if user exists in DB (important even if session is set)
if ($result_user->num_rows !== 1) {
    // User not found in DB despite session - potentially invalid session or data issue
    // In a real app, you might want to log them out here
    die("Error: User data not found in database.");
}
$user = $result_user->fetch_assoc();
$user_id = $user['user_id'];
$stmt_user->close();

// Fetch user's reviews (using prepared statement)
$sql_reviews = "
    SELECT m.movie_title, r.review_id, r.review, r.rating
    FROM movie_review_rating r
    JOIN movie_review_user ru ON r.review_id = ru.review_id
    JOIN movie m ON ru.movie_id = m.movie_id
    WHERE ru.user_id = ?
";
$stmt_reviews = $conn->prepare($sql_reviews);
if ($stmt_reviews === false) {
     die("Error preparing reviews query: " . $conn->error);
}
$stmt_reviews->bind_param("i", $user_id);
$stmt_reviews->execute();
$result_reviews = $stmt_reviews->get_result();

// Fetch user's movie lists (using prepared statement)
$sql_lists = "SELECT list_id, list_name FROM movie_list WHERE user_id = ?";
$stmt_lists = $conn->prepare($sql_lists);
if ($stmt_lists === false) {
    die("Error preparing lists query: " . $conn->error);
}
$stmt_lists->bind_param("i", $user_id);
$stmt_lists->execute();
$result_lists = $stmt_lists->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile</title>
</head>
<body>
    <h2>My Profile (<?= htmlspecialchars($username) ?>)</h2> <h3>My Reviews</h3>
    <?php if ($result_reviews->num_rows > 0): ?>
        <form method="GET" action="discover.php">
            <label>
                <select name="reviews">
                    <option value="">-- Select a Review --</option>
                    <?php while ($review = $result_reviews->fetch_assoc()): ?>
                        <option value="<?= $review['review_id'] ?>">
                            <?= htmlspecialchars($review['movie_title']) ?> - <?= $review['rating'] ?>/10 -
                            <?= htmlspecialchars(mb_strimwidth($review['review'], 0, 50, "...")) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </label>
            <p><button type="submit">View Movie Details</button></p> </form>
    <?php else: ?>
        <p>You have not submitted any reviews yet.</p>
    <?php endif; ?>


    <h3>My Lists</h3>
     <?php if ($result_lists->num_rows > 0): ?>
        <form method="GET" action="edit_list.php">
            <label>
                <select name="movie_lists">
                    <option value="">-- Select a List --</option>
                    <?php while ($list = $result_lists->fetch_assoc()): ?>
                        <option value="<?= $list['list_id'] ?>">
                            <?= htmlspecialchars($list['list_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </label>
            <p><button type="submit">Edit List</button></p>
        </form>
    <?php else: ?>
         <p>You have not created any movie lists yet.</p>
    <?php endif; ?>

    <br>
    <a href="create_list.php"><button type="button">Create New Movie List</button></a>

    <br>

    <form action="create_review.php" method="post">
    <button type="submit">Create New Review</button>
    </form>
    
    <br>

    <form action="Discover.php" method="post">
    <button type="submit">Discover Page</button>
    </form>

    <br><br>

    <form action="logout.php" method="post">
    <button type="submit">Logout</button>
    </form>


</body>
</html>

<?php
$stmt_reviews->close();
$stmt_lists->close();
$conn->close();
?>