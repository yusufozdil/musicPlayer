<?php
session_start();
require_once('includes/db_connect.php');

// 1. Kullanıcı giriş yapmış mı kontrol et
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html?error=Yeni çalma listesi oluşturmak için lütfen giriş yapın.");
    exit;
}

// 2. Form POST metodu ile gönderilmiş mi ve playlist_title var mı kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['playlist_title'])) {
    
    $user_id = $_SESSION['user_id'];
    $playlist_title = trim($_POST['playlist_title']);
    // Yeni alanları al (boşsa NULL olacak şekilde)
    $playlist_description = !empty(trim($_POST['playlist_description'])) ? trim($_POST['playlist_description']) : NULL;
    $playlist_image_url = !empty(trim($_POST['playlist_image'])) ? trim($_POST['playlist_image']) : NULL;


    // 3. Playlist başlığı boş mu kontrol et
    if (empty($playlist_title)) {
        header("Location: homepage.php?error_playlist=Çalma listesi adı boş bırakılamaz.");
        exit;
    }

    // 4. Veritabanına yeni çalma listesini ekle
    $date_created = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO PLAYLISTS (user_id, title, date_created, description, image) VALUES (?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("issss", $user_id, $playlist_title, $date_created, $playlist_description, $playlist_image_url);
        
        if ($stmt->execute()) {
            $new_playlist_id = $stmt->insert_id; 
            $stmt->close();
            header("Location: homepage.php?success_playlist=Çalma listesi '" . urlencode($playlist_title) . "' başarıyla oluşturuldu.");
            exit;
        } else {
            $error_message_db = "Çalma listesi oluşturulurken bir hata oluştu: " . $stmt->error;
            error_log($error_message_db); 
            $stmt->close();
            header("Location: homepage.php?error_playlist=" . urlencode("Veritabanı hatası oluştu. Detaylar için logları kontrol edin."));
            exit;
        }
    } else {
        $error_message_sql = "SQL hazırlama hatası: " . $conn->error;
        error_log($error_message_sql);
        header("Location: homepage.php?error_playlist=" . urlencode("Sistem hatası oluştu."));
        exit;
    }

} else {
    header("Location: homepage.php");
    exit;
}

// Eğer script buraya kadar geldiyse bağlantı kapanır
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>