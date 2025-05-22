<?php
session_start();
require_once('includes/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html?error=Lütfen önce giriş yapın.");
    exit;
}

$search_query_history = "";
$song_results_history = [];
$search_performed_history = false;

if (isset($_GET['song_title']) && !empty(trim($_GET['song_title']))) {
    $search_performed_history = true;
    $search_query_history = trim($_GET['song_title']);
    $search_term_history = "%" . $search_query_history . "%";
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare(
        "SELECT DISTINCT s.song_id, s.title, s.image AS song_image, al.name_title AS album_name, ar.name AS artist_name
         FROM PLAY_HISTORY ph
         JOIN SONGS s ON ph.song_id = s.song_id
         JOIN ALBUMS al ON s.album_id = al.album_id
         JOIN ARTISTS ar ON al.artist_id = ar.artist_id
         WHERE ph.user_id = ? AND s.title LIKE ?
         ORDER BY s.title"
    );

    if ($stmt) {
        $stmt->bind_param("is", $user_id, $search_term_history);

        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $song_results_history[] = $row;
        }
        $stmt->close();
    } else {
        error_log("Song search from history prepare failed: " . $conn->error);
    }
}

?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geçmişten Şarkı Arama: <?php echo htmlspecialchars($search_query_history ?? ''); ?></title>
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
            max-width: 800px;
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
        .song-list {
            list-style: none;
            padding: 0;
        }
        .song-item { 
            display: flex;
            align-items: center;
            padding: 15px 10px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s ease-in-out;
        }
        .song-item:last-child {
            border-bottom: none;
        }
        .song-item:hover {
            background-color: #f9f9f9;
        }
        .song-item img {
            width: 50px; 
            height: 50px;
            object-fit: cover;
            margin-right: 15px;
            border-radius: 4px;
            border: 1px solid #eee;
        }
        .song-info {
            flex-grow: 1;
        }
        .song-info .song-title-link { 
            font-size: 1.15em;
            font-weight: 600;
            color: #4CAF50; 
            text-decoration: none;
            display: block;
            margin-bottom: 4px;
        }
        .song-info .song-title-link:hover {
            text-decoration: underline;
            color: #0056b3;
        }
        .song-details {
            font-size: 0.9em;
            color: #666;
        }
        .no-results, .prompt-message {
            padding: 20px;
            text-align: center;
            font-size: 1.1em;
            color: #777;
            background-color: #f9f9f9;
            border-radius: 6px;
            border: 1px dashed #ddd;
        }
    </style>
</head>
<body>
    <div class="search-results-container">
    <a href="homepage.php" class="back-link" style="margin: 20px; color: #2f6a31; font-size: 1.0em; text-decoration:none;">Anasayfaya Dön</a>

        <h1>Geçmişten Şarkı Arama Sonuçları</h1>

        <?php if (!$search_performed_history && empty($search_query_history)): ?>
            <p class="prompt-message">Lütfen anasayfadaki "Geçmişten Şarkı Ara" çubuğunu kullanarak bir arama yapın.</p>
        <?php elseif (!$search_performed_history && !empty($search_query_history)): ?>
            <p class="search-query-display">"<?php echo htmlspecialchars($search_query_history); ?>" için sonuçlar:</p>
        <?php elseif ($search_performed_history && !empty($search_query_history)): ?>
             <p class="search-query-display">Aranan şarkı: "<strong><?php echo htmlspecialchars($search_query_history); ?></strong>"</p>
        <?php endif; ?>


        <?php if ($search_performed_history): ?>
            <?php if (empty($song_results_history)): ?>
                <p class="no-results">"<?php echo htmlspecialchars($search_query_history); ?>" ile eşleşen şarkı dinleme geçmişinizde veya veritabanında bulunamadı.</p>
            <?php else: ?>
                <p>Bulunan Şarkılar (<?php echo count($song_results_history); ?>):</p>
                <ul class="song-list">
                    <?php foreach ($song_results_history as $song): ?>
                        <li class="song-item">
                            <img src="<?php echo htmlspecialchars($song['song_image'] ? $song['song_image'] : 'assets/images/default_song.png'); ?>" alt="<?php echo htmlspecialchars($song['title']); ?>">
                            <div class="song-info">
                                <a href="currentmusic.php?song_id=<?php echo $song['song_id']; ?>" class="song-title-link">
                                    <?php echo htmlspecialchars($song['title']); ?>
                                </a>
                                <small class="song-details">
                                    Albüm: <?php echo htmlspecialchars($song['album_name'] ?? 'Bilinmiyor'); ?> 
                                    | Sanatçı: <?php echo htmlspecialchars($song['artist_name'] ?? 'Bilinmiyor'); ?>
                                </small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php elseif (empty($search_query_history) && $_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET)): ?>
             <p class="prompt-message">Arama yapmak için anasayfadaki ilgili arama çubuğunu kullanın.</p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>