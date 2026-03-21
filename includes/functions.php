<?php
// functions.php
function getCurrentWeek() {
    global $mysqli;

    $sql = "SELECT DISTINCT weekNum FROM " . DB_PREFIX . "schedule
            WHERE DATE_ADD(NOW(), INTERVAL " . SERVER_TIMEZONE_OFFSET . " HOUR) < gameTimeEastern
            ORDER BY weekNum
            LIMIT 1";

    $query = $mysqli->query($sql);

    if ($query && $query->num_rows > 0) {
        $row = $query->fetch_assoc();
        $weekNum = $row['weekNum'];
        $query->free_result();
        return $weekNum;
    }

    if ($query) $query->free_result();

    $sql = "SELECT MAX(weekNum) AS weekNum FROM " . DB_PREFIX . "schedule";
    $query2 = $mysqli->query($sql);

    if ($query2 && $query2->num_rows > 0) {
        $row = $query2->fetch_assoc();
        $weekNum = $row['weekNum'];
        $query2->free_result();
        return $weekNum;
    }

    if ($query2) $query2->free_result();

    return false;
}

function getCutoffDateTime($week) {
    global $mysqli;
    $week = (int)$week;
    $stmt = $mysqli->prepare(
        "SELECT gameTimeEastern FROM " . DB_PREFIX . "schedule
         WHERE weekNum = ? AND DATE_FORMAT(gameTimeEastern, '%W') = 'Sunday'
         ORDER BY gameTimeEastern LIMIT 1"
    );
    if (!$stmt) return false;
    $stmt->bind_param('i', $week);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['gameTimeEastern'] : false;
}

function getFirstGameTime($week) {
    global $mysqli;
    $week = (int)$week;
    $stmt = $mysqli->prepare(
        "SELECT gameTimeEastern FROM " . DB_PREFIX . "schedule
         WHERE weekNum = ? ORDER BY gameTimeEastern LIMIT 1"
    );
    if (!$stmt) return false;
    $stmt->bind_param('i', $week);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['gameTimeEastern'] : false;
}

function getPickID($gameID, $userID) {
    global $mysqli;
    $gameID = (int)$gameID;
    $userID = (int)$userID;

    $stmt = $mysqli->prepare(
        "SELECT pickID FROM " . DB_PREFIX . "picks WHERE gameID = ? AND userID = ?"
    );
    if (!$stmt) return false;
    $stmt->bind_param('ii', $gameID, $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['pickID'] : false;
}

function getGameIDByTeamName($week, $teamName) {
    global $mysqli;
    $week = (int)$week;
    $sql = "SELECT gameID
            FROM " . DB_PREFIX . "schedule s
            INNER JOIN " . DB_PREFIX . "teams t1 ON s.homeID = t1.teamID
            INNER JOIN " . DB_PREFIX . "teams t2 ON s.visitorID = t2.teamID
            WHERE weekNum = ?
              AND ((t1.city = ? OR t1.displayName = ?) OR (t2.city = ? OR t2.displayName = ?))";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param('issss', $week, $teamName, $teamName, $teamName, $teamName);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['gameID'] : false;
}

function getGameIDByTeamID($week, $teamID) {
    global $mysqli;
    $week   = (int)$week;
    $teamID = (int)$teamID;
    $sql = "SELECT gameID
            FROM " . DB_PREFIX . "schedule s
            INNER JOIN " . DB_PREFIX . "teams t1 ON s.homeID = t1.teamID
            INNER JOIN " . DB_PREFIX . "teams t2 ON s.visitorID = t2.teamID
            WHERE weekNum = ?
              AND (t1.teamID = ? OR t2.teamID = ?)";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param('iii', $week, $teamID, $teamID);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['gameID'] : false;
}

function getUserPicks($week, $userID) {
    global $mysqli;
    $week   = (int)$week;
    $userID = (int)$userID;
    $picks  = [];

    $stmt = $mysqli->prepare(
        "SELECT p.gameID, p.pickID, p.points
         FROM " . DB_PREFIX . "picks p
         INNER JOIN " . DB_PREFIX . "schedule s ON p.gameID = s.gameID
         WHERE s.weekNum = ? AND p.userID = ?"
    );
    if (!$stmt) return $picks;
    $stmt->bind_param('ii', $week, $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $picks[$row['gameID']] = ['pickID' => $row['pickID'], 'points' => $row['points']];
    }
    $stmt->close();
    return $picks;
}

function getUserScore($week, $userID) {
    global $mysqli;
    $week   = (int)$week;
    $userID = (int)$userID;
    $score  = 0;

    // Get games and determine winners
    $games = [];
    $stmt = $mysqli->prepare(
        "SELECT gameID, homeID, visitorID, homeScore, visitorScore
         FROM " . DB_PREFIX . "schedule WHERE weekNum = ? ORDER BY gameTimeEastern, gameID"
    );
    if (!$stmt) return 0;
    $stmt->bind_param('i', $week);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $games[$row['gameID']] = $row;
        if ((int)$row['homeScore'] > (int)$row['visitorScore']) {
            $games[$row['gameID']]['winnerID'] = $row['homeID'];
        } elseif ((int)$row['visitorScore'] > (int)$row['homeScore']) {
            $games[$row['gameID']]['winnerID'] = $row['visitorID'];
        } else {
            $games[$row['gameID']]['winnerID'] = null;
        }
    }
    $stmt->close();

    // Score the user's picks
    $stmt2 = $mysqli->prepare(
        "SELECT p.gameID, p.pickID
         FROM " . DB_PREFIX . "picks p
         INNER JOIN " . DB_PREFIX . "schedule s ON p.gameID = s.gameID
         WHERE s.weekNum = ? AND p.userID = ?
         ORDER BY s.gameTimeEastern"
    );
    if (!$stmt2) return 0;
    $stmt2->bind_param('ii', $week, $userID);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) {
        if (!empty($games[$row['gameID']]['winnerID']) &&
            $row['pickID'] == $games[$row['gameID']]['winnerID']) {
            $score++;
        }
    }
    $stmt2->close();

    return $score;
}

function getGameTotal($week) {
    global $mysqli;
    $week = (int)$week;
    $stmt = $mysqli->prepare(
        "SELECT COUNT(gameID) AS gameTotal FROM " . DB_PREFIX . "schedule WHERE weekNum = ?"
    );
    if (!$stmt) return 0;
    $stmt->bind_param('i', $week);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? (int)$row['gameTotal'] : 0;
}

function gameIsLocked($gameID) {
    global $mysqli, $cutoffDateTime;
    $gameID = (int)$gameID;
    $cutoff = $mysqli->real_escape_string($cutoffDateTime);
    $sql = "SELECT (DATE_ADD(NOW(), INTERVAL " . SERVER_TIMEZONE_OFFSET . " HOUR) > gameTimeEastern
             OR DATE_ADD(NOW(), INTERVAL " . SERVER_TIMEZONE_OFFSET . " HOUR) > '" . $cutoff . "') AS expired
            FROM " . DB_PREFIX . "schedule WHERE gameID = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param('i', $gameID);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? (bool)$row['expired'] : false;
}

function hidePicks($userID, $week) {
    global $mysqli;
    $userID = (int)$userID;
    $week   = (int)$week;
    $stmt = $mysqli->prepare(
        "SELECT showPicks FROM " . DB_PREFIX . "picksummary WHERE userID = ? AND weekNum = ?"
    );
    if (!$stmt) return 0;
    $stmt->bind_param('ii', $userID, $week);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? ($row['showPicks'] ? 0 : 1) : 0;
}

function calculateStats() {
    global $mysqli, $weekStats, $playerTotals, $possibleScoreTotal;

    for ($week = 1; $week <= NFL_TOTAL_WEEKS; $week++) {
        $games = [];
        $stmt = $mysqli->prepare(
            "SELECT gameID, homeID, visitorID, homeScore, visitorScore
             FROM " . DB_PREFIX . "schedule WHERE weekNum = ? ORDER BY gameTimeEastern, gameID"
        );
        if (!$stmt) continue;
        $stmt->bind_param('i', $week);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $games[$row['gameID']] = $row;
            if ((int)$row['homeScore'] > (int)$row['visitorScore']) {
                $games[$row['gameID']]['winnerID'] = $row['homeID'];
            } elseif ((int)$row['visitorScore'] > (int)$row['homeScore']) {
                $games[$row['gameID']]['winnerID'] = $row['visitorID'];
            } else {
                $games[$row['gameID']]['winnerID'] = null;
            }
        }
        $stmt->close();

        $playerWeeklyTotals = [];
        $stmt2 = $mysqli->prepare(
            "SELECT p.userID, p.gameID, p.pickID, u.firstname, u.lastname, u.userName
             FROM " . DB_PREFIX . "picks p
             INNER JOIN " . DB_PREFIX . "users u ON p.userID = u.userID
             INNER JOIN " . DB_PREFIX . "schedule s ON p.gameID = s.gameID
             WHERE s.weekNum = ? AND u.userName <> 'admin'
             ORDER BY u.lastname, u.firstname, s.gameTimeEastern"
        );
        if (!$stmt2) continue;
        $stmt2->bind_param('i', $week);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        while ($row = $result2->fetch_assoc()) {
            if (!isset($playerWeeklyTotals[$row['userID']])) {
                $playerWeeklyTotals[$row['userID']] = ['week' => $week, 'score' => 0];
            }
            if (!isset($playerTotals[$row['userID']]['wins']))     $playerTotals[$row['userID']]['wins']     = 0;
            if (!isset($playerTotals[$row['userID']]['score']))    $playerTotals[$row['userID']]['score']    = 0;
            if (!isset($playerTotals[$row['userID']]['name']))     $playerTotals[$row['userID']]['name']     = $row['firstname'] . ' ' . $row['lastname'];
            if (!isset($playerTotals[$row['userID']]['userName'])) $playerTotals[$row['userID']]['userName'] = $row['userName'];

            if (!empty($games[$row['gameID']]['winnerID']) && $row['pickID'] == $games[$row['gameID']]['winnerID']) {
                $playerWeeklyTotals[$row['userID']]['score']++;
                $playerTotals[$row['userID']]['score']++;
            }
        }
        $stmt2->close();

        $highestScore = 0;
        arsort($playerWeeklyTotals);
        foreach ($playerWeeklyTotals as $playerID => $stats) {
            if ($stats['score'] > $highestScore) $highestScore = $stats['score'];
            if ($stats['score'] < $highestScore) break;
            $weekStats[$week]['winners'][] = $playerID;
            $playerTotals[$playerID]['wins']++;
        }
        $weekStats[$week]['highestScore']  = $highestScore;
        $weekStats[$week]['possibleScore'] = getGameTotal($week);
        $possibleScoreTotal += $weekStats[$week]['possibleScore'];
    }
}

function rteSafe($strText) {
    $tmp = $strText;
    $tmp = str_replace(chr(145), chr(39), $tmp);
    $tmp = str_replace(chr(146), chr(39), $tmp);
    $tmp = str_replace("'", "&#39;", $tmp);
    $tmp = str_replace(chr(147), chr(34), $tmp);
    $tmp = str_replace(chr(148), chr(34), $tmp);
    $tmp = str_replace(chr(10), " ", $tmp);
    $tmp = str_replace(chr(13), " ", $tmp);
    return $tmp;
}

function sort2d($array, $index, $order = 'asc', $natsort = false, $case_sensitive = false) {
    if (is_array($array) && count($array) > 0) {
        foreach (array_keys($array) as $key) {
            $temp[$key] = $array[$key][$index];
        }
        if (!$natsort) {
            ($order == 'asc') ? asort($temp) : arsort($temp);
        } else {
            ($case_sensitive) ? natsort($temp) : natcasesort($temp);
            if ($order != 'asc') $temp = array_reverse($temp, true);
        }
        foreach (array_keys($temp) as $key) {
            (is_numeric($key)) ? $sorted[] = $array[$key] : $sorted[$key] = $array[$key];
        }
        return $sorted;
    }
    return $array;
}

function getTeamRecord($teamID) {
    global $mysqli;

    $stmt = $mysqli->prepare(
        "SELECT
            (homeScore > visitorScore) AS gameWon,
            (homeScore = visitorScore) AS gameTied
         FROM " . DB_PREFIX . "schedule
         WHERE homeScore IS NOT NULL AND visitorScore IS NOT NULL AND homeID = ?
         UNION ALL
         SELECT
            (homeScore < visitorScore) AS gameWon,
            (homeScore = visitorScore) AS gameTied
         FROM " . DB_PREFIX . "schedule
         WHERE homeScore IS NOT NULL AND visitorScore IS NOT NULL AND visitorID = ?"
    );
    if (!$stmt) return '';
    $stmt->bind_param('ss', $teamID, $teamID);
    $stmt->execute();
    $result = $stmt->get_result();

    $wins = 0; $losses = 0; $ties = 0;
    while ($row = $result->fetch_assoc()) {
        if ($row['gameTied'])     $ties++;
        elseif ($row['gameWon']) $wins++;
        else                      $losses++;
    }
    $stmt->close();

    return ($wins + $losses + $ties > 0) ? "$wins-$losses-$ties" : '';
}

function getTeamStreak($teamID) {
    global $mysqli;

    $sql = "
        SELECT
            CASE
                WHEN homeScore = visitorScore THEN 'T'
                WHEN (homeID = ? AND homeScore > visitorScore) OR
                     (visitorID = ? AND visitorScore > homeScore) THEN 'W'
                ELSE 'L'
            END AS gameResult
        FROM " . DB_PREFIX . "schedule
        WHERE homeScore IS NOT NULL
          AND visitorScore IS NOT NULL
          AND (homeID = ? OR visitorID = ?)
        ORDER BY weekNum DESC";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return '';
    $stmt->bind_param('ssss', $teamID, $teamID, $teamID, $teamID);
    if (!$stmt->execute()) {
        $stmt->close();
        return '';
    }

    $result     = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        return '';
    }

    $firstRow    = $result->fetch_assoc();
    $currentType = $firstRow['gameResult'];
    $count       = 1;

    while ($row = $result->fetch_assoc()) {
        if ($row['gameResult'] !== $currentType) break;
        $count++;
    }
    $stmt->close();

    return $currentType . ' ' . $count;
}

function getFriendlyTimezoneName($timezone) {
    $names = [
        'America/New_York'    => 'Eastern',
        'America/Chicago'     => 'Central',
        'America/Denver'      => 'Mountain',
        'America/Los_Angeles' => 'Pacific',
    ];
    return $names[$timezone] ?? $timezone;
}
