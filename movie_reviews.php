<?php
session_start();

// DB connection
$servername = "localhost";
$username = "root";
$password = "password";
$database = "movie_app";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    die("Access denied. Please <a href='login.html'>log in</a>.");
}

// Get movie_id
if (!isset($_GET['movie_id'])) {
    die("No movie selected.");
}
$movie_id = (int) $_GET['movie_id'];

// Get movie title
$stmt = $conn->prepare("SELECT movie_title FROM movie WHERE movie_id = ?");
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Movie not found.");
}
$movie_title = $result->fetch_assoc()['movie_title'];
$stmt->close();

// ✅ Get user ID based on session username
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
$user_id = $res->fetch_assoc()['user_id'];
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'], $_POST['review'])) {
    $rating = (int) $_POST['rating'];
    $review = trim($_POST['review']);

    // Validate rating
    if ($rating < 1 || $rating > 10) {
        die("Rating must be between 1 and 10.");
    }

    // Insert into movie_review_rating
    $stmt = $conn->prepare("INSERT INTO movie_review_rating (rating, review) VALUES (?, ?)");
    $stmt->bind_param("is", $rating, $review);
    $stmt->execute();
    $review_id = $stmt->insert_id;
    $stmt->close();

    // ✅ Use user_id from session here
    $stmt = $conn->prepare("INSERT INTO movie_review_user (review_id, user_id, movie_id) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $review_id, $user_id, $movie_id);
    $stmt->execute();
    $stmt->close();

    // Redirect to avoid form resubmission
    header("Location: movie_reviews.php?movie_id=$movie_id");
    exit;
}

// Fetch all reviews for the movie
$sql = "
    SELECT u.username, r.rating, r.review
    FROM movie_review_user mru
    JOIN movie_review_rating r ON mru.review_id = r.review_id
    JOIN users u ON mru.user_id = u.user_id
    WHERE mru.movie_id = ?
    ORDER BY r.rating DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$reviews = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reviews for <?= htmlspecialchars($movie_title) ?></title>
</head>
<body>
  <h2>Reviews for <?= htmlspecialchars($movie_title) ?></h2>

  <?php if ($reviews->num_rows === 0): ?>
    <p>No reviews yet.</p>
  <?php else: ?>
    <ul>
      <?php while ($review = $reviews->fetch_assoc()): ?>
        <li>
          <strong><?= htmlspecialchars($review['username']) ?></strong> rated it 
          <strong><?= $review['rating'] ?>/10</strong><br>
          <?= nl2br(htmlspecialchars($review['review'])) ?>
        </li>
        <hr>
      <?php endwhile; ?>
    </ul>
  <?php endif; ?>

  <h3>Leave a Review</h3>
  <form method="POST" action="movie_reviews.php?movie_id=<?= $movie_id ?>">
    <label for="rating">Rating (1-10):</label>
    <input type="number" name="rating" min="1" max="10" required><br><br>

    <label for="review">Your Review:</label><br>
    <textarea name="review" rows="4" cols="50" required></textarea><br><br>

    <button type="submit">Submit Review</button>
  </form>

  <br><a href="discover.php">← Back to Discover</a>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>