<?php
session_start();

require_once('includes/application_top.php');

// Ensure signup is allowed
if (!ALLOW_SIGNUP) {
    header('Location: login.php?signup=no');
    exit;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize display message
$display = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token. Please refresh the page and try again.');
        }

        // Get and sanitize input
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname  = trim($_POST['lastname'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $username  = trim($_POST['username'] ?? '');
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        // Validate required fields
        $required_fields = ['firstname', 'lastname', 'email', 'username', 'password', 'password2'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = ucwords($field);
            }
        }
        
        if (!empty($missing_fields)) {
            throw new Exception('Please fill in the following fields: ' . implode(', ', $missing_fields));
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address, please check.');
        }

        // Validate passwords match
        if ($password !== $password2) {
            throw new Exception('Passwords do not match, please try again.');
        }

        // Validate password strength
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long.');
        }

        // Clean username
        $username_clean = str_replace(' ', '_', $username);

        // Check if username exists
        $stmt = $mysqli->prepare("SELECT userName FROM " . DB_PREFIX . "users WHERE userName = ?");
        if (!$stmt) {
            throw new Exception('Database error: ' . $mysqli->error);
        }
        
        $stmt->bind_param('s', $username_clean);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            throw new Exception('User already exists, please try another username.');
        }
        $stmt->close();

        // Check if email exists
        $stmt = $mysqli->prepare("SELECT email FROM " . DB_PREFIX . "users WHERE email = ?");
        if (!$stmt) {
            throw new Exception('Database error: ' . $mysqli->error);
        }
        
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            throw new Exception('Email address already exists. If this is your account, please log in or reset your password.');
        }
        $stmt->close();

        // Start transaction
        $mysqli->begin_transaction();

        try {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $emptySalt = ''; // Legacy column, empty since BCRYPT handles salting

            // Insert new user
            $sql = "INSERT INTO " . DB_PREFIX . "users 
                   (userName, password, salt, firstname, lastname, email, status) 
                   VALUES (?, ?, ?, ?, ?, ?, 1)";
            
            $stmt = $mysqli->prepare($sql);
            if (!$stmt) {
                throw new Exception('Database prepare failed: ' . $mysqli->error);
            }

            $stmt->bind_param('ssssss', 
                $username_clean,
                $hashedPassword,
                $emptySalt,    // Add empty salt for legacy column
                $firstname,
                $lastname,
                $email
            );    

            if (!$stmt->execute()) {
                throw new Exception('Database error: ' . $stmt->error);
            }

            $stmt->close();
            $mysqli->commit();

            // Log in the new user
            $_SESSION['logged'] = 'yes';
            $_SESSION['loggedInUser'] = $username_clean;
            
            // Redirect to success page
            header('Location: ./?login=success');
            exit;

        } catch (Exception $e) {
            $mysqli->rollback();
            throw new Exception('Account creation failed: ' . $e->getMessage());
        }

    } catch (Exception $e) {
        error_log("Signup error: " . $e->getMessage());
        $display = '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo APP_NAME; ?> Signup</title>
    <base href="<?php echo htmlspecialchars(SITE_URL); ?>" />
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Theme fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="/css/site.css?v=20">
</head>
<body class="page-auth page-signup">
<div class="container py-5">
    <div class="auth-shell">
        <div class="auth-header">
            <img src="images/phppickemlogo.png" alt="<?php echo APP_NAME; ?> Logo" class="auth-logo">
            <div class="auth-subtitle"><?php echo APP_NAME; ?> <?php echo htmlspecialchars(SEASON_YEAR); ?></div>
        </div>
        <form class="form-signup auth-card needs-validation" method="POST" action="signup.php" novalidate>
        <h1><?php echo APP_NAME; ?> Signup</h1>
        
        <?php if (!empty($display)) echo $display; ?>
        
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
        
        <div class="mb-3">
            <label for="firstname" class="form-label">First Name</label>
            <input type="text" 
                   id="firstname"
                   name="firstname" 
                   class="form-control" 
                   value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>" 
                   required 
                   autofocus />
            <div class="invalid-feedback">Please enter your first name</div>
        </div>

        <div class="mb-3">
            <label for="lastname" class="form-label">Last Name</label>
            <input type="text" 
                   id="lastname"
                   name="lastname" 
                   class="form-control" 
                   value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>" 
                   required />
            <div class="invalid-feedback">Please enter your last name</div>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" 
                   id="email"
                   name="email" 
                   class="form-control" 
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                   required />
            <div class="invalid-feedback">Please enter a valid email address</div>
        </div>

        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" 
                   id="username"
                   name="username" 
                   class="form-control" 
                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                   required />
            <div class="invalid-feedback">Please choose a username</div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" 
                   id="password"
                   name="password" 
                   class="form-control" 
                   required 
                   minlength="8" />
            <div class="invalid-feedback">Password must be at least 8 characters</div>
        </div>

        <div class="mb-4">
            <label for="password2" class="form-label">Confirm Password</label>
            <input type="password" 
                   id="password2"
                   name="password2" 
                   class="form-control" 
                   required />
            <div class="invalid-feedback">Passwords must match</div>
        </div>

        <button type="submit" name="submit" value="Submit" class="btn btn-primary w-100 btn-lg">
            Create Account
        </button>

    </form>
    <div class="auth-footer">
        <a href="login.php">Back to login</a>
    </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.needs-validation');
    const password = document.getElementById('password');
    const password2 = document.getElementById('password2');

    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }

        // Custom password validation
        if (password.value !== password2.value) {
            password2.setCustomValidity('Passwords must match');
            event.preventDefault();
        } else {
            password2.setCustomValidity('');
        }

        form.classList.add('was-validated');
    }, false);

    // Clear custom validity on input
    password2.addEventListener('input', function() {
        if (password.value === password2.value) {
            password2.setCustomValidity('');
        } else {
            password2.setCustomValidity('Passwords must match');
        }
    });
});
</script>

</body>
</html>
