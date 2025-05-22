<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(300);
ob_start();
// --- Veritabanı Bağlantı Bilgileri ---
$servername = "localhost";
$username = "root";
$password = "mysql"; 
$dbname = "yusuf_ozdil";
$generated_sql_file_path = 'sql/generated_data.sql';

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
// --- 5. generate_data.php'yi Çalıştır ---
echo "<h3>`generate_data.php` çalıştırılıyor...</h3>";
ob_start();
include 'generate_data.php';
$generate_data_output = ob_get_clean();
echo "<h4>`generate_data.php` Çalışma Çıktısı:</h4><pre>" . htmlspecialchars($generate_data_output) . "</pre>";


// --- 6. Oluşturulan SQL Dosyasını Oku ve Çalıştır ---
echo "<h3>Oluşturulan `$generated_sql_file_path` dosyasındaki veriler import ediliyor...</h3>";
$sql_content = file_get_contents($generated_sql_file_path);
if ($sql_content === false) {
    ob_end_flush();
    die("<p style='color:red;'>HATA: `$generated_sql_file_path` dosyası okunamadı.</p>");
}


$sql_commands = explode(';', $sql_content);
$total_commands = 0;
$successful_commands = 0;
$failed_commands = 0;
$errors_import = [];

foreach ($sql_commands as $command) {
    $command = trim($command);
    if (!empty($command)) { // Boş komutları atla
        $total_commands++;
        if ($conn->query($command) === TRUE) {
            $successful_commands++;
        } else {
            $failed_commands++;
            $errors_import[] = "Hata ('" . substr($command, 0, 50) . "...'): " . $conn->error;
            // Çok fazla hata olursa loglamayı durdurabilirsin
            if (count($errors_import) > 10) {
                 $errors_import[] = "... ve daha fazla hata.";
                 break;
            }
        }
    }
}

if ($failed_commands > 0) {
    echo "<p style='color:red;'>SQL import sırasında $failed_commands / $total_commands komutta hata oluştu.</p>";
    echo "<h4>Import Hataları (ilk 10):</h4><pre>";
    foreach($errors_import as $err) {
        echo htmlspecialchars($err) . "\n";
    }
    echo "</pre>";
} else {
    echo "<p>$successful_commands / $total_commands SQL komutu başarıyla çalıştırıldı. Tablolar dolduruldu.</p>";
}


// --- 7. İşlemler Tamamlandı, Yönlendirme ---
echo "<h2>Kurulum ve Veri Yükleme İşlemleri Tamamlandı!</h2>";
echo "<p>Giriş sayfasına yönlendiriliyorsunuz...</p>";

$conn->close();
ob_end_flush();

sleep(3); 

header("Location: login.html?setup=success");
exit;
?>

?>