<?php
require('includes/application_top.php');

function getWeekGameNumber($gameID, $games) {
    $gameIDs = array_keys($games);
    sort($gameIDs); // Ensure sequential ordering
    return array_search($gameID, $gameIDs) + 1;
}

// Initialize variables
$week = isset($_GET['week']) ? (int)$_GET['week'] : getCurrentWeek();
$cutoffDateTime = getCutoffDateTime($week);
$weekExpired = (time() + (SERVER_TIMEZONE_OFFSET * 3600) > strtotime($cutoffDateTime)) ? 1 : 0;

// Retrieve games for the selected week
$allScoresIn = true;
$games = [];
$sql = "SELECT * FROM " . DB_PREFIX . "schedule
        WHERE weekNum = ?
        ORDER BY gameTimeEastern, gameID";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $week);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $games[$row['gameID']] = [
        'gameID'        => $row['gameID'],
        'homeID'        => $row['homeID'],
        'visitorID'     => $row['visitorID'],
        'winnerID'      => '',
        'gameTime'      => $row['gameTimeEastern'],
        'homeScore'     => $row['homeScore'],
        'visitorScore'  => $row['visitorScore']
    ];

    if ($row['homeScore'] !== null && $row['visitorScore'] !== null) {
        $games[$row['gameID']]['winnerID'] = ($row['homeScore'] > $row['visitorScore']) ? $row['homeID'] : $row['visitorID'];
    } else {
        $allScoresIn = false;
    }
}
$stmt->close();

// First get all players who made picks for this week
$playerPicks = [];
$playerTotals = [];
$sql = "SELECT DISTINCT u.userID, u.userName
        FROM " . DB_PREFIX . "users u
        INNER JOIN " . DB_PREFIX . "picks p ON u.userID = p.userID
        INNER JOIN " . DB_PREFIX . "schedule s ON p.gameID = s.gameID
        WHERE s.weekNum = ?
          AND u.userName <> 'admin'
          AND u.status = 1
        ORDER BY u.userName";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $week);
$stmt->execute();
$result = $stmt->get_result();

// Initialize all players with 0 correct picks
while ($row = $result->fetch_assoc()) {
    $playerTotals[$row['userID']] = 0;
}
$stmt->close();

// Now get the actual picks
if (!empty($playerTotals)) {
    $sql = "SELECT p.userID, p.gameID, p.pickID
            FROM " . DB_PREFIX . "picks p
            INNER JOIN " . DB_PREFIX . "schedule s ON p.gameID = s.gameID
            WHERE s.weekNum = ?
            ORDER BY p.userID, s.gameTimeEastern, s.gameID";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $week);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $playerPicks[$row['userID']][$row['gameID']] = $row['pickID'];

        // Only tally correct picks if we have scores
        if (!empty($games[$row['gameID']]['winnerID']) && $row['pickID'] == $games[$row['gameID']]['winnerID']) {
            $playerTotals[$row['userID']]++;
        }
    }
    $stmt->close();
}

include('includes/header.php');
?>

<div class="container my-4">
    <h2 class="mb-3">Results - Week <?php echo $week; ?></h2>

    <?php include('includes/week_nav.php'); ?>

    <?php if (!$allScoresIn): ?>
        <div class="alert alert-warning">
            <strong>Note:</strong> Not all scores have been updated for week <?php echo $week; ?> yet.
        </div>
    <?php endif; ?>

    <?php
    $hideMyPicks = hidePicks($user->userID, $week);
    if ($hideMyPicks && !$weekExpired) {
        echo '<div class="alert alert-info">Your picks are currently hidden to other users.</div>';
    }
    ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center bg-body-tertiary text-body-secondary py-2">
            <strong>Week <?php echo $week; ?> Results</strong>
            <?php if (!empty($playerPicks)): ?>
                <button class="btn btn-sm btn-outline-primary d-none d-md-inline-block" onclick="toggleView()">
                    Toggle View
                </button>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <?php if (empty($playerPicks)): ?>
                <div class="alert alert-info">
                    <h4 class="alert-heading">No Picks Yet!</h4>
                    <p class="mb-0">There are no picks recorded for Week <?php echo $week; ?> yet.</p>
                </div>

                <!-- Display Games Grid -->
                <div class="mt-4">
                    <h5>Scheduled Games</h5>
                    <div class="row">
                        <?php foreach ($games as $game): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-2">
                                            Game #<?php echo getWeekGameNumber($game['gameID'], $games); ?>
                                        </h6>
                                        <p class="card-text d-flex align-items-center gap-2 flex-wrap">
                                            <strong><?php echo htmlspecialchars($game['visitorID']); ?></strong>
                                            <?php if ($game['visitorScore'] !== null): ?>
                                                <span class="badge bg-secondary"><?php echo $game['visitorScore']; ?></span>
                                            <?php endif; ?>
                                            <span class="text-muted">@</span>
                                            <strong><?php echo htmlspecialchars($game['homeID']); ?></strong>
                                            <?php if ($game['homeScore'] !== null): ?>
                                                <span class="badge bg-secondary"><?php echo $game['homeScore']; ?></span>
                                            <?php endif; ?>
                                        </p>
                                        <small class="text-muted">
                                            <?php echo date('D n/j g:i a', strtotime($game['gameTime'])); ?> ET
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php else: ?>
                <!-- Desktop View -->
                <div class="d-none d-md-block desktop-view">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Player</th>
                                    <?php foreach ($games as $game): ?>
                                        <th class="text-center" title="<?php echo $game['visitorID'] . ' @ ' . $game['homeID']; ?>">
                                            Game <?php echo getWeekGameNumber($game['gameID'], $games); ?>
                                        </th>
                                    <?php endforeach; ?>
                                    <th>Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                arsort($playerTotals);
                                $winners = [];
                                $topScore = max($playerTotals);

                                foreach ($playerTotals as $userID => $totalCorrect):
                                    if ($totalCorrect == $topScore && $allScoresIn) {
                                        $winners[] = $userID;
                                    }
                                    $tmpUser = $login->get_user_by_id($userID);
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(trim($tmpUser->userName)); ?></td>
                                        <?php foreach ($games as $game):
                                            $pick = $playerPicks[$userID][$game['gameID']] ?? '';
                                            $winnerID = $game['winnerID'];

                                            if (!empty($winnerID)) {
                                                if ($pick == $winnerID) {
                                                    $pick = '<span class="text-success fw-bold">' . htmlspecialchars($pick) . '</span>';
                                                } else {
                                                    $pick = '<span class="text-danger">' . htmlspecialchars($pick) . '</span>';
                                                }
                                            } elseif (!gameIsLocked($game['gameID']) && !$weekExpired &&
                                                     hidePicks($userID, $week) && (int)$userID !== (int)$user->userID) {
                                                $pick = '***';
                                            } else {
                                                $pick = htmlspecialchars($pick);
                                            }
                                        ?>
                                            <td class="text-center"><?php echo $pick; ?></td>
                                        <?php endforeach; ?>

                                        <?php
                                        $totalGames = count($games);
                                        $percentage = $totalGames > 0 ? number_format(($totalCorrect / $totalGames) * 100, 2) . '%' : '0%';
                                        ?>
                                        <td><strong><?php echo $totalCorrect . '/' . $totalGames . ' (' . $percentage . ')'; ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile View -->
                <div class="d-md-none mobile-view">
                    <?php
                    arsort($playerTotals);
                    foreach ($playerTotals as $userID => $totalCorrect):
                        $tmpUser = $login->get_user_by_id($userID);
                        $totalGames = count($games);
                        $percentage = $totalGames > 0 ? number_format(($totalCorrect / $totalGames) * 100, 2) . '%' : '0%';
                        $isWinner = in_array($userID, $winners);
                    ?>
                        <div class="card mb-3 <?php echo $isWinner ? 'border-success' : ''; ?>">
                            <div class="card-header <?php echo $isWinner ? 'bg-success text-white' : 'bg-light'; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong><?php echo htmlspecialchars(trim($tmpUser->userName)); ?></strong>
                                    <span><?php echo $totalCorrect . '/' . $totalGames . ' (' . $percentage . ')'; ?></span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>Game</th>
                                                <th>Matchup</th>
                                                <th>Pick</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($games as $game):
                                                $pick = $playerPicks[$userID][$game['gameID']] ?? '';
                                                $winnerID = $game['winnerID'];

                                                if (!empty($winnerID)) {
                                                    if ($pick == $winnerID) {
                                                        $pickDisplay = '<span class="text-success fw-bold">' . htmlspecialchars($pick) . '</span>';
                                                    } else {
                                                        $pickDisplay = '<span class="text-danger">' . htmlspecialchars($pick) . '</span>';
                                                    }
                                                } elseif (!gameIsLocked($game['gameID']) && !$weekExpired &&
                                                         hidePicks($userID, $week) && (int)$userID !== (int)$user->userID) {
                                                    $pickDisplay = '***';
                                                } else {
                                                    $pickDisplay = htmlspecialchars($pick);
                                                }
                                            ?>
                                                <tr>
                                                    <td><?php echo getWeekGameNumber($game['gameID'], $games); ?></td>
                                                    <td>
                                                        <small>
                                                            <?php echo htmlspecialchars($game['visitorID']); ?> @
                                                            <?php echo htmlspecialchars($game['homeID']); ?>
                                                        </small>
                                                    </td>
                                                    <td><?php echo $pickDisplay; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($allScoresIn && !empty($winners)):
                    $winnersHtml = implode(', ', array_map(fn($id) => htmlspecialchars($login->get_user_by_id($id)->userName), $winners));
                ?>
                    <div class="alert alert-success mt-3">
                        <strong>Winner<?php echo count($winners) > 1 ? 's' : ''; ?>:</strong>
                        <?php echo $winnersHtml; ?>
                    </div>
                <?php endif; ?>

                <?php
                // Display absent players with proper error handling
                if (!empty($playerTotals)) {
                    try {
                        // Get all active users who didn't make picks this week
                        $sql = "SELECT DISTINCT u.*
                                FROM " . DB_PREFIX . "users u
                                WHERE u.status = 1
                                  AND u.userName <> 'admin'
                                  AND u.userID NOT IN (
                                      SELECT DISTINCT p.userID
                                      FROM " . DB_PREFIX . "picks p
                                      INNER JOIN " . DB_PREFIX . "schedule s ON p.gameID = s.gameID
                                      WHERE s.weekNum = ?
                                  )";
                        $stmt = $mysqli->prepare($sql);
                        $stmt->bind_param('i', $week);

                        if ($stmt->execute()) {
                            $result = $stmt->get_result();
                            if ($result->num_rows > 0) {
                                $absentPlayers = [];
                                while ($row = $result->fetch_assoc()) {
                                    $absentPlayers[] = htmlspecialchars(trim($row['userName']));
                                }
                                if (!empty($absentPlayers)) {
                                    echo '<div class="alert alert-warning mt-3">';
                                    echo '<strong>Absent Players:</strong> ' . implode(', ', $absentPlayers);
                                    echo '</div>';
                                }
                            }
                        }
                        $stmt->close();
                    } catch (Exception $e) {
                        error_log("Error in absent players query: " . $e->getMessage());
                    }
                }
                ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleView() {
    const desktopView = document.querySelector('.desktop-view');
    const mobileView = document.querySelector('.mobile-view');

    const desktopHidden = desktopView.classList.contains('d-md-none');

    if (desktopHidden) {
        desktopView.classList.remove('d-md-none');
        desktopView.classList.add('d-md-block');
        mobileView.classList.add('d-md-none');
        mobileView.classList.remove('d-md-block');
    } else {
        desktopView.classList.add('d-md-none');
        desktopView.classList.remove('d-md-block');
        mobileView.classList.remove('d-md-none');
        mobileView.classList.add('d-md-block');
    }
}
</script>

<?php
include('includes/footer.php');
?>
