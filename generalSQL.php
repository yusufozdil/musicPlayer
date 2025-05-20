<?php
session_start();
require_once('includes/db_connect.php');

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html?error=Lütfen önce giriş yapın.");
    exit;
}

$page_title = "Genel SQL Raporları";

// --- Hazır Raporlar ---

// 1. En Çok Dinlenen İlk 5 Tür
$top_genres_users = [];
$stmt_top_genres_users = $conn->query(
    "SELECT top_genre, COUNT(user_id) AS user_count 
     FROM USERS 
     WHERE top_genre IS NOT NULL AND top_genre != ''
     GROUP BY top_genre 
     ORDER BY user_count DESC 
     LIMIT 5"
);
if ($stmt_top_genres_users) {
    while ($row = $stmt_top_genres_users->fetch_assoc()) {
        $top_genres_users[] = $row;
    }
} else {
    error_log("Top genres users query error: " . $conn->error);
}


// 2. En Çok Dinlenen İlk 5 Şarkı (Genel - PLAY_HISTORY'ye göre)
$top_songs_overall = [];
$stmt_top_songs_overall = $conn->query(
    "SELECT s.title, ar.name as artist_name, COUNT(ph.song_id) AS play_count
     FROM PLAY_HISTORY ph
     JOIN SONGS s ON ph.song_id = s.song_id
     JOIN ALBUMS al ON s.album_id = al.album_id
     JOIN ARTISTS ar ON al.artist_id = ar.artist_id
     GROUP BY ph.song_id, s.title, ar.name
     ORDER BY play_count DESC
     LIMIT 5"
);
if ($stmt_top_songs_overall) {
    while ($row = $stmt_top_songs_overall->fetch_assoc()) {
        $top_songs_overall[] = $row;
    }
} else {
    error_log("Top songs overall query error: " . $conn->error);
}

// 3. Ülkelere Göre Sanatçı Sayıları (Top 5 Ülke)
$artist_counts_by_country = [];
$stmt_artists_country = $conn->query(
    "SELECT c.country_name, COUNT(ar.artist_id) AS artist_count
     FROM ARTISTS ar
     JOIN COUNTRY c ON ar.country_id = c.country_id
     GROUP BY ar.country_id, c.country_name
     ORDER BY artist_count DESC
     LIMIT 5"
);
if ($stmt_artists_country) {
    while ($row = $stmt_artists_country->fetch_assoc()) {
        $artist_counts_by_country[] = $row;
    }
} else {
    error_log("Artist counts by country query error: " . $conn->error);
}


// --- Özel SQL Sorgusu ---
$custom_sql_query = "";
$custom_sql_results = null; 
$custom_sql_error = null;
$custom_sql_headers = [];
$custom_sql_affected_rows = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['custom_query'])) {
    $custom_sql_query = trim($_POST['custom_query']);

    if (!empty($custom_sql_query)) {
        $custom_sql_query_to_run = $custom_sql_query;
        $result = $conn->query($custom_sql_query_to_run);

        if ($result === false) {
            $custom_sql_error = "Sorgu hatası: " . $conn->error;
        } else {
            if ($result === true) {
                $custom_sql_affected_rows = $conn->affected_rows;
                $custom_sql_results = [];
                $custom_sql_headers = ["Durum", "Etkilenen Satır Sayısı"];
                $custom_sql_results[] = ["Başarılı", $custom_sql_affected_rows];

            } elseif (is_object($result) && isset($result->num_rows)) { 
                if ($result->num_rows > 0) {
                    $fields = $result->fetch_fields();
                    foreach ($fields as $field) {
                        $custom_sql_headers[] = $field->name;
                    }
                    $rowCount = 0;
                    $custom_sql_results = []; 
                    while (($row = $result->fetch_assoc()) && $rowCount < 5) {
                        $custom_sql_results[] = $row;
                        $rowCount++;
                    }
                    if ($result->num_rows > 5) {
                         $custom_sql_error = "Not: Sonuç 5 satırla sınırlandırılmıştır.";
                    }
                } else {
                    $custom_sql_results = [];
                    $custom_sql_headers = ["Bilgi"];
                    $custom_sql_results[] = ["Sorgu sonuç döndürmedi."];
                }
                $result->free();
            } else {
                $custom_sql_error = "Sorgu çalıştırıldı ancak sonuç tipi anlaşılamadı.";
            }
        }
    } else {
        $custom_sql_error = "Lütfen bir SQL sorgusu girin.";
    }
}


include 'generalSQL.html';
$conn->close();
?>