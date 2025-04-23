<?php
session_start();

$servername = "localhost";
$db_username = "root";
$db_password = "password";
$database = "movie_app";

// connection db 
$conn = new mysqli($servername, $db_username, $db_password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// check admin or user to ensure no one other than admin can access this page
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header("Location: login.html");
    exit();
}

$message = "";

// handle form submission for adding, updating, and removing movies
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_movie_submit'])) {
        $title = trim($_POST['new_movie_title']);
        $genre_id = (int)$_POST['new_movie_genre_id'];

        if (!empty($title) && $genre_id > 0) {
            $stmt_add = $conn->prepare("INSERT INTO movie (movie_title, genre_id) VALUES (?, ?)");
            if ($stmt_add === false) {
                 $message = "Error preparing add movie query: " . $conn->error;
            } else {
                $stmt_add->bind_param("si", $title, $genre_id);
                if ($stmt_add->execute()) {
                    $message = "Movie '" . htmlspecialchars($title) . "' added successfully!";
                } else {
                    $message = "Error adding movie: " . $stmt_add->error;
                }
                $stmt_add->close();
            }
        } else {
            $message = "Error: Movie title and valid Genre ID are required.";
        }
        // movie update
    } elseif (isset($_POST['update_movie_submit'])) {
        $title = trim($_POST['update_movie_title']);
        $new_genre_id = (int)$_POST['update_movie_genre_id'];

         if (!empty($title) && $new_genre_id > 0) {
             $stmt_update = $conn->prepare("UPDATE movie SET genre_id = ? WHERE movie_title = ?");
             if ($stmt_update === false) {
                  $message = "Error preparing update movie query: " . $conn->error;
             } else {
                 $stmt_update->bind_param("is", $new_genre_id, $title);
                 if ($stmt_update->execute()) {
                     if ($stmt_update->affected_rows > 0) {
                         $message = "Movie(s) with title '" . htmlspecialchars($title) . "' updated successfully!";
                     } else {
                         $message = "No movie found with title '" . htmlspecialchars($title) . "' or genre was already set.";
                     }
                 } else {
                     $message = "Error updating movie: " . $stmt_update->error;
                 }
                 $stmt_update->close();
             }
         } else {
            $message = "Error: Movie title and a valid New Genre ID are required for update.";
         }

         // movie removal
    } elseif (isset($_POST['remove_movie_submit'])) {
         $title = trim($_POST['remove_movie_title']);

         if (!empty($title)) {
             $stmt_delete = $conn->prepare("DELETE FROM movie WHERE movie_title = ?");
              if ($stmt_delete === false) {
                   $message = "Error preparing delete movie query: " . $conn->error;
              } else {
                  $stmt_delete->bind_param("s", $title);
                  if ($stmt_delete->execute()) {
                      if ($stmt_delete->affected_rows > 0) {
                          $message = "Movie(s) with title '" . htmlspecialchars($title) . "' removed successfully!";
                      } else {
                           $message = "No movie found with title '" . htmlspecialchars($title) . "'.";
                      }
                  } else {
                      $message = "Error removing movie: " . $stmt_delete->error;
                  }
                  $stmt_delete->close();
              }
         } else {
             $message = "Error: Movie title is required for removal.";
         }
    }

}


// fetch all users and movies for display
$stmt_users_list = $conn->prepare("SELECT user_id, username FROM users ORDER BY username");
if ($stmt_users_list === false) {
    die("Error preparing users list query: " . $conn->error);
}
$stmt_users_list->execute();
$result_users_list = $stmt_users_list->get_result();

// fetch all movies
$stmt_movies_list = $conn->prepare("SELECT movie_id, movie_title, genre_id FROM movie ORDER BY movie_title");
if ($stmt_movies_list === false) {
    die("Error preparing movies list query: " . $conn->error);
}
$stmt_movies_list->execute();
$result_movies_list = $stmt_movies_list->get_result();

// have to include the admin dashboard HTML file here
include 'admin_dashboard.html';

// extra html for the form submission and database content summary and i didnt want to add it to the admin_dashboard.html file
// cause that would take me more time 
?>

<div style="margin-top: 20px;">
    <h3>Database Content Summary</h3>

    <?php if (!empty($message)):?>
        <p style="color: green; font-weight: bold;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <h4>All Users</h4>
    <?php if ($result_users_list->num_rows > 0): ?>
        <ul>
        <?php while($user_row = $result_users_list->fetch_assoc()): ?>
            <li>User ID: <?= $user_row['user_id'] ?>, Username: <?= htmlspecialchars($user_row['username']) ?></li>
            <?php
            ?>
        <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No users found in the database.</p>
    <?php endif; ?>
    <?php $stmt_users_list->close(); ?>


    <h4>All Movies</h4>
     <?php if ($result_movies_list->num_rows > 0): ?>
        <ul>
        <?php while($movie_row = $result_movies_list->fetch_assoc()): ?>
            <li>Movie ID: <?= $movie_row['movie_id'] ?>, Title: <?= htmlspecialchars($movie_row['movie_title']) ?>, Genre ID: <?= $movie_row['genre_id'] ?></li>
            <?php
            ?>
        <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No movies found in the database.</p>
    <?php endif; ?>
    <?php $stmt_movies_list->close(); ?>

    <?php
    $conn->close();
    ?>
</div>

</body>
</html>
