<?php
session_start();
require_once('includes/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html?error=Lütfen önce giriş yapın.");
    exit;
}

$playlist_id = null;
$playlist_title = "Çalma Listesi Bulunamadı";
$playlist_songs = [];
$user_id = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $playlist_id = intval($_GET['id']);
    $stmt_pl_info = $conn->prepare("SELECT title, user_id FROM PLAYLISTS WHERE playlist_id = ?");
    if ($stmt_pl_info) {
        $stmt_pl_info->bind_param("i", $playlist_id);
        $stmt_pl_info->execute();
        $result_pl_info = $stmt_pl_info->get_result();
        if ($pl_row = $result_pl_info->fetch_assoc()) {
            if ($pl_row['user_id'] != $user_id) {
            header("Location: homepage.php?error=Bu çalma listesini görüntüleme yetkiniz yok.");
            exit;
            }
            $playlist_title = $pl_row['title'];
        } else {
            header("Location: homepage.php?error_playlist=Çalma listesi bulunamadı.");
            exit;
        }
        $stmt_pl_info->close();
    } else {
        error_log("Playlist info query error: " . $conn->error);
        header("Location: homepage.php?error_playlist=Sistem hatası.");
        exit;
    }


    // Çalma listesindeki şarkıları çek
    $stmt_songs = $conn->prepare(
        "SELECT s.song_id, s.title AS song_title, s.image AS song_image, 
                ar.name AS artist_name, ar.artist_id, 
                co.country_name, co.country_code
         FROM PLAYLIST_SONGS pls
         JOIN SONGS s ON pls.song_id = s.song_id
         JOIN ALBUMS al ON s.album_id = al.album_id
         JOIN ARTISTS ar ON al.artist_id = ar.artist_id
         LEFT JOIN COUNTRY co ON ar.country_id = co.country_id
         WHERE pls.playlist_id = ?
         ORDER BY pls.date_added DESC"
    );

    if ($stmt_songs) {
        $stmt_songs->bind_param("i", $playlist_id);
        $stmt_songs->execute();
        $result_songs = $stmt_songs->get_result();
        while ($row = $result_songs->fetch_assoc()) {
            $playlist_songs[] = $row;
        }
        $stmt_songs->close();
    } else {
        error_log("Playlist songs query error: " . $conn->error);
    }
} else {
    header("Location: homepage.php?error_playlist=Çalma listesi ID'si belirtilmedi.");
    exit;
}

$searched_song_to_add = null;
if (isset($_GET['search_song_title']) && !empty(trim($_GET['search_song_title'])) && $playlist_id) {
    $song_title_query = "%" . trim($_GET['search_song_title']) . "%";
    $stmt_search_song = $conn->prepare(
        "SELECT s.song_id, s.title, s.image AS song_image, ar.name AS artist_name
         FROM SONGS s
         JOIN ALBUMS al ON s.album_id = al.album_id
         JOIN ARTISTS ar ON al.artist_id = ar.artist_id
         WHERE s.title LIKE ?
         LIMIT 1"
    );
    if ($stmt_search_song) {
        $stmt_search_song->bind_param("s", $song_title_query);
        $stmt_search_song->execute();
        $result_search_song = $stmt_search_song->get_result();
        if ($found_song = $result_search_song->fetch_assoc()) {
            $searched_song_to_add = $found_song;
        }
        $stmt_search_song->close();
    } else {
        error_log("Search song to add query error: " . $conn->error);
    }
}

// Şarkı ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_song' && isset($_POST['song_id_to_add']) && $playlist_id) {
    $song_id_to_add = intval($_POST['song_id_to_add']);

    // Şarkının zaten playlistte olup olmadığını kontrol et
    $stmt_check = $conn->prepare("SELECT playlistsong_id FROM PLAYLIST_SONGS WHERE playlist_id = ? AND song_id = ?");
    if ($stmt_check) {
        $stmt_check->bind_param("ii", $playlist_id, $song_id_to_add);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            header("Location: playlistpage.php?id=" . $playlist_id . "&error_add=Bu şarkı zaten çalma listenizde.");
            exit;
        }
        $stmt_check->close();
    }


    $stmt_add = $conn->prepare("INSERT INTO PLAYLIST_SONGS (playlist_id, song_id, date_added) VALUES (?, ?, NOW())");
    if ($stmt_add) {
        $stmt_add->bind_param("ii", $playlist_id, $song_id_to_add);
        if ($stmt_add->execute()) {
            header("Location: playlistpage.php?id=" . $playlist_id . "&success_add=Şarkı başarıyla eklendi.");
            exit;
        } else {
            error_log("Add song to playlist DB error: " . $stmt_add->error);
            header("Location: playlistpage.php?id=" . $playlist_id . "&error_add=Şarkı eklenirken bir hata oluştu.");
            exit;
        }
        $stmt_add->close();
    } else {
        error_log("Add song to playlist prepare error: " . $conn->error);
        header("Location: playlistpage.php?id=" . $playlist_id . "&error_add=Sistem hatası.");
        exit;
    }
}


include 'playlistpage.html';
$conn->close();
?>