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

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geçmişten Şarkı Arama: <?php echo htmlspecialchars($search_query_history); ?></title>
    <style>
        body { font-family: sans-serif; }
        .container { padding: 20px; }
        .item { display: flex; align-items: center; margin-bottom: 10px; padding: 5px; border: 1px solid #eee; }
        .item img { width: 40px; height: 40px; object-fit: cover; margin-right: 10px; }
        a { text-decoration: none; color: #007bff; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Geçmişten Şarkı Arama Sonuçları: "<?php echo htmlspecialchars($search_query_history); ?>"</h1>
        <p><a href="homepage.php"><< Anasayfaya Dön</a></p>

        <?php if (!$search_performed_history): ?>
            <p>Lütfen aramak için bir şarkı adı girin.</p>
        <?php elseif (empty($song_results_history)): ?>
            <p>Bu aramayla eşleşen şarkı dinleme geçmişinizde veya veritabanında bulunamadı.</p>
        <?php else: ?>
            <p>Bulunan Şarkılar:</p>
            <?php foreach ($song_results_history as $song): ?>
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
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>