<?php
header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?php echo APP_NAME; ?> <?php echo htmlspecialchars(SEASON_YEAR); ?></title>

  <!-- Base URL -->
  <base href="<?php echo htmlspecialchars(SITE_URL); ?>" />

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Font Awesome CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Theme fonts: Oswald (headings) + Mulish (body) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Mulish:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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

  <link rel="stylesheet" href="/css/site.css?v=20">

  <!-- Favicon -->
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />

</head>

<body>
  <div class="container">

    <header id="header" class="mb-3">
      <nav class="navbar navbar-expand-lg navbar-theme">
        <div class="container-fluid">
          <!-- Brand/logo -->
          <a class="navbar-brand d-flex align-items-center" href="./">
            <img src="images/phppickemlogo.png" alt="PHP Pick 'Em <?php echo htmlspecialchars(SEASON_YEAR); ?>" class="me-2 navbar-logo" />
          </a>

          <!-- Hamburger Toggler (Bootstrap 5 style) -->
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" 
            aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>

          <!-- Collapsible Content -->
          <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Left-aligned Nav Links -->
            <ul class="navbar-nav me-auto">
              <li class="nav-item <?php echo ($activeTab === 'home') ? 'active' : ''; ?>">
                <a class="nav-link" href="./">Home</a>
              </li>
              <?php if ($user->userName !== 'admin'): ?>
                <li class="nav-item">
                  <a class="nav-link" href="entry_form.php<?php echo (!empty($_GET['week']) ? '?week=' . (int)$_GET['week'] : ''); ?>">Entry Form</a>
                </li>
              <?php endif; ?>

              <li class="nav-item">
                <a class="nav-link" href="results.php<?php echo (!empty($_GET['week']) ? '?week=' . (int)$_GET['week'] : ''); ?>">Results</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="standings.php">Standings</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="teams.php">Teams</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="schedules.php">Schedules</a>
              </li>

              <?php if (!empty($_SESSION['logged']) && $_SESSION['logged'] === 'yes' && $user->is_admin): ?>
                <!-- Admin Dropdown -->
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" 
                     aria-expanded="false">
                    Admin
                  </a>
                  <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                    <li><h6 class="dropdown-header bg-body-tertiary text-body-secondary py-2">Basic functions</h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="scores.php">Enter Scores</a></li>
                    <li><a class="dropdown-item" href="send_email.php">Send Email</a></li>
                    <li><a class="dropdown-item" href="users.php">Update Users</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header bg-body-tertiary text-body-secondary py-2">Advanced functions</h6></li>
                    <li><hr class="dropdown-divider"></li>
		    <li><a class="dropdown-item" href="schedule_edit.php">Edit Schedule</a></li>
		    <li><a class="dropdown-item" href="email_templates.php">Email Templates</a></li>

		    <?php if (isset($user) && $user->is_admin && strtolower($user->userName) === 'admin'): ?>
	 	        <li><hr class="dropdown-divider"></li>
		        <li><h6 class="dropdown-header bg-body-tertiary text-body-secondary py-2">Database functions</h6></li>
		        <li><hr class="dropdown-divider"></li>
		        <li><a class="dropdown-item" href="schedule_import.php">Import Schedule</a></li>
		        <li><a class="dropdown-item" href="admin_reset_tables.php">Select and Reset Tables</a></li>
		    <?php endif; ?>

                  </ul>
                </li>
              <?php endif; ?>
            </ul>

            <!-- Right-aligned Nav Links -->
            <ul class="navbar-nav ms-auto align-items-lg-center">
              <li class="nav-item d-flex align-items-center">
                <button id="themeToggle" class="btn btn-sm btn-outline-secondary me-2" type="button" aria-pressed="false">
                  <i class="fa-solid fa-moon"></i> Theme
                </button>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="rules.php" title="Rules/Help">
                  <i class="fa-solid fa-book"></i> Rules/Help
                </a>
              </li>
              <?php if (!empty($_SESSION['loggedInUser'])): ?>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" 
                     aria-expanded="false">
                    <i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($_SESSION['loggedInUser']); ?>
                  </a>
                  <ul class="dropdown-menu" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="user_edit.php">My Account</a></li>
                    <li><a class="dropdown-item" href="logout.php">Logout <?php echo htmlspecialchars($user->userName); ?></a></li>
                  </ul>
                </li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
      </nav>
    </header>

    <div id="pageContent">
      <?php if ($user->is_admin && !empty($warnings) && is_array($warnings)): ?>
        <div id="warnings">
          <?php foreach ($warnings as $warning): ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($warning); ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
