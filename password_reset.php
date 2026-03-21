<?php
require('includes/application_top.php');
require_once __DIR__ . '/vendor/autoload.php';
require_once('includes/email_helper.php');

$display = '';
if (isset($_GET['reset']) && $_GET['reset'] === 'true') {
    $display = '<div class="alert alert-success text-center">Your password has been reset and sent to your email address.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $display = '<div class="alert alert-danger text-center">Invalid CSRF token. Please refresh the page and try again.</div>';
    } else {
    // Retrieve and sanitize inputs
    $firstname = $mysqli->real_escape_string(trim($_POST['firstname']));
    $email     = $mysqli->real_escape_string(trim($_POST['email']));

    // Find a matching user account by first name and email
    $sql = "SELECT * FROM " . DB_PREFIX . "users WHERE firstname = ? AND email = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $firstname, $email);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        die("Database error: " . $mysqli->error);
    }

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Generate a new random password (10 characters)
        $newPassword = randomString(10);
        // Hash with bcrypt
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        // Update password (clear legacy salt)
        $updateSql = "UPDATE " . DB_PREFIX . "users SET password = ?, salt = '' WHERE firstname = ? AND email = ?";
        $updateStmt = $mysqli->prepare($updateSql);
        if ($updateStmt) {
            $updateStmt->bind_param("sss", $hashedPassword, $firstname, $email);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            die("Update error: " . $mysqli->error);
        }

        // Send email
        $emailHelper = new EmailHelper();
        $subject = "Your " . APP_NAME . " Password Reset";
        $msg  = '<p>Your password for ' . APP_NAME . ' has been reset.</p>';
        $msg .= '<p><strong>Username:</strong> ' . htmlspecialchars($row['userName']) . '</p>';
        $msg .= '<p><strong>New Password:</strong> ' . htmlspecialchars($newPassword) . '</p>';
        $msg .= '<p><a href="' . SITE_URL . 'login.php">Click here to log in</a></p>';

        if ($emailHelper->sendEmail($email, $subject, $msg, $row['firstname'])) {
            header('Location: password_reset.php?reset=true');
            exit;
        } else {
            $display = '<div class="alert alert-danger text-center">Password was reset but email failed to send. Please contact support.</div>';
        }
    } else {
        $display = '<div class="alert alert-danger text-center">No account matched your details. Please try again.</div>';
    }

    if ($stmt) {
        $stmt->close();
    }
    }
}

// Helper function to generate a cryptographically secure random string
function randomString($length) {
    return substr(bin2hex(random_bytes((int)ceil($length / 2))), 0, $length);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo APP_NAME; ?> - Password Reset</title>
  <base href="<?php echo SITE_URL; ?>" />

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Mulish:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <link rel="stylesheet" href="/css/site.css?v=20">
</head>
<body class="page-auth page-password-reset">
<div class="container py-5">
  <div class="auth-shell">
    <div class="auth-header">
      <img src="images/phppickemlogo.png" alt="PHP Pick 'Em Logo" class="auth-logo" style="display:block;max-height:200px !important;width:auto !important;height:auto !important;margin:0 auto !important;">
      <div class="auth-subtitle"><?php echo APP_NAME; ?> <?php echo htmlspecialchars(SEASON_YEAR); ?></div>
    </div>
    <div class="form-password-reset auth-card">
    <h2>Password Reset</h2>

    <?php if (!empty($display)) echo $display; ?>

    <p class="text-center">
      Enter your first name and email address. A new password will be generated and sent to you.
    </p>

    <form action="password_reset.php" method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
      <div class="mb-3">
        <div class="input-group">
          <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
          <input id="firstname" type="text" name="firstname" class="form-control" placeholder="First Name" required autocomplete="given-name">
        </div>
      </div>
      <div class="mb-3">
        <div class="input-group">
          <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
          <input id="email" type="email" name="email" class="form-control" placeholder="Email Address" required autocomplete="email">
        </div>
      </div>
      <button type="submit" class="btn btn-success btn-login w-100">Reset Password</button>
    </form>
	  </div>
    <div class="auth-footer">
      <a href="login.php">Back to login</a>
    </div>
	</div>

<!-- Bootstrap 5 JS Bundle (single include) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
