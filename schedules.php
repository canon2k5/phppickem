<?php
require_once('includes/application_top.php');
require_once('includes/classes/team.php');
if (function_exists('opcache_reset')) {
    opcache_reset();
}

// Get parameters
$team = $_GET['team'] ?? null;
$week = $_GET['week'] ?? null;

// If no team or week is specified, default to current week
if (empty($team) && empty($week)) {
    $week = getCurrentWeek();
}

// Include header (which loads Bootstrap 4)
include('includes/header.php');
?>

<div class="container my-4">
  <h1 class="mb-3">Schedules</h1>

  <div class="row mb-4">
    <!-- Team Select -->
    <div class="col-auto">
      <label for="teamSelect" class="me-2 fw-bold">Select a Team:</label>
      <select id="teamSelect" name="team" class="form-select d-inline-block"
              onchange="location.href='schedules.php?team=' + this.value;">
        <option value="">Select a team...</option>
        <?php
        // Populate teams
        $sql = "SELECT * FROM " . DB_PREFIX . "teams ORDER BY city, team";
        $query = $mysqli->query($sql);

        while ($row = $query->fetch_assoc()) {
            $selected = (!empty($team) && $team == $row['teamID']) ? ' selected="selected"' : '';
            echo '<option value="' . $row['teamID'] . '"' . $selected . '>'
                 . htmlspecialchars($row['city'] . ' ' . $row['team']) 
                 . '</option>';
        }
        $query->free();
        ?>
      </select>
    </div>

    <!-- OR separator -->
    <div class="col-auto d-flex align-items-center">
      <strong class="mx-3">OR</strong>
    </div>

    <!-- Week Select -->
    <div class="col-auto">
      <label for="weekSelect" class="me-2 fw-bold">Week:</label>
      <select id="weekSelect" name="week" class="form-select d-inline-block"
              onchange="location.href='schedules.php?week=' + this.value;">
        <option value="all"<?php echo ($week === 'all') ? ' selected="selected"' : ''; ?>>All</option>
        <?php
        // Populate weeks
        $sql = "SELECT DISTINCT weekNum FROM " . DB_PREFIX . "schedule ORDER BY weekNum";
        $query = $mysqli->query($sql);

        while ($row = $query->fetch_assoc()) {
            $selected = (!empty($week) && $week == $row['weekNum']) ? ' selected="selected"' : '';
            echo '<option value="' . $row['weekNum'] . '"' . $selected . '>'
                 . htmlspecialchars($row['weekNum'])
                 . '</option>';
        }
        $query->free();
        ?>
      </select>
    </div>
  </div> <!-- /.row -->

  <?php
  // If a team is selected, show team header
  if (!empty($team)) {
      $teamDetails = new team($team);
      echo '<h2 class="mb-4">';
      echo '<img src="images/logos/' . htmlspecialchars($team) . '.svg" height="70" width="70" alt="' 
           . htmlspecialchars($teamDetails->teamName) . ' Logo" class="schedule-team-logo me-2" />';
      echo htmlspecialchars($teamDetails->teamName) . ' Schedule';
      echo '</h2>';
  }

  // Build the schedule query
  $sql = "
    SELECT 
      s.*,
      ht.city AS homeCity, ht.team AS homeTeam, ht.displayName AS homeDisplayName,
      vt.city AS visitorCity, vt.team AS visitorTeam, vt.displayName AS visitorDisplayName
    FROM " . DB_PREFIX . "schedule s
    INNER JOIN " . DB_PREFIX . "teams ht ON s.homeID = ht.teamID
    INNER JOIN " . DB_PREFIX . "teams vt ON s.visitorID = vt.teamID
  ";

  $where = [];

  if (!empty($team)) {
      // Show only games for this team
      $teamEscaped = $mysqli->real_escape_string($team); 
      $where[] = "(homeID = '{$teamEscaped}' OR visitorID = '{$teamEscaped}')";
  } elseif (!empty($week) && $week !== 'all') {
      $where[] = "weekNum = " . (int)$week;
  }

  if ($where) {
      $sql .= 'WHERE ' . implode(' AND ', $where);
  }

  $sql .= " ORDER BY gameTimeEastern";

  $query = $mysqli->query($sql);

  if ($query && $query->num_rows > 0) {
      echo '<div class="card"><div class="card-body p-0"><div class="table-responsive">';
      echo '<table class="table table-striped mb-0">';
      echo '<thead class="table-dark">';
      echo '  <tr>';
      echo '    <th scope="col">Visitor</th>';
      echo '    <th scope="col">Home</th>';
      echo '    <th scope="col">Matchup</th>';
      echo '    <th scope="col">Time / Result</th>';
      echo '  </tr>';
      echo '</thead>';
      echo '<tbody>';

      $prevWeek = null;
      while ($row = $query->fetch_assoc()) {
          // If the week changed (and we're not filtering by team), print a subheader row
          if ($prevWeek !== $row['weekNum'] && empty($team)) {
              echo '<tr class="schedule-week-row">';
              echo '  <td colspan="4"><strong>Week ' . htmlspecialchars($row['weekNum']) . '</strong></td>';
              echo '</tr>';
          }

          // Create team objects (if needed for teamName logic)
          $homeTeam    = new team($row['homeID']);
          $visitorTeam = new team($row['visitorID']);

          echo '<tr>';
// Visitor logo
echo '  <td class="align-middle text-center">';
echo '    <img src="images/logos/' . htmlspecialchars($visitorTeam->teamID) . '.svg" alt="'
     . htmlspecialchars($visitorTeam->teamName) . ' Logo" class="schedule-team-logo" width="70" height="70" />';
echo '  </td>';
// Home logo
echo '  <td class="align-middle text-center">';
echo '    <img src="images/logos/' . htmlspecialchars($homeTeam->teamID) . '.svg" alt="'
     . htmlspecialchars($homeTeam->teamName) . ' Logo" class="schedule-team-logo" width="70" height="70" />';
echo '  </td>';


// Matchup text
          echo '  <td class="align-middle">';
          echo htmlspecialchars($visitorTeam->teamName) 
               . ' @ ' . htmlspecialchars($homeTeam->teamName);
          echo '  </td>';
          // Time / Score
          echo '  <td class="align-middle text-center">';

          if (is_numeric($row['homeScore']) && is_numeric($row['visitorScore'])) {
              // If score is entered, show result
              echo htmlspecialchars($row['visitorScore']) 
                   . ' - ' . htmlspecialchars($row['homeScore']);
          } else {
              // Show game time
              echo date('D n/j g:i a', strtotime($row['gameTimeEastern'])) . ' ET';
          }

          echo '  </td>';
          echo '</tr>';

          $prevWeek = $row['weekNum'];
      }

      echo '</tbody></table>';
      echo '</div></div></div>';
      $query->free();
  } else {
      echo '<div class="alert alert-info">No games found for the selected criteria.</div>';
  }
  ?>
</div><!-- /.container -->

<?php
include('includes/footer.php');
