<?php
// application_top.php -- included first on all pages

// Enable debugging if DEBUG_MODE is true.
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    // Optionally, log errors to a debug log file
    ini_set('log_errors', 1);
    ini_set('error_log', '/home/secure/nfl-pickem-debug.log');
} else {
    error_reporting(0);
}

// Harden session cookie before starting session
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require('includes/config.php');
require('includes/functions.php');
require('includes/classes/crypto.php');
// require('includes/classes/class.phpmailer.php');

// Initialize Crypto Class
// $crypto = new phpFreaksCrypto();
$crypto = new OpenSSLCrypto();

// Establish a secure MySQL connection
$mysqli = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

if ($mysqli->connect_error) {
    error_log('Database connection error: ' . $mysqli->connect_error);
    http_response_code(503);
    exit('Service temporarily unavailable. Please try again later.');
}

$mysqli->set_charset('utf8mb4');

// Security check: Ensure the install folder is removed after installation
if (is_dir('install')) {
    $sql = "SELECT 1 FROM " . DB_PREFIX . "teams LIMIT 1"; // Check if tables exist
    if ($mysqli->query($sql) === false) {
        header('Location: ./install/');
        exit;
    } else {
        $warnings[] = 'For security, please delete or rename the install folder.';
    }
}

// Initialize Login Class
require('includes/classes/login.php');
$login = new Login();

// Retrieve the logged-in user
$adminUser = $login->get_user('admin');

// Allow only specific pages to be accessed without login
$publicPages = ['login.php', 'signup.php', 'password_reset.php'];
$currentFile = basename($_SERVER['PHP_SELF']);

if (!in_array($currentFile, $publicPages) && (empty($_SESSION['logged']) || $_SESSION['logged'] !== 'yes')) {
    header('Location: login.php');
    exit;
}

// Fetch user details if logged in
if (!empty($_SESSION['loggedInUser'])) {
    $user = $login->get_user($_SESSION['loggedInUser']);
}

// Determine if the current user is an admin
$isAdmin = ($_SESSION['loggedInUser'] === 'admin' && $_SESSION['logged'] === 'yes');

// If not an admin, fetch current game week details
if (!$isAdmin) {
    $currentWeek = getCurrentWeek(); 
    $cutoffDateTime = getCutoffDateTime($currentWeek);
    $firstGameTime = getFirstGameTime($currentWeek);

    $firstGameExpired = (time() + (SERVER_TIMEZONE_OFFSET * 3600)) > strtotime($firstGameTime);
    $weekExpired = (time() + (SERVER_TIMEZONE_OFFSET * 3600)) > strtotime($cutoffDateTime);
}
?>
