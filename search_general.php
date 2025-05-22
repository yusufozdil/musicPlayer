<?php
session_start();
require_once('includes/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html?error=Lütfen önce giriş yapın.");
    exit;
}

$search_query = "";
$playlist_results = [];
$song_results = [];
$search_performed = false;

if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
    $search_performed = true;
    $search_query = trim($_GET['query']);
    $search_term = "%" . $search_query . "%";

    $user_id = $_SESSION['user_id'];

    $stmt_playlists = $conn->prepare("SELECT playlist_id, title, image FROM PLAYLISTS WHERE user_id = ? AND title LIKE ? ORDER BY title");
    if ($stmt_playlists) {
        $stmt_playlists->bind_param("is", $user_id, $search_term);
        $stmt_playlists->execute();
        $result_pl = $stmt_playlists->get_result();
        while ($row = $result_pl->fetch_assoc()) {
            $playlist_results[] = $row;
        }
        $stmt_playlists->close();
    } else {
        error_log("Playlist search prepare failed: " . $conn->error);
    }

    $stmt_songs = $conn->prepare(
        "SELECT s.song_id, s.title, s.image AS song_image, al.name_title AS album_name, ar.name AS artist_name
         FROM SONGS s
         JOIN ALBUMS al ON s.album_id = al.album_id
         JOIN ARTISTS ar ON al.artist_id = ar.artist_id
         WHERE s.title LIKE ? ORDER BY s.title"
    );
    if ($stmt_songs) {
        $stmt_songs->bind_param("s", $search_term);
        $stmt_songs->execute();
        $result_s = $stmt_songs->get_result();
        while ($row = $result_s->fetch_assoc()) {
            $song_results[] = $row;
        }
        $stmt_songs->close();
    } else {
        error_log("Song search prepare failed: " . $conn->error);
    }
}

?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Genel Arama Sonuçları: <?php echo htmlspecialchars($search_query ?? ''); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .search-results-container {
            background-color: #ffffff;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 850px; 
            margin: 20px auto;
        }
        .search-results-container h1 {
            color: #333;
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.8em;
            font-weight: 600;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .search-query-display {
            font-style: italic;
            color: #555;
            margin-bottom: 25px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #4CAF50; 
            text-decoration: none;
            font-weight: 500;
            padding: 8px 12px;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            transition: background-color 0.3s, color 0.3s;
        }
        .back-link:hover {
            background-color: #4CAF50;
            color: white;
        }
        .results-section {
            margin-bottom: 35px;
        }
        .results-section h2 {
            font-size: 1.5em;
            color: #444;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .item-list { 
            list-style: none;
            padding: 0;
        }
        .item {
            display: flex;
            align-items: center;
            padding: 12px 8px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s ease-in-out;
        }
        .item:last-child {
            border-bottom: none;
        }
        .item:hover {
            background-color: #f9f9f9;
        }
        .item img {
            width: 50px; 
            height: 50px;
            object-fit: cover;
            margin-right: 15px;
            border-radius: 4px; 
            border: 1px solid #eee;
        }
        .item-info {
            flex-grow: 1;
        }
        .item-info .item-title-link {
            font-size: 1.1em;
            font-weight: 600;
            color: #4CAF50;
            text-decoration: none;
            display: block;
            margin-bottom: 3px;
        }
        .item-info .item-title-link:hover {
            text-decoration: underline;
            color: #0056b3;
        }
        .item-details { 
            font-size: 0.85em;
            color: #666;
        }
        .no-results, .prompt-message {
            padding: 15px;
            text-align: left;
            font-size: 1em;
            color: #777;
            background-color: #fdfdfd;
            border-radius: 6px;
            border: 1px dashed #ddd;
        }
    </style>
</head>
<body>
    <div class="search-results-container">
    <a href="homepage.php" class="back-link" style="margin: 20px; color: #2f6a31; font-size: 1.0em; text-decoration:none;">Anasayfaya Dön</a>

        <h1>Genel Arama Sonuçları</h1>

        <?php if (!$search_performed && empty($search_query)): ?>
            <p class="prompt-message">Lütfen anasayfadaki genel arama çubuğunu kullanarak bir arama yapın.</p>
        <?php elseif (!$search_performed && !empty($search_query)): ?>
            <p class="search-query-display">"<?php echo htmlspecialchars($search_query); ?>" için sonuçlar:</p>
        <?php elseif ($search_performed && !empty($search_query)): ?>
             <p class="search-query-display">Aranan kelime: "<strong><?php echo htmlspecialchars($search_query); ?></strong>"</p>
        <?php endif; ?>

        <?php if ($search_performed): ?>
            <div class="results-section">
                <h2>Çalma Listeleri</h2>
                <?php if (!empty($playlist_results)): ?>
                    <ul class="item-list">
                        <?php foreach ($playlist_results as $playlist): ?>
                            <li class="item">
                                <img src="<?php echo htmlspecialchars($playlist['image'] ? $playlist['image'] : 'assets/images/default_playlist.png'); ?>" alt="<?php echo htmlspecialchars($playlist['title']); ?>">
                                <div class="item-info">
                                    <a href="playlistpage.php?id=<?php echo $playlist['playlist_id']; ?>" class="item-title-link">
                                        <?php echo htmlspecialchars($playlist['title']); ?>
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-results">Bu aramayla eşleşen çalma listesi bulunamadı.</p>
                <?php endif; ?>
            </div>

            <hr style="border:0; border-top:1px solid #eee; margin: 30px 0;">

            <div class="results-section">
                <h2>Şarkılar</h2>
                <?php if (!empty($song_results)): ?>
                    <ul class="item-list">
                        <?php foreach ($song_results as $song): ?>
                            <li class="item">
                                <img src="<?php echo htmlspecialchars($song['song_image'] ? $song['song_image'] : 'assets/images/default_song.png'); ?>" alt="<?php echo htmlspecialchars($song['title']); ?>">
                                <div class="item-info">
                                    <a href="currentmusic.php?song_id=<?php echo $song['song_id']; ?>" class="item-title-link">
                                        <?php echo htmlspecialchars($song['title']); ?>
                                    </a>
                                    <small class="item-details">
                                        Albüm: <?php echo htmlspecialchars($song['album_name'] ?? 'Bilinmiyor'); ?> 
                                        | Sanatçı: <?php echo htmlspecialchars($song['artist_name'] ?? 'Bilinmiyor'); ?>
                                    </small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-results">Bu aramayla eşleşen şarkı bulunamadı.</p>
                <?php endif; ?>
            </div>
        <?php elseif (empty($search_query) && $_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET)): ?>
             <p class="prompt-message">Arama yapmak için anasayfayı kullanın.</p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>