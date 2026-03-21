<?php
require('includes/application_top.php');

if (!$user->is_admin) {
    header('Location: ./');
    exit;
}

$csrf = $_SESSION['csrf_token'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'edit_action':
        if (!isset($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) {
            die('<div class="alert alert-danger">Invalid CSRF token. Please refresh the page and try again.</div>');
        }
        // Process game edit submission
        $gameID = (int)$_POST['gameID'];
        $week = (int)$_POST['weekNum'];
        $gameTimeEastern = date('Y-m-d G:i:00', strtotime($_POST['gameTimeEastern'] . ' ' . $_POST['gameTimeEastern2']));
        $homeID = (int)$_POST['homeID'];
        $visitorID = (int)$_POST['visitorID'];

        if (empty($homeID) || empty($visitorID)) {
            die('<div class="alert alert-danger">Error: Missing home or visiting team.</div>');
        }

        $stmt = $mysqli->prepare("SELECT * FROM " . DB_PREFIX . "schedule WHERE gameID = ?");
        $stmt->bind_param("i", $gameID);
        $stmt->execute();
        $query = $stmt->get_result();
        if ($query->num_rows > 0) {
            $row = $query->fetch_assoc();
            if (date('U') < strtotime($row['gameTimeEastern'])) {
                if ($week !== $row['weekNum'] || $homeID !== $row['homeID'] || $visitorID !== $row['visitorID']) {
                    $stmt_delete = $mysqli->prepare("DELETE FROM " . DB_PREFIX . "picks WHERE gameID = ?");
                    $stmt_delete->bind_param("i", $gameID);
                    $stmt_delete->execute();
                    $stmt_delete->close();
                }
            }
        } else {
            die('<div class="alert alert-danger">Error: Something went wrong...</div>');
        }
        $query->free();
        $stmt->close();

        $stmt_update = $mysqli->prepare("UPDATE " . DB_PREFIX . "schedule
                SET weekNum = ?, gameTimeEastern = ?, homeID = ?, visitorID = ?
                WHERE gameID = ?");
        $stmt_update->bind_param("isiii", $week, $gameTimeEastern, $homeID, $visitorID, $gameID);
        $stmt_update->execute();
        $stmt_update->close();

        header('Location: ' . $_SERVER['PHP_SELF'] . '?week=' . $week);
        exit;
        break;

    case 'add_action':
        if (!isset($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) {
            die('<div class="alert alert-danger">Invalid CSRF token. Please refresh the page and try again.</div>');
        }
        // Process adding a new game
        $week = (int)$_POST['weekNum'];
        $gameTimeEastern = date('Y-m-d G:i:00', strtotime($_POST['gameTimeEastern'] . ' ' . $_POST['gameTimeEastern2']));
        $homeID = (int)$_POST['homeID'];
        $visitorID = (int)$_POST['visitorID'];

        if (empty($homeID) || empty($visitorID)) {
            die('<div class="alert alert-danger">Error: Missing home or visiting team.</div>');
        }

        $stmt_insert = $mysqli->prepare("INSERT INTO " . DB_PREFIX . "schedule (weekNum, gameTimeEastern, homeID, visitorID)
                VALUES (?, ?, ?, ?)");
        if (!$stmt_insert) {
            die('<div class="alert alert-danger">Error adding game: ' . htmlspecialchars($mysqli->error) . '</div>');
        }
        $stmt_insert->bind_param("isii", $week, $gameTimeEastern, $homeID, $visitorID);
        $stmt_insert->execute();
        $stmt_insert->close();

        header('Location: ' . $_SERVER['PHP_SELF'] . '?week=' . $week);
        exit;
        break;

    case 'delete':
        if (!isset($_GET['csrf_token']) || !hash_equals($csrf, $_GET['csrf_token'])) {
            die('<div class="alert alert-danger">Invalid CSRF token. Please refresh the page and try again.</div>');
        }
        // Process deletion of a game
        $gameID = (int)$_GET['id'];
        $week = (int)$_GET['week'];

        $stmt_delete_picks = $mysqli->prepare("DELETE FROM " . DB_PREFIX . "picks WHERE gameID = ?");
        $stmt_delete_picks->bind_param("i", $gameID);
        $stmt_delete_picks->execute();
        $stmt_delete_picks->close();
        $stmt_delete_game = $mysqli->prepare("DELETE FROM " . DB_PREFIX . "schedule WHERE gameID = ?");
        $stmt_delete_game->bind_param("i", $gameID);
        $stmt_delete_game->execute();
        $stmt_delete_game->close();

        header('Location: ' . $_SERVER['PHP_SELF'] . '?week=' . $week);
        exit;
        break;

    default:
        break;
}

include('includes/header.php');
?>

<?php if ($action == 'edit'): ?>
  <!-- Edit Game Form Card -->
  <div class="container my-4">
    <div class="card">
      <div class="card-header">
        <h1 class="h4 mb-0">Edit Game</h1>
      </div>
      <div class="card-body">
        <div class="alert alert-warning">
          Warning: Changes made to future games will erase picks entered for games affected.
        </div>
        <?php
        $sql = "SELECT * FROM " . DB_PREFIX . "schedule WHERE gameID = " . (int)$_GET['id'];
        $query = $mysqli->query($sql);
        if ($query->num_rows > 0) {
            $game = $query->fetch_assoc();
        } else {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        $query->free();
        ?>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>?action=edit_action" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>" />
            <input type="hidden" name="gameID" value="<?php echo $game['gameID']; ?>" />
            <div class="mb-3">
              <label for="weekNum">Week:</label>
              <input type="text" name="weekNum" id="weekNum" value="<?php echo $game['weekNum']; ?>" class="form-control week-input" />
            </div>
            <div class="mb-3">
              <label>Date/Time:</label>
              <div class="row g-2">
                <div class="col">
                  <input type="date" name="gameTimeEastern" value="<?php echo date('Y-m-d', strtotime($game['gameTimeEastern'])); ?>" class="form-control" />
                </div>
                <div class="col">
                  <input type="time" name="gameTimeEastern2" value="<?php echo date('H:i', strtotime($game['gameTimeEastern'])); ?>" class="form-control" />
                </div>
              </div>
            </div>
            <div class="mb-3">
              <label for="homeID">Home Team:</label>
              <select name="homeID" id="homeID" class="form-select">
                  <?php
                  $sql = "SELECT * FROM " . DB_PREFIX . "teams ORDER BY city, team";
                  $teamQuery = $mysqli->query($sql);
                  while ($row = $teamQuery->fetch_assoc()) {
                      echo '<option value="' . $row['teamID'] . '"' . (($game['homeID'] == $row['teamID']) ? ' selected' : '') . '>' . $row['city'] . ' ' . $row['team'] . '</option>';
                  }
                  $teamQuery->free();
                  ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="visitorID">Visitor Team:</label>
              <select name="visitorID" id="visitorID" class="form-select">
                  <?php
                  $teamQuery = $mysqli->query($sql);
                  while ($row = $teamQuery->fetch_assoc()) {
                      echo '<option value="' . $row['teamID'] . '"' . (($game['visitorID'] == $row['teamID']) ? ' selected' : '') . '>' . $row['city'] . ' ' . $row['team'] . '</option>';
                  }
                  $teamQuery->free();
                  ?>
              </select>
            </div>
            <div class="mb-3">
              <input type="submit" value="Save Changes" class="btn btn-success" />
              <a href="<?php echo $_SERVER['PHP_SELF']; ?>?week=<?php echo $game['weekNum']; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
      </div><!-- /.card-body -->
    </div><!-- /.card -->
  </div><!-- /.container -->

<?php elseif ($action == 'add'): ?>
  <!-- Add New Game Form Card -->
  <div class="container my-4">
    <div class="card">
      <div class="card-header">
        <h1 class="h4 mb-0">Add New Game</h1>
      </div>
      <div class="card-body">
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>?action=add_action" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>" />
            <div class="mb-3">
              <label for="weekNum">Week:</label>
              <input type="text" name="weekNum" id="weekNum" value="<?php echo $_GET['week'] ?? getCurrentWeek(); ?>" class="form-control week-input" />
            </div>
            <div class="mb-3">
              <label>Date/Time:</label>
              <div class="row g-2">
                <div class="col">
                  <input type="date" name="gameTimeEastern" class="form-control" required />
                </div>
                <div class="col">
                  <input type="time" name="gameTimeEastern2" class="form-control" required />
                </div>
              </div>
            </div>
            <div class="mb-3">
              <label for="homeID">Home Team:</label>
              <select name="homeID" id="homeID" class="form-select">
                  <?php
                  $sql = "SELECT * FROM " . DB_PREFIX . "teams ORDER BY city, team";
                  $teamQuery = $mysqli->query($sql);
                  while ($row = $teamQuery->fetch_assoc()) {
                      echo '<option value="' . $row['teamID'] . '">' . $row['city'] . ' ' . $row['team'] . '</option>';
                  }
                  $teamQuery->free();
                  ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="visitorID">Visitor Team:</label>
              <select name="visitorID" id="visitorID" class="form-select">
                  <?php
                  $teamQuery = $mysqli->query($sql);
                  while ($row = $teamQuery->fetch_assoc()) {
                      echo '<option value="' . $row['teamID'] . '">' . $row['city'] . ' ' . $row['team'] . '</option>';
                  }
                  $teamQuery->free();
                  ?>
              </select>
            </div>
            <div class="mb-3">
              <input type="submit" value="Add Game" class="btn btn-success" />
              <a href="<?php echo $_SERVER['PHP_SELF']; ?>?week=<?php echo $_GET['week'] ?? getCurrentWeek(); ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
      </div><!-- /.card-body -->
    </div><!-- /.card -->
  </div><!-- /.container -->

<?php else: ?>
  <!-- Schedule List Card -->
  <?php
  $week = (int)($_GET['week'] ?? getCurrentWeek());
  ?>
  <div class="container my-4">
    <h2 class="mb-3">Edit Schedule - Week <?php echo $week; ?></h2>
    <?php include('includes/week_nav.php'); ?>

    <!-- Add New Game Button -->
    <div class="mb-3">
      <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=add&week=<?php echo $week; ?>" class="btn btn-primary">Add New Game</a>
    </div>

    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped mb-0">
            <thead class="thead-light">
              <tr>
                <th>Home</th>
                <th>Visitor</th>
                <th>Time</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $stmt = $mysqli->prepare("SELECT * FROM " . DB_PREFIX . "schedule WHERE weekNum = ? ORDER BY gameTimeEastern");
              $stmt->bind_param("i", $week);
              $stmt->execute();
              $query = $stmt->get_result();
              while ($row = $query->fetch_assoc()) {
                  echo "<tr>
                          <td>" . htmlspecialchars($row['homeID']) . "</td>
                          <td>" . htmlspecialchars($row['visitorID']) . "</td>
                          <td>" . date('D n/j g:i a', strtotime($row['gameTimeEastern'])) . " ET</td>
                          <td>
                              <a href='schedule_edit.php?action=edit&id={$row['gameID']}' class='btn btn-sm btn-primary'>Edit</a>
                              <a href='schedule_edit.php?action=delete&id={$row['gameID']}&week={$week}&csrf_token=" . urlencode($csrf) . "' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this game?\");'>Delete</a>
                          </td>
                        </tr>";
              }
              $query->free();
              $stmt->close();
              ?>
            </tbody>
          </table>
        </div><!-- /.table-responsive -->
      </div><!-- /.card-body -->
    </div><!-- /.card -->
  </div><!-- /.container -->
<?php endif; ?>

<?php include('includes/footer.php'); ?>
