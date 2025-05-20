# CSE348 Database Management Systems - Term Project: Music Player (Spring 2025)

## Project Overview

This project is a simple web-based Music Player application developed for the CSE348 Database Management Systems course. It allows users to manage and listen to music, interact with playlists, view artist and song details, and perform various database operations. The application uses PHP for server-side logic, HTML for the front-end structure, and a MySQL (or similar relational) database for data storage.

## Student Information

*   **Name:** [Yusuf Özdil]
*   **Student ID:** [20210702049]
*   **Course:** CSE348 Database Management Systems
*   **Term:** Spring 2025

## Features

The application includes the following core features as per the project requirements:

*   **Database Initialization:** A setup process to create the necessary database schema and tables.
*   **Data Generation:** A script to populate the database with a substantial amount of sample data for users, artists, albums, songs, playlists, etc.
*   **User Authentication:** Login functionality for users to access their personalized music player.
*   **Homepage:** Displays user's playlists, play history, and artists from the user's country. Includes search functionalities.
*   **Playlist Management:**
    *   View songs within a playlist.
    *   Display country information for each song's artist.
    *   Add new songs to a playlist.
    *   Create new playlists.
*   **Currently Playing Music Page:** Displays information about the currently selected song.
*   **Artist Page:** Shows artist details, their last five albums, and their top 5 most listened songs. Includes a "follow" functionality (simulated by increasing listener count).
*   **Album Page:** Displays songs within a selected album (similar to playlist page but without adding new songs).
*   **General SQL Operations Page:**
    *   Displays pre-defined reports (e.g., top genres, top songs, artists by country).
    *   Allows users to execute custom SQL queries (with appropriate warnings about its use).

## File Structure

The project is organized as follows:

MusicPlayer_CSE348/
├── index.html # Initial page for database setup
├── install.php # Database and table creation script
├── generate_data.php # Script to generate SQL INSERT statements for data
│
├── login.html # Login form
├── login.php # Login authentication script
│
├── homepage.html # Main user homepage (view)
├── homepage.php # Homepage logic and data retrieval
│
├── playlistpage.html # Playlist details page (view)
├── playlistpage.php # Playlist page logic
├── create_playlist.php # Script to handle new playlist creation
│
├── currentmusic.html # Currently playing music page (view)
├── currentmusic.php # Currently playing music page logic
│
├── artistpage.html # Artist details page (view)
├── artistpage.php # Artist page logic
│
├── albumpage.html # Album details page (view)
├── albumpage.php # Album page logic
│
├── generalSQL.html # General SQL operations page (view)
├── generalSQL.php # General SQL operations page logic
│
├── includes/
│ └── db_connect.php # Database connection script
│
├── assets/
│ └── images/ 
│ ├── default_album.png
│ └── default_song.png
│
├── data/ # Input files for data generation
│ ├── input_countries.txt
│ ├── input_artist_names.txt
│ ├── input_genres.txt
│ ├── input_bios.txt
│ ├── input_names.txt
│ ├── input_surnames.txt
│ ├── input_album_titles.txt
│ ├── input_song_titles.txt
│ ├── input_playlist_titles.txt
│ ├── input_image_urls_artist.txt
│ ├── input_image_urls_user.txt
│ ├── input_image_urls_album.txt
│ └── input_image_urls_playlist.txt
│
├── sql/
│ ├── generated_data.sql 
│
└── README.md # This file

## Setup and Installation

To set up and run the project locally, follow these steps:

1.  **Prerequisites:**
    *   A web server environment with PHP support (e.g., XAMPP, WAMP, MAMP, or a custom LAMP/LEMP stack).
    *   A MySQL (or compatible) database server.
    *   A web browser.

2.  **Database Configuration:**
    *   Open `install.php` and `includes/db_connect.php`.
    *   Modify the database connection variables (`$servername`, `$username`, `$password`, `$dbname`) to match your local database setup. The `$dbname` in `install.php` will be the name of the database created; ensure `db_connect.php` uses the same name after creation.

3.  **Place Project Files:**
    *   Copy the entire `MusicPlayer_CSE348` project folder into your web server's document root

4.  **Prepare Data Generation Files (Optional but Recommended):**
    *   Populate the `.txt` files inside the `data/` directory with sample data as described in the project or `generate_data.php` comments. This is crucial for the `generate_data.php` script to produce a rich dataset.
    *   Ensure the `sql/` directory exists and is writable if you plan to use `generate_data.php`.

5.  **Initialize Database:**
    *   Open your web browser and navigate to `http://localhost/musicPlayer/index.html` (or the equivalent path if you placed the project in a subdirectory).
    *   Click the "Initialize Database" (or similar) button. This will execute `install.php`, which creates the database and the required tables.
    *   You should be redirected to `login.html` upon successful installation.

6.  **Generate and Import Data:**
    *   Execute `generate_data.php` (either by navigating to `http://localhost/musicPlayer/generate_data.php` in your browser or via the PHP CLI: `php generate_data.php`). This will create an `sql/generated_data.sql` file.
    *   Import `sql/generated_data.sql` into your newly created database using a tool like phpMyAdmin or the MySQL command line. This will populate your tables.
        *   `mysql -u your_mysql_username -p your_database_name < sql/generated_data.sql`

7.  **Login and Use:**
    *   Navigate to `http://localhost/musicPlayer/login.html`.
    *   Log in using one of the user accounts created by `generate_data.php`. (You might need to check the `generated_data.sql` or the `USERS` table directly to find a valid username/password if you didn't set a default one or if passwords are not hashed as per your setup).
    *   If registration was implemented, you can create a new account via `register.php`.

## Important Notes

*   **Password Security:** The current implementation might store passwords in plain text for simplicity as per project phase or if explicitly requested. **In a real-world application, passwords MUST be hashed using functions like `password_hash()` and verified with `password_verify()`.** If this was implemented, `register.php` would hash passwords and `login.php` would use `password_verify()`.
*   **Custom SQL Query Feature:** The `generalSQL.php` page includes a feature for executing custom SQL queries.
    *   The default implementation (as per PDF suggestion) might restrict this to `SELECT` queries for security.
    *   If this restriction was lifted, **be extremely cautious** as it poses a significant security risk, allowing potentially harmful DML/DDL commands to be executed. This should only be used in a trusted, controlled environment.
*   **Error Reporting:** For development, PHP error reporting is enabled in some files (`ini_set('display_errors', 1); error_reporting(E_ALL);`). This should be turned off or logged to a file in a production environment.
*   **Image URLs:** The `generate_data.php` script is configured to pick image URLs from text files in the `data/` directory (e.g., `input_image_urls_user.txt`). Ensure these files contain valid and accessible image URLs for a better visual experience. Fallback images are located in `assets/images/`.

## Deliverables (as per project PDF)

*   ER Diagram (`FullName_StudentID_ER.pdf`)
*   Action Flow Diagram (`FullName_StudentID_ActionFlow.pdf`)
*   SQL, PHP, and HTML files (`FullName_StudentID.zip` containing the project structure)

---

This README provides a general guide to the project. Please refer to the project specification PDF and comments within the code for more specific details.