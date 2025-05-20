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

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arama Sonuçları: <?php echo htmlspecialchars($search_query); ?></title>
    <style>
        body { font-family: sans-serif; }
        .container { padding: 20px; }
        .results-section { margin-bottom: 30px; }
        .item { display: flex; align-items: center; margin-bottom: 10px; padding: 5px; border: 1px solid #eee; }
        .item img { width: 40px; height: 40px; object-fit: cover; margin-right: 10px; }
        a { text-decoration: none; color: #007bff; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Arama Sonuçları: "<?php echo htmlspecialchars($search_query); ?>"</h1>
        <p><a href="homepage.php"><< Anasayfaya Dön</a></p>

        <?php if (!$search_performed): ?>
            <p>Lütfen aramak için bir terim girin.</p>
        <?php else: ?>
            <div class="results-section">
                <h2>Çalma Listeleri</h2>
                <?php if (!empty($playlist_results)): ?>
                    <?php foreach ($playlist_results as $playlist): ?>
                        <div class="item">
                            <img src="<?php echo htmlspecialchars($playlist['image'] ? $playlist['image'] : 'assets/images/default_playlist.png'); ?>" alt="">
                            <a href="playlistpage.php?id=<?php echo $playlist['playlist_id']; ?>">
                                <?php echo htmlspecialchars($playlist['title']); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Bu aramayla eşleşen çalma listesi bulunamadı.</p>
                <?php endif; ?>
            </div>

            <div class="results-section">
                <h2>Şarkılar</h2>
                <?php if (!empty($song_results)): ?>
                    <?php foreach ($song_results as $song): ?>
                        <div class="item">
                            <img src="<?php echo htmlspecialchars($song['song_image'] ? $song['song_image'] : 'assets/images/default_song.png'); ?>" alt="">
                            <div>
                                <a href="currentmusic.php?song_id=<?php echo $song['song_id']; ?>">
                                    <?php echo htmlspecialchars($song['title']); ?>
                                </a><br>
                                <small>Albüm: <?php echo htmlspecialchars($song['album_name']); ?> - Sanatçı: <?php echo htmlspecialchars($song['artist_name']); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Bu aramayla eşleşen şarkı bulunamadı.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>