<?php
session_start();
require_once('includes/db_connect.php');

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

$artist_id = null;
$artist_data = null;
$artist_albums = [];
$top_songs = [];
$page_title = "Sanatçı Bilgisi";
$is_following = false;

if (isset($_GET['id'])) {
    $artist_id = intval($_GET['id']);

    // 1. Sanatçı Temel Bilgilerini Çek
    $stmt_artist = $conn->prepare(
        "SELECT ar.*, co.country_name 
         FROM ARTISTS ar
         LEFT JOIN COUNTRY co ON ar.country_id = co.country_id
         WHERE ar.artist_id = ?"
    );
    if ($stmt_artist) {
        $stmt_artist->bind_param("i", $artist_id);
        $stmt_artist->execute();
        $result_artist = $stmt_artist->get_result();
        if ($result_artist->num_rows === 1) {
            $artist_data = $result_artist->fetch_assoc();
            $page_title = htmlspecialchars($artist_data['name']) . " - Sanatçı Detayları";
        } else {
            $page_title = "Sanatçı Bulunamadı";
        }
        $stmt_artist->close();
    } else {
        error_log("Artist data query error: " . $conn->error);
        $page_title = "Sistem Hatası";
    }

    if ($artist_data) {
        // 2. Sanatçının Son 5 Albümünü Çek
        $stmt_albums = $conn->prepare(
            "SELECT album_id, name_title, release_date, image 
             FROM ALBUMS 
             WHERE artist_id = ? 
             ORDER BY release_date DESC 
             LIMIT 5"
        );
        if ($stmt_albums) {
            $stmt_albums->bind_param("i", $artist_id);
            $stmt_albums->execute();
            $result_albums = $stmt_albums->get_result();
            while ($row = $result_albums->fetch_assoc()) {
                $artist_albums[] = $row;
            }
            $stmt_albums->close();
        } else {
            error_log("Artist albums query error: " . $conn->error);
        }

        // 3. Sanatçının En Çok Dinlenen 5 Şarkısını Çek (PLAY_HISTORY'den)
        $stmt_top_songs = $conn->prepare(
            "SELECT s.song_id, s.title, s.image, COUNT(ph.song_id) AS play_count
             FROM SONGS s
             JOIN ALBUMS al ON s.album_id = al.album_id
             JOIN PLAY_HISTORY ph ON s.song_id = ph.song_id
             WHERE al.artist_id = ?
             GROUP BY s.song_id, s.title, s.image
             ORDER BY play_count DESC
             LIMIT 5"
        );
        if ($stmt_top_songs) {
            $stmt_top_songs->bind_param("i", $artist_id);
            $stmt_top_songs->execute();
            $result_top_songs = $stmt_top_songs->get_result();
            while ($row = $result_top_songs->fetch_assoc()) {
                $top_songs[] = $row;
            }
            $stmt_top_songs->close();
        } else {
            error_log("Artist top songs query error: " . $conn->error);
        }
    }

} else {
    $page_title = "Sanatçı Belirtilmedi";
}

// Takip Etme Aksiyonu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $user_id && $artist_id) {
    if ($_POST['action'] === 'follow_artist') {
        // ARTISTS tablosundaki listeners sayısını artır
        $stmt_follow = $conn->prepare("UPDATE ARTISTS SET listeners = listeners + 1 WHERE artist_id = ?");
        if ($stmt_follow) {
            $stmt_follow->bind_param("i", $artist_id);
            $stmt_follow->execute();
            $stmt_follow->close();
            // Sayfayı yenilemek için veya başarı mesajı için yönlendirme
            header("Location: artistpage.php?id=" . $artist_id . "&follow_success=1");
            exit;
        } else {
            error_log("Follow artist query error: " . $conn->error);
            header("Location: artistpage.php?id=" . $artist_id . "&follow_error=1");
            exit;
        }
    }
}


include 'artistpage.html';
$conn->close();
?>