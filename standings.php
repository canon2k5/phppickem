<?php
require('includes/application_top.php');

$weekStats = [];
$playerTotals = [];
$possibleScoreTotal = 0;

// Get the number of weeks to display from GET parameter, default to 5
$displayWeeks = isset($_GET['weeks']) ? (int)$_GET['weeks'] : 5;

calculateStats(); // Ensure this function is defined in an included file.

// If we have week stats, limit them based on the selection
if (!empty($weekStats)) {
    if ($displayWeeks != 0) { // 0 means show all
        $weekStats = array_slice($weekStats, -$displayWeeks, null, true);
    }
}

include('includes/header.php');
?>
<div class="container my-4 page-standings">
    <h1 class="mb-4">Standings</h1>

    <!-- Weekly Stats Section -->
    <section class="mb-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <strong>Weekly Stats</strong>
                <div class="btn-group btn-group-sm" role="group" aria-label="Weeks to display">
                    <a href="standings.php?weeks=5"  class="btn <?php echo $displayWeeks == 5  ? 'btn-primary' : 'btn-outline-secondary'; ?>">Last 5</a>
                    <a href="standings.php?weeks=10" class="btn <?php echo $displayWeeks == 10 ? 'btn-primary' : 'btn-outline-secondary'; ?>">Last 10</a>
                    <a href="standings.php?weeks=0"  class="btn <?php echo $displayWeeks == 0  ? 'btn-primary' : 'btn-outline-secondary'; ?>">All</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive mb-0">
                    <table class="table table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Week</th>
                                <th>Winner(s)</th>
                                <th class="text-center">Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($weekStats)) {
                                foreach ($weekStats as $week => $stats) {
                                    $winners = [];
                                    if (isset($stats['winners']) && is_array($stats['winners'])) {
                                        foreach ($stats['winners'] as $winnerID) {
                                            $tmpUser = $login->get_user_by_id($winnerID);
                                            $winnerName = htmlspecialchars($tmpUser->userName);
                                            switch (USER_NAMES_DISPLAY) {
                                                case 1:
                                                    $winners[] = htmlspecialchars(trim($tmpUser->firstname . ' ' . $tmpUser->lastname));
                                                    break;
                                                case 2:
                                                    $winners[] = $winnerName;
                                                    break;
                                                default: // 3
                                                    $winners[] = '<abbr title="' . htmlspecialchars(trim($tmpUser->firstname . ' ' . $tmpUser->lastname)) . '">' . $winnerName . '</abbr>';
                                                    break;
                                            }
                                        }
                                    }
                                    $winnersString = implode(', ', $winners);
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($week) . '</td>';
                                    echo '<td>' . $winnersString . '</td>';
                                    echo '<td class="text-center">' . htmlspecialchars($stats['highestScore']) . '/' . htmlspecialchars($stats['possibleScore']) . '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="3">No weeks have been completed yet.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- User Stats Section -->
    <section class="mb-5">
        <h2 class="h3">User Stats</h2>
        <div class="row">
            <?php
            // Define sort categories
            $categories = [
                "By Name"       => "name",
                "By Wins"       => "wins",
                "By Pick Ratio" => "score"
            ];

            // Loop through each category
            foreach ($categories as $categoryTitle => $sortKey) {
                // Copy the totals to sort without affecting the original array.
                $sortedTotals = $playerTotals;
                if ($sortKey == 'score') {
                    usort($sortedTotals, function ($a, $b) {
                        return $b['score'] <=> $a['score'];
                    });
                } else {
                    // Sort descending by the chosen key
                    usort($sortedTotals, function ($a, $b) use ($sortKey) {
                        return $b[$sortKey] <=> $a[$sortKey];
                    });
                }
            ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <strong><?php echo htmlspecialchars($categoryTitle); ?></strong>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Player</th>
                                            <th class="text-center">Wins</th>
                                            <th class="text-center">Pick Ratio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (!empty($sortedTotals)) {
                                            foreach ($sortedTotals as $stats) {
                                                $pickRatio      = $stats['score'] . '/' . $possibleScoreTotal;
                                                $pickPercentage = number_format(($stats['score'] / max($possibleScoreTotal, 1)) * 100, 2) . '%';
                                                switch (USER_NAMES_DISPLAY) {
                                                    case 1:
                                                        $playerDisplay = htmlspecialchars($stats['name']);
                                                        break;
                                                    case 2:
                                                        $playerDisplay = htmlspecialchars($stats['userName'] ?? 'Unknown');
                                                        break;
                                                    default: // 3
                                                        $playerDisplay = '<abbr title="' . htmlspecialchars($stats['name']) . '">' . htmlspecialchars($stats['userName'] ?? 'Unknown') . '</abbr>';
                                                        break;
                                                }
                                                echo '<tr>';
                                                echo '<td>' . $playerDisplay . '</td>';
                                                echo '<td class="text-center">' . htmlspecialchars($stats['wins']) . '</td>';
                                                echo '<td class="text-center">' . $pickRatio . ' (' . $pickPercentage . ')</td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="3">No weeks have been completed yet.</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } // End foreach ?>
        </div>
    </section>

</div><!-- /.container -->

<?php include('includes/footer.php'); ?>
