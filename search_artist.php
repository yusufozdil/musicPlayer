<?php
session_start();
require_once('includes/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html?error=Lütfen önce giriş yapın.");
    exit;
}

$search_query_artist = "";
$artist_results = [];
$search_performed_artist = false;

if (isset($_GET['artist_name']) && !empty(trim($_GET['artist_name']))) {
    $search_performed_artist = true;
    $search_query_artist = trim($_GET['artist_name']);
    $search_term_artist = "%" . $search_query_artist . "%";

    $stmt = $conn->prepare("SELECT artist_id, name, image, genre, listeners FROM ARTISTS WHERE name LIKE ? ORDER BY name");
    if ($stmt) {
        $stmt->bind_param("s", $search_term_artist);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $artist_results[] = $row;
        }
        $stmt->close();
    } else {
        error_log("Artist search prepare failed: " . $conn->error);
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sanatçı Arama: <?php echo htmlspecialchars($search_query_artist); ?></title>
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
        <h1>Sanatçı Arama Sonuçları: "<?php echo htmlspecialchars($search_query_artist); ?>"</h1>
        <p><a href="homepage.php"><< Anasayfaya Dön</a></p>

        <?php if (!$search_performed_artist): ?>
            <p>Lütfen aramak için bir sanatçı adı girin.</p>
        <?php elseif (empty($artist_results)): ?>
            <p>Bu aramayla eşleşen sanatçı bulunamadı.</p>
        <?php else: ?>
            <p>Bulunan Sanatçılar:</p>
            <?php foreach ($artist_results as $artist): ?>
                <div class="item">
                    <img src="<?php echo htmlspecialchars($artist['image'] ? $artist['image'] : 'assets/images/default_artist.png'); ?>" alt="">
                    <div>
                        <a href="artistpage.php?id=<?php echo $artist['artist_id']; ?>">
                            <?php echo htmlspecialchars($artist['name']); ?>
                        </a><br>
                        <small>Tür: <?php echo htmlspecialchars($artist['genre']); ?> - Dinleyiciler: <?php echo number_format($artist['listeners']); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>