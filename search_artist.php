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
?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sanatçı Arama Sonuçları: <?php echo htmlspecialchars($search_query_artist ?? ''); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5; /* Hafif gri arkaplan */
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
            max-width: 800px; /* Sonuçlar için biraz daha geniş */
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
        .artist-list {
            list-style: none;
            padding: 0;
        }
        .artist-item {
            display: flex;
            align-items: center;
            padding: 15px 10px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s ease-in-out;
        }
        .artist-item:last-child {
            border-bottom: none;
        }
        .artist-item:hover {
            background-color: #f9f9f9;
        }
        .artist-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            margin-right: 20px;
            border-radius: 50%;
            border: 2px solid #eee;
        }
        .artist-info {
            flex-grow: 1;
        }
        .artist-info .artist-name-link {
            font-size: 1.2em;
            font-weight: 600;
            color: #4CAF50; 
            text-decoration: none;
            display: block;
            margin-bottom: 4px;
        }
        .artist-info .artist-name-link:hover {
            text-decoration: underline;
            color: #0056b3;
        }
        .artist-details {
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

        <h1>Sanatçı Arama Sonuçları</h1>
        
        <?php if (!$search_performed_artist && empty($search_query_artist)): ?>
            <p class="prompt-message">Lütfen anasayfadaki sanatçı arama çubuğunu kullanarak bir arama yapın.</p>
        <?php elseif (!$search_performed_artist && !empty($search_query_artist)): ?>
            <p class="search-query-display">"<?php echo htmlspecialchars($search_query_artist); ?>" için sonuçlar:</p>
        <?php elseif ($search_performed_artist && !empty($search_query_artist)): ?>
             <p class="search-query-display">Aranan sanatçı: "<strong><?php echo htmlspecialchars($search_query_artist); ?></strong>"</p>
        <?php endif; ?>


        <?php if ($search_performed_artist): ?>
            <?php if (empty($artist_results)): ?>
                <p class="no-results">"<?php echo htmlspecialchars($search_query_artist); ?>" ile eşleşen sanatçı bulunamadı.</p>
            <?php else: ?>
                <p>Bulunan Sanatçılar (<?php echo count($artist_results); ?>):</p>
                <ul class="artist-list">
                    <?php foreach ($artist_results as $artist): ?>
                        <li class="artist-item">
                            <img src="<?php echo htmlspecialchars($artist['image'] ? $artist['image'] : 'assets/images/default_artist.png'); ?>" alt="<?php echo htmlspecialchars($artist['name']); ?>">
                            <div class="artist-info">
                                <a href="artistpage.php?id=<?php echo $artist['artist_id']; ?>" class="artist-name-link">
                                    <?php echo htmlspecialchars($artist['name']); ?>
                                </a>
                                <small class="artist-details">
                                    Tür: <?php echo htmlspecialchars($artist['genre'] ?? 'Belirtilmemiş'); ?> 
                                    | Dinleyiciler: <?php echo number_format($artist['listeners'] ?? 0); ?>
                                </small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php elseif (empty($search_query_artist) && $_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET)): ?>
             <!-- Sayfaya doğrudan, arama yapılmadan gelinmişse -->
             <p class="prompt-message">Arama yapmak için anasayfayı kullanın.</p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>