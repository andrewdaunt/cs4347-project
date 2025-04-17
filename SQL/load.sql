SET GLOBAL local_infile = 1;
USE movie_app;

-- Load data into users table
LOAD DATA INFILE '.../users.csv'
INTO TABLE users
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(user_id, username, user_password);

-- Load data into movie table
LOAD DATA INFILE '.../movies.csv'
INTO TABLE movie
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(movie_id, genre_id, movie_title);

-- Load data into movie_review_rating table
LOAD DATA INFILE '.../movie_review_rating.csv'
INTO TABLE movie_review_rating
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(review_id, rating, review);

-- Load data into movie_review_user table
LOAD DATA INFILE '.../movie_review_user.csv'
INTO TABLE movie_review_user
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(review_id, user_id, movie_id);

-- Load data into movie_list table
LOAD DATA INFILE '.../movie_lists.csv'
INTO TABLE movie_list
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(list_id, user_id, list_name);

-- Load data into list_entry table
LOAD DATA INFILE '.../list_entry.csv'
INTO TABLE list_entry
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(list_id, movie_id);

-- Load data into genre table
LOAD DATA INFILE '.../genre.csv'
INTO TABLE genre
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(genre_id, name);

-- Load data into movie_genre table
LOAD DATA INFILE '.../movie_genre.csv'
INTO TABLE movie_genre
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(movie_id, genre_id);

-- Load data into movie_list_description table
LOAD DATA INFILE '.../movie_list_description.csv'
INTO TABLE movie_list_description
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(list_id, list_description);

