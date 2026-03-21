<?php
// schedule_import.php — Admin-only importer for NFL schedule (SQL or CSV)
// Place this file in your web root (same level as schedules.php).
// Requires: includes/application_top.php (provides $mysqli, $user, constants).

require_once('includes/application_top.php');
if (!$user->is_admin || strtolower($user->userName) !== 'admin') {
  http_response_code(403);
  exit('Forbidden');
}

include('includes/header.php');

$table = DB_PREFIX . 'schedule';
$messages = [];
$preview  = [];

// Load allowed team IDs from DB
function loadAllowedTeams($mysqli) {
  $out = [];
  $sql = "SELECT teamID FROM " . DB_PREFIX . "teams";
  if ($res = $mysqli->query($sql)) {
    while ($row = $res->fetch_assoc()) { $out[$row['teamID']] = true; }
    $res->free();
  }
  return $out;
}

$allowedTeams = loadAllowedTeams($mysqli);

function isValidDateTime($s) {
  if ($s === null || $s === '') return true; // allow NULL
  $d = DateTime::createFromFormat('Y-m-d H:i:s', $s);
  return $d && $d->format('Y-m-d H:i:s') === $s;
}

// Parse INSERT tuples from .sql dump (only reads INSERT INTO `...schedule` (... ) VALUES (...), (...); lines)
function parseSqlInsertTuples($sql, $tablePattern) {
  $rows = [];
  $sql = preg_replace('/\s+/', ' ', $sql);
  $pattern = '/INSERT\s+INTO\s+`?' . preg_quote($tablePattern, '/') . '`?\s*\((.*?)\)\s*VALUES\s*(.+?);/i';
  if (!preg_match_all($pattern, $sql, $blocks, PREG_SET_ORDER)) {
    return $rows;
  }
  foreach ($blocks as $b) {
    $cols = array_map('trim', explode(',', $b[1]));
    $valsBlob = $b[2];

    // Split top-level tuples
    $tuples = [];
    $depth = 0; $start = 0; $len = strlen($valsBlob);
    for ($i=0; $i<$len; $i++) {
      $ch = $valsBlob[$i];
      if ($ch === '(') { if ($depth++ === 0) $start = $i; }
      if ($ch === ')') { if (--$depth === 0) { $tuples[] = substr($valsBlob, $start, $i-$start+1); } }
    }

    foreach ($tuples as $t) {
      $inner = trim($t, '() ');
      $parts = [];
      $cur = ''; $inQ = false; $esc = false;
      $n = strlen($inner);
      for ($i=0; $i<$n; $i++) {
        $c = $inner[$i];
        if ($c === "\\" && !$esc) { $esc = true; $cur .= $c; continue; }
        if ($c === "'" && !$esc) { $inQ = !$inQ; $cur .= $c; continue; }
        if ($c === ',' && !$inQ) { $parts[] = $cur; $cur=''; continue; }
        $cur .= $c; $esc = false;
      }
      if ($cur !== '') $parts[] = $cur;

      $row = [];
      foreach ($parts as $p) {
        $p = trim($p);
        if (strcasecmp($p, 'NULL') === 0) { $row[] = null; continue; }
        if (strlen($p) >= 2 && $p[0] === "'" && substr($p,-1) === "'") {
          $row[] = str_replace("\\'", "'", substr($p,1,-1));
        } else {
          $row[] = $p;
        }
      }
      $assoc = [];
      foreach ($cols as $i=>$c) {
        $key = trim($c, " `");
        $assoc[$key] = $row[$i] ?? null;
      }
      $rows[] = $assoc;
    }
  }
  return $rows;
}

function parseCsvContent($csv) {
  $rows = [];
  $fh = fopen('php://memory','r+');
  fwrite($fh, $csv);
  rewind($fh);
  $header = fgetcsv($fh);
  if (!$header) return [];
  $header = array_map('trim', $header);
  while (($r = fgetcsv($fh)) !== false) {
    if (count($r) === 1 && trim($r[0])==='') continue;
    $rows[] = array_combine($header, array_map('trim', $r));
  }
  fclose($fh);
  return $rows;
}

function toIntOrNull($v) { return ($v === '' || $v === null) ? null : (int)$v; }

function validateAndNormalize($rows, $allowedTeams, &$errors) {
  $out = [];
  foreach ($rows as $idx => $r) {
    $line = $idx + 1;
    $row = [
      'gameID'         => isset($r['gameID']) ? (int)$r['gameID'] : null,
      'weekNum'        => isset($r['weekNum']) ? (int)$r['weekNum'] : null,
      'gameTimeEastern'=> $r['gameTimeEastern'] ?? null,
      'homeID'         => strtoupper(trim((string)($r['homeID'] ?? ''))),
      'homeScore'      => toIntOrNull($r['homeScore'] ?? null),
      'visitorID'      => strtoupper(trim((string)($r['visitorID'] ?? ''))),
      'visitorScore'   => toIntOrNull($r['visitorScore'] ?? null),
      'overtime'       => isset($r['overtime']) ? (int)$r['overtime'] : 0,
    ];
    $ok = true;
    if (!is_int($row['gameID']) || $row['gameID']<=0) { $errors[]="Row $line: invalid gameID"; $ok=false; }
    if (!is_int($row['weekNum']) || $row['weekNum']<1 || $row['weekNum']>NFL_TOTAL_WEEKS) { $errors[]="Row $line: invalid weekNum"; $ok=false; }
    if (!isValidDateTime($row['gameTimeEastern'])) { $errors[]="Row $line: invalid gameTimeEastern"; $ok=false; }
    if (!isset($allowedTeams[$row['homeID']])) { $errors[]="Row $line: invalid homeID '{$row['homeID']}'"; $ok=false; }
    if (!isset($allowedTeams[$row['visitorID']])) { $errors[]="Row $line: invalid visitorID '{$row['visitorID']}'"; $ok=false; }
    if (!in_array($row['overtime'], [0,1], true)) { $errors[]="Row $line: overtime must be 0/1"; $ok=false; }
    if ($ok) $out[] = $row;
  }
  return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $mode = $_POST['mode'] ?? 'replace'; // replace | upsert
  $dry  = isset($_POST['dry_run']);
  $recreate = isset($_POST['recreate']);

  if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $messages[] = ['err','Upload failed.'];
  } else {
    $name = $_FILES['file']['name'];
    $blob = file_get_contents($_FILES['file']['tmp_name']);
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    $tableNameInDump = $table; // Expect INSERT INTO `nflp_schedule`

    $mysqli->begin_transaction();
    try {
      if ($recreate) {
        $mysqli->query("DROP TABLE IF EXISTS `$table`");
        $mysqli->query("CREATE TABLE IF NOT EXISTS `$table` (
          `gameID` int(11) NOT NULL,
          `weekNum` int(11) NOT NULL,
          `gameTimeEastern` datetime DEFAULT NULL,
          `homeID` varchar(10) NOT NULL,
          `homeScore` int(11) DEFAULT NULL,
          `visitorID` varchar(10) NOT NULL,
          `visitorScore` int(11) DEFAULT NULL,
          `overtime` tinyint(1) NOT NULL DEFAULT 0,
          PRIMARY KEY (`gameID`),
          KEY `HomeID` (`homeID`),
          KEY `VisitorID` (`visitorID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
      }

      // Parse rows
      if ($ext === 'sql') {
        $rows = parseSqlInsertTuples($blob, $table);
      } elseif ($ext === 'csv') {
        $rows = parseCsvContent($blob);
      } else {
        throw new RuntimeException("Unsupported file type: .$ext (use .sql or .csv)");
      }

      if (empty($rows)) {
        throw new RuntimeException("No rows found to import. Ensure your .sql has INSERT INTO `" . $table . "` ... VALUES (...)");
      }

      $errors = [];
      $rows = validateAndNormalize($rows, $allowedTeams, $errors);
      if (!empty($errors)) {
        foreach ($errors as $e) $messages[] = ['err',$e];
        throw new RuntimeException("Validation failed.");
      }

      if ($dry) {
        $mysqli->rollback();
        $preview = array_slice($rows, 0, 10);
        $messages[] = ['ok', "Dry run OK. Rows ready: ".count($rows).". Nothing was written. Showing first 10 rows below."];
      } else {
        if ($mode === 'replace') {
          $mysqli->query("TRUNCATE TABLE `$table`");
        }
        $stmtSql = ($mode === 'upsert')
          ? "INSERT INTO `$table` (gameID,weekNum,gameTimeEastern,homeID,homeScore,visitorID,visitorScore,overtime)
             VALUES (?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE
               weekNum=VALUES(weekNum),
               gameTimeEastern=VALUES(gameTimeEastern),
               homeID=VALUES(homeID),
               homeScore=VALUES(homeScore),
               visitorID=VALUES(visitorID),
               visitorScore=VALUES(visitorScore),
               overtime=VALUES(overtime)"
          : "INSERT INTO `$table` (gameID,weekNum,gameTimeEastern,homeID,homeScore,visitorID,visitorScore,overtime)
             VALUES (?,?,?,?,?,?,?,?)";
        $stmt = $mysqli->prepare($stmtSql);
        if (!$stmt) throw new RuntimeException("Prepare failed: " . $mysqli->error);

        $writes = 0;
        foreach ($rows as $r) {
          $gameID = (int)$r['gameID'];
          $weekNum = (int)$r['weekNum'];
          $gte = $r['gameTimeEastern'] ?: null;
          $homeID = $r['homeID'];
          $homeScore = isset($r['homeScore']) ? $r['homeScore'] : null;
          $visitorID = $r['visitorID'];
          $visitorScore = isset($r['visitorScore']) ? $r['visitorScore'] : null;
          $overtime = (int)$r['overtime'];

          $stmt->bind_param('iissisii', $gameID, $weekNum, $gte, $homeID, $homeScore, $visitorID, $visitorScore, $overtime);
          if (!$stmt->execute()) {
            throw new RuntimeException("Insert failed for gameID $gameID: " . $stmt->error);
          }
          $writes += $stmt->affected_rows;
        }
        $stmt->close();
        $mysqli->commit();
        $messages[] = ['ok', "Imported ".count($rows)." rows using mode '$mode'."];
      }
    } catch (Throwable $e) {
      if ($mysqli->errno) {}
      $mysqli->rollback();
      $messages[] = ['err', "Import failed: ".$e->getMessage()];
    }
  }
}
?>
<div class="container my-4">
  <div class="card shadow-sm">
    <div class="card-body">
      <h1 class="h4 mb-3">Schedule Import</h1>

      <?php foreach ($messages as [$t,$m]): ?>
        <div class="alert alert-<?php echo $t==='ok'?'success':'danger'; ?>"><?php echo htmlspecialchars($m); ?></div>
      <?php endforeach; ?>

      <form method="post" enctype="multipart/form-data" class="mb-3">
        <div class="mb-3">
          <label class="form-label">Upload file (.sql or .csv)</label>
          <input class="form-control" type="file" name="file" accept=".sql,.csv" required>
          <div class="form-text">
            For .sql, only <code>INSERT INTO `<?php echo htmlspecialchars($table); ?>` ... VALUES (...)</code> statements are read.
          </div>
        </div>
        <div class="row g-3">
          <div class="col-auto">
            <label class="form-label">Mode</label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="mode" value="replace" id="modeReplace" checked>
              <label class="form-check-label" for="modeReplace">Replace (truncate then insert)</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="mode" value="upsert" id="modeUpsert">
              <label class="form-check-label" for="modeUpsert">Upsert by gameID</label>
            </div>
          </div>
          <div class="col-auto form-check">
            <input class="form-check-input" type="checkbox" name="dry_run" id="dryrun" value="1">
            <label class="form-check-label" for="dryrun">Dry run (validate only)</label>
          </div>
          <div class="col-auto form-check">
            <input class="form-check-input" type="checkbox" name="recreate" id="recreate" value="1">
            <label class="form-check-label" for="recreate">(Re)create table if missing</label>
          </div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">Import</button>
        <a class="btn btn-outline-secondary mt-3" href="schedules.php">Back to Schedules</a>
      </form>

      <?php if (!empty($preview)): ?>
        <h2 class="h6 mt-4">Preview (first 10 rows)</h2>
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead>
              <tr>
                <th>gameID</th><th>weekNum</th><th>gameTimeEastern</th>
                <th>homeID</th><th>homeScore</th><th>visitorID</th><th>visitorScore</th><th>overtime</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($preview as $r): ?>
                <tr>
                  <td><?php echo (int)$r['gameID']; ?></td>
                  <td><?php echo (int)$r['weekNum']; ?></td>
                  <td><?php echo htmlspecialchars($r['gameTimeEastern']); ?></td>
                  <td><?php echo htmlspecialchars($r['homeID']); ?></td>
                  <td><?php echo (is_null($r['homeScore'])?'':(int)$r['homeScore']); ?></td>
                  <td><?php echo htmlspecialchars($r['visitorID']); ?></td>
                  <td><?php echo (is_null($r['visitorScore'])?'':(int)$r['visitorScore']); ?></td>
                  <td><?php echo (int)$r['overtime']; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <p class="text-muted small mt-4">
        Note: This importer stores values as-is. This app expects Eastern time in
        <code>gameTimeEastern</code>.
      </p>
    </div>
  </div>
</div>
<?php include('includes/footer.php'); ?>
