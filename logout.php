<?php
require 'config.php';
session_start();

// Clear all session data
session_unset();
session_destroy();

// Expire the cookie immediately on the client side
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 3600,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header("Location: login.php?reason=logout");
exit;