<?php
session_start();
require_once('includes/db_connect.php');


$song_id = null;
$song_data = null;
$page_title = "Şarkı Bilgisi";

if (isset($_GET['song_id'])) {
    $song_id = intval($_GET['song_id']);

    $stmt = $conn->prepare(
        "SELECT s.title AS song_title, s.duration, s.genre AS song_genre, s.release_date AS song_release_date, s.image AS song_image,
                al.album_id, al.name_title AS album_title, al.release_date AS album_release_date,
                ar.artist_id, ar.name AS artist_name, ar.genre AS artist_genre
         FROM SONGS s
         JOIN ALBUMS al ON s.album_id = al.album_id
         JOIN ARTISTS ar ON al.artist_id = ar.artist_id
         WHERE s.song_id = ?"
    );

    if ($stmt) {
        $stmt->bind_param("i", $song_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $song_data = $result->fetch_assoc();
            $page_title = htmlspecialchars($song_data['song_title']) . " - " . htmlspecialchars($song_data['artist_name']);

        } else {
            $page_title = "Şarkı Bulunamadı";
        }
        $stmt->close();
    } else {
        error_log("Current music query error: " . $conn->error);
        $page_title = "Sistem Hatası";
    }
} else {
    $page_title = "Şarkı Belirtilmedi";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_played') {
    if (isset($_SESSION['user_id']) && $song_id) {
        $current_user_id = $_SESSION['user_id'];
        $can_add = true;

        $stmt_last_play = $conn->prepare("SELECT playtime FROM PLAY_HISTORY WHERE user_id = ? AND song_id = ? ORDER BY playtime DESC LIMIT 1");
        if ($stmt_last_play) {
            $stmt_last_play->bind_param("ii", $current_user_id, $song_id);
            $stmt_last_play->execute();
            $result_last_play = $stmt_last_play->get_result();
            if ($last_play_row = $result_last_play->fetch_assoc()) {
                if (strtotime($last_play_row['playtime']) > (time() - 5)) {
                    $can_add = false;
                    $action_message_params = "&played=already_recent";
                }
            }
            $stmt_last_play->close();
        }


        if ($can_add) {
            $stmt_history = $conn->prepare("INSERT INTO PLAY_HISTORY (user_id, song_id, playtime) VALUES (?, ?, NOW())");
            if ($stmt_history) {
                $stmt_history->bind_param("ii", $current_user_id, $song_id);
                if ($stmt_history->execute()) {
                    $action_message_params = "&played=success";
                } else {
                    error_log("currentmusic.php - DB Execute Error (mark_played): " . $stmt_history->error);
                    $action_message_params = "&played=error";
                }
                $stmt_history->close();
            } else {
                error_log("currentmusic.php - DB Prepare Error (mark_played): " . $conn->error);
                $action_message_params = "&played=error";
            }
        }
    } else {
        $action_message_params = "&played=auth_error";
    }
    header("Location: currentmusic.php?song_id=" . $song_id . $action_message_params);
    exit;
}

include 'currentmusic.html';
$conn->close();
?>