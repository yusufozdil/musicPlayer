<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ayarlar
define('NUM_USERS', 100); 
define('NUM_ARTISTS', 250); 
define('NUM_ALBUMS_PER_ARTIST_AVG', 2); 
define('NUM_SONGS_PER_ALBUM_AVG', 7); 
define('NUM_PLAYLISTS_PER_USER_AVG', 5); 
define('NUM_SONGS_PER_PLAYLIST_AVG', 10); 
define('NUM_PLAY_HISTORY_PER_USER_AVG', 10);

define('MIN_ALBUMS_TOTAL', 100); 
define('MIN_SONGS_TOTAL', 1000); 
define('MIN_PLAYLISTS_TOTAL', 500); 
define('MIN_PLAYLIST_SONGS_TOTAL', 500); 
define('MIN_PLAY_HISTORY_TOTAL', 100);

$output_sql_file = 'sql/generated_data.sql'; 
$data_path = 'data/'; 

// --- Yardımcı Fonksiyonlar ---

function getRandomLineFromFile($filepath) {
    if (!file_exists($filepath) || !is_readable($filepath)) {
        echo "Hata: '$filepath' dosyası bulunamadı veya okunamıyor.\n";
        return false;
    }
    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (empty($lines)) {
        echo "Hata: '$filepath' dosyası boş veya sadece boş satırlar içeriyor.\n";
        return false;
    }
    return $lines[array_rand($lines)];
}

function getRandomDate($startDate, $endDate) {
    $startTimestamp = strtotime($startDate);
    $endTimestamp = strtotime($endDate);
    $randomTimestamp = mt_rand($startTimestamp, $endTimestamp);
    return date('Y-m-d', $randomTimestamp);
}

function getRandomDuration() {
    $h = 0; 
    $m = mt_rand(1, 7); 
    $s = mt_rand(0, 59);
    return sprintf('%02d:%02d:%02d', $h, $m, $s);
}

function generateEmail($name, $surname) {
    $domains = ['example.com', 'mail.com', 'test.org', 'demo.net'];
    $cleaned_name = strtolower(preg_replace('/[^a-z0-9]/i', '', $name));
    $cleaned_surname = strtolower(preg_replace('/[^a-z0-9]/i', '', $surname));
    return $cleaned_name . '.' . $cleaned_surname . mt_rand(10, 999) . '@' . $domains[array_rand($domains)];
}

function generateUsername($name, $surname) {
    $cleaned_name = strtolower(preg_replace('/[^a-z0-9]/i', '', $name));
    $cleaned_surname = strtolower(preg_replace('/[^a-z0-9]/i', '', $surname));
    $separator = ['_', '.', ''];
    return $cleaned_name . $separator[array_rand($separator)] . $cleaned_surname . mt_rand(1, 99);
}

function generatePassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    return substr(str_shuffle($chars), 0, $length);
}

// --- Veri Depolama Dizileri ---
$country_ids = [];
$artist_ids_data = []; 
$user_ids_data = [];   
$album_ids_data = [];  
$song_ids = [];
$playlist_ids_data = []; 

// --- SQL Çıktı Akışı ---
$sql_output = "-- Müzik Çalar Veritabanı için Otomatik Oluşturulmuş Veriler --\n";
$sql_output .= "-- Oluşturma Tarihi: " . date('Y-m-d H:i:s') . " --\n\n";

// --- 1. COUNTRY Verilerini Oluştur ---
$sql_output .= "-- COUNTRIES --\n";
$country_file_path = $data_path . 'input_countries.txt';
if (file_exists($country_file_path) && is_readable($country_file_path)) {
    $country_lines = file($country_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $country_id_counter = 1;
    foreach ($country_lines as $line) {
        list($code, $name) = explode(';', $line, 2);
        if ($code && $name) {
            $code = trim($code);
            $name = trim($name);
            $escaped_name = addslashes($name);
            $escaped_code = addslashes($code);
            $sql_output .= "INSERT INTO COUNTRY (country_id, country_name, country_code) VALUES ($country_id_counter, '$escaped_name', '$escaped_code');\n";
            $country_ids[] = $country_id_counter;
            $country_id_counter++;
        }
    }
} else {
    echo "Uyarı: '$country_file_path' bulunamadı. Ülke verisi oluşturulamadı.\n";
    $fallback_countries = [1 => ['TR', 'Türkiye'], 2 => ['US', 'Amerika Birleşik Devletleri']];
    foreach($fallback_countries as $id => $data) {
        $sql_output .= "INSERT INTO COUNTRY (country_id, country_name, country_code) VALUES ($id, '{$data[1]}', '{$data[0]}');\n";
        $country_ids[] = $id;
    }
}
$sql_output .= "\n";
echo "Ülkeler oluşturuldu.\n";

// --- 2. ARTISTS Verilerini Oluştur ---
$sql_output .= "-- ARTISTS --\n";
$artist_names_file = $data_path . 'input_artist_names.txt'; 
$genres_file = $data_path . 'input_genres.txt';
$bio_snippets_file = $data_path . 'input_bios.txt'; 
$artist_image_urls_file = $data_path . 'input_image_urls_artist.txt'; 

for ($i = 1; $i <= NUM_ARTISTS; $i++) {
    $name = getRandomLineFromFile($artist_names_file);
    if ($name === false) { echo "Sanatçı adı üretilemedi.\n"; continue; }
    
    $genre = getRandomLineFromFile($genres_file);
    if ($genre === false) $genre = 'Pop'; 

    $date_joined = getRandomDate('2010-01-01', '2024-01-01');
    $listeners = mt_rand(500, 5000000);
    $bio_part1 = getRandomLineFromFile($bio_snippets_file);
    $bio_part2 = getRandomLineFromFile($bio_snippets_file);
    $bio = ($bio_part1 ? $bio_part1 : "Bu sanatçı hakkında bilgi bulunmamaktadır.") . " " . ($bio_part2 ? $bio_part2 : "");
    
    $country_id = !empty($country_ids) ? $country_ids[array_rand($country_ids)] : 'NULL';
    
    $image_url = getRandomLineFromFile($artist_image_urls_file);
    if ($image_url === false) {
        $image_url = "assets/images/default_artist.png"; 
    }

    $escaped_name = addslashes($name);
    $escaped_genre = addslashes($genre);
    $escaped_bio = addslashes(trim($bio));
    $escaped_image_url = addslashes($image_url);

    $sql_output .= "INSERT INTO ARTISTS (artist_id, name, genre, date_joined, total_num_music, total_albums, listeners, bio, country_id, image) VALUES ($i, '$escaped_name', '$escaped_genre', '$date_joined', 0, 0, $listeners, '$escaped_bio', $country_id, '$escaped_image_url');\n";
    $artist_ids_data[$i] = ['country_id' => $country_id, 'name' => $name, 'image_url' => $image_url];
}
$sql_output .= "\n";
echo NUM_ARTISTS . " sanatçı oluşturuldu.\n";

// --- 3. USERS Verilerini Oluştur ---
$sql_output .= "-- USERS --\n";
$names_file = $data_path . 'input_names.txt';
$surnames_file = $data_path . 'input_surnames.txt';
$subscription_types = ['free', 'premium', 'family_pack'];
$user_image_urls_file = $data_path . 'input_image_urls_user.txt'; 

for ($i = 1; $i <= NUM_USERS; $i++) {
    $name = getRandomLineFromFile($names_file);
    $surname = getRandomLineFromFile($surnames_file);
    if ($name === false || $surname === false) { echo "Kullanıcı adı/soyadı üretilemedi.\n"; continue; }

    $full_name = $name . " " . $surname;
    $username = generateUsername($name, $surname);
    $email = generateEmail($name, $surname);
    $password = generatePassword(); 
    
    $age = mt_rand(15, 60);
    $date_joined = getRandomDate('2015-01-01', '2024-05-01');
    $last_login = (mt_rand(0,1) == 1) ? getRandomDate($date_joined, '2024-05-20') . ' ' . sprintf('%02d:%02d:%02d', mt_rand(0,23), mt_rand(0,59), mt_rand(0,59)) : 'NULL';
    $follower_num = mt_rand(0, 500);
    $subscription_type = $subscription_types[array_rand($subscription_types)];
    $top_genre = getRandomLineFromFile($genres_file);
    if ($top_genre === false) $top_genre = 'Unknown';
    $num_songs_liked = mt_rand(0, 200);
    
    $random_artist_id_for_most_played = !empty($artist_ids_data) ? array_rand($artist_ids_data) : null;
    $most_played_artist_name = $random_artist_id_for_most_played ? $artist_ids_data[$random_artist_id_for_most_played]['name'] : 'NULL';
    
    $country_id = !empty($country_ids) ? $country_ids[array_rand($country_ids)] : 'NULL';
    
    $user_image_url = getRandomLineFromFile($user_image_urls_file);
    if ($user_image_url === false) {
        $user_image_url = "assets/images/default_user.png"; 
    }

    $escaped_full_name = addslashes($full_name);
    $escaped_username = addslashes($username);
    $escaped_email = addslashes($email);
    $escaped_password = addslashes($password); 
    $escaped_subscription_type = addslashes($subscription_type);
    $escaped_top_genre = addslashes($top_genre);
    $escaped_most_played_artist = $most_played_artist_name !== 'NULL' ? "'" . addslashes($most_played_artist_name) . "'" : "NULL";
    $escaped_user_image_url = addslashes($user_image_url);
    $last_login_sql = ($last_login !== 'NULL') ? "'$last_login'" : "NULL";

    $sql_output .= "INSERT INTO USERS (user_id, country_id, age, name, username, email, password, date_joined, last_login, follower_num, subscription_type, top_genre, num_songs_liked, most_played_artist, image) VALUES ($i, $country_id, $age, '$escaped_full_name', '$escaped_username', '$escaped_email', '$escaped_password', '$date_joined', $last_login_sql, $follower_num, '$escaped_subscription_type', '$escaped_top_genre', $num_songs_liked, $escaped_most_played_artist, '$escaped_user_image_url');\n";
    $user_ids_data[$i] = ['country_id' => $country_id, 'username' => $username, 'image_url' => $user_image_url];
}
$sql_output .= "\n";
echo NUM_USERS . " kullanıcı oluşturuldu.\n";

// --- ALBUMS ---
$sql_output .= "-- ALBUMS --\n";
$album_titles_file = $data_path . 'input_album_titles.txt';
$album_image_urls_file = $data_path . 'input_image_urls_album.txt';
$album_id_counter = 1;
$total_albums_created = 0;

if (empty($artist_ids_data)) {
     echo "Uyarı: Sanatçı bulunmadığı için albüm oluşturulamıyor.\n";
} else {
    foreach ($artist_ids_data as $artist_id => $artist_data) {
        $num_albums_for_this_artist = mt_rand(max(0, NUM_ALBUMS_PER_ARTIST_AVG -1), NUM_ALBUMS_PER_ARTIST_AVG + 1);
        for ($j = 0; $j < $num_albums_for_this_artist; $j++) {
            $album_title_part1 = getRandomLineFromFile($album_titles_file);
            $album_title_part2 = getRandomLineFromFile($album_titles_file);
            $album_name = ($album_title_part1 ? $album_title_part1 : "Untitled") . " " . ($album_title_part2 ? $album_title_part2 : "Collection");
            if(strlen($album_name) > 150) $album_name = substr($album_name, 0, 150);

            $release_date = getRandomDate('2005-01-01', '2024-05-01');
            $genre = getRandomLineFromFile($genres_file);
            if ($genre === false) $genre = 'Various';
            
            $album_image_url = getRandomLineFromFile($album_image_urls_file);
            if ($album_image_url === false) {
                $album_image_url = $artist_data['image_url'] ?? "assets/images/default_album.png";
            }

            $escaped_album_name = addslashes(trim($album_name));
            $escaped_genre_album = addslashes($genre);
            $escaped_album_image_url = addslashes($album_image_url);

            $sql_output .= "INSERT INTO ALBUMS (album_id, artist_id, name_title, release_date, genre, music_number, image) VALUES ($album_id_counter, $artist_id, '$escaped_album_name', '$release_date', '$escaped_genre_album', 0, '$escaped_album_image_url');\n"; 
            $album_ids_data[$album_id_counter] = [
                'artist_id' => $artist_id, 
                'image' => $album_image_url, 
                'song_count' => 0,
                'genre' => $genre, 
                'release_date' => $release_date 
            ];
            $album_id_counter++;
            $total_albums_created++;
        }
    }
}
while ($total_albums_created < MIN_ALBUMS_TOTAL && !empty($artist_ids_data)) {
    $random_artist_id = array_rand($artist_ids_data);
    $artist_data = $artist_ids_data[$random_artist_id];
    $album_title_part1 = getRandomLineFromFile($album_titles_file);
    $album_title_part2 = getRandomLineFromFile($album_titles_file);
    $album_name = ($album_title_part1 ? $album_title_part1 : "Extra") . " " . ($album_title_part2 ? $album_title_part2 : "Hits");
    if(strlen($album_name) > 150) $album_name = substr($album_name, 0, 150);
    $release_date = getRandomDate('2005-01-01', '2024-05-01');
    $genre = getRandomLineFromFile($genres_file);
    if ($genre === false) $genre = 'Various';
    $album_image_url = getRandomLineFromFile($album_image_urls_file);
    if ($album_image_url === false) $album_image_url = $artist_data['image_url'] ?? "assets/images/default_album.png";
    
    $escaped_album_name = addslashes(trim($album_name));
    $escaped_genre_album = addslashes($genre);
    $escaped_album_image_url = addslashes($album_image_url);

    $sql_output .= "INSERT INTO ALBUMS (album_id, artist_id, name_title, release_date, genre, music_number, image) VALUES ($album_id_counter, $random_artist_id, '$escaped_album_name', '$release_date', '$escaped_genre_album', 0, '$escaped_album_image_url');\n";
    $album_ids_data[$album_id_counter] = ['artist_id' => $random_artist_id, 'image' => $album_image_url, 'song_count' => 0, 'genre' => $genre, 'release_date' => $release_date];
    $album_id_counter++;
    $total_albums_created++;
}
$sql_output .= "\n";
echo "$total_albums_created albüm oluşturuldu.\n";

// --- SONGS ---
$sql_output .= "-- SONGS --\n";
$song_titles_file = $data_path . 'input_song_titles.txt';
$song_id_counter = 1;
$total_songs_created = 0;

if (empty($album_ids_data)) {
    echo "Uyarı: Hiç albüm oluşturulmadığı için şarkı oluşturulamıyor.\n";
} else {
    foreach ($album_ids_data as $album_id => &$album_data_ref) { 
        $num_songs_for_this_album = mt_rand(max(1, NUM_SONGS_PER_ALBUM_AVG - 5), NUM_SONGS_PER_ALBUM_AVG + 5);
        for ($k = 0; $k < $num_songs_for_this_album; $k++) {
            $song_title_part1 = getRandomLineFromFile($song_titles_file);
            $song_title_part2 = getRandomLineFromFile($song_titles_file);
            $song_title = ($song_title_part1 ? $song_title_part1 : "Track") . " " . ($song_title_part2 ? $song_title_part2 : ($k+1));
            if(strlen($song_title) > 180) $song_title = substr($song_title, 0, 180);

            $duration = getRandomDuration();
            $genre_song = getRandomLineFromFile($genres_file);
            if ($genre_song === false) $genre_song = $album_data_ref['genre'] ?? 'Various'; 
            $release_date_song = getRandomDate($album_data_ref['release_date'] ?? '2000-01-01', '2024-05-20');
            $rank = mt_rand(1, 100);
            $song_image_url = $album_data_ref['image']; 

            $escaped_song_title = addslashes(trim($song_title));
            $escaped_genre_song = addslashes($genre_song);
            $escaped_song_image_url = addslashes($song_image_url);

            $sql_output .= "INSERT INTO SONGS (song_id, album_id, title, duration, genre, release_date, `rank`, image) VALUES ($song_id_counter, $album_id, '$escaped_song_title', '$duration', '$escaped_genre_song', '$release_date_song', $rank, '$escaped_song_image_url');\n";
            $song_ids[] = $song_id_counter;
            $album_data_ref['song_count']++; 
            $song_id_counter++;
            $total_songs_created++;

            if ($total_songs_created >= MIN_SONGS_TOTAL * 1.5) break; 
        }
        if ($total_songs_created >= MIN_SONGS_TOTAL * 1.5) break;
    }
    unset($album_data_ref); 

    foreach($album_ids_data as $alb_id => $alb_data) {
        if ($alb_data['song_count'] > 0) {
            $sql_output .= "UPDATE ALBUMS SET music_number = " . $alb_data['song_count'] . " WHERE album_id = " . $alb_id . ";\n";
        }
    }
}
while ($total_songs_created < MIN_SONGS_TOTAL && !empty($album_ids_data)) {
    $random_album_id = array_rand($album_ids_data);
    $song_title_part1 = getRandomLineFromFile($song_titles_file);
    $song_title_part2 = getRandomLineFromFile($song_titles_file);
    $song_title = ($song_title_part1 ? $song_title_part1 : "Bonus Track") . " " . ($song_title_part2 ? $song_title_part2 : "");
    if(strlen($song_title) > 180) $song_title = substr($song_title, 0, 180);
    $duration = getRandomDuration();
    $genre_song = getRandomLineFromFile($genres_file);
    if ($genre_song === false) $genre_song = $album_ids_data[$random_album_id]['genre'] ?? 'Various';
    $release_date_song = getRandomDate($album_ids_data[$random_album_id]['release_date'] ?? '2000-01-01', '2024-05-20');
    $rank = mt_rand(1, 100);
    $song_image_url = $album_ids_data[$random_album_id]['image'];

    $escaped_song_title = addslashes(trim($song_title));
    $escaped_genre_song = addslashes($genre_song);
    $escaped_song_image_url = addslashes($song_image_url);

    $sql_output .= "INSERT INTO SONGS (song_id, album_id, title, duration, genre, release_date, `rank`, image) VALUES ($song_id_counter, $random_album_id, '$escaped_song_title', '$duration', '$escaped_genre_song', '$release_date_song', $rank, '$escaped_song_image_url');\n";
    $song_ids[] = $song_id_counter;
    if(isset($album_ids_data[$random_album_id]['song_count'])) {
        $album_ids_data[$random_album_id]['song_count']++;
        $sql_output .= "UPDATE ALBUMS SET music_number = " . $album_ids_data[$random_album_id]['song_count'] . " WHERE album_id = " . $random_album_id . ";\n";
    }
    $song_id_counter++;
    $total_songs_created++;
}
$sql_output .= "\n";
echo "$total_songs_created şarkı oluşturuldu.\n";

// --- PLAYLISTS ---
$sql_output .= "-- PLAYLISTS --\n";
$playlist_id_counter = 1;
$total_playlists_created = 0;
$playlist_titles_file = $data_path . 'input_playlist_titles.txt';
$playlist_image_urls_file = $data_path . 'input_image_urls_playlist.txt'; 

if (empty($user_ids_data)) {
    echo "Uyarı: Kullanıcı yok, çalma listesi oluşturulamıyor.\n";
} else {
    foreach ($user_ids_data as $user_id => $user_data) {
        $num_playlists_for_this_user = mt_rand(max(0, NUM_PLAYLISTS_PER_USER_AVG - 2) , NUM_PLAYLISTS_PER_USER_AVG + 2);
        for ($p = 0; $p < $num_playlists_for_this_user; $p++) {
            $playlist_title = getRandomLineFromFile($playlist_titles_file);
            if ($playlist_title === false) $playlist_title = $user_data['username'] . "'s Mix";
            $playlist_title .= " #" . mt_rand(1,100);
            
            $description = "Bu, " . ($user_data['username'] ?? 'bir kullanıcı') . " tarafından oluşturulan özel bir çalma listesi.";
            $date_created_playlist = getRandomDate('2020-01-01', '2024-05-20');
            
            $playlist_image_url = getRandomLineFromFile($playlist_image_urls_file);
            if ($playlist_image_url === false) {
                $playlist_image_url = "assets/images/default_playlist.png"; 
            }

            $escaped_playlist_title = addslashes($playlist_title);
            $escaped_desc = addslashes($description);
            $escaped_playlist_image_url = addslashes($playlist_image_url);

            $sql_output .= "INSERT INTO PLAYLISTS (playlist_id, user_id, title, description, date_created, image) VALUES ($playlist_id_counter, $user_id, '$escaped_playlist_title', '$escaped_desc', '$date_created_playlist', '$escaped_playlist_image_url');\n";
            $playlist_ids_data[$playlist_id_counter] = ['user_id' => $user_id];
            $playlist_id_counter++;
            $total_playlists_created++;
            if ($total_playlists_created >= MIN_PLAYLISTS_TOTAL * 1.2) break;
        }
        if ($total_playlists_created >= MIN_PLAYLISTS_TOTAL * 1.2) break;
    }
}
while($total_playlists_created < MIN_PLAYLISTS_TOTAL && !empty($user_ids_data)) {
    $random_user_id = array_rand($user_ids_data);
    $user_data = $user_ids_data[$random_user_id];
    $playlist_title = getRandomLineFromFile($playlist_titles_file);
    if ($playlist_title === false) $playlist_title = ($user_data['username'] ?? 'Ekstra') . " Favoriler";
    $playlist_title .= " #" . mt_rand(101,200);
    $description = "Ekstra çalma listesi.";
    $date_created_playlist = getRandomDate('2020-01-01', '2024-05-20');
    $playlist_image_url = getRandomLineFromFile($playlist_image_urls_file);
    if ($playlist_image_url === false) $playlist_image_url = "assets/images/default_playlist.png";

    $escaped_playlist_title = addslashes($playlist_title);
    $escaped_desc = addslashes($description);
    $escaped_playlist_image_url = addslashes($playlist_image_url);
    $sql_output .= "INSERT INTO PLAYLISTS (playlist_id, user_id, title, description, date_created, image) VALUES ($playlist_id_counter, $random_user_id, '$escaped_playlist_title', '$escaped_desc', '$date_created_playlist', '$escaped_playlist_image_url');\n";
    $playlist_ids_data[$playlist_id_counter] = ['user_id' => $random_user_id];
    $playlist_id_counter++;
    $total_playlists_created++;
}
echo "$total_playlists_created çalma listesi oluşturuldu.\n";

// --- PLAYLIST_SONGS ---
$sql_output .= "-- PLAYLIST_SONGS --\n";
$playlistsong_id_counter = 1;
$total_playlist_songs_created = 0;

if (empty($playlist_ids_data) || empty($song_ids)) {
    echo "Uyarı: Çalma listesi veya şarkı bulunmadığı için PLAYLIST_SONGS doldurulamıyor.\n";
} else {
    foreach ($playlist_ids_data as $playlist_id => $playlist_data) {
        $num_songs_for_this_playlist = mt_rand(max(1, NUM_SONGS_PER_PLAYLIST_AVG - 10), NUM_SONGS_PER_PLAYLIST_AVG + 10);
        $added_songs_to_this_playlist = []; 
        for ($ps = 0; $ps < $num_songs_for_this_playlist; $ps++) {
            if (empty($song_ids)) break;
            $attempts = 0; // Sonsuz döngüyü engellemek için
            do {
                $random_song_id = $song_ids[array_rand($song_ids)];
                $attempts++;
            } while (isset($added_songs_to_this_playlist[$random_song_id]) && $attempts < count($song_ids) * 2);

            if (isset($added_songs_to_this_playlist[$random_song_id])) continue;


            $date_added = getRandomDate('2021-01-01', '2024-05-20') . ' ' . sprintf('%02d:%02d:%02d', mt_rand(0,23), mt_rand(0,59), mt_rand(0,59));
            $sql_output .= "INSERT INTO PLAYLIST_SONGS (playlistsong_id, playlist_id, song_id, date_added) VALUES ($playlistsong_id_counter, $playlist_id, $random_song_id, '$date_added');\n";
            
            $added_songs_to_this_playlist[$random_song_id] = true;
            $playlistsong_id_counter++;
            $total_playlist_songs_created++;
            if ($total_playlist_songs_created >= MIN_PLAYLIST_SONGS_TOTAL * 1.2) break;
        }
        if ($total_playlist_songs_created >= MIN_PLAYLIST_SONGS_TOTAL * 1.2) break;
    }
}
while ($total_playlist_songs_created < MIN_PLAYLIST_SONGS_TOTAL && !empty($playlist_ids_data) && !empty($song_ids)) {
    $random_playlist_id = array_rand($playlist_ids_data);
    $random_song_id = $song_ids[array_rand($song_ids)];
    $date_added = getRandomDate('2021-01-01', '2024-05-20') . ' ' . sprintf('%02d:%02d:%02d', mt_rand(0,23), mt_rand(0,59), mt_rand(0,59));
    $sql_output .= "INSERT IGNORE INTO PLAYLIST_SONGS (playlistsong_id, playlist_id, song_id, date_added) VALUES ($playlistsong_id_counter, $random_playlist_id, $random_song_id, '$date_added');\n";
    $playlistsong_id_counter++; 
    $total_playlist_songs_created++; 
    if ($total_playlist_songs_created > MIN_PLAYLIST_SONGS_TOTAL * 1.5) break;
}
echo "$total_playlist_songs_created çalma listesi-şarkı ilişkisi denemesi oluşturuldu.\n";

// --- PLAY_HISTORY ---
$sql_output .= "-- PLAY_HISTORY --\n";
$play_id_counter = 1;
$total_play_history_created = 0;

if (empty($user_ids_data) || empty($song_ids)) {
    echo "Uyarı: Kullanıcı veya şarkı bulunmadığı için PLAY_HISTORY oluşturulamıyor.\n";
} else {
    foreach($user_ids_data as $user_id => $user_data) {
        $num_history_for_user = mt_rand(max(0, NUM_PLAY_HISTORY_PER_USER_AVG -5), NUM_PLAY_HISTORY_PER_USER_AVG + 10);
        for ($h = 0; $h < $num_history_for_user; $h++) {
            if (empty($song_ids)) break;
            $random_song_id_hist = $song_ids[array_rand($song_ids)];
            $playtime = getRandomDate('2022-01-01', '2024-05-20') . ' ' . sprintf('%02d:%02d:%02d', mt_rand(0,23), mt_rand(0,59), mt_rand(0,59));

            $sql_output .= "INSERT INTO PLAY_HISTORY (play_id, user_id, song_id, playtime) VALUES ($play_id_counter, $user_id, $random_song_id_hist, '$playtime');\n";
            $play_id_counter++;
            $total_play_history_created++;
             if ($total_play_history_created >= MIN_PLAY_HISTORY_TOTAL * 1.5) break;
        }
        if ($total_play_history_created >= MIN_PLAY_HISTORY_TOTAL * 1.5) break;
    }
}
while($total_play_history_created < MIN_PLAY_HISTORY_TOTAL && !empty($user_ids_data) && !empty($song_ids)) {
    $random_user_id_hist = array_rand($user_ids_data);
    $random_song_id_hist_fill = $song_ids[array_rand($song_ids)];
    $playtime = getRandomDate('2022-01-01', '2024-05-20') . ' ' . sprintf('%02d:%02d:%02d', mt_rand(0,23), mt_rand(0,59), mt_rand(0,59));
    $sql_output .= "INSERT INTO PLAY_HISTORY (play_id, user_id, song_id, playtime) VALUES ($play_id_counter, $random_user_id_hist, $random_song_id_hist_fill, '$playtime');\n";
    $play_id_counter++;
    $total_play_history_created++;
}
echo "$total_play_history_created dinleme geçmişi kaydı oluşturuldu.\n";

// --- SQL Dosyasına Yaz ---
if (!is_dir(dirname($output_sql_file))) {
    mkdir(dirname($output_sql_file), 0777, true);
}

if (file_put_contents($output_sql_file, $sql_output)) {
    echo "\nBaşarılı! Veriler '$output_sql_file' dosyasına yazıldı.\n";
    echo "Bu dosyayı phpMyAdmin veya benzeri bir araçla veritabanınıza import edebilirsiniz.\n";
} else {
    echo "\nHata! Veriler '$output_sql_file' dosyasına yazılamadı. Klasör izinlerini kontrol edin.\n";
}

echo "Veri üretme işlemi tamamlandı.\n";
?>