<?php
/**
 * Configuration File
 * Main configuration settings for PHP Pick 'Em
 */

/************************
 * ENVIRONMENT & DEBUG
 ************************/
// Load environment variables (database credentials)
$envPath = '/home/secure/env.php';
if (file_exists($envPath)) {
    require_once $envPath;
    define('ENV_MISSING', false);
} else {
    define('ENV_MISSING', true);
    if (!defined('DB_HOST')) { define('DB_HOST', ''); }
    if (!defined('DB_USER')) { define('DB_USER', ''); }
    if (!defined('DB_PASS')) { define('DB_PASS', ''); }
    if (!defined('DB_NAME')) { define('DB_NAME', ''); }
}

// Debug Settings
define('DEBUG_MODE', false); // Set to true for development
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

/************************
 * DATABASE CONFIGURATION
 ************************/
define('DB_HOSTNAME', DB_HOST);
define('DB_USERNAME', DB_USER);
define('DB_PASSWORD', DB_PASS);
define('DB_DATABASE', DB_NAME);
define('DB_PREFIX', 'nflp_');

/************************
 * SITE CONFIGURATION
 ************************/
define('SITE_URL', 'http://nfl.example.com/');
define('ALLOW_SIGNUP', false);
define('USER_NAMES_DISPLAY', 3);      // Options: 1=Full Name, 2=Username, 3=Username with Full Name tooltip

/************************
 * NFL SEASON SETTINGS
 ************************/
define('APP_NAME', "PHP Pick 'Em");
define('SEASON_YEAR', '2025');
define('NFL_TOTAL_WEEKS', 18);
define('SEASON_TYPE', 2);             // 2=regular season

/************************
 * BALLDONTLIE API CONFIG
 ************************/

// BallDontLie NFL API
define('BALLDONTLIE_API_ENABLED', true);
define('BALLDONTLIE_API_BASE', 'https://api.balldontlie.io/nfl/v1');
//define('BALLDONTLIE_API_KEY', 'PUT_YOUR_API_KEY_HERE');
if (!defined('BALLDONTLIE_API_KEY')) {
    define('BALLDONTLIE_API_KEY', '');
}

/************************
 * TIMEZONE SETTINGS
 ************************/
define('SERVER_TIMEZONE', 'America/New_York');
date_default_timezone_set(SERVER_TIMEZONE);

// Calculate timezone offset dynamically
$dateTimeZoneCurrent = new DateTimeZone(SERVER_TIMEZONE);
$dateTimeZoneEastern = new DateTimeZone("America/New_York");
$dateTimeCurrent = new DateTime("now", $dateTimeZoneCurrent);
$dateTimeEastern = new DateTime("now", $dateTimeZoneEastern);
$offsetCurrent = $dateTimeCurrent->getOffset();
$offsetEastern = $dateTimeEastern->getOffset();
$offsetHours = ($offsetEastern - $offsetCurrent) / 3600;
define('SERVER_TIMEZONE_OFFSET', $offsetHours);

/************************
 * DISPLAY SETTINGS
 ************************/
// Leader Display Configuration
define('DISPLAY_TOP_WINNERS', 3);     // Number of top winners to display
define('DISPLAY_TOP_RATIOS', 5);      // Number of top pick ratios to display

/************************
 * EMAIL SETTINGS
 ************************/
define('SMTP_HOST', 'your.smtp.server.com');
define('SMTP_USER', 'your_username');
define('SMTP_PASS', 'your_password');
define('SMTP_PORT', 587);             // Common ports: 587 (TLS) or 465 (SSL)
define('SMTP_FROM_EMAIL', 'admin@yourdomain.com');
define('SMTP_FROM_NAME', APP_NAME . ' Admin');

/************************
 * PAYPAL CONFIGURATION
 ************************/
$config['paypal'] = [
    'business_email' => 'admin@elvisrecords.com',
    'item_name' => APP_NAME . " Support",
    'item_number' => '2025',
    'currency_code' => 'USD',
    'min_amount' => 1,
    'button_image' => 'https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif',
    'button_text' => 'Donate with PayPal',
    'button_class' => 'btn btn-warning'
];

// Footer Settings
$config['show_donation_button'] = true; // Set to true to enable donation button
$config['custom_footer_text'] = ''; // Empty string by default
// Example of setting custom footer text
//$config['custom_footer_text'] = 'Internal Use Only - Copyright (c) ' . date('Y') . ' Your Company Name';
