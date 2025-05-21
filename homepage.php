<?php
session_start();

// Giriş yapılmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html?error=Lütfen önce giriş yapın.");
    exit;
}

require_once('includes/db_connect.php');

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_image_url = isset($_SESSION['user_image']) ? $_SESSION['user_image'] : null;

// Sayfa başlığı için
$page_title = "Merhaba, " . htmlspecialchars($user_name) . "!";

// 1. Kullanıcının Çalma Listelerini Çek
$playlists = [];
$stmt_playlists = $conn->prepare("SELECT playlist_id, title, image FROM PLAYLISTS WHERE user_id = ? ORDER BY title ASC");
if ($stmt_playlists) {
    $stmt_playlists->bind_param("i", $user_id);
    $stmt_playlists->execute();
    $result_playlists = $stmt_playlists->get_result();
    while ($row = $result_playlists->fetch_assoc()) {
        $playlists[] = $row;
    }
    $stmt_playlists->close();
} else {
    error_log("Error preparing playlists query: " . $conn->error);
}


// 2. Kullanıcının Son Dinlediği 10 Şarkıyı Çek (PLAY_HISTORY ve SONGS join)
$play_history = [];
$stmt_history = $conn->prepare(
    "SELECT s.song_id, s.title, s.image AS song_image, ar.name AS artist_name
     FROM PLAY_HISTORY ph
     JOIN SONGS s ON ph.song_id = s.song_id
     JOIN ALBUMS al ON s.album_id = al.album_id
     JOIN ARTISTS ar ON al.artist_id = ar.artist_id
     WHERE ph.user_id = ?
     ORDER BY ph.playtime DESC
     LIMIT 10"
);
if ($stmt_history) {
    $stmt_history->bind_param("i", $user_id);
    $stmt_history->execute();
    $result_history = $stmt_history->get_result();
    while ($row = $result_history->fetch_assoc()) {
        $play_history[] = $row;
    }
    $stmt_history->close();
} else {
    error_log("Error preparing play history query: " . $conn->error);
}


// 3. Kullanıcının Ülkesinden En Popüler 5 Sanatçıyı Çek
$user_country_id = null;
$stmt_user_country = $conn->prepare("SELECT country_id FROM USERS WHERE user_id = ?");
if ($stmt_user_country) {
    $stmt_user_country->bind_param("i", $user_id);
    $stmt_user_country->execute();
    $result_user_country = $stmt_user_country->get_result();
    if ($user_row = $result_user_country->fetch_assoc()) {
        $user_country_id = $user_row['country_id'];
    }
    $stmt_user_country->close();
} else {
    error_log("Error preparing user country query: " . $conn->error);
}

$popular_artists_in_country = [];
if ($user_country_id !== null) {
    $stmt_artists_country = $conn->prepare(
        "SELECT artist_id, name, image, listeners 
         FROM ARTISTS 
         WHERE country_id = ? 
         ORDER BY listeners DESC 
         LIMIT 5"
    );
    if ($stmt_artists_country) {
        $stmt_artists_country->bind_param("i", $user_country_id);
        $stmt_artists_country->execute();
        $result_artists_country = $stmt_artists_country->get_result();
        while ($row = $result_artists_country->fetch_assoc()) {
            $popular_artists_in_country[] = $row;
        }
        $stmt_artists_country->close();
    } else {
        error_log("Error preparing popular artists in country query: " . $conn->error);
    }
} else {
    error_log("User country_id not found or user has no country set.");
}

include 'homepage.html';

$conn->close();
?>