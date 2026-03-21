<?php
// admin_password_reset_cli.php — CLI-only admin password reset without emitting headers
// Usage:
//   php admin_password_reset_cli.php                 # prompts for new password for 'admin'
//   php admin_password_reset_cli.php admin NewPa$$   # sets directly
//
// This script avoids including application_top.php to prevent header() warnings.
// It reads DB constants from includes/config.php and connects with mysqli.

if (PHP_SAPI !== 'cli') {
  http_response_code(403);
  exit("Run this script from the command line only.\n");
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = dirname(__DIR__);
$configPath = $root . '/includes/config.php';
if (!file_exists($configPath)) {
  fwrite(STDERR, "Cannot find includes/config.php\n");
  exit(1);
}
require_once $configPath;

// Resolve DB constants across common names
$host = defined('DB_SERVER') ? DB_SERVER : (defined('DB_HOST') ? DB_HOST : 'localhost');
$user = defined('DB_SERVER_USERNAME') ? DB_SERVER_USERNAME : (defined('DB_USERNAME') ? DB_USERNAME : (defined('DB_USER') ? DB_USER : ''));
$pass = defined('DB_SERVER_PASSWORD') ? DB_SERVER_PASSWORD : (defined('DB_PASSWORD') ? DB_PASSWORD : '');
$name = defined('DB_DATABASE') ? DB_DATABASE : (defined('DB_NAME') ? DB_NAME : '');
$pref = defined('DB_PREFIX') ? DB_PREFIX : 'nflp_';

$mysqli = @new mysqli($host, $user, $pass, $name);
if ($mysqli->connect_errno) {
  fwrite(STDERR, "DB connect failed: {$mysqli->connect_error}\n");
  exit(2);
}
$mysqli->set_charset('utf8mb4');

// Parse args
$userName = $argv[1] ?? 'admin';
$newPassword = $argv[2] ?? null;
if ($newPassword === null || $newPassword === '') {
  echo "New password for {$userName}: ";
  $newPassword = trim(fgets(STDIN));
}
if ($newPassword === '') {
  fwrite(STDERR, "Empty password not allowed.\n");
  exit(3);
}

// Ensure password column can hold bcrypt hashes
$check = $mysqli->query("SHOW COLUMNS FROM `{$pref}users` LIKE 'password'");
if ($check && $check->num_rows === 1) {
  $col = $check->fetch_assoc();
  if (preg_match('/varchar\((\d+)\)/i', $col['Type'], $m)) {
    $len = (int)$m[1];
    if ($len < 60) {
      $mysqli->query("ALTER TABLE `{$pref}users` MODIFY `password` VARCHAR(255) NOT NULL");
    }
  }
}

// Update
$hash = password_hash($newPassword, PASSWORD_BCRYPT);
$sql  = "UPDATE `{$pref}users` SET `password`=?, `salt`='' WHERE `userName`=?";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
  fwrite(STDERR, "Prepare failed: {$mysqli->error}\n");
  exit(4);
}
$stmt->bind_param('ss', $hash, $userName);
$stmt->execute();

if ($stmt->affected_rows > 0) {
  echo "Password updated for {$userName}.\n";
} else {
  // Could be same password hash or user missing
  $exists = $mysqli->prepare("SELECT 1 FROM `{$pref}users` WHERE `userName`=? LIMIT 1");
  $exists->bind_param('s', $userName);
  $exists->execute();
  $exists->store_result();
  if ($exists->num_rows === 1) {
    echo "No rows changed (user exists; hash may be identical).\n";
  } else {
    echo "No rows updated — user '{$userName}' not found.\n";
  }
  $exists->close();
}
$stmt->close();
$mysqli->close();
