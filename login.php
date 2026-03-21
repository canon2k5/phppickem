<?php
require_once('includes/application_top.php');
require_once('includes/classes/login.php');

$login = new Login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header('Location: login.php?login=failed');
        exit;
    }
    $login->validate_password();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login — <?php echo APP_NAME; ?> <?php echo htmlspecialchars(SEASON_YEAR); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php if (defined('SITE_URL')): ?>
    <base href="<?php echo htmlspecialchars(SITE_URL); ?>">
  <?php endif; ?>

  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

  <!-- Font Awesome 6.4.0 for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <!-- Theme fonts: Oswald (headings) + Mulish (body) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Mulish:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/css/site.css?v=20">
</head>
<body class="page-auth page-login">
  <div class="container py-5">
    <div class="auth-shell">
      <div class="auth-header">
        <img src="images/phppickemlogo.png" alt="PHP Pick 'Em Logo" class="auth-logo" style="display:block;max-height:200px !important;width:auto !important;height:auto !important;margin:0 auto !important;">
        <div class="auth-subtitle"><?php echo APP_NAME; ?> <?php echo htmlspecialchars(SEASON_YEAR); ?></div>
      </div>
      <div class="login-card auth-card">
      <button id="themeToggle" class="btn btn-sm btn-outline-secondary theme-toggle d-none" type="button" aria-pressed="false" aria-hidden="true" tabindex="-1">
        <i class="fa-solid fa-moon"></i> Theme
      </button>
      <?php if (isset($_GET['login']) && $_GET['login'] == 'failed'): ?>
        <div class="alert alert-danger text-center">
          <i class="fa-solid fa-triangle-exclamation"></i> Login failed. Please check your credentials.
          <br><a href="password_reset.php" class="alert-link">Forgot your password?</a>
        </div>
      <?php endif; ?>

      <?php if (isset($_GET['signup']) && $_GET['signup'] == 'no'): ?>
        <div class="alert alert-warning text-center">
          <i class="fa-solid fa-triangle-exclamation"></i> Signups are currently disabled.
        </div>
      <?php endif; ?>

      <form action="login.php" method="post" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <h1><?php echo APP_NAME; ?> Login</h1>

        <!-- Username -->
        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
            <input
              type="text"
              name="username"
              placeholder="Username"
              class="form-control"
              required
              autofocus
              autocomplete="username"
            >
          </div>
        </div>

        <!-- Password -->
        <div class="mb-3">
          <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
            <input
              type="password"
              name="password"
              placeholder="Password"
              class="form-control"
              required
              autocomplete="current-password"
            >
          </div>
        </div>

        <button type="submit" class="btn btn-success btn-login w-100">Log In</button>

        <div class="mt-3 text-center">
          <?php if (defined('ALLOW_SIGNUP') && ALLOW_SIGNUP): ?>
            <div class="divider"><span>OR</span></div>
            <a href="signup.php" class="btn btn-outline-success btn-login w-100">
              <i class="fa-solid fa-user-plus"></i> Create New Account
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>
    <div class="auth-footer">
      <a href="password_reset.php">Forgot password</a>
    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function () {
      var storageKey = 'phppickem-theme';
      function getCookie(name) {
        var match = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
        return match ? decodeURIComponent(match.pop()) : null;
      }
      var theme = getCookie(storageKey);
      if (!theme) {
        theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
          ? 'dark'
          : 'light';
      }
      document.documentElement.setAttribute('data-theme', theme);
    })();
  </script>
</body>
</html>
