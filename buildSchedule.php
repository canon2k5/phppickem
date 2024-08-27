<?php

// Error reporting and script settings
error_reporting(E_ALL);
set_time_limit(0);

// Define constants
define('SEASON_YEAR', '2024');

$weeks = 18;
$schedule = [];

// Function to get contents using cURL
function curl_get_contents($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// Function to get ESPN team IDs
function getESPNTeamIDS() {
    $teamArray = [];
    $url = "https://site.api.espn.com/apis/site/v2/sports/football/nfl/teams";
    $json = curl_get_contents($url);

    if ($json === false) {
        die('Error getting team IDs from espn.com.');
    }

    $decoded = json_decode($json, true);
    if (isset($decoded['sports'])) {
        foreach ($decoded['sports'] as $sports) {
            foreach ($sports['leagues'] as $leagues) {
                foreach ($leagues['teams'] as $teams) {
                    $teamArray[$teams['team']['id']] = $teams['team']['abbreviation'];
                }
            }
        }
    } else {
        die('Error decoding team IDs JSON.');
    }

    return $teamArray;
}

$teamArray = getESPNTeamIDS();

// Mapping ESPN codes to database codes
$mismatchTeams = [
    'WSH' => 'WAS',
    'LAR' => 'LA',
];

for ($week = 1; $week <= $weeks; $week++) {
    $url = "https://sports.core.api.espn.com/v2/sports/football/leagues/nfl/seasons/" . SEASON_YEAR . "/types/2/weeks/" . $week . "/events";
    $json = curl_get_contents($url);

    if ($json === false) {
        die('Error getting schedule from espn.com.');
    }

    $gameURL = json_decode($json, true);

    foreach ($gameURL['items'] as $espnGameURL) {
        $gameJSON = curl_get_contents($espnGameURL['$ref']);

        if ($gameJSON === false) {
            die('Error getting game details from espn.com.');
        }

        $gameArray = json_decode($gameJSON, true);
        $game = $gameArray['competitions'][0];

        // Get game time (Eastern)
        $date = $game['date'];
        $gameTimeEastern = date('Y-m-d H:i:s', strtotime($date));

        // Determine home and away teams
        $home_team = null;
        $away_team = null;

        foreach ($game['competitors'] as $competitor) {
            $teamID = $competitor['id'];
            $teamAbbreviation = $teamArray[$teamID] ?? null;

            if ($competitor['homeAway'] == 'home') {
                $home_team = $mismatchTeams[$teamAbbreviation] ?? $teamAbbreviation;
            } else {
                $away_team = $mismatchTeams[$teamAbbreviation] ?? $teamAbbreviation;
            }
        }

        // Add to schedule array
        if ($home_team && $away_team) {
            $schedule[] = [
                'weekNum' => $week,
                'gameTimeEastern' => $gameTimeEastern,
                'homeID' => $home_team,
                'visitorID' => $away_team,
            ];
        }
    }
}

$output = 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

DROP TABLE IF EXISTS `nflp_schedule`;
CREATE TABLE IF NOT EXISTS `nflp_schedule` (
  `gameID` int(11) NOT NULL AUTO_INCREMENT,
  `weekNum` int(11) NOT NULL,
  `gameTimeEastern` datetime DEFAULT NULL,
  `homeID` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `homeScore` int(11) DEFAULT NULL,
  `visitorID` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `visitorScore` int(11) DEFAULT NULL,
  `overtime` tinyint(1) NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`gameID`),
  KEY `GameID` (`gameID`),
  KEY `HomeID` (`homeID`),
  KEY `VisitorID` (`visitorID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=273;

INSERT INTO `nflp_schedule` (`gameID`, `weekNum`, `gameTimeEastern`, `homeID`, `homeScore`, `visitorID`, `visitorScore`, `overtime`) VALUES'."\n";

foreach ($schedule as $index => $game) {
    $output .= '(' . ($index+1) . ', ' . $game['weekNum'] . ', \'' . $game['gameTimeEastern'] . '\', \'' . $game['homeID'] . '\', NULL,\'' . $game['visitorID'] . '\', NULL, 0)';
    if ($index < count($schedule) - 1) {
        $output .= ",\n";
    } else {
        $output .= ";\n";
    }
}

$output .= "\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;"
;

// Fix for IE caching or PHP bug issue
//header("Pragma: public");
//header("Expires: 0"); // Set expiration time
//header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

// Output the result
echo $output;

// Optional: Create SQL file for export
file_put_contents("nfl_schedule_" . SEASON_YEAR . ".sql", $output);

// Convert the file format (assuming you have dos2unix installed)
system("dos2unix \"nfl_schedule_" . SEASON_YEAR . ".sql\"");

exit;
