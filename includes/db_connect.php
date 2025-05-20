<?php
$servername = "localhost";
$username = "root";
$password = "mysql"; // Kendi MySQL şifren
$dbname = "yusuf_ozdil"; // install.php'de oluşturduğun veritabanı adı

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>