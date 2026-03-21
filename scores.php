<?php
require('includes/application_top.php');

// Restrict access to admin users
if (!$user->is_admin) {
    header('Location: ./');
    exit;
}

// If form submitted, update scores
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'Update') {
    $stmt = $mysqli->prepare("
        UPDATE " . DB_PREFIX . "schedule
        SET homeScore = ?, visitorScore = ?, overtime = ?
        WHERE gameID = ?
    ");

    foreach ($_POST['game'] as $game) {
        $gameID       = (int)$game['gameID'];
        $homeScore    = is_numeric($game['homeScore']) ? (int)$game['homeScore'] : null;
        $visitorScore = is_numeric($game['visitorScore']) ? (int)$game['visitorScore'] : null;
        $overtime     = !empty($game['OT']) ? 1 : 0;

        $stmt->bind_param('iiii', $homeScore, $visitorScore, $overtime, $gameID);
        $stmt->execute();
    }

    $stmt->close();

    // Redirect to prevent form resubmission
    header("Location: scores.php?week=" . (int)$_POST['week']);
    exit;
}

// Determine week
$week = isset($_GET['week']) ? (int)$_GET['week'] : getCurrentWeek();

include('includes/header.php');
?>

<div class="container my-4 page-scores">
    <h2 class="mb-3">Enter Scores - Week <?php echo $week; ?></h2>

    <?php include('includes/week_nav.php'); ?>

    <div class="mb-3">
        <button onclick="getScores(<?php echo $week; ?>);" class="btn btn-info btn-lg w-100 w-md-auto">
            Load Scores
        </button>
    </div>

    <div class="card">
        <div class="card-header bg-body-tertiary text-body-secondary py-2">
            <strong>Manage Scores</strong>
        </div>
        <div class="card-body">
            <form id="scoresForm" name="scoresForm" action="scores.php" method="post">
                <input type="hidden" name="week" value="<?php echo $week; ?>" />

                <?php
                // Fetch games for this week
                $sql = "
                    SELECT s.*, ht.team AS homeAbbr, vt.team AS visitorAbbr
                    FROM " . DB_PREFIX . "schedule s
                    JOIN " . DB_PREFIX . "teams ht ON s.homeID = ht.teamID
                    JOIN " . DB_PREFIX . "teams vt ON s.visitorID = vt.teamID
                    WHERE s.weekNum = ?
                    ORDER BY s.gameTimeEastern
                ";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param('i', $week);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    echo '<div class="scores-grid">'; // Grid container for desktop

                    while ($row = $result->fetch_assoc()) {
                        $gameID = (int)$row['gameID'];
                        $visitorScore = (int)$row['visitorScore'];
                        $homeScore = (int)$row['homeScore'];
                        $overtime = $row['overtime'] ? ' checked' : '';
                        ?>
                        <div class="game-card">
                            <div class="game-time">
                                <?php echo date('D n/j g:i a', strtotime($row['gameTimeEastern'])); ?> ET
                            </div>
                            
                            <input type="hidden" name="game[<?php echo $gameID; ?>][gameID]" value="<?php echo $gameID; ?>" />
                            
                            <div class="team-score">
                                <span class="team-name"><?php echo htmlspecialchars($row['visitorAbbr']); ?></span>
                                <input type="number" 
                                       class="score-input" 
                                       name="game[<?php echo $gameID; ?>][visitorScore]" 
                                       id="visitorScore-<?php echo $gameID; ?>" 
                                       value="<?php echo $visitorScore; ?>" />
                            </div>

                            <div class="team-score">
                                <span class="team-name"><?php echo htmlspecialchars($row['homeAbbr']); ?></span>
                                <input type="number" 
                                       class="score-input" 
                                       name="game[<?php echo $gameID; ?>][homeScore]" 
                                       id="homeScore-<?php echo $gameID; ?>" 
                                       value="<?php echo $homeScore; ?>" />
                            </div>

                            <div class="ot-checkbox">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" 
                                           class="custom-control-input" 
                                           name="game[<?php echo $gameID; ?>][OT]" 
                                           id="OT-<?php echo $gameID; ?>" 
                                           value="1"<?php echo $overtime; ?>>
                                    <label class="custom-control-label" for="OT-<?php echo $gameID; ?>">Overtime</label>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    echo '</div>'; // End .scores-grid
                } else {
                    echo '<p class="text-muted mb-0">No games scheduled for this week.</p>';
                }
                $stmt->close();
                ?>

                <div class="mt-4">
                    <button type="submit" name="action" value="Update" class="btn btn-success btn-lg w-100 w-md-auto">
                        Update Scores
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
function getScores(weekNum) {
    fetch(`getBallDontLieScores.php?week=${weekNum}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            data.forEach(item => {
                const gID = item.gameID;
                const homeScoreField = document.getElementById('homeScore-' + gID);
                const visitorScoreField = document.getElementById('visitorScore-' + gID);
                const OTField = document.getElementById('OT-' + gID);

                if (homeScoreField) {
                    homeScoreField.value = item.homeScore;
                    homeScoreField.classList.add("fieldLoaded");
                }
                if (visitorScoreField) {
                    visitorScoreField.value = item.visitorScore;
                    visitorScoreField.classList.add("fieldLoaded");
                }
                if (OTField) {
                    OTField.checked = parseInt(item.overtime, 10) === 1;
                }
            });
        })
        .catch(error => {
            alert("Could not load scores from BallDontLie.");
            console.error('Error:', error);
        });
}

// Remove transition class after animation completes
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('transitionend', (e) => {
        if (e.target.classList.contains('fieldLoaded')) {
            // Wait a bit before removing the class to ensure the color is visible
            setTimeout(() => {
                e.target.classList.remove('fieldLoaded');
            }, 1500);
        }
    });
});
</script>

<?php include('includes/footer.php'); ?>
