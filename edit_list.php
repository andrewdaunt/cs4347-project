<?php

session_start(); 

$servername = "localhost";
$db_username = "root"; 
$db_password = "password";   
$database = "movie_app";

//create connection
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
//get user data to ensure correct user
$stmt_user = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
if ($stmt_user === false) {
    die("Error preparing user ID query: " . $conn->error);
}
$stmt_user->bind_param("s", $username);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

//check if user exists in database
if ($result_user->num_rows === 1) {
    $user = $result_user->fetch_assoc();
    $user_id = $user['user_id'];
} else {
    die("Error: Logged in user not found in database.");
}
$stmt_user->close();

$message = "";

$current_list_id = isset($_GET['list_id']) ? (int)$_GET['list_id'] : null;
//check if list_id is valid
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_list_id = isset($_POST['list_id']) ? (int)$_POST['list_id'] : null;


    if ($posted_list_id === null) {
        $message = "Error: No list selected for action.";
    } else {
        $stmt_check_list = $conn->prepare("SELECT list_id FROM movie_list WHERE list_id = ? AND user_id = ?");
        if ($stmt_check_list === false) {
             $message = "Error preparing list verification query: " . $conn->error;
        } else {
            $stmt_check_list->bind_param("ii", $posted_list_id, $user_id);
            $stmt_check_list->execute();
            $stmt_check_list->store_result();

            if ($stmt_check_list->num_rows === 0) {
                $message = "Error: You do not have permission to edit this list.";
                 $posted_list_id = null; 
            } else {
                if (isset($_POST['update_list_details'])) {
                    $list_name = trim($_POST['list_name']);
                    $list_description = trim($_POST['description']);

                    if (!empty($list_name)) {
                        // update list name
                        $stmt_update_name = $conn->prepare("UPDATE movie_list SET list_name = ? WHERE list_id = ?");
                        if ($stmt_update_name === false) {
                            $message = "Error preparing update list name query: " . $conn->error;
                        } else {
                            $stmt_update_name->bind_param("si", $list_name, $posted_list_id);
                            if ($stmt_update_name->execute()) {
                                $message = "List details updated successfully!";
                            } else {
                                $message = "Error updating list name: " . $stmt_update_name->error;
                            }
                            $stmt_update_name->close();
                        }

                        // update/Insert list description (handled if description is empty or exists)
                        // check if a description already exists for this list
                        $stmt_check_desc = $conn->prepare("SELECT list_id FROM movie_list_description WHERE list_id = ?");
                        if ($stmt_check_desc === false) {
                            $message .= " Error preparing check description query: " . $conn->error;
                        } else {
                            $stmt_check_desc->bind_param("i", $posted_list_id);
                            $stmt_check_desc->execute();
                            $stmt_check_desc->store_result();

                            if ($stmt_check_desc->num_rows > 0) {
                                // if description updated/exists in this context, update it
                                $stmt_update_desc = $conn->prepare("UPDATE movie_list_description SET list_description = ? WHERE list_id = ?");
                                if ($stmt_update_desc === false) {
                                    $message .= " Error preparing update description query: " . $conn->error;
                                } else {
                                    $stmt_update_desc->bind_param("si", $list_description, $posted_list_id);
                                    if (!$stmt_update_desc->execute()) {
                                        $message .= " Error updating list description: " . $stmt_update_desc->error;
                                    }
                                    $stmt_update_desc->close();
                                }
                            } elseif (!empty($list_description)) {
                                // no description exists and a new one is provided, insert it
                                $stmt_insert_desc = $conn->prepare("INSERT INTO movie_list_description (list_id, list_description) VALUES (?, ?)");
                                if ($stmt_insert_desc === false) {
                                    $message .= " Error preparing insert description query: " . $conn->error;
                                } else {
                                    $stmt_insert_desc->bind_param("is", $posted_list_id, $list_description);
                                    if (!$stmt_insert_desc->execute()) {
                                        $message .= " Error inserting list description: " . $stmt_insert_desc->error;
                                    }
                                    $stmt_insert_desc->close();
                                }
                            }
                            $stmt_check_desc->close();
                        }

                    } else {
                        $message = "Error: List name cannot be empty.";
                    }

                // add movie to list
                } elseif (isset($_POST['add_movie_to_list'])) {
                    $movie_title_to_add = trim($_POST['movie_title']);

                    if (!empty($movie_title_to_add)) {
                        // find the movie_id based on the title
                        $stmt_find_movie = $conn->prepare("SELECT movie_id FROM movie WHERE movie_title = ?");
                        if ($stmt_find_movie === false) {
                             $message = "Error preparing find movie query: " . $conn->error;
                        } else {
                            $stmt_find_movie->bind_param("s", $movie_title_to_add);
                            $stmt_find_movie->execute();
                            $result_find_movie = $stmt_find_movie->get_result();

                            if ($result_find_movie->num_rows === 1) {
                                $movie_to_add = $result_find_movie->fetch_assoc();
                                $movie_id_to_add = $movie_to_add['movie_id'];

                                // check if the movie is already in the list
                                $stmt_check_entry = $conn->prepare("SELECT COUNT(*) FROM list_entry WHERE list_id = ? AND movie_id = ?");
                                if ($stmt_check_entry === false) {
                                    $message = "Error preparing check list entry query: " . $conn->error;
                                } else {
                                    $stmt_check_entry->bind_param("ii", $posted_list_id, $movie_id_to_add);
                                    $stmt_check_entry->execute();
                                    $entry_count = $stmt_check_entry->get_result()->fetch_row()[0];
                                    $stmt_check_entry->close();

                                    if ($entry_count > 0) {
                                        $message = "'" . htmlspecialchars($movie_title_to_add) . "' is already in this list.";
                                    } else {
                                        // insert the movie into the list_entry table
                                        $stmt_add_entry = $conn->prepare("INSERT INTO list_entry (list_id, movie_id) VALUES (?, ?)");
                                        if ($stmt_add_entry === false) {
                                            $message = "Error preparing add list entry query: " . $conn->error;
                                        } else {
                                            $stmt_add_entry->bind_param("ii", $posted_list_id, $movie_id_to_add);
                                            if ($stmt_add_entry->execute()) {
                                                $message = "'" . htmlspecialchars($movie_title_to_add) . "' added to list successfully!";
                                            } else {
                                                $message = "Error adding movie to list: " . $stmt_add_entry->error;
                                            }
                                            $stmt_add_entry->close();
                                        }
                                    }
                                }
                            } else {
                                $message = "Error: Movie with title '" . htmlspecialchars($movie_title_to_add) . "' not found.";
                            }
                            $stmt_find_movie->close();
                        }
                    } else {
                        $message = "Error: Movie title is required to add to list.";
                    }

                } elseif (isset($_POST['remove_movies_from_list'])) {

                    $movies_to_remove_ids = isset($_POST['movies_to_remove']) ? $_POST['movies_to_remove'] : [];
                    // check if any movies were selected for removal
                    if (!empty($movies_to_remove_ids)) {
                        $placeholders = implode(',', array_fill(0, count($movies_to_remove_ids), '?'));
                        $delete_sql = "DELETE FROM list_entry WHERE list_id = ? AND movie_id IN ($placeholders)";

                        $stmt_remove_entries = $conn->prepare($delete_sql);
                        if ($stmt_remove_entries === false) {
                            $message = "Error preparing remove list entries query: " . $conn->error;
                        } else {
                            $types = 'i' . str_repeat('i', count($movies_to_remove_ids));
                            $bind_params = array_merge([$types, $posted_list_id], $movies_to_remove_ids);

                            call_user_func_array([$stmt_remove_entries, 'bind_param'], $bind_params);
                            if ($stmt_remove_entries->execute()) {
                                $affected_rows = $stmt_remove_entries->affected_rows;
                                $message = $affected_rows . " movie(s) removed from list successfully!";
                            } else {
                                $message = "Error removing movies from list: " . $stmt_remove_entries->error;
                            }
                            $stmt_remove_entries->close();
                        }
                    } else {
                        $message = "No movies selected to remove.";
                    }
                }

            }
            $stmt_check_list->close();
        }
    }
    // redirect to the same page to avoid form resubmission
    if ($current_list_id !== null) {
        header("Location: edit_list.php?list_id=" . $current_list_id);
    } else {
         header("Location: edit_list.php");
    }
    exit(); 

}

$user_lists = [];
$selected_list_details = null;
$movies_in_list = [];
$all_movies = [];
// check if a list_id is provided in the URL
if ($current_list_id !== null) {
    $stmt_selected_list = $conn->prepare("SELECT list_id, list_name FROM movie_list WHERE list_id = ? AND user_id = ?");
    if ($stmt_selected_list === false) {
        die("Error preparing selected list query: " . $conn->error);
    }
    $stmt_selected_list->bind_param("ii", $current_list_id, $user_id);
    $stmt_selected_list->execute();
    $result_selected_list = $stmt_selected_list->get_result();
    // check if the list exists and belongs to the user
    if ($result_selected_list->num_rows === 1) {
        $selected_list_details = $result_selected_list->fetch_assoc();

        $stmt_list_desc = $conn->prepare("SELECT list_description FROM movie_list_description WHERE list_id = ?");
         if ($stmt_list_desc === false) {
             die("Error preparing list description query: " . $conn->error);
         }
        $stmt_list_desc->bind_param("i", $current_list_id);
        $stmt_list_desc->execute();
        $result_list_desc = $stmt_list_desc->get_result();
        $selected_list_details['list_description'] = ($result_list_desc->num_rows > 0) ? $result_list_desc->fetch_assoc()['list_description'] : '';
        $stmt_list_desc->close();
         // get movies in the list
        $sql_movies_in_list = "
            SELECT m.movie_id, m.movie_title
            FROM list_entry le
            JOIN movie m ON le.movie_id = m.movie_id
            WHERE le.list_id = ?
            ORDER BY m.movie_title";
        $stmt_movies_in_list = $conn->prepare($sql_movies_in_list);
         if ($stmt_movies_in_list === false) {
             die("Error preparing movies in list query: " . $conn->error);
         }
        // bind the list_id to the prepare statement to prevent SQL injection
        $stmt_movies_in_list->bind_param("i", $current_list_id);
        $stmt_movies_in_list->execute();
        $result_movies_in_list = $stmt_movies_in_list->get_result();
        while ($row = $result_movies_in_list->fetch_assoc()) {
            $movies_in_list[] = $row;
        }
        $stmt_movies_in_list->close();
        // get all movies for the datalist
        $stmt_all_movies = $conn->prepare("SELECT movie_id, movie_title FROM movie ORDER BY movie_title");
         if ($stmt_all_movies === false) {
             die("Error preparing all movies query: " . $conn->error);
         }
        $stmt_all_movies->execute();
        $result_all_movies = $stmt_all_movies->get_result();
        while ($row = $result_all_movies->fetch_assoc()) {
            $all_movies[] = $row;
        }
        $stmt_all_movies->close();

    } else {
        $message = "Error: List not found or you do not have permission to edit it.";
        $current_list_id = null;
    }
    $stmt_selected_list->close();

}
// check if the user has any lists to edit
if ($current_list_id === null) {
    $stmt_user_lists = $conn->prepare("SELECT list_id, list_name FROM movie_list WHERE user_id = ? ORDER BY list_name");
     if ($stmt_user_lists === false) {
         die("Error preparing user lists for selection query: " . $conn->error);
     }
    $stmt_user_lists->bind_param("i", $user_id);
    $stmt_user_lists->execute();
    $result_user_lists = $stmt_user_lists->get_result();
    while ($row = $result_user_lists->fetch_assoc()) {
        $user_lists[] = $row;
    }
    $stmt_user_lists->close();
}
// extra html dependent on the list_id
// if no list_id is provided, show the list selection form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Movie List</title>
</head>
<body>
    <h2>Edit Movie List</h2>

    <?php if (!empty($message)):?>
        <div class="message <?= (strpos($message, 'Error') === 0) ? 'error' : 'success' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($current_list_id === null):?>
        <form action="edit_list.php" method="get">
            <label for="list_to_edit">Select a List to Edit:</label>
            <select name="list_id" id="list_to_edit" required>
                <option value="">-- Select Your List --</option>
                <?php foreach ($user_lists as $list): ?>
                    <option value="<?= $list['list_id'] ?>">
                        <?= htmlspecialchars($list['list_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Edit Selected List</button>
        </form>

        <?php if (empty($user_lists)): ?>
             <p>You have not created any movie lists yet. <a href="create_list.php">Create a new list.</a></p>
        <?php endif; ?>

    <?php else:?>

        <h3>Editing List: <?= htmlspecialchars($selected_list_details['list_name']) ?></h3>

        <form action="edit_list.php?list_id=<?= $current_list_id ?>" method="post">
             <input type="hidden" name="list_id" value="<?= $current_list_id ?>">
            <label for="list_name">List Name:</label>
            <input type="text" name="list_name" id="list_name" value="<?= htmlspecialchars($selected_list_details['list_name']) ?>" required><br>

            <label for="description">Description:</label><br>
            <textarea name="description" id="description"><?= htmlspecialchars($selected_list_details['list_description']) ?></textarea><br>

            <button type="submit" name="update_list_details">Update List Details</button>
        </form>

        <form action="edit_list.php?list_id=<?= $current_list_id ?>" method="post">
             <input type="hidden" name="list_id" value="<?= $current_list_id ?>">
            <h3>Add New Movie to List:</h3>
            <label for="movie_title_add">Movie Title:</label>
            <input list="movie_titles_datalist" name="movie_title" id="movie_title_add" required type="text" />
            <datalist id="movie_titles_datalist">
                <?php foreach ($all_movies as $movie): ?>
                    <option value="<?= htmlspecialchars($movie['movie_title']) ?>"></option>
                <?php endforeach; ?>
            </datalist>
            <br>
            <button type="submit" name="add_movie_to_list">Add Movie to List</button>
        </form>

        <form action="edit_list.php?list_id=<?= $current_list_id ?>" method="post">
             <input type="hidden" name="list_id" value="<?= $current_list_id ?>">
            <h3>Movies Currently In List:</h3>
             <?php if (!empty($movies_in_list)): ?>
                <label for="movies_to_remove">Select Movies to Remove (hold Ctrl/Cmd to select multiple):</label><br>
                <select multiple="multiple" name="movies_to_remove[]" id="movies_to_remove" size="5">
                    <?php foreach ($movies_in_list as $movie): ?>
                        <option value="<?= $movie['movie_id'] ?>">
                            <?= htmlspecialchars($movie['movie_title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select><br>
                <button type="submit" name="remove_movies_from_list">Remove Selected Movie(s)</button>
             <?php else: ?>
                <p>This list is currently empty.</p>
             <?php endif; ?>
        </form>

        <?php endif;?>

    <p><a href="profile.php">Back to Profile</a></p>
    <p><a href="logout.php">Logout</a></p>

</body>
</html>

<?php
$conn->close();
?>