<?php
require_once('includes/application_top.php');

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$error_messages = [];
$success_message = '';
$user_data = [
    'firstname' => '',
    'lastname' => '',
    'email' => ''
];

// Fetch current user data
try {
    $stmt = $mysqli->prepare("SELECT firstname, lastname, email FROM " . DB_PREFIX . "users WHERE userID = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare user select statement");
    }

    $stmt->bind_param("i", $user->userID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $user_data = array_map('htmlspecialchars', $row);
    }
    $stmt->close();

} catch (Exception $e) {
    $error_messages[] = "Error loading user data: " . htmlspecialchars($e->getMessage());
}

// Handle form submission
if (isset($_POST['submit']) && $_POST['submit'] === 'Submit') {
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid form submission');
        }

        // Validate required fields
        $required_fields = ['firstname', 'lastname', 'email'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                $missing_fields[] = ucwords($field);
            }
        }
        
        if (!empty($missing_fields)) {
            throw new Exception('Please fill in the following fields: ' . implode(', ', $missing_fields));
        }

        // Validate email
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        if (!$email) {
            throw new Exception('Please enter a valid email address');
        }

        // Start transaction
        $mysqli->begin_transaction();

        // Prepare base update SQL
        $update_sql = "UPDATE " . DB_PREFIX . "users SET
                      firstname = ?,
                      lastname = ?,
                      email = ?";
        $param_types = "sss";
        $params = [
            trim($_POST['firstname']),
            trim($_POST['lastname']),
            $email
        ];

        // If password is being updated
        if (!empty($_POST['password'])) {
            if (strlen($_POST['password']) < 8) {
                throw new Exception('Password must be at least 8 characters long');
            }
            if ($_POST['password'] !== $_POST['password2']) {
                throw new Exception('Passwords do not match');
            }
            // Hash the new password
            $hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $update_sql .= ", password = ?";
            $param_types .= "s";
            $params[] = $hashedPassword;
        }

        $update_sql .= " WHERE userID = ?";
        $param_types .= "i";
        $params[] = $user->userID;

        // Prepare and execute update
        $stmt = $mysqli->prepare($update_sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare update statement");
        }
        $stmt->bind_param($param_types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update user account");
        }
        $stmt->close();
        $mysqli->commit();

        $success_message = 'Account updated successfully';

        // Update displayed data
        $user_data['firstname'] = htmlspecialchars($_POST['firstname']);
        $user_data['lastname']  = htmlspecialchars($_POST['lastname']);
        $user_data['email']     = htmlspecialchars($email);

    } catch (Exception $e) {
        $mysqli->rollback();
        $error_messages[] = $e->getMessage();

        // Preserve posted data on error
        if (isset($_POST['firstname'])) $user_data['firstname'] = htmlspecialchars($_POST['firstname']);
        if (isset($_POST['lastname']))  $user_data['lastname']  = htmlspecialchars($_POST['lastname']);
        if (isset($_POST['email']))     $user_data['email']     = htmlspecialchars($_POST['email']);
    }
}

include('includes/header.php');
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h1 class="card-title mb-0">Edit Account Details</h1>
                </div>
                <div class="card-body">
                    <?php
                    // Display error messages
                    if (!empty($error_messages)) {
                        foreach ($error_messages as $message) {
                            echo '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
                        }
                    }

                    // Display success message
                    if (!empty($success_message)) {
                        echo '<div class="alert alert-success">' . htmlspecialchars($success_message) . '</div>';
                    }
                    ?>

                    <form action="user_edit.php" method="post" name="edituser" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <div class="form-group mb-3">
                            <label for="firstname" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo $user_data['firstname']; ?>" required>
                            <div class="invalid-feedback">Please enter your first name</div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="lastname" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo $user_data['lastname']; ?>" required>
                            <div class="invalid-feedback">Please enter your last name</div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $user_data['email']; ?>" required>
                            <div class="invalid-feedback">Please enter a valid email address</div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="8">
                            <small class="form-text text-muted">Leave blank to keep current password</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="password2" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="password2" name="password2">
                            <div class="invalid-feedback">Passwords must match</div>
                        </div>

                        <div class="form-group mt-4 mb-2">
                            <button type="submit" name="submit" value="Submit" class="btn btn-primary btn-lg">
                                Update Account
                            </button>
                            <a href="./" class="btn btn-secondary btn-lg ms-2">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form.needs-validation');
    const password = document.getElementById('password');
    const password2 = document.getElementById('password2');

    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        // Custom password validation
        if (password.value || password2.value) {
            if (password.value.length < 8) {
                password.setCustomValidity('Password must be at least 8 characters');
                event.preventDefault();
            } else if (password.value !== password2.value) {
                password2.setCustomValidity('Passwords must match');
                event.preventDefault();
            } else {
                password.setCustomValidity('');
                password2.setCustomValidity('');
            }
        }
        form.classList.add('was-validated');
    }, false);

    // Clear custom validity on input
    password.addEventListener('input', function() {
        password.setCustomValidity('');
        password2.setCustomValidity('');
    });
    password2.addEventListener('input', function() {
        password.setCustomValidity('');
        password2.setCustomValidity('');
    });
});
</script>

<?php include('includes/footer.php'); ?>
