<?php
session_start();

// Buang semua session data
$_SESSION = [];

// Destroy session sepenuhnya
session_destroy();

// Optional: delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Redirect ke login atau homepage
header("Location: login");
exit;
?>