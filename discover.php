<?php
session_start();

// DB connection
$servername = "localhost";
$username = "root";
$password = "password";
$database = "movie_app";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Input handling
$movie_title = $_GET['movie_title'] ?? '';
$sort = $_GET['sort'] ?? 'title';
$movies = [];

// Build query
$query = "
    SELECT m.movie_id, m.movie_title, g.name AS genre_name, AVG(r.rating) AS average_rating
    FROM movie m
    LEFT JOIN movie_genre mg ON m.movie_id = mg.movie_id
    LEFT JOIN genre g ON mg.genre_id = g.genre_id
    LEFT JOIN movie_review_user u ON m.movie_id = u.movie_id
    LEFT JOIN movie_review_rating r ON u.review_id = r.review_id
    WHERE m.movie_title LIKE ?
    GROUP BY m.movie_id
";

// Sorting
switch ($sort) {
    case 'rating': $order = 'average_rating DESC'; break;
    case 'genre':  $order = 'genre_name'; break;
    default:       $order = 'm.movie_title';
}
$query .= " ORDER BY $order";

// Execute
$stmt = $conn->prepare($query);
$search_term = "%$movie_title%";
$stmt->bind_param("s", $search_term);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $movies[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Discover Movies</title>
</head>
<body>
  <h2>Discover Movies</h2>

  <!-- Search -->
  <form method="GET" action="discover.php">
    <label>Movie Title:
      <input list="movie_titles" name="movie_title" value="<?= htmlspecialchars($movie_title) ?>">
      <datalist id="movie_titles">
        <?php
        $titles_result = $conn->query("SELECT DISTINCT movie_title FROM movie ORDER BY movie_title");
        while ($row = $titles_result->fetch_assoc()) {
            echo '<option value="' . htmlspecialchars($row['movie_title']) . '">';
        }
        ?>
      </datalist>
    </label>
    <input type="submit" value="Search">
  </form>

  <!-- Sort -->
  <form method="GET" action="discover.php">
    <input type="hidden" name="movie_title" value="<?= htmlspecialchars($movie_title) ?>">
    <label for="sort">Sort by:</label>
    <select name="sort" onchange="this.form.submit()">
      <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Average Rating</option>
      <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Title</option>
      <option value="genre" <?= $sort === 'genre' ? 'selected' : '' ?>>Genre</option>
    </select>
    <input type="submit" value="Sort">
  </form>

  <!-- Movie Results -->
  <h3>Results:</h3>
  <?php if (empty($movies)): ?>
    <p>No movies found.</p>
  <?php else: ?>
    <ul>
      <?php foreach ($movies as $movie): ?>
        <li>
          <form method="GET" action="movie_reviews.php" style="display: inline;">
            <input type="hidden" name="movie_id" value="<?= $movie['movie_id'] ?>">
            <button type="submit">
              <?= htmlspecialchars($movie['movie_title']) ?> 
              (<?= htmlspecialchars($movie['genre_name'] ?? 'N/A') ?>) - 
              Rating: <?= $movie['average_rating'] ? round($movie['average_rating'], 1) : 'N/A' ?>
            </button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

</body>
</html>

<?php $conn->close(); ?>