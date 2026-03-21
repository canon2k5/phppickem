<?php
// admin_reset_tables.php — Admin-only: truncate selected tables by purpose group

require_once('includes/application_top.php');
if (!$user->is_admin || strtolower($user->userName) !== 'admin') {
  http_response_code(403);
  exit('Forbidden');
}

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf_token'];

// --- Table groups ---
// nflp_teams and nflp_divisions are static reference data and are never reset.
$groups = [
  'new_season' => [
    'label'   => 'New Season Reset',
    'desc'    => 'Clears picks and schedule so a new season can begin. Users and settings are preserved.',
    'color'   => 'warning',
    'tables'  => [
      DB_PREFIX.'picks'        => 'All player picks for the season',
      DB_PREFIX.'picksummary'  => 'Weekly pick submission records',
      DB_PREFIX.'schedule'     => 'Game schedule and scores',
    ],
    'defaults' => [ DB_PREFIX.'picks', DB_PREFIX.'picksummary', DB_PREFIX.'schedule' ],
  ],
  'reinstall' => [
    'label'   => 'Full Reinstall',
    'desc'    => 'Wipes all user accounts including admin. The installer will allow a fresh setup once users are cleared.',
    'color'   => 'danger',
    'tables'  => [
      DB_PREFIX.'users' => 'All user accounts (including admin) — installer will re-run after this',
    ],
    'defaults' => [],
  ],
];

// Build flat whitelist of all allowed table names
$allowedTables = [];
foreach ($groups as $g) {
  foreach (array_keys($g['tables']) as $t) {
    $allowedTables[] = $t;
  }
}

// Fetch row counts for display
function getRowCount($mysqli, $table) {
  $t = $mysqli->real_escape_string($table);
  $r = $mysqli->query("SELECT COUNT(*) AS cnt FROM `$t`");
  if (!$r) return '—';
  $row = $r->fetch_assoc();
  $r->free();
  return number_format((int)$row['cnt']);
}

$messages = [];
$errors   = [];
$didRun   = false;
$previewSelected = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['csrf']) || !hash_equals($csrf, $_POST['csrf'])) {
    $errors[] = 'Invalid CSRF token.';
  }

  $selected = isset($_POST['tables']) && is_array($_POST['tables']) ? array_values($_POST['tables']) : [];
  $selected = array_values(array_intersect($selected, $allowedTables));

  $isDry = isset($_POST['dry_run']);

  if (empty($selected)) {
    $errors[] = 'No tables selected.';
  }
  if (empty($_POST['confirm']) || strtoupper(trim($_POST['confirm'])) !== 'RESET') {
    $errors[] = 'Type RESET to confirm.';
  }

  if (empty($errors)) {
    if ($isDry) {
      $previewSelected = $selected;
      $messages[] = ['ok', 'Dry run only — no changes were made.'];
    } else {
      $mysqli->begin_transaction();
      try {
        $mysqli->query("SET FOREIGN_KEY_CHECKS=0");
        foreach ($selected as $tbl) {
          $sql = "TRUNCATE TABLE `" . $mysqli->real_escape_string($tbl) . "`";
          if (!$mysqli->query($sql)) {
            throw new RuntimeException("Failed to truncate $tbl: " . $mysqli->error);
          }
          $messages[] = ['ok', "Truncated <code>" . htmlspecialchars($tbl) . "</code>"];
        }
        $mysqli->query("SET FOREIGN_KEY_CHECKS=1");
        $mysqli->commit();
        $didRun = true;
        if (in_array(DB_PREFIX.'users', $selected, true)) {
          $messages[] = ['info', 'User table cleared — <a href="install/">run the installer</a> to create a fresh admin account.'];
        }
      } catch (Throwable $e) {
        $mysqli->rollback();
        $errors[] = $e->getMessage();
      }
    }
  }
}

include('includes/header.php');
?>
<div class="container py-4">
  <div class="row">
    <div class="col-12 col-xl-9 mx-auto">
      <h1 class="h3 mb-1">Reset Tables</h1>
      <p class="text-muted mb-4">Select a group below based on what you need to do. Teams and divisions are never listed here — they are static reference data that does not change between seasons.</p>

      <?php foreach ($messages as [$type, $text]): ?>
        <div class="alert alert-<?php echo $type === 'ok' ? 'success' : ($type === 'info' ? 'info' : 'warning'); ?>">
          <?php echo $text; ?>
        </div>
      <?php endforeach; ?>
      <?php foreach ($errors as $e): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($e); ?></div>
      <?php endforeach; ?>

      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">

        <?php foreach ($groups as $groupKey => $group): ?>
          <div class="card mb-3">
            <div class="card-header bg-<?php echo $group['color']; ?> <?php echo $group['color'] === 'warning' ? 'text-dark' : 'text-white'; ?>">
              <strong><?php echo htmlspecialchars($group['label']); ?></strong>
            </div>
            <div class="card-body">
              <p class="text-muted small mb-3"><?php echo htmlspecialchars($group['desc']); ?></p>
              <?php foreach ($group['tables'] as $tbl => $desc): ?>
                <?php
                  $checked  = in_array($tbl, $group['defaults'], true) ? 'checked' : '';
                  $rowCount = getRowCount($mysqli, $tbl);
                  $id       = 't_' . htmlspecialchars($tbl);
                ?>
                <div class="border rounded p-2 mb-2">
                  <div class="form-check">
                    <input class="form-check-input table-box" type="checkbox"
                           name="tables[]" value="<?php echo htmlspecialchars($tbl); ?>"
                           id="<?php echo $id; ?>" <?php echo $checked; ?>>
                    <label class="form-check-label" for="<?php echo $id; ?>">
                      <code><?php echo htmlspecialchars($tbl); ?></code>
                      <span class="text-muted small ms-2"><?php echo htmlspecialchars($desc); ?></span>
                    </label>
                  </div>
                  <div class="small text-muted ms-4"><?php echo $rowCount; ?> rows</div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <div class="row g-3 mt-2">
          <div class="col-md-6">
            <label class="form-label">Type <strong>RESET</strong> to confirm</label>
            <input type="text" class="form-control" name="confirm" placeholder="RESET" autocomplete="off" required>
            <div class="form-text text-danger">This permanently deletes all rows in the selected tables.</div>
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="dry_run" id="dryrun" <?php echo !empty($previewSelected) ? 'checked' : ''; ?>>
              <label class="form-check-label" for="dryrun">Dry run (preview only — no changes)</label>
            </div>
          </div>
        </div>

        <div class="d-flex gap-2 mt-3">
          <button type="submit" class="btn btn-danger">Reset Selected Tables</button>
          <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </form>

      <?php if (!empty($previewSelected)): ?>
        <div class="card card-body mt-3">
          <h2 class="h6">Dry Run Preview</h2>
          <ul class="mb-0">
            <?php foreach ($previewSelected as $t): ?>
              <li>Would TRUNCATE <code><?php echo htmlspecialchars($t); ?></code></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php include('includes/footer.php'); ?>
