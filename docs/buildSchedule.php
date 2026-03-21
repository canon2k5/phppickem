<?php
/**
 * buildSchedule.php
 * NFL Schedule Builder using BallDontLie API
 * Standalone / portable / no site dependencies
 */

error_reporting(E_ALL);
set_time_limit(0);

/* -------------------------
 * CLI argument check
 * ------------------------- */
if ($argc !== 2) {
    echo "Usage: php buildSchedule.php <season_year>\n";
    exit(1);
}

define('SEASON_YEAR', (int)$argv[1]);

/* -------------------------
 * API key — loaded from /home/secure/env.php
 * ------------------------- */
$envFile = '/home/secure/env.php';
if (!file_exists($envFile)) {
    die("Error: /home/secure/env.php not found. Cannot load API key.\n");
}
require $envFile;
if (!defined('BALLDONTLIE_API_KEY') || empty(BALLDONTLIE_API_KEY)) {
    die("Error: BALLDONTLIE_API_KEY is not defined in /home/secure/env.php.\n");
}

/* -------------------------
 * Settings
 * ------------------------- */
$baseUrl  = 'https://api.balldontlie.io/nfl/v1/games';
$perPage  = 100; // max allowed
$cursor   = null;
$schedule = [];

/* -------------------------
 * Timezone handling
 * ------------------------- */
$utcTz = new DateTimeZone('UTC');
$estTz = new DateTimeZone('America/New_York');

/* -------------------------
 * cURL helper
 * ------------------------- */
function api_get(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . BALLDONTLIE_API_KEY
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_USERAGENT => 'PHPPickem/1.0'
    ]);

    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        die("cURL error: $err\n");
    }

    curl_close($ch);

    $decoded = json_decode($resp, true);
    if (!is_array($decoded)) {
        die("Invalid JSON response\n");
    }

    return $decoded;
}

/* -------------------------
 * Fetch schedule (paginated)
 * ------------------------- */
do {
    $query = [
        'seasons[]=' . SEASON_YEAR,
        'per_page=' . $perPage
    ];

    if ($cursor !== null) {
        $query[] = 'cursor=' . $cursor;
    }

    $url = $baseUrl . '?' . implode('&', $query);
    $data = api_get($url);

    if (!isset($data['data'])) {
        break;
    }

    foreach ($data['data'] as $game) {

        // Skip postseason if present
        if (!empty($game['postseason'])) {
            continue;
        }

        // Convert UTC → Eastern
        $dt = new DateTime($game['date'], $utcTz);
        $dt->setTimezone($estTz);

        $schedule[] = [
            'weekNum'         => (int)$game['week'],
            'gameTimeEastern' => $dt->format('Y-m-d H:i:s'),
            'homeID'          => $game['home_team']['abbreviation'],
            'visitorID'       => $game['visitor_team']['abbreviation']
        ];
    }

    $cursor = $data['meta']['next_cursor'] ?? null;

} while ($cursor);

/* -------------------------
 * SQL output
 * ------------------------- */
date_default_timezone_set('America/New_York');

$output = "-- buildSchedule.php Dump
-- https://it.megocollector.com
-- Generated: " . date('M d, Y h:i A') . "

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;

DROP TABLE IF EXISTS `nflp_schedule`;
CREATE TABLE `nflp_schedule` (
  `gameID` int(11) NOT NULL,
  `weekNum` int(11) NOT NULL,
  `gameTimeEastern` datetime DEFAULT NULL,
  `homeID` varchar(10) NOT NULL,
  `homeScore` int(11) DEFAULT NULL,
  `visitorID` varchar(10) NOT NULL,
  `visitorScore` int(11) DEFAULT NULL,
  `overtime` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `nflp_schedule`
(`gameID`,`weekNum`,`gameTimeEastern`,`homeID`,`homeScore`,`visitorID`,`visitorScore`,`overtime`)
VALUES
";

foreach ($schedule as $i => $game) {
    $output .= sprintf(
        "(%d,%d,'%s','%s',NULL,'%s',NULL,0)%s\n",
        $i + 1,
        $game['weekNum'],
        $game['gameTimeEastern'],
        $game['homeID'],
        $game['visitorID'],
        ($i + 1 < count($schedule)) ? "," : ";"
    );
}

$output .= "
ALTER TABLE `nflp_schedule`
  ADD PRIMARY KEY (`gameID`),
  ADD KEY `weekNum` (`weekNum`),
  ADD KEY `HomeID` (`homeID`),
  ADD KEY `VisitorID` (`visitorID`);

ALTER TABLE `nflp_schedule`
  MODIFY `gameID` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT=" . (count($schedule) + 1) . ";

COMMIT;
";

echo $output;
file_put_contents("nfl_schedule_" . SEASON_YEAR . ".sql", $output);
exit;
