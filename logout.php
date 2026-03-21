<?php
session_start();  // Ensure session is started

// Unset all session variables
$_SESSION = [];

// Destroy session
session_destroy();

// Invalidate session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Prevent session fixation
session_regenerate_id(true);

// Redirect to login
header('Location: login.php');
exit;

