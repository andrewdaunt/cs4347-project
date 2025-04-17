-- USERS table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    user_password VARCHAR(100) NOT NULL
);

-- MOVIE table
CREATE TABLE movie (
    movie_id INT AUTO_INCREMENT PRIMARY KEY,
    genre_id INT,
    movie_title VARCHAR(255) NOT NULL
);

-- MOVIE_REVIEW_RATING table
CREATE TABLE movie_review_rating (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    rating INT CHECK (rating BETWEEN 1 AND 10),
    review VARCHAR(1000)
);

-- MOVIE_REVIEW_USER table
CREATE TABLE movie_review_user (
    review_id INT PRIMARY KEY,
    user_id INT,
    movie_id INT,
    FOREIGN KEY (review_id) REFERENCES movie_review_rating(review_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (movie_id) REFERENCES movie(movie_id)
);

-- MOVIE_LIST table
CREATE TABLE movie_list (
    list_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    list_name VARCHAR(100),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- LIST_ENTRY table
CREATE TABLE list_entry (
    list_id INT,
    movie_id INT,
    PRIMARY KEY (list_id, movie_id),
    FOREIGN KEY (list_id) REFERENCES movie_list(list_id),
    FOREIGN KEY (movie_id) REFERENCES movie(movie_id)
);

-- GENRE table
CREATE TABLE genre (
    genre_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100)
);

-- MOVIE_GENRE table
CREATE TABLE movie_genre (
    movie_id INT PRIMARY KEY,
    genre_id INT,
    FOREIGN KEY (movie_id) REFERENCES movie(movie_id),
    FOREIGN KEY (genre_id) REFERENCES genre(genre_id)
);

-- MOVIE_LIST_DESCRIPTION table
CREATE TABLE movie_list_description (
    list_id INT PRIMARY KEY,
    list_description VARCHAR(1000),
    FOREIGN KEY (list_id) REFERENCES movie_list(list_id)
);

