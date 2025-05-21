<?php
session_start();

require_once('includes/db_connect.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password_input = trim($_POST['password']);

    if (empty($username) || empty($password_input)) {
        header("Location: login.html?error=Kullanıcı adı ve şifre boş bırakılamaz.");
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM USERS WHERE username = ?");
    if ($stmt === false) {
        error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        header("Location: login.html?error=Sistem hatası, lütfen daha sonra tekrar deneyin. (Prep)");
        exit;
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($password_input === $user['password']) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_image'] = $user['image'];

            $update_stmt = $conn->prepare("UPDATE USERS SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?");
            if($update_stmt) {
                $update_stmt->bind_param("i", $user['user_id']);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                error_log("Update last_login failed: (" . $conn->errno . ") " . $conn->error);
            }
            header("Location: homepage.php");
            exit;
        } else {
            header("Location: login.html?error=Kullanıcı adı veya şifre yanlış.");
            exit;
        }
    } else {
        header("Location: login.html?error=Kullanıcı adı veya şifre yanlış.");
        exit;
    }

    $stmt->close();
    $conn->close();

} else {
    header("Location: login.html");
    exit;
}
?>