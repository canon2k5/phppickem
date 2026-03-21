<?php
require_once('includes/application_top.php');

// Mark this tab active
$activeTab = 'home';

// Include header
include('includes/header.php');

// Sanitize critical variables
$currentWeek = isset($currentWeek) ? (int)$currentWeek : 0;
$userID = (int)$user->userID;
?>

<div class="container-fluid py-3 page-home">
    <?php if ($user->userName === 'admin'): ?>
        <?php
        // For admin, getCurrentWeek() is not called in application_top — fetch it here
        $adminWeek    = getCurrentWeek();   // returns last week num or false if no schedule
        $offSeason    = false;

        // Detect off-season: all games are in the past
        $futureCheck = $mysqli->query(
            "SELECT 1 FROM " . DB_PREFIX . "schedule
             WHERE DATE_ADD(NOW(), INTERVAL " . SERVER_TIMEZONE_OFFSET . " HOUR) < gameTimeEastern
             LIMIT 1"
        );
        if (!$futureCheck || $futureCheck->num_rows === 0) {
            $offSeason = true;
        }
        if ($futureCheck) $futureCheck->free();

        // Active player count
        $stmt = $mysqli->prepare("SELECT COUNT(*) FROM " . DB_PREFIX . "users WHERE status = 1 AND userName <> 'admin'");
        $stmt->execute();
        $stmt->bind_result($totalPlayers);
        $stmt->fetch();
        $stmt->close();

        $weekGames      = $adminWeek ? getGameTotal($adminWeek) : 0;
        $playersWithPicks = 0;
        $scoresEntered  = 0;

        if ($adminWeek) {
            $stmt = $mysqli->prepare(
                "SELECT COUNT(DISTINCT p.userID) FROM " . DB_PREFIX . "picks p
                 INNER JOIN " . DB_PREFIX . "schedule s ON p.gameID = s.gameID
                 WHERE s.weekNum = ?"
            );
            $stmt->bind_param('i', $adminWeek);
            $stmt->execute();
            $stmt->bind_result($playersWithPicks);
            $stmt->fetch();
            $stmt->close();

            $stmt = $mysqli->prepare(
                "SELECT COUNT(*) FROM " . DB_PREFIX . "schedule
                 WHERE weekNum = ? AND homeScore IS NOT NULL AND visitorScore IS NOT NULL"
            );
            $stmt->bind_param('i', $adminWeek);
            $stmt->execute();
            $stmt->bind_result($scoresEntered);
            $stmt->fetch();
            $stmt->close();
        }
        ?>

        <!-- Header -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
            <div>
                <h1 class="mb-1"><i class="fa-solid fa-shield-halved me-2" style="color:var(--accent)"></i>Admin Dashboard</h1>
                <div class="text-muted">
                    <?php echo APP_NAME; ?> <?php echo htmlspecialchars(SEASON_YEAR); ?>
                    <?php if ($offSeason): ?>
                        &mdash; <span class="badge" style="background:var(--accent);font-size:.75rem">Off-Season</span>
                    <?php elseif ($adminWeek): ?>
                        &mdash; Week <?php echo $adminWeek; ?>
                    <?php endif; ?>
                </div>
            </div>
            <img src="images/phppickemlogo.png" alt="Logo" style="height:64px;width:auto;opacity:.85">
        </div>

        <!-- Stat strip -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card h-100 text-center admin-stat-card">
                    <div class="card-body py-3">
                        <div class="admin-stat-icon"><i class="fa-solid fa-calendar-week"></i></div>
                        <?php if ($offSeason): ?>
                            <div class="admin-stat-value" style="font-size:1.1rem">Off-Season</div>
                            <div class="admin-stat-label">Last Week: <?php echo $adminWeek ?: '—'; ?></div>
                        <?php else: ?>
                            <div class="admin-stat-value"><?php echo $adminWeek ?: '—'; ?></div>
                            <div class="admin-stat-label">Current Week</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 text-center admin-stat-card">
                    <div class="card-body py-3">
                        <div class="admin-stat-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="admin-stat-value"><?php echo $totalPlayers; ?></div>
                        <div class="admin-stat-label">Active Players</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 text-center admin-stat-card">
                    <div class="card-body py-3">
                        <div class="admin-stat-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                        <div class="admin-stat-value">
                            <?php echo $adminWeek ? "$playersWithPicks/$totalPlayers" : '—'; ?>
                        </div>
                        <div class="admin-stat-label"><?php echo $offSeason ? 'Final Week Picks' : 'Picks Submitted'; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card h-100 text-center admin-stat-card">
                    <div class="card-body py-3">
                        <div class="admin-stat-icon"><i class="fa-solid fa-football"></i></div>
                        <div class="admin-stat-value">
                            <?php echo $adminWeek ? "$scoresEntered/$weekGames" : '—'; ?>
                        </div>
                        <div class="admin-stat-label"><?php echo $offSeason ? 'Final Week Scores' : 'Scores Entered'; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action cards -->
        <div class="row g-3">

            <div class="col-sm-6 col-lg-4">
                <a href="scores.php" class="admin-action-card card h-100 text-decoration-none">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <div class="admin-action-icon"><i class="fa-solid fa-pen-to-square"></i></div>
                        <div>
                            <div class="admin-action-title">Enter Scores</div>
                            <div class="admin-action-desc">Record game outcomes for the current week.</div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-sm-6 col-lg-4">
                <a href="users.php" class="admin-action-card card h-100 text-decoration-none">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <div class="admin-action-icon"><i class="fa-solid fa-users-gear"></i></div>
                        <div>
                            <div class="admin-action-title">Manage Users</div>
                            <div class="admin-action-desc">View, edit, activate or deactivate player accounts.</div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-sm-6 col-lg-4">
                <a href="send_email.php" class="admin-action-card card h-100 text-decoration-none">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <div class="admin-action-icon"><i class="fa-solid fa-envelope-open-text"></i></div>
                        <div>
                            <div class="admin-action-title">Send Email</div>
                            <div class="admin-action-desc">Broadcast reminders or results to all players.</div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-sm-6 col-lg-4">
                <a href="schedule_edit.php" class="admin-action-card card h-100 text-decoration-none">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <div class="admin-action-icon"><i class="fa-solid fa-calendar-days"></i></div>
                        <div>
                            <div class="admin-action-title">Edit Schedule</div>
                            <div class="admin-action-desc">Add, edit or remove games from the schedule.</div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-sm-6 col-lg-4">
                <a href="email_templates.php" class="admin-action-card card h-100 text-decoration-none">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <div class="admin-action-icon"><i class="fa-solid fa-file-lines"></i></div>
                        <div>
                            <div class="admin-action-title">Email Templates</div>
                            <div class="admin-action-desc">Customise the weekly reminder and results templates.</div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-sm-6 col-lg-4">
                <a href="results.php" class="admin-action-card card h-100 text-decoration-none">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <div class="admin-action-icon"><i class="fa-solid fa-chart-bar"></i></div>
                        <div>
                            <div class="admin-action-title">View Results</div>
                            <div class="admin-action-desc">See picks and scores for any completed week.</div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-sm-6 col-lg-4">
                <a href="standings.php" class="admin-action-card card h-100 text-decoration-none">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <div class="admin-action-icon"><i class="fa-solid fa-ranking-star"></i></div>
                        <div>
                            <div class="admin-action-title">Standings</div>
                            <div class="admin-action-desc">Season leaderboard and weekly win history.</div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-sm-6 col-lg-4">
                <a href="schedule_import.php" class="admin-action-card card h-100 text-decoration-none">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <div class="admin-action-icon"><i class="fa-solid fa-file-import"></i></div>
                        <div>
                            <div class="admin-action-title">Import Schedule</div>
                            <div class="admin-action-desc">Upload a CSV to bulk-import the season schedule.</div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-sm-6 col-lg-4">
                <a href="admin_reset_tables.php" class="admin-action-card admin-action-card--danger card h-100 text-decoration-none">
                    <div class="card-body d-flex gap-3 align-items-start">
                        <div class="admin-action-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        <div>
                            <div class="admin-action-title">Reset Tables</div>
                            <div class="admin-action-desc">Clear season data to start a new year. Use with caution.</div>
                        </div>
                    </div>
                </a>
            </div>

        </div>

    <?php else: ?>
        <!-- User Dashboard -->
        <div class="row align-items-center mb-4">
            <div class="col-lg-8">
                <h1>Welcome to <?php echo APP_NAME; ?></h1>
                <p class="lead">
                    <?php if ($weekExpired): ?>
                        Week <?php echo $currentWeek; ?> is now locked. Check out the results and your performance below.
                    <?php else: ?>
                        Track your picks and performance for Week <?php echo $currentWeek; ?>.
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <img src="images/phppickemlogo.png" alt="PHP Pick'em Logo" class="img-fluid dashboard-logo-sm" />
            </div>
        </div>

        <!-- Status Cards Row -->
        <div class="row mb-4 dashboard-cards">
            <!-- Current Week Card -->
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="card h-100 border-primary">
                    <div class="card-body">
                        <h5 class="card-title">Current Week</h5>
                        <h2 class="card-text mb-0">Week <?php echo $currentWeek; ?></h2>
                        <?php if (!$weekExpired): ?>
                            <small class="text-muted">Active</small>
                        <?php else: ?>
                            <small class="text-muted">Locked</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Picks Status Card -->
            <?php
            $picks = getUserPicks($currentWeek, $userID);
            $gameTotal = getGameTotal($currentWeek);
            $picksComplete = count($picks) >= $gameTotal;
            ?>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="card h-100 <?php echo $picksComplete ? 'border-success' : 'border-warning'; ?>">
                    <div class="card-body">
                        <h5 class="card-title">Your Picks</h5>
                        <h2 class="card-text mb-0"><?php echo count($picks); ?>/<?php echo $gameTotal; ?></h2>
                        <small class="<?php echo $picksComplete ? 'text-success' : 'text-warning'; ?>">
                            <?php echo $picksComplete ? 'Complete' : 'Incomplete'; ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Stats Card (Week or Season) -->
            <?php if ($weekExpired): ?>
                <?php
                // Show specific week stats when week is locked
                $weekTotal = getGameTotal($currentWeek);
                $weekCorrect = getUserScore($currentWeek, $userID);
                $percentage = $weekTotal > 0 ? ($weekCorrect / $weekTotal) * 100 : 0;
                ?>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card h-100 border-info">
                        <div class="card-body">
                            <h5 class="card-title">Week <?php echo $currentWeek; ?> Stats</h5>
                            <h2 class="card-text mb-0"><?php echo number_format($percentage, 1); ?>%</h2>
                            <small class="text-muted">
                                <?php echo $weekCorrect; ?>/<?php echo $weekTotal; ?> correct
                            </small>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php
                // Show season stats for active weeks
                $totalPicks = 0;
                $totalCorrect = 0;
                for ($w = 1; $w <= $currentWeek; $w++) {
                    if ($w <= $lastCompletedWeek) {
                        $weekTotal = getGameTotal($w);
                        $totalPicks += $weekTotal;
                        $totalCorrect += getUserScore($w, $userID);
                    }
                }
                $percentage = $totalPicks > 0 ? ($totalCorrect / $totalPicks) * 100 : 0;
                ?>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="card h-100 border-info">
                        <div class="card-body">
                            <h5 class="card-title">Season Stats</h5>
                            <h2 class="card-text mb-0"><?php echo number_format($percentage, 1); ?>%</h2>
                            <small class="text-muted">
                                <?php echo $totalCorrect; ?>/<?php echo $totalPicks; ?> correct
                            </small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quick Actions Card -->
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Quick Actions</h5>
                        <?php if (!$weekExpired && !$picksComplete): ?>
                            <a href="entry_form.php" class="btn btn-primary btn-sm d-block mb-2">Make Picks</a>
                        <?php endif; ?>
                        <a href="standings.php" class="btn btn-outline-secondary btn-sm d-block mb-2">View Standings</a>
                        <a href="results.php" class="btn btn-outline-secondary btn-sm d-block">View Results</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="row">
            <!-- Weekly Overview -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0">Season Overview</h2>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Week</th>
                                        <th>Games</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Your Picks</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "
                                        SELECT
                                            s.weekNum,
                                            COUNT(s.gameID) AS gamesTotal,
                                            MIN(s.gameTimeEastern) AS firstGameTime,
                                            (
                                                SELECT gameTimeEastern
                                                FROM " . DB_PREFIX . "schedule
                                                WHERE weekNum = s.weekNum
                                                AND DATE_FORMAT(gameTimeEastern, '%W') = 'Sunday'
                                                ORDER BY gameTimeEastern
                                                LIMIT 1
                                            ) AS cutoffTime,
                                            (
                                                DATE_ADD(NOW(), INTERVAL " . SERVER_TIMEZONE_OFFSET . " HOUR)
                                                > (
                                                    SELECT gameTimeEastern
                                                    FROM " . DB_PREFIX . "schedule
                                                    WHERE weekNum = s.weekNum
                                                    AND DATE_FORMAT(gameTimeEastern, '%W') = 'Sunday'
                                                    ORDER BY gameTimeEastern
                                                    LIMIT 1
                                                )
                                            ) AS expired
                                        FROM " . DB_PREFIX . "schedule s
                                        GROUP BY s.weekNum
                                        ORDER BY s.weekNum DESC
                                    ";

                                    $query = $mysqli->query($sql);
                                    while ($row = $query->fetch_assoc()):
                                        $weekNum = (int)$row['weekNum'];
                                        $gamesTotal = (int)$row['gamesTotal'];
                                        $weekPicks = getUserPicks($weekNum, $userID);
                                        $expired = (bool)$row['expired'];
                                        $isCurrentWeek = $weekNum === $currentWeek;
                                    ?>
                                        <tr<?php echo $isCurrentWeek ? ' class="table-active"' : ''; ?>>
                                            <td>
                                                <strong>Week <?php echo $weekNum; ?></strong>
                                                <div class="small text-muted">
                                                    <?php echo date('n/j g:i a', strtotime($row['firstGameTime'])); ?> ET
                                                </div>
                                            </td>
                                            <td><?php echo $gamesTotal; ?> games</td>
                                            <td class="text-center">
                                                <?php if ($expired): ?>
                                                    <span class="badge bg-secondary">Closed</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Open</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($expired && $weekNum <= $lastCompletedWeek): 
                                                    $score = getUserScore($weekNum, $userID);
                                                    $percentage = ($score / $gamesTotal) * 100;
                                                ?>
                                                    <span class="badge bg-info">
                                                        <?php echo $score; ?>/<?php echo $gamesTotal; ?>
                                                        (<?php echo number_format($percentage, 1); ?>%)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge <?php echo count($weekPicks) >= $gamesTotal ? 'bg-success' : 'bg-warning'; ?>">
                                                        <?php echo count($weekPicks); ?>/<?php echo $gamesTotal; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($expired): ?>
                                                    <a href="results.php?week=<?php echo $weekNum; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        View Results
                                                    </a>
                                                <?php else: ?>
                                                    <a href="entry_form.php?week=<?php echo $weekNum; ?>" 
                                                       class="btn btn-sm <?php echo count($weekPicks) >= $gamesTotal ? 'btn-outline-primary' : 'btn-primary'; ?>">
                                                        <?php echo count($weekPicks) >= $gamesTotal ? 'Update Picks' : 'Make Picks'; ?>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    <?php $query->free(); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <?php include('includes/column_right.php'); ?>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php
require('includes/footer.php');
?>
