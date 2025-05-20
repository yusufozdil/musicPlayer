<?php
session_start();
require_once('includes/db_connect.php');


$album_id = null;
$album_data = null;
$album_songs = [];
$page_title = "Albüm Bilgisi";

if (isset($_GET['id'])) {
    $album_id = intval($_GET['id']);

    // 1. Albüm Temel Bilgilerini ve Sanatçısını Çek
    $stmt_album = $conn->prepare(
        "SELECT al.name_title AS album_title, al.release_date AS album_release_date, 
                al.genre AS album_genre, al.image AS album_image, al.music_number,
                ar.artist_id, ar.name AS artist_name
         FROM ALBUMS al
         JOIN ARTISTS ar ON al.artist_id = ar.artist_id
         WHERE al.album_id = ?"
    );
    if ($stmt_album) {
        $stmt_album->bind_param("i", $album_id);
        $stmt_album->execute();
        $result_album = $stmt_album->get_result();
        if ($result_album->num_rows === 1) {
            $album_data = $result_album->fetch_assoc();
            $page_title = htmlspecialchars($album_data['album_title']) . " - " . htmlspecialchars($album_data['artist_name']);
        } else {
            $page_title = "Albüm Bulunamadı";
        }
        $stmt_album->close();
    } else {
        error_log("Album data query error: " . $conn->error);
        $page_title = "Sistem Hatası";
    }

    if ($album_data) {
        // 2. Albümdeki Şarkıları Çek
        $stmt_songs = $conn->prepare(
            "SELECT s.song_id, s.title AS song_title, s.duration, s.image AS song_image,
                    s.genre AS song_genre, s.release_date AS song_release_date
             FROM SONGS s
             WHERE s.album_id = ?
             ORDER BY s.song_id ASC"
        );
        if ($stmt_songs) {
            $stmt_songs->bind_param("i", $album_id);
            $stmt_songs->execute();
            $result_songs = $stmt_songs->get_result();
            while ($row = $result_songs->fetch_assoc()) {
                $album_songs[] = $row;
            }
            $stmt_songs->close();
        } else {
            error_log("Album songs query error: " . $conn->error);
        }
    }
} else {
    $page_title = "Albüm Belirtilmedi";
}

include 'albumpage.html';
$conn->close();
?>