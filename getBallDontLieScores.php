<?php
// getBallDontLieScores.php
//
// BallDontLie-based score updater
// Reads API settings from config.php

require('includes/application_top.php');

/************************
 * SAFETY CHECKS
 ************************/

if (!defined('BALLDONTLIE_API_ENABLED') || !BALLDONTLIE_API_ENABLED) {
    echo json_encode(['error' => 'BallDontLie API disabled']);
    exit;
}

if (empty(BALLDONTLIE_API_KEY)) {
    echo json_encode(['error' => 'BallDontLie API key missing']);
    exit;
}

/************************
 * INPUT
 ************************/

$week = isset($_GET['week']) ? (int)$_GET['week'] : 0;
if ($week < 1 || $week > NFL_TOTAL_WEEKS) {
    echo json_encode(['error' => 'Invalid week']);
    exit;
}

/************************
 * FETCH DATA
 ************************/

$query = http_build_query([
    'seasons[]' => SEASON_YEAR,
    'weeks[]'   => $week,
    'per_page'  => 100
]);

$url = rtrim(BALLDONTLIE_API_BASE, '/') . '/games?' . $query;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . BALLDONTLIE_API_KEY,
        'Accept: application/json'
    ],
    CURLOPT_TIMEOUT        => 15
]);

$json = curl_exec($ch);
if ($json === false) {
    echo json_encode(['error' => 'Failed to fetch BallDontLie data']);
    exit;
}
curl_close($ch);

$data = json_decode($json, true);
if (!$data || empty($data['data'])) {
    echo json_encode([]);
    exit;
}

/************************
 * PROCESS SCORES
 ************************/

$response = [];

foreach ($data['data'] as $game) {

    // Only finished games
    if (!in_array($game['status'], ['Final', 'Final/OT'], true)) {
        continue;
    }

    $homeAbbr  = $game['home_team']['abbreviation'];
    $awayAbbr  = $game['visitor_team']['abbreviation'];
    $homeScore = (int)$game['home_team_score'];
    $awayScore = (int)$game['visitor_team_score'];
    $overtime  = (stripos($game['status'], 'OT') !== false) ? 1 : 0;

    $sql = "
        SELECT gameID
        FROM " . DB_PREFIX . "schedule
        WHERE weekNum = ?
          AND (
                (homeID = ? AND visitorID = ?)
             OR (homeID = ? AND visitorID = ?)
          )
        LIMIT 1
    ";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        continue;
    }

    $stmt->bind_param(
        'issss',
        $week,
        $homeAbbr,
        $awayAbbr,
        $awayAbbr,
        $homeAbbr
    );

    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $response[] = [
            'gameID'       => (int)$row['gameID'],
            'homeScore'    => $homeScore,
            'visitorScore' => $awayScore,
            'overtime'     => $overtime
        ];
    }

    $stmt->close();
}

/************************
 * OUTPUT
 ************************/

header('Content-Type: application/json');
echo json_encode($response);

