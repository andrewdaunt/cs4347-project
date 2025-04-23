<?php

session_start(); 

$servername = "localhost";
$db_username = "root"; 
$db_password = "password";    
$database = "movie_app";

// connection db yeah man
$conn = new mysqli($servername, $db_username, $db_password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

$username = $_SESSION['username'];
$user_id = null; 

// check if the user is logged in and get their user ID
$stmt_user = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
if ($stmt_user === false) {
    die("Error preparing user ID query: " . $conn_error);
}
$stmt_user->bind_param("s", $username);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

// check if the user exists in the database
if ($result_user->num_rows === 1) {
    $user = $result_user->fetch_assoc();
    $user_id = $user['user_id'];
} else {
    die("Error: Logged in user not found in database.");
}
$stmt_user->close();

$message = "";

// handle form submission for creating a review
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $movie_id = isset($_POST['movie_id']) ? (int)$_POST['movie_id'] : 0;
    $review = isset($_POST['review']) ? trim($_POST['review']) : '';
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;

    if ($movie_id > 0 && !empty($review) && $rating >= 1 && $rating <= 10) { // check if the movie ID, review, and rating are valid
        $check_review_sql = "SELECT COUNT(*) FROM movie_review_user WHERE user_id = ? AND movie_id = ?";
        $stmt_check_review = $conn->prepare($check_review_sql);
         if ($stmt_check_review === false) {
            $message = "Error preparing check review query: " . $conn->error;
         } else { // check if the user has already submitted a review for this movie
            $stmt_check_review->bind_param("ii", $user_id, $movie_id);
            $stmt_check_review->execute();
            $check_result = $stmt_check_review->get_result()->fetch_row();
            $review_count = $check_result[0];
            $stmt_check_review->close();

            if ($review_count > 0) { // check if the user has already submitted a review for this movie
                 $message = "You have already submitted a review for this movie.";
            } else { // insert the review and rating
                $insert_rating_sql = "INSERT INTO movie_review_rating (review, rating) VALUES (?, ?)";
                $stmt_rating = $conn->prepare($insert_rating_sql);
                 if ($stmt_rating === false) {
                     $message = "Error preparing insert rating query: " . $conn->error;
                 } else {
                    $stmt_rating->bind_param("si", $review, $rating);

                    if ($stmt_rating->execute()) { // insert the review and rating
                        $new_review_id = $conn->insert_id;
                        $insert_user_movie_sql = "INSERT INTO movie_review_user (user_id, movie_id, review_id) VALUES (?, ?, ?)";
                        $stmt_user_movie = $conn->prepare($insert_user_movie_sql);
                         if ($stmt_user_movie === false) {
                             $message = "Error preparing insert user_movie query: " . $conn->error;
                         } else {
                            $stmt_user_movie->bind_param("iii", $user_id, $movie_id, $new_review_id);

                            if ($stmt_user_movie->execute()) { // link the review to the user and movie
                                $message = "Review submitted successfully!";
                            } else {
                                $message = "Error linking review to user/movie: " . $stmt_user_movie->error;
                            }
                            $stmt_user_movie->close();
                         }
                    } else {
                        $message = "Error submitting review and rating: " . $stmt_rating->error;
                    }
                    $stmt_rating->close();
                 }
            }
         }

    } else {
        $message = "Error: Please select a movie, provide a review, and a rating between 1 and 10.";
    }
}

// fetch all movies for the dropdown
$movies = [];
$stmt_movies = $conn->prepare("SELECT movie_id, movie_title FROM movie ORDER BY movie_title");
if ($stmt_movies === false) {
    die("Error preparing movies list query: " . $conn->error);
}
$stmt_movies->execute();
$result_movies = $stmt_movies->get_result();

if ($result_movies->num_rows > 0) { // fetch all movies
    while ($movie_row = $result_movies->fetch_assoc()) {
        $movies[] = $movie_row;
    }
}
$stmt_movies->close();

// check if the user has already submitted a review for any movie
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Movie Review</title>
</head>
<body>
    <h2>Create a New Movie Review</h2>

    <?php if (!empty($message)): ?>
        <div class="message <?= (strpos($message, 'Error') === 0 || strpos($message, 'already submitted') === 0) ? 'error' : 'success' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form action="create_review.php" method="post">
        <label for="movie_id">Select Movie:</label>
        <select name="movie_id" id="movie_id" required>
            <option value="">-- Select a Movie --</option>
            <?php foreach ($movies as $movie): ?>
                <option value="<?= $movie['movie_id'] ?>">
                    <?= htmlspecialchars($movie['movie_title']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="rating">Rating (1-10):</label>
        <input type="number" name="rating" id="rating" min="1" max="10" required>

        <label for="review">Your Review:</label>
        <textarea name="review" id="review" required></textarea>

        <button type="submit">Submit Review</button>
    </form>

    <p><a href="profile.php">Back to Profile</a></p>
    <p><a href="logout.php">Logout</a></p>

</body>
</html>

<?php
$conn->close();
?>
