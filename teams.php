<?php
require_once('includes/application_top.php');
require_once('includes/header.php');
?>

<div class="container my-4 page-teams">
    <div class="d-flex align-items-center mb-4">
        <div>
            <h1 class="mb-2">Teams</h1>
            <p class="text-muted mb-0">Click on a team below to see their schedule.</p>
        </div>
    </div>

    <div class="row">
        <?php
        $sql = "
            SELECT t.teamID,
                   t.city,
                   t.team,
                   d.conference,
                   d.division
            FROM " . DB_PREFIX . "teams t
            INNER JOIN " . DB_PREFIX . "divisions d
               ON t.divisionid = d.divisionid
            ORDER BY d.conference, d.division
        ";
        $query = $mysqli->query($sql);
        if ($query) {
            $groups = [];
            $conferenceOrder = [];
            $divisionOrder = [];

            while ($row = $query->fetch_assoc()) {
                $conf = $row['conference'];
                $div = $row['division'];

                if (!isset($groups[$conf])) {
                    $groups[$conf] = [];
                    $conferenceOrder[] = $conf;
                    $divisionOrder[$conf] = [];
                }
                if (!isset($groups[$conf][$div])) {
                    $groups[$conf][$div] = [];
                    $divisionOrder[$conf][] = $div;
                }

                $groups[$conf][$div][] = $row;
            }

            foreach ($conferenceOrder as $conf) {
                echo '<div class="col-md-6">';
                echo '<div class="card shadow-sm mb-4 border-1">';
                echo '<div class="card-header bg-body-tertiary text-body-secondary py-3">';

                echo '<h2 class="h4 mb-0 text-primary">';
                echo '<img src="images/logos/' . htmlspecialchars($conf) . '.svg" alt="'
                     . htmlspecialchars($conf) . ' Logo" '
                     . 'class="conference-logo">';
                echo htmlspecialchars($conf);
                echo '</h2>';

                echo '</div>';
                echo '<div class="card-body">';

                foreach ($divisionOrder[$conf] as $div) {
                    echo '<h3 class="h5 mt-3 mb-3 text-muted border-bottom pb-2">' .
                         htmlspecialchars($div) . '</h3>';

                    $teams = $groups[$conf][$div];
                    shuffle($teams);

                    foreach ($teams as $row) {
                        echo '<div class="team-item mb-2">';
                        echo '<a href="schedules.php?team=' . htmlspecialchars($row['teamID']) . '" ' .
                             'class="team-link text-decoration-none d-block py-1 px-2">';
                        echo '<img src="images/logos/' . htmlspecialchars($row['teamID']) . '.svg" 
                                    alt="' . htmlspecialchars($row['city'] . ' ' . $row['team']) . ' Logo" 
                                    class="team-logo" />';
                        echo htmlspecialchars($row['city'] . ' ' . $row['team']);
                        echo '</a>';
                        echo '</div>';
                    }
                }

                echo '</div></div></div>';
            }

            $query->free_result();
        } else {
            echo '<div class="col-12">';
            echo '<div class="alert alert-danger">Error fetching team data: ' .
                 htmlspecialchars($mysqli->error) . '</div>';
            echo '</div>';
        }
        ?>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>
