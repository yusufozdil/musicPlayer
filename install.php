<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- Veritabanı Bağlantı Bilgileri ---
$servername = "localhost";
$username = "root";
$password = "mysql"; 
$dbname = "yusuf_ozdil";

// --- 1. MySQL Sunucusuna Bağlan (Veritabanı belirtmeden) ---
$conn = new mysqli($servername, $username, $password);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    die("MySQL sunucusuna bağlantı başarısız: " . $conn->connect_error);
}
echo "MySQL sunucusuna başarıyla bağlanıldı.<br>";

// --- 2. Veritabanını Oluştur  ---
$sql_create_db = "CREATE DATABASE IF NOT EXISTS `$dbname` ";
if ($conn->query($sql_create_db) === TRUE) {
    echo "Veritabanı '$dbname' başarıyla oluşturuldu veya zaten mevcut.<br>";
} else {
    die("Veritabanı oluşturulurken hata: " . $conn->error . "<br>");
}

// --- 3. Oluşturulan Veritabanını Seç ---
if (!$conn->select_db($dbname)) {
    die("Veritabanı '$dbname' seçilemedi: " . $conn->error . "<br>");
}
echo "Veritabanı '$dbname' başarıyla seçildi.<br>";

// --- 4. Tabloları Oluştur ---
$table_queries = [
    "COUNTRY" => "CREATE TABLE IF NOT EXISTS COUNTRY (
        country_id INT AUTO_INCREMENT PRIMARY KEY,
        country_name VARCHAR(100) NOT NULL UNIQUE,
        country_code VARCHAR(5) NOT NULL UNIQUE
    )",

    "USERS" => "CREATE TABLE IF NOT EXISTS USERS (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        country_id INT,
        age INT,
        name VARCHAR(100),
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        date_joined DATE,
        last_login TIMESTAMP NULL DEFAULT NULL,
        follower_num INT DEFAULT 0,
        subscription_type VARCHAR(20),
        top_genre VARCHAR(50),
        num_songs_liked INT DEFAULT 0,
        most_played_artist VARCHAR(100),
        image VARCHAR(255),
        FOREIGN KEY (country_id) REFERENCES COUNTRY(country_id) ON DELETE SET NULL ON UPDATE CASCADE
    )",

    "ARTISTS" => "CREATE TABLE IF NOT EXISTS ARTISTS (
        artist_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        genre VARCHAR(50),
        date_joined DATE,
        total_num_music INT DEFAULT 0,
        total_albums INT DEFAULT 0,
        listeners INT DEFAULT 0,
        bio TEXT,
        country_id INT,
        image VARCHAR(255),
        FOREIGN KEY (country_id) REFERENCES COUNTRY(country_id) ON DELETE SET NULL ON UPDATE CASCADE
    )",

    "ALBUMS" => "CREATE TABLE IF NOT EXISTS ALBUMS (
        album_id INT AUTO_INCREMENT PRIMARY KEY,
        artist_id INT NOT NULL,
        name_title VARCHAR(200) NOT NULL,
        release_date DATE,
        genre VARCHAR(50),
        music_number INT,
        image VARCHAR(255),
        FOREIGN KEY (artist_id) REFERENCES ARTISTS(artist_id) ON DELETE CASCADE ON UPDATE CASCADE
    )",

    "SONGS" => "CREATE TABLE IF NOT EXISTS SONGS (
        song_id INT AUTO_INCREMENT PRIMARY KEY,
        album_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        duration TIME,
        genre VARCHAR(50),
        release_date DATE,
        `rank` INT,
        image VARCHAR(255),
        FOREIGN KEY (album_id) REFERENCES ALBUMS(album_id) ON DELETE CASCADE ON UPDATE CASCADE
    )",
    
    "PLAYLISTS" => "CREATE TABLE IF NOT EXISTS PLAYLISTS (
        playlist_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(150) NOT NULL,
        description TEXT,
        date_created DATE,
        image VARCHAR(255),
        FOREIGN KEY (user_id) REFERENCES USERS(user_id) ON DELETE CASCADE ON UPDATE CASCADE
    )",

    "PLAYLIST_SONGS" => "CREATE TABLE IF NOT EXISTS PLAYLIST_SONGS (
        playlistsong_id INT AUTO_INCREMENT PRIMARY KEY,
        playlist_id INT NOT NULL,
        song_id INT NOT NULL,
        date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (playlist_id) REFERENCES PLAYLISTS(playlist_id) ON DELETE CASCADE ON UPDATE CASCADE,
        FOREIGN KEY (song_id) REFERENCES SONGS(song_id) ON DELETE CASCADE ON UPDATE CASCADE,
        UNIQUE KEY unique_playlist_song (playlist_id, song_id)
    )",

    "PLAY_HISTORY" => "CREATE TABLE IF NOT EXISTS PLAY_HISTORY (
        play_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        song_id INT NOT NULL,
        playtime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES USERS(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
        FOREIGN KEY (song_id) REFERENCES SONGS(song_id) ON DELETE CASCADE ON UPDATE CASCADE
    )"
];

$all_tables_created_successfully = true;
foreach ($table_queries as $table_name => $sql_create_table) {
    if ($conn->query($sql_create_table) === TRUE) {
        echo "Tablo '$table_name' başarıyla oluşturuldu veya zaten mevcut.<br>";
    } else {
        echo "Tablo '$table_name' oluşturulurken HATA: " . $conn->error . "<br>";
        $all_tables_created_successfully = false;
    }
}

// --- 5. İşlemler Tamamlandı, Yönlendirme ---
if ($all_tables_created_successfully) {
    echo "Tüm veritabanı ve tablo kurulum işlemleri başarıyla tamamlandı!<br>";
    echo "Giriş sayfasına yönlendiriliyorsunuz...";
    
    $conn->close();

    header("Location: login.html");
    exit;
} else {
    echo "Bazı tablolar oluşturulurken hatalar meydana geldi. Lütfen yukarıdaki mesajları kontrol edin.<br>";
    $conn->close();
}

?>