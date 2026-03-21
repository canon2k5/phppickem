<?php
require_once('includes/application_top.php');
if (function_exists('opcache_reset')) {
    opcache_reset();
}
require_once('includes/classes/team.php');

// Mark this tab active
$activeTab = 'picks';

// Sanitize critical variables
$week = isset($_GET['week']) ? (int)$_GET['week'] : (int)getCurrentWeek();
$userID = (int)$user->userID;
$cutoffDateTime = getCutoffDateTime($week);
$firstGameTime = getFirstGameTime($week);

// Get week from form submission if available
$submitWeek = isset($_POST['week']) ? (int)$_POST['week'] : $week;

// Handle form submission
if (isset($_POST['action']) && $_POST['action'] === 'Submit') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token. Please refresh the page and try again.');
    }
    // Update pick summary
    $showPicks = (int)($_POST['showPicks'] ?? 0);
    $sql = "REPLACE INTO " . DB_PREFIX . "picksummary 
            (weekNum, userID, showPicks, tieBreakerPoints) 
            VALUES ({$submitWeek}, {$user->userID}, {$showPicks}, 
                    COALESCE((SELECT tieBreakerPoints FROM 
                        (SELECT * FROM " . DB_PREFIX . "picksummary) AS ps 
                        WHERE weekNum = {$submitWeek} AND userID = {$user->userID}), 0))";
    $mysqli->query($sql) or die('Error updating picks summary: ' . $mysqli->error);

    // Get all games for the submitted week that haven't expired
    $sql = "SELECT gameID FROM " . DB_PREFIX . "schedule
            WHERE weekNum = {$submitWeek}
            AND (DATE_ADD(NOW(), INTERVAL " . SERVER_TIMEZONE_OFFSET . " HOUR) < gameTimeEastern
            AND DATE_ADD(NOW(), INTERVAL " . SERVER_TIMEZONE_OFFSET . " HOUR) < '{$cutoffDateTime}')";

    $query = $mysqli->query($sql);
    
    if ($query && $query->num_rows > 0) {
        $stmt_delete = $mysqli->prepare("DELETE FROM " . DB_PREFIX . "picks WHERE userID = ? AND gameID = ?");
        $stmt_insert = $mysqli->prepare("INSERT INTO " . DB_PREFIX . "picks (userID, gameID, pickID, points) VALUES (?, ?, ?, 1)");
        $mysqli->begin_transaction();
        try {
            while ($row = $query->fetch_assoc()) {
                $gameID     = (int)$row['gameID'];
                $pickedTeam = isset($_POST['game' . $gameID]) ? (int)$_POST['game' . $gameID] : null;
                if (!empty($pickedTeam)) {
                    $stmt_delete->bind_param("ii", $user->userID, $gameID);
                    $stmt_delete->execute();
                    $stmt_insert->bind_param("iii", $user->userID, $gameID, $pickedTeam);
                    $stmt_insert->execute();
                }
            }
            $mysqli->commit();
        } catch (Exception $e) {
            $mysqli->rollback();
            error_log("Pick submission error: " . $e->getMessage());
        }
        $stmt_delete->close();
        $stmt_insert->close();
        $query->free();
    }

    header("Location: results.php?week={$submitWeek}");
    exit;
}

// Include header from this install
require __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-3 page-entry-form">
    <h1>Make Your Picks</h1>
    <p class="lead">Week <?php echo $week; ?> Selections</p>

    <?php require __DIR__ . '/includes/week_nav.php'; ?>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8 mb-4">
            <?php
            // Get user's existing picks
            $picks = getUserPicks($week, $user->userID);

            // Check "showPicks" setting
            $showPicksSql = "SELECT showPicks FROM " . DB_PREFIX . "picksummary WHERE weekNum = {$week} AND userID = {$user->userID} LIMIT 1";
            $query = $mysqli->query($showPicksSql);
            if ($query && $query->num_rows > 0) {
                $row = $query->fetch_assoc();
                $showPicks = (int)$row['showPicks'];
            } else {
                $showPicks = 1;
            }
            $query->free();

            // Retrieve schedule
            $sql = "SELECT s.*,
                   (DATE_ADD(NOW(), INTERVAL " . SERVER_TIMEZONE_OFFSET . " HOUR) > s.gameTimeEastern
                    OR DATE_ADD(NOW(), INTERVAL " . SERVER_TIMEZONE_OFFSET . " HOUR) > '{$cutoffDateTime}') AS expired
                   FROM " . DB_PREFIX . "schedule s
                   INNER JOIN " . DB_PREFIX . "teams ht ON s.homeID = ht.teamID
                   INNER JOIN " . DB_PREFIX . "teams vt ON s.visitorID = vt.teamID
                   WHERE s.weekNum = {$week}
                   ORDER BY s.gameTimeEastern, s.gameID";

            $query = $mysqli->query($sql);

            if ($query && $query->num_rows > 0) {
                echo '<form name="entryForm" action="entry_form.php?week=' . htmlspecialchars($week) . '" method="post" onsubmit="return checkform();" class="mb-4">';
                echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '" />';
                echo '<input type="hidden" name="week" value="' . htmlspecialchars($week) . '" />';

                while ($row = $query->fetch_assoc()) {
                    $homeTeam = new team($row['homeID']);
                    $visitorTeam = new team($row['visitorID']);
                    $scoreEntered = (strlen($row['homeScore']) > 0 && strlen($row['visitorScore']) > 0);
                    $winnerID = null;

                    if ($scoreEntered) {
                        $homeScore = (int)$row['homeScore'];
                        $visitorScore = (int)$row['visitorScore'];
                        if ($homeScore > $visitorScore) {
                            $winnerID = $row['homeID'];
                        } elseif ($visitorScore > $homeScore) {
                            $winnerID = $row['visitorID'];
                        }
                    }

                    // Determine selected state for visitor / home
                    $visitorChecked = isset($picks[$row['gameID']]) && $picks[$row['gameID']]['pickID'] == $visitorTeam->teamID;
                    $homeChecked    = isset($picks[$row['gameID']]) && $picks[$row['gameID']]['pickID'] == $homeTeam->teamID;

                    // Game Card wrapper
                    echo '<div class="game-card">';

                    // Header
                    $headerClass = $scoreEntered ? 'game-header final' : 'game-header';
                    echo '<div class="' . $headerClass . '">';
                    if ($scoreEntered) {
                        echo htmlspecialchars($visitorTeam->teamID) . ' ' . htmlspecialchars($row['visitorScore'])
                           . ' &nbsp;—&nbsp; '
                           . htmlspecialchars($row['homeScore']) . ' ' . htmlspecialchars($homeTeam->teamID);
                    } else {
                        echo date('D M j · g:i a', strtotime($row['gameTimeEastern'])) . ' ET';
                    }
                    echo '</div>';

                    // Matchup row
                    if (!$row['expired']) {
                        $visitorSlotClass = 'team-slot' . ($visitorChecked ? ' selected' : '');
                        $homeSlotClass    = 'team-slot' . ($homeChecked    ? ' selected' : '');

                        // versus flex container wraps both team slots
                        echo '<div class="versus">';

                        // Visitor slot — clicking the label selects the radio
                        echo '  <label for="' . $row['gameID'] . $visitorTeam->teamID . '" class="' . $visitorSlotClass . '">';
                        echo '    <div class="team-logo"><img src="images/logos/' . htmlspecialchars($visitorTeam->teamID) . '.svg" alt="' . htmlspecialchars($visitorTeam->teamName) . '" class="team-logo-img" width="68" height="68" /></div>';
                        echo '    <div class="team-name">' . htmlspecialchars($visitorTeam->city) . '<br>' . htmlspecialchars($visitorTeam->team) . '</div>';
                        echo '    <div class="team-meta">' . htmlspecialchars(getTeamRecord($visitorTeam->teamID)) . ' &bull; ' . htmlspecialchars(getTeamStreak($visitorTeam->teamID)) . '</div>';
                        echo '  </label>';

                        echo '  <div class="at-divider">@</div>';

                        // Home slot
                        echo '  <label for="' . $row['gameID'] . $homeTeam->teamID . '" class="' . $homeSlotClass . '">';
                        echo '    <div class="team-logo"><img src="images/logos/' . htmlspecialchars($homeTeam->teamID) . '.svg" alt="' . htmlspecialchars($homeTeam->teamName) . '" class="team-logo-img" width="68" height="68" /></div>';
                        echo '    <div class="team-name">' . htmlspecialchars($homeTeam->city) . '<br>' . htmlspecialchars($homeTeam->team) . '</div>';
                        echo '    <div class="team-meta">' . htmlspecialchars(getTeamRecord($homeTeam->teamID)) . ' &bull; ' . htmlspecialchars(getTeamStreak($homeTeam->teamID)) . '</div>';
                        echo '  </label>';

                        echo '</div>'; // .versus

                        // Radio inputs in pick-row (visually shown as small controls)
                        echo '<div class="pick-row">';
                        echo '  <input type="radio" name="game' . $row['gameID'] . '" value="' . htmlspecialchars($visitorTeam->teamID) . '" id="' . $row['gameID'] . $visitorTeam->teamID . '"' . ($visitorChecked ? ' checked' : '') . ' />';
                        echo '  <input type="radio" name="game' . $row['gameID'] . '" value="' . htmlspecialchars($homeTeam->teamID)    . '" id="' . $row['gameID'] . $homeTeam->teamID    . '"' . ($homeChecked    ? ' checked' : '') . ' />';
                        echo '</div>';

                    } else {
                        // Locked — show matchup display only
                        echo '<div class="versus">';
                        // Visitor
                        echo '<div class="team-slot" style="pointer-events:none">';
                        echo '  <div class="team-logo">';
                        echo '    <img src="images/logos/' . htmlspecialchars($visitorTeam->teamID) . '.svg" alt="' . htmlspecialchars($visitorTeam->teamName) . '" class="team-logo-img" width="68" height="68" />';
                        echo '  </div>';
                        echo '  <div class="team-name">' . htmlspecialchars($visitorTeam->city) . '<br>' . htmlspecialchars($visitorTeam->team) . '</div>';
                        echo '  <div class="team-meta">' . htmlspecialchars(getTeamRecord($visitorTeam->teamID)) . ' &bull; ' . htmlspecialchars(getTeamStreak($visitorTeam->teamID)) . '</div>';
                        echo '</div>';
                        echo '<div class="at-divider">@</div>';
                        // Home
                        echo '<div class="team-slot" style="pointer-events:none">';
                        echo '  <div class="team-logo">';
                        echo '    <img src="images/logos/' . htmlspecialchars($homeTeam->teamID) . '.svg" alt="' . htmlspecialchars($homeTeam->teamName) . '" class="team-logo-img" width="68" height="68" />';
                        echo '  </div>';
                        echo '  <div class="team-name">' . htmlspecialchars($homeTeam->city) . '<br>' . htmlspecialchars($homeTeam->team) . '</div>';
                        echo '  <div class="team-meta">' . htmlspecialchars(getTeamRecord($homeTeam->teamID)) . ' &bull; ' . htmlspecialchars(getTeamStreak($homeTeam->teamID)) . '</div>';
                        echo '</div>';
                        echo '</div>';

                        // Show locked pick result
                        $pickID = getPickID($row['gameID'], $user->userID);
                        if (!empty($pickID)) {
                            $pickTeam  = new team($pickID);
                            $pickLabel = htmlspecialchars($pickTeam->teamName);
                            if ($scoreEntered) {
                                $statusIcon = ($pickID == $winnerID)
                                    ? '<span class="text-success fw-bold">&#10004;</span>'
                                    : '<span class="text-danger">&#10008;</span>';
                            } else {
                                $statusIcon = '';
                            }
                        } else {
                            $pickLabel  = 'None Selected';
                            $statusIcon = '<span class="text-danger">&#10008;</span>';
                        }
                        echo '<div class="your-pick"><strong>Your Pick</strong>' . $statusIcon . ' ' . $pickLabel . '</div>';
                    }

                    echo '</div>'; // .game-card
                }

                // Show Picks checkbox and Submit button
                echo '<div class="mb-3 mt-2">';
                echo '  <div class="form-check">';
                echo '    <input type="checkbox" class="form-check-input" name="showPicks" id="showPicks" value="1"' . ($showPicks ? ' checked' : '') . ' />';
                echo '    <label class="form-check-label" for="showPicks">Allow others to see my picks before the week locks</label>';
                echo '  </div>';
                echo '</div>';

                echo '<button type="submit" name="action" value="Submit" class="btn btn-primary">Submit</button>';
                echo '</form>';
            } else {
                echo '<div class="alert alert-info">No games found for this week.</div>';
            }
            ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <?php require __DIR__ . '/includes/column_right.php'; ?>
        </div>
    </div>

</div>

<!-- Your custom scripts -->
<script>
function checkform() {
    let allChecked = true;
    const radios = document.querySelectorAll('input[type=radio]');
    const namesChecked = {};

    radios.forEach(function(radio) {
        namesChecked[radio.name] = namesChecked[radio.name] || radio.checked;
        if (radio.checked) {
            namesChecked[radio.name] = true;
        }
    });
    for (let r in namesChecked) {
        if (!namesChecked[r]) {
            allChecked = false;
            break;
        }
    }

    if (!allChecked) {
        return confirm('One or more picks are missing for the current week. Do you wish to submit anyway?');
    }
    return true;
}

function checkRadios() {
    document.querySelectorAll('input[type=radio]').forEach(function(radio) {
        const label = document.querySelector('label[for="' + radio.id + '"]');
        if (!label) return;
        if (radio.checked) {
            label.classList.add('selected');
            label.classList.add('highlight');
        } else {
            label.classList.remove('selected');
            label.classList.remove('highlight');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    checkRadios();
    document.querySelectorAll('input[type=radio]').forEach(function(radio) {
        radio.addEventListener('change', checkRadios);
    });
});
</script>

<?php
include('includes/footer.php');
?>
