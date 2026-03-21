<?php
/**
 * install.php — Robust installer for PHP Pick 'Em
 * Notes:
 *  - Assumes table prefix is hardcoded to 'nflp_' throughout the app.
 *    This installer DOES NOT attempt to change prefixes. It will refuse to proceed
 *    if DB_PREFIX is defined and not equal to 'nflp_'.
 *  - Uses POST + CSRF for the import step.
 *  - Uses utf8mb4 and mysqli::multi_query to execute the dump.
 *  - Creates/updates the admin account password provided in Step 3.
 *  - Drops an install.lock to prevent rerun.
 *
 * Place this file in /install/ alongside the SQL dump: phppickem.sql
 * Access via /install/install.php
 */

declare(strict_types=1);
session_start();
error_reporting(E_ALL);
ini_set('display_errors', '1');

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// --- Lock file path (written if permissions allow; DB check is the real gate) ---
$lockPath = __DIR__ . '/install.lock';

// --- Basic CSRF token ---
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf'];

// --- Load config & connect ---
$configPath = realpath(__DIR__ . '/../includes/config.php');
if (!$configPath || !file_exists($configPath)) {
  http_response_code(500);
  echo "Cannot find includes/config.php";
  exit;
}
require_once $configPath;
$env_missing = defined('ENV_MISSING') && ENV_MISSING;

// Enforce the hardcoded prefix 'nflp_'
if (defined('DB_PREFIX') && DB_PREFIX !== 'nflp_') {
  http_response_code(500);
  echo "DB_PREFIX must be 'nflp_' for this installer (current: '" . htmlspecialchars((string)DB_PREFIX, ENT_QUOTES, 'UTF-8') . "').";
  exit;
}

// Resolve DB constants across variants (env.php uses DB_HOST/DB_USER/DB_PASS/DB_NAME;
// config.php maps them to DB_HOSTNAME/DB_USERNAME/DB_PASSWORD/DB_DATABASE)
$db_host = defined('DB_HOSTNAME') ? DB_HOSTNAME : (defined('DB_HOST') ? DB_HOST : (defined('DB_SERVER') ? DB_SERVER : 'localhost'));
$db_user = defined('DB_USERNAME') ? DB_USERNAME : (defined('DB_USER') ? DB_USER : (defined('DB_SERVER_USERNAME') ? DB_SERVER_USERNAME : ''));
$db_pass = defined('DB_PASSWORD') ? DB_PASSWORD : (defined('DB_PASS') ? DB_PASS : (defined('DB_SERVER_PASSWORD') ? DB_SERVER_PASSWORD : ''));
$db_name = defined('DB_DATABASE') ? DB_DATABASE : (defined('DB_NAME') ? DB_NAME : '');

if ($env_missing) {
  $mysqli = null;
  $connect_error = 'Missing /home/secure/env.php';
} else {
  try {
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
    $connect_error = $mysqli->connect_errno ? $mysqli->connect_error : '';
    if (!$mysqli->connect_errno) { $mysqli->set_charset('utf8mb4'); }
  } catch (mysqli_sql_exception $e) {
    $mysqli = null;
    $connect_error = $e->getMessage();
  }
}

$provision_message = '';
$provision_status = '';

// --- Install lock: DB-based (file lock is a bonus if writable) ---
// $isInstalled = nflp_users exists AND has at least one admin user
$isInstalled = false;
if ($mysqli !== null && !$connect_error) {
  $r = @$mysqli->query("SELECT COUNT(*) AS cnt FROM nflp_users WHERE is_admin = 1 LIMIT 1");
  if ($r) { $row = $r->fetch_assoc(); $isInstalled = ((int)$row['cnt'] > 0); $r->free(); }
}

// Block re-running install steps 1–3 if already installed
if ($isInstalled && $step < 4) {
  echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Installer Locked</title>'
     . '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>'
     . '<body class="p-5">'
     . '<div class="text-center mb-4"><img src="../images/phppickemlogo.png" alt="PHP Pick\'Em" style="max-height:80px"></div>'
     . '<div class="alert alert-danger text-center"><strong><i class="fa-solid fa-triangle-exclamation"></i> Security Risk:</strong> Delete the <code>install</code> directory from your server immediately.</div>'
     . '<div class="card p-4 mx-auto" style="max-width:560px">'
     . '<h4 class="mb-3">Installer Locked</h4>'
     . '<p>The application is already installed.</p>'
     . '<ul>'
     . '<li>If you need to <strong>import a schedule</strong>, use the link below.</li>'
     . '<li>If you need to <strong>clear season data</strong> for a new season, use <code>admin_reset_tables.php</code> (truncates picks/schedule — does not affect users).</li>'
     . '<li>If you need to <strong>re-run the full installer</strong>, drop all <code>nflp_</code> tables via SSH or phpMyAdmin, then return here.</li>'
     . '</ul>'
     . '<div class="d-flex gap-2 mt-3">'
     . '<a href="?step=4" class="btn btn-primary">Import Schedule</a>'
     . '<a href="../login.php" class="btn btn-outline-secondary">Go to Login</a>'
     . '</div>'
     . '</div>'
     . '</body></html>';
  exit;
}

// Block step 4 if install hasn't been completed
if (!$isInstalled && $step === 4) {
  echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Not Installed</title>'
     . '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"></head>'
     . '<body class="p-5 text-center">'
     . '<p>Installation must be completed first.</p>'
     . '<a href="?step=1" class="btn btn-primary">Start Installation</a>'
     . '</body></html>';
  exit;
}

if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'provision_db') {
  if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
    $provision_status = 'error';
    $provision_message = 'Invalid request token. Please reload and try again.';
  } else {
    $root_host = trim($_POST['root_host'] ?? 'localhost');
    $root_user = trim($_POST['root_user'] ?? '');
    $root_pass = (string)($_POST['root_pass'] ?? '');
    $db_name = trim($_POST['db_name'] ?? '');
    $app_user = trim($_POST['app_user'] ?? '');
    $app_pass = (string)($_POST['app_pass'] ?? '');
    $app_host = trim($_POST['app_host'] ?? 'localhost');

    if ($root_user === '' || $db_name === '' || $app_user === '' || $app_pass === '') {
      $provision_status = 'error';
      $provision_message = 'Please fill in all required fields.';
    } else {
      try {
        $root = @new mysqli($root_host, $root_user, $root_pass, '');
        if ($root->connect_errno) {
          $provision_status = 'error';
          $provision_message = 'Root connection failed: ' . $root->connect_error;
        } else {
          $db_name_esc = $root->real_escape_string($db_name);
          $app_user_esc = $root->real_escape_string($app_user);
          $app_host_esc = $root->real_escape_string($app_host);
          $app_pass_esc = $root->real_escape_string($app_pass);

          $root->query("CREATE DATABASE IF NOT EXISTS `{$db_name_esc}`");
          $root->query("CREATE USER IF NOT EXISTS '{$app_user_esc}'@'{$app_host_esc}' IDENTIFIED BY '{$app_pass_esc}'");
          $root->query("GRANT ALL PRIVILEGES ON `{$db_name_esc}`.* TO '{$app_user_esc}'@'{$app_host_esc}'");
          $root->query("FLUSH PRIVILEGES");

          if ($root->errno) {
            $provision_status = 'error';
            $provision_message = 'Provisioning failed: ' . $root->error;
          } else {
            $provision_status = 'success';
            $provision_message = 'Database and user provisioned. Update /home/secure/env.php if needed, then reload this step.';
          }
          $root->close();
        }
      } catch (mysqli_sql_exception $e) {
        $provision_status = 'error';
        $provision_message = 'Provisioning failed: ' . $e->getMessage();
      }
    }
  }
}

// --- Step 4: Skip (write lock if possible, redirect to login) ---
if ($step === 4 && isset($_GET['skip'])) {
  @file_put_contents($lockPath, "installed: " . date('c') . "\n");
  header('Location: ../login.php');
  exit;
}

// --- Step 4: Schedule Import POST ---
$scheduleMessages = [];
$scheduleSuccess  = false;

if ($step === 4 && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
    $scheduleMessages[] = ['err', 'Invalid CSRF token.'];
  } elseif ($mysqli === null) {
    $scheduleMessages[] = ['err', 'No database connection.'];
  } else {
    $source = $_POST['source'] ?? '';
    if ($source === 'bundled') {
      $chosen = basename($_POST['bundled_file'] ?? '');
      if (!preg_match('/^nfl_schedule_\d{4}\.sql$/', $chosen)) {
        $scheduleMessages[] = ['err', 'Invalid file selection.'];
      } else {
        $filePath = __DIR__ . '/' . $chosen;
        if (!file_exists($filePath)) {
          $scheduleMessages[] = ['err', 'Bundled file not found: ' . $chosen];
        } else {
          $result = runSqlDump($mysqli, $filePath);
          if ($result['ok']) {
            @file_put_contents($lockPath, "installed: " . date('c') . "\n");
            $scheduleMessages[] = ['ok', 'Schedule imported from ' . $chosen . '.'];
            $scheduleSuccess = true;
          } else {
            $scheduleMessages[] = ['err', 'Import failed: ' . $result['error']];
          }
        }
      }
    } elseif ($source === 'upload') {
      if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $scheduleMessages[] = ['err', 'Upload failed or no file selected.'];
      } else {
        $result = runSqlDump($mysqli, $_FILES['file']['tmp_name']);
        if ($result['ok']) {
          @file_put_contents($lockPath, "installed: " . date('c') . "\n");
          $scheduleMessages[] = ['ok', 'Schedule imported from uploaded file.'];
          $scheduleSuccess = true;
        } else {
          $scheduleMessages[] = ['err', 'Import failed: ' . $result['error']];
        }
      }
    } else {
      $scheduleMessages[] = ['err', 'Please select a source.'];
    }
  }
}

// --- Helpers ---
function keepVersionedMySQLComments(string $sql): string {
  // Turn /*!40101 SET ... */ into "SET ..."
  return preg_replace_callback('/\/\*![0-9]{5}\s(.*?)\*\//s', function($m){ return $m[1]; }, $sql);
}
function stripSqlComments(string $sql): string {
  // Remove standard /* ... */ comments (but keep versioned via keepVersionedMySQLComments first)
  $sql = keepVersionedMySQLComments($sql);
  $sql = preg_replace('~/\*[^!].*?\*/~s', '', $sql);
  // Remove -- line comments
  $out = [];
  foreach (preg_split("/(\r\n|\n|\r)/", $sql) as $line) {
    $trim = ltrim($line);
    if (strpos($trim, '--') === 0) continue;
    $out[] = $line;
  }
  return implode("\n", $out);
}
function runSqlDump(mysqli $mysqli, string $path): array {
  if (!file_exists($path)) return ['ok' => false, 'error' => "Dump not found: $path"];
  $raw = file_get_contents($path);
  if ($raw === false) return ['ok' => false, 'error' => "Unable to read: $path"];
  // We do NOT alter table prefixes — the dump must already use nflp_
  $sql = stripSqlComments($raw);

  if (!$mysqli->multi_query($sql)) {
    return ['ok' => false, 'error' => $mysqli->error];
  }
  // Flush result sets
  do { if ($res = $mysqli->store_result()) { $res->free(); } } while ($mysqli->more_results() && $mysqli->next_result());
  if ($mysqli->errno) return ['ok' => false, 'error' => $mysqli->error];
  return ['ok' => true];
}
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function terminal(string $code, string $label = ''): string {
  $id = 'cb-' . substr(md5($code), 0, 8);
  $escaped = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
  $labelHtml = $label ? '<span class="terminal-label">' . h($label) . '</span>' : '';
  return <<<HTML
<div class="terminal-block" id="{$id}">
  <div class="terminal-bar">
    {$labelHtml}
    <button class="copy-btn" onclick="copyTerminal('{$id}')" title="Copy to clipboard">
      <i class="fa-regular fa-copy"></i> <span class="copy-label">Copy</span>
    </button>
  </div>
  <pre class="terminal-pre">{$escaped}</pre>
</div>
HTML;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Installer - PHP Pick 'Em</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Font Awesome 6 CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
  <link rel="stylesheet" href="/css/site.css?v=2">
  <style>
    .terminal-block { margin-bottom: 1.25rem; border-radius: 0.6rem; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,.35); }
    .terminal-bar { background: #2a2a2a; display: flex; align-items: center; justify-content: space-between; padding: .4rem .75rem; border-bottom: 1px solid #444; }
    .terminal-label { color: #aaa; font-size: .78rem; font-family: monospace; }
    .terminal-pre { background: #1a1a1a; color: #e8e8e8; margin: 0; padding: 1rem 1.1rem; font-size: .88rem; line-height: 1.6; white-space: pre-wrap; word-break: break-all; border-radius: 0; }
    .copy-btn { background: none; border: 1px solid #555; color: #ccc; border-radius: .35rem; padding: .2rem .6rem; font-size: .78rem; cursor: pointer; transition: all .15s; display: flex; align-items: center; gap: .3rem; }
    .copy-btn:hover { background: #444; color: #fff; border-color: #888; }
    .copy-btn.copied { border-color: #28a745; color: #28a745; }
  </style>
</head>
<body class="page-installer">
<div id="pageContent">
  <div class="text-center">
    <img src="../images/phppickemlogo.png" alt="PHP Pick 'Em Logo" class="installer-logo img-fluid">
  </div>
  <h1 class="text-center mb-4">PHP Pick 'Em Installation</h1>

  <?php if ($step === 1): ?>
    <div class="step"><h2>&gt; Step 1 - Before You Begin</h2></div>
    <div class="step"><h3>Step 2 - Install Database</h3></div>
    <div class="step"><h3>Step 3 - Complete</h3></div>

    <?php if ($env_missing): ?>
      <div class="alert alert-danger">
        <strong><i class="fa-solid fa-triangle-exclamation"></i> Environment file not found:</strong>
        <code>/home/secure/env.php</code> is missing. Complete the steps below via SSH, then reload this page.
      </div>
    <?php else: ?>
      <div class="alert alert-success">
        <strong><i class="fa-solid fa-circle-check"></i> Environment file found:</strong>
        <code>/home/secure/env.php</code> is present and loaded.
      </div>
    <?php endif; ?>

    <h4 class="mt-4">1. Create the environment file <small class="text-muted">(via SSH)</small></h4>
    <p>Credentials are stored <strong>outside the web root</strong> for security. The installer cannot create this file for you — it must be created over SSH.</p>

    <p><strong>Create the directory (if it doesn't exist):</strong></p>
    <?php echo terminal('sudo mkdir -p /home/secure', 'bash'); ?>

    <p><strong>Create the file with your database credentials:</strong></p>
    <?php echo terminal('sudo nano /home/secure/env.php', 'bash'); ?>

    <p><strong>Paste the following — replace values with your own:</strong></p>
    <?php echo terminal("<?php\ndefine('DB_HOST', 'localhost');        // Database server hostname\ndefine('DB_USER', 'your_db_user');     // Database username\ndefine('DB_PASS', 'your_db_password'); // Database password\ndefine('DB_NAME', 'your_db_name');     // Database name\n\n// BallDontLie API key — required for in-app schedule import and buildSchedule.php\n// Get your free key at https://www.balldontlie.io\ndefine('BALLDONTLIE_API_KEY', 'your_api_key_here');", '/home/secure/env.php'); ?>

    <p><strong>Set ownership and permissions</strong> so the web server can read it but nothing else can. Run the block that matches your OS:</p>

    <p class="mb-1"><strong>RHEL / AlmaLinux / Rocky / CentOS</strong> <span class="text-muted">(Apache — web server user is <code>apache</code>)</span></p>
    <?php echo terminal("sudo chown root:apache /home/secure/env.php\nsudo chmod 640 /home/secure/env.php", 'bash'); ?>

    <p class="mb-1"><strong>Debian / Ubuntu</strong> <span class="text-muted">(Apache — web server user is <code>www-data</code>)</span></p>
    <?php echo terminal("sudo chown root:www-data /home/secure/env.php\nsudo chmod 640 /home/secure/env.php", 'bash'); ?>

    <div class="alert alert-info mt-2">
      <strong><i class="fa-solid fa-circle-info"></i> Using nginx or a different web server?</strong>
      Replace the group name in <code>chown</code> with your web server's user. Not sure what it is? Run:
      <?php echo terminal("ps aux | grep -E 'apache|httpd|nginx|www-data' | grep -v grep", 'bash'); ?>
    </div>

    <hr class="my-4">

    <h4>2. Verify <code>includes/config.php</code></h4>
    <p>Open <code>includes/config.php</code> and confirm the table prefix is unchanged — the installer will update the other values in Step 2:</p>
    <?php echo terminal("define('DB_PREFIX', 'nflp_');  // do not change", 'includes/config.php'); ?>

    <div class="alert alert-info mt-3">
      <strong>Note:</strong> The table prefix is hardcoded to <code>nflp_</code> throughout the app. This installer will not work with any other prefix.
    </div>

    <div class="text-center mt-4">
      <a class="btn btn-primary btn-lg" href="?step=2">Continue to Step 2 &rarr;</a>
    </div>

  <?php elseif ($step === 2): ?>

    <div class="step"><h3><i class="fa-solid fa-circle-check text-success icon"></i> Step 1 - Edit Config File</h3></div>
    <div class="step"><h2>&gt; Step 2 - Install Database</h2></div>
    <div class="step"><h3>Step 3 - Complete</h3></div>

    <?php if ($provision_message !== ''): ?>
      <div class="alert alert-<?php echo $provision_status === 'success' ? 'success' : 'danger'; ?> text-center">
        <?php echo h($provision_message); ?>
      </div>
    <?php endif; ?>

    <?php if ($env_missing): ?>
      <div class="alert alert-warning text-center">
        <strong>Missing environment file:</strong> create <code>/home/secure/env.php</code> then reload this step.
      </div>
      <div class="text-center">
        <a class="btn btn-secondary" href="?step=1">Back to step 1</a>
      </div>
    <?php elseif ($connect_error): ?>
      <div class="alert alert-danger text-center">
        <strong>Database connection failed:</strong> <?php echo h($connect_error); ?>
      </div>
      <div class="text-center">
        <a class="btn btn-warning" href="?step=2">Try Again</a>
      </div>
    <?php else: ?>
      <?php if (defined('DB_PREFIX') && DB_PREFIX !== 'nflp_'): ?>
        <div class="alert alert-danger">
          DB_PREFIX is <code><?php echo h(DB_PREFIX); ?></code>. This installer requires <code>nflp_</code>. Update your config and reload.
        </div>
        <div class="text-center">
          <a class="btn btn-secondary" href="?step=2">Recheck</a>
        </div>
      <?php else: ?>
        <div class="alert alert-success text-center small">
          <i class="fa-solid fa-circle-check icon"></i> Database Connected
        </div>
        <p class="text-muted text-center">The installer will import <code>phppickem.sql</code> using utf8mb4 and create required tables.</p>

        <form method="post" action="?step=3" class="mt-3">
          <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
          <div class="row justify-content-center g-3">
            <div class="col-md-8">
              <h5 class="mb-3">Site Settings</h5>

              <label class="form-label">Site URL <span class="text-danger">*</span></label>
              <input type="url" name="site_url" class="form-control mb-1"
                placeholder="http://your-domain.com/"
                value="<?php echo h(defined('SITE_URL') ? SITE_URL : 'http://'); ?>" required>
              <div class="form-text mb-3">Full URL to your Pick 'Em install — trailing slash required.</div>

              <label class="form-label">Season Year <span class="text-danger">*</span></label>
              <input type="number" name="season_year" class="form-control mb-1"
                placeholder="2025" min="2020" max="2099"
                value="<?php echo h(defined('SEASON_YEAR') ? SEASON_YEAR : date('Y')); ?>" required>
              <div class="form-text mb-3">The NFL season start year (e.g. 2025 for the 2025–26 season).</div>

              <label class="form-label">Allow Public Sign-Up</label>
              <select name="allow_signup" class="form-select mb-1">
                <option value="0" <?php echo (!defined('ALLOW_SIGNUP') || !ALLOW_SIGNUP) ? 'selected' : ''; ?>>No — invite only (recommended)</option>
                <option value="1" <?php echo (defined('ALLOW_SIGNUP') && ALLOW_SIGNUP) ? 'selected' : ''; ?>>Yes — anyone can register</option>
              </select>
              <div class="form-text mb-4">Controls whether a Register link is shown on the login page.</div>

              <hr>
              <h5 class="mb-3">Admin Account</h5>

              <label class="form-label">Admin Password <span class="text-danger">*</span></label>
              <input type="password" name="admin_password" class="form-control mb-3" placeholder="Choose a strong password" required>

              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="ack" required>
                <label class="form-check-label" for="ack">
                  I understand this will create/overwrite database tables.
                </label>
              </div>
            </div>
          </div>
          <div class="text-center">
            <button class="btn btn-primary btn-lg">Continue to Step 3 &rarr;</button>
          </div>
        </form>
      <?php endif; ?>
    <?php endif; ?>

    <hr>
    <h5 class="mt-4">Advanced: Provision Database/User (MariaDB root)</h5>
    <p class="text-muted">Optional. Use this only if you want the installer to create the database and user. You can skip this if you already created them.</p>
    <form method="post" action="?step=2" class="mt-3">
      <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
      <input type="hidden" name="action" value="provision_db">
      <div class="row">
        <div class="col-md-6">
          <label class="form-label">MariaDB root host</label>
          <input type="text" name="root_host" class="form-control" value="<?php echo h($db_host ?: 'localhost'); ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">MariaDB root user</label>
          <input type="text" name="root_user" class="form-control" placeholder="root" required>
        </div>
        <div class="col-md-6 mt-3">
          <label class="form-label">MariaDB root password</label>
          <input type="password" name="root_pass" class="form-control" autocomplete="new-password">
        </div>
        <div class="col-md-6 mt-3">
          <label class="form-label">Database name</label>
          <input type="text" name="db_name" class="form-control" value="<?php echo h($db_name); ?>" required>
        </div>
        <div class="col-md-6 mt-3">
          <label class="form-label">App DB user</label>
          <input type="text" name="app_user" class="form-control" value="<?php echo h($db_user); ?>" required>
        </div>
        <div class="col-md-6 mt-3">
          <label class="form-label">App DB password</label>
          <input type="password" name="app_pass" class="form-control" autocomplete="new-password" required>
        </div>
        <div class="col-md-6 mt-3">
          <label class="form-label">App DB host</label>
          <input type="text" name="app_host" class="form-control" value="<?php echo h($db_host ?: 'localhost'); ?>" required>
        </div>
      </div>
      <div class="text-center mt-3">
        <button class="btn btn-outline-primary">Create DB and User</button>
      </div>
    </form>

  <?php elseif ($step === 3): ?>

    <div class="step"><h3><i class="fa-solid fa-circle-check text-success icon"></i> Step 1 - Edit Config File</h3></div>
    <div class="step"><h3><i class="fa-solid fa-circle-check text-success icon"></i> Step 2 - Install Database</h3></div>
    <div class="step"><h2>&gt; Step 3 - Complete</h2></div>

    <?php
      if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($csrf, $_POST['csrf'] ?? '')) {
        echo '<div class="alert alert-danger">Invalid request. Please start again.</div>';
      } elseif ($connect_error) {
        echo '<div class="alert alert-danger">Lost DB connection: ' . h($connect_error) . '</div>';
      } else {
        // Write site settings to config.php
        $configFile = realpath(__DIR__ . '/../includes/config.php');
        $configWarnings = [];
        if ($configFile && is_writable($configFile)) {
          $cfg = file_get_contents($configFile);
          $siteUrl    = rtrim(trim($_POST['site_url'] ?? ''), '/') . '/';
          $seasonYear = (int)($_POST['season_year'] ?? date('Y'));
          $allowSignup = ($_POST['allow_signup'] ?? '0') === '1' ? 'true' : 'false';
          $cfg = preg_replace("/define\('SITE_URL',\s*'[^']*'\)/",   "define('SITE_URL', '" . addslashes($siteUrl) . "')", $cfg);
          $cfg = preg_replace("/define\('SEASON_YEAR',\s*'[^']*'\)/", "define('SEASON_YEAR', '" . $seasonYear . "')",          $cfg);
          $cfg = preg_replace("/define\('ALLOW_SIGNUP',\s*(true|false)\)/", "define('ALLOW_SIGNUP', " . $allowSignup . ")",    $cfg);
          file_put_contents($configFile, $cfg);
        } else {
          $configWarnings[] = 'Could not write to <code>includes/config.php</code> — set SITE_URL, SEASON_YEAR, and ALLOW_SIGNUP manually.';
        }

        $dumpFile = __DIR__ . '/phppickem.sql';
        $result = runSqlDump($mysqli, $dumpFile);
        if (!$result['ok']) {
          echo '<div class="alert alert-danger">Import failed: ' . h($result['error']) . '</div>';
        } else {
          // Set or create admin password
          $adminPass = trim($_POST['admin_password'] ?? '');
          if ($adminPass === '') { $adminPass = bin2hex(random_bytes(6)); }
          $hash = password_hash($adminPass, PASSWORD_BCRYPT);
          $table = 'nflp_users';

          // Update existing admin; else insert
          $stmt = $mysqli->prepare("UPDATE `$table` SET `password`=?, `salt`='' WHERE `userName`='admin'");
          if ($stmt) {
            $stmt->bind_param('s', $hash);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
              $stmt->close();
              $stmt = $mysqli->prepare("INSERT INTO `$table` (`userName`, `password`, `firstname`, `lastname`, `email`, `is_admin`) VALUES ('admin', ?, 'Admin', '', '', 1)");
              if ($stmt) { $stmt->bind_param('s', $hash); $stmt->execute(); $stmt->close(); }
            } else {
              $stmt->close();
            }
          }

          // Drop lock file
          @file_put_contents($lockPath, "installed: " . date('c') . "
");

          // Surface any config.php write warnings
          foreach ($configWarnings as $w) {
            echo '<div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> ' . $w . '</div>';
          }

          // Success UI
          echo '<div class="alert alert-success text-center">';
          echo '<i class="fa-solid fa-circle-check"></i> <strong>Installation complete.</strong>';
          echo '</div>';
          echo '<div class="text-center mb-3"><div class="alert alert-info d-inline-block text-start">';
          echo '<i class="fa-solid fa-key"></i> Admin username: <strong>admin</strong><br>Temporary password: <strong>' . h($adminPass) . '</strong>';
          echo '</div></div>';
          echo '<div class="text-center mt-3 d-flex gap-2 justify-content-center">';
          echo '<a class="btn btn-primary btn-lg" href="?step=4">Next: Import Schedule &rarr;</a>';
          echo '<a class="btn btn-outline-secondary btn-lg" href="../login.php">Skip &mdash; Go to Login</a>';
          echo '</div>';
        }
      }
    ?>

  <?php elseif ($step === 4): ?>

    <div class="step"><h3><i class="fa-solid fa-circle-check text-success icon"></i> Step 1 - Edit Config File</h3></div>
    <div class="step"><h3><i class="fa-solid fa-circle-check text-success icon"></i> Step 2 - Install Database</h3></div>
    <div class="step"><h3><i class="fa-solid fa-circle-check text-success icon"></i> Step 3 - Complete</h3></div>
    <div class="step"><h2>&gt; Step 4 - Import Schedule</h2></div>

    <?php foreach ($scheduleMessages as [$t, $m]): ?>
      <div class="alert alert-<?php echo $t === 'ok' ? 'success' : 'danger'; ?>"><?php echo h($m); ?></div>
    <?php endforeach; ?>

    <div class="alert alert-danger"><strong><i class="fa-solid fa-triangle-exclamation"></i> Delete the <code>install</code> directory from your server before going live.</strong> Leaving it accessible is a security risk.</div>

    <?php if ($scheduleSuccess): ?>
      <div class="text-center mt-3">
        <a class="btn btn-primary btn-lg" href="../login.php">Go to Login &rarr;</a>
      </div>
    <?php else: ?>
      <?php
        $bundledFiles = glob(__DIR__ . '/nfl_schedule_*.sql') ?: [];
        $bundledFiles = array_map('basename', $bundledFiles);
        sort($bundledFiles);
      ?>
      <p class="text-muted">The database has no games yet. Import the season schedule below.</p>
      <form method="post" enctype="multipart/form-data" class="card p-3 mb-3">
        <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">

        <?php if (!empty($bundledFiles)): ?>
          <div class="mb-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="source" value="bundled" id="srcBundled" checked>
              <label class="form-check-label fw-semibold" for="srcBundled">Use bundled file</label>
            </div>
            <div class="ms-4 mt-1">
              <select name="bundled_file" class="form-select form-select-sm w-auto">
                <?php foreach ($bundledFiles as $f): ?>
                  <option value="<?php echo h($f); ?>"><?php echo h($f); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="source" value="upload" id="srcUpload">
              <label class="form-check-label fw-semibold" for="srcUpload">Choose a different file (.sql or .csv)</label>
            </div>
            <div class="ms-4 mt-1">
              <input type="file" name="file" id="fileInput" class="form-control form-control-sm w-auto" accept=".sql,.csv" disabled>
            </div>
          </div>
        <?php else: ?>
          <input type="hidden" name="source" value="upload">
          <div class="mb-3">
            <label class="form-label fw-semibold">Upload schedule file (.sql or .csv)</label>
            <input type="file" name="file" id="fileInput" class="form-control" accept=".sql,.csv" required>
          </div>
        <?php endif; ?>

        <div class="d-flex gap-2 mt-2">
          <button type="submit" class="btn btn-primary">Import Schedule</button>
          <a href="?step=4&skip=1" class="btn btn-outline-secondary">Skip — Go to Login</a>
        </div>
      </form>
      <script>
        (function() {
          var radios   = document.querySelectorAll('input[name="source"]');
          var fileInput = document.getElementById('fileInput');
          if (!radios.length || !fileInput) return;
          function toggle() {
            var isUpload = document.getElementById('srcUpload')?.checked;
            fileInput.disabled = !isUpload;
            fileInput.required = !!isUpload;
          }
          radios.forEach(function(r) { r.addEventListener('change', toggle); });
          toggle();
        })();
      </script>
    <?php endif; ?>

  <?php endif; ?>

  <div class="clearfix"></div>
</div>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function copyTerminal(id) {
  const block = document.getElementById(id);
  const text = block.querySelector('.terminal-pre').innerText;

  function onSuccess() {
    const btn = block.querySelector('.copy-btn');
    const label = btn.querySelector('.copy-label');
    const icon = btn.querySelector('i');
    btn.classList.add('copied');
    icon.className = 'fa-solid fa-check';
    label.textContent = 'Copied!';
    setTimeout(function() {
      btn.classList.remove('copied');
      icon.className = 'fa-regular fa-copy';
      label.textContent = 'Copy';
    }, 2000);
  }

  // Modern clipboard API (requires HTTPS or localhost)
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(text).then(onSuccess).catch(function() { fallback(); });
  } else {
    fallback();
  }

  function fallback() {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0';
    document.body.appendChild(ta);
    ta.focus();
    ta.select();
    try { document.execCommand('copy'); onSuccess(); } catch(e) {}
    document.body.removeChild(ta);
  }
}
</script>
</body>
</html>
