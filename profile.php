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

// Get user ID from username
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

// Fetch user's reviews
$sql_reviews = "
    SELECT m.movie_title, r.review_id, r.review, r.rating
    FROM movie_review_rating r
    JOIN movie_review_user ru ON r.review_id = ru.review_id
    JOIN movie m ON ru.movie_id = m.movie_id
    WHERE ru.user_id = ?
";
$stmt_reviews = $conn->prepare($sql_reviews);
$stmt_reviews->bind_param("i", $user_id);
$stmt_reviews->execute();
$result_reviews = $stmt_reviews->get_result();

// Fetch user's movie lists
$sql_lists = "SELECT list_id, list_name FROM movie_list WHERE user_id = ?";
$stmt_lists = $conn->prepare($sql_lists);
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
  <h2>My Profile</h2>

  <!-- Reviews -->
  <h3>My Reviews</h3>
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
    <p><button type="submit">Discover Movies</button></p>
  </form>

  <!-- Movie Lists -->
  <h3>My Lists</h3>
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

  <!-- Create New Movie List Button -->
  <br>
  <a href="create_list.php"><button type="button">Create New Movie List</button></a>

</body>
</html>

<?php
// Cleanup
$stmt_reviews->close();
$stmt_lists->close();
$conn->close();
?>