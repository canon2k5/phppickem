<?php
require_once('includes/application_top.php');

if (!$user->is_admin) {
    header('Location: ./');
    exit;
}

$loggedInUserID = $user->userID;
$action         = $_GET['action'] ?? '';
$display        = '';
$csrf           = $_SESSION['csrf_token'];

// Handle actions
switch ($action) {
    case 'add_action':
        if (!isset($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) {
            $display = '<div class="alert alert-danger">Invalid CSRF token. Please refresh the page and try again.</div>';
            $action = 'add';
            break;
        }
        // Sanitize inputs
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname  = trim($_POST['lastname'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $userName  = trim($_POST['userName'] ?? '');
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $is_admin  = isset($_POST['is_admin']) ? 1 : 0;

        // Check required fields
        if (empty($firstname) || empty($lastname) || empty($userName) || empty($email) || empty($password)) {
            $display = '<div class="alert alert-danger">All fields are required.</div>';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $display = '<div class="alert alert-danger">Invalid email address, please try again.</div>';
        } elseif ($password !== $password2) {
            $display = '<div class="alert alert-danger">Passwords do not match.</div>';
        } else {
            // Check if username already exists
            $stmt = $mysqli->prepare("SELECT userName FROM " . DB_PREFIX . "users WHERE userName = ?");
            $stmt->bind_param("s", $userName);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $display = '<div class="alert alert-danger">Username already exists.</div>';
            } else {
                // Hash the password using one-way hashing (bcrypt)
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt_insert = $mysqli->prepare("INSERT INTO " . DB_PREFIX . "users (userName, password, salt, firstname, lastname, email, status, is_admin) VALUES (?, ?, '', ?, ?, ?, 1, ?)");
                $stmt_insert->bind_param("sssssi", $userName, $hashedPassword, $firstname, $lastname, $email, $is_admin);
                if ($stmt_insert->execute()) {
                    $display = '<div class="alert alert-success">User ' . htmlspecialchars($userName) . ' added successfully.</div>';
                } else {
                    $display = '<div class="alert alert-danger">Error adding user: ' . htmlspecialchars($stmt_insert->error) . '</div>';
                }
                $stmt_insert->close();
            }
            $stmt->close();
        }
        $action = 'add'; // show the add form again
        break;

    case 'edit_action':
        if (!isset($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) {
            $display = '<div class="alert alert-danger">Invalid CSRF token. Please refresh the page and try again.</div>';
            $action = 'edit';
            break;
        }
        $userID    = (int)($_POST['userID'] ?? 0);
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname  = trim($_POST['lastname'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $userName  = trim($_POST['userName'] ?? '');
        $is_admin  = isset($_POST['is_admin']) ? 1 : 0;

        if (empty($firstname) || empty($lastname) || empty($userName) || empty($email)) {
            $display = '<div class="alert alert-danger">All fields are required.</div>';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $display = '<div class="alert alert-danger">Invalid email address.</div>';
        } else {
            // Don't allow changing admin status for userID 1 or logged in user
            if ($userID === 1 || $userID === $loggedInUserID) {
                $stmt = $mysqli->prepare("UPDATE " . DB_PREFIX . "users SET firstname = ?, lastname = ?, email = ?, userName = ? WHERE userID = ?");
                $stmt->bind_param("ssssi", $firstname, $lastname, $email, $userName, $userID);
            } else {
                $stmt = $mysqli->prepare("UPDATE " . DB_PREFIX . "users SET firstname = ?, lastname = ?, email = ?, userName = ?, is_admin = ? WHERE userID = ?");
                $stmt->bind_param("ssssii", $firstname, $lastname, $email, $userName, $is_admin, $userID);
            }
            if ($stmt->execute()) {
                $display = '<div class="alert alert-success">User updated successfully.</div>';
            } else {
                $display = '<div class="alert alert-danger">Error updating user: ' . htmlspecialchars($stmt->error) . '</div>';
            }
            $stmt->close();
        }
        $action = 'edit';
        break;

    case 'toggle':
        if (!isset($_GET['csrf_token']) || !hash_equals($csrf, $_GET['csrf_token'])) {
            $display = '<div class="alert alert-danger">Invalid CSRF token. Please refresh the page and try again.</div>';
            break;
        }
        $userID = (int)($_GET['id'] ?? 0);
        if ($userID !== 1 && $userID !== $loggedInUserID) {
            $stmt = $mysqli->prepare("SELECT status FROM " . DB_PREFIX . "users WHERE userID = ?");
            $stmt->bind_param("i", $userID);
            $stmt->execute();
            $stmt->bind_result($status);
            $stmt->fetch();
            $stmt->close();
            $newStatus = $status ? 0 : 1;
            $stmt_toggle = $mysqli->prepare("UPDATE " . DB_PREFIX . "users SET status = ? WHERE userID = ?");
            $stmt_toggle->bind_param("ii", $newStatus, $userID);
            $stmt_toggle->execute();
            $stmt_toggle->close();
        }
        header('Location: users.php');
        exit;
        break;

    case 'toggle_admin':
        if (!isset($_GET['csrf_token']) || !hash_equals($csrf, $_GET['csrf_token'])) {
            $display = '<div class="alert alert-danger">Invalid CSRF token. Please refresh the page and try again.</div>';
            break;
        }
        $userID = (int)($_GET['id'] ?? 0);
        if ($userID !== 1 && $userID !== $loggedInUserID) {
            $stmt = $mysqli->prepare("SELECT is_admin FROM " . DB_PREFIX . "users WHERE userID = ?");
            $stmt->bind_param("i", $userID);
            $stmt->execute();
            $stmt->bind_result($is_admin);
            $stmt->fetch();
            $stmt->close();
            
            $newAdminStatus = $is_admin ? 0 : 1;
            $stmt_toggle = $mysqli->prepare("UPDATE " . DB_PREFIX . "users SET is_admin = ? WHERE userID = ?");
            $stmt_toggle->bind_param("ii", $newAdminStatus, $userID);
            $stmt_toggle->execute();
            $stmt_toggle->close();
        }
        header('Location: users.php');
        exit;
        break;

    case 'delete':
        if (!isset($_GET['csrf_token']) || !hash_equals($csrf, $_GET['csrf_token'])) {
            $display = '<div class="alert alert-danger">Invalid CSRF token. Please refresh the page and try again.</div>';
            break;
        }
        $userID = (int)($_GET['id'] ?? 0);
        // Prevent deletion of main admin (userID 1) or the logged-in admin
        if ($userID !== 1 && $userID !== $loggedInUserID) {
            $stmt_delete = $mysqli->prepare("DELETE FROM " . DB_PREFIX . "users WHERE userID = ?");
            $stmt_delete->bind_param("i", $userID);
            $stmt_delete->execute();
            $stmt_delete->close();
            $display = '<div class="alert alert-success">User deleted successfully.</div>';
        } else {
            $display = '<div class="alert alert-danger">Cannot delete this user.</div>';
        }
        break;
}

// If editing and no POST data, load user info for editing
if ($action == 'edit' && !empty($_GET['id']) && empty($_POST)) {
    $userID = (int)$_GET['id'];
    $stmt = $mysqli->prepare("SELECT firstname, lastname, email, userName, is_admin FROM " . DB_PREFIX . "users WHERE userID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->bind_result($firstname, $lastname, $email, $userName, $is_admin);
    if (!$stmt->fetch()) {
        header('Location: users.php');
        exit;
    }
    $stmt->close();
}

include('includes/header.php');
?>

<div class="container my-4">
    <?php if (!empty($display)) echo $display; ?>

    <?php if ($action == 'add' || $action == 'edit'): ?>
        <!-- Add/Edit User Form Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h1 class="h4 mb-0"><?php echo ucfirst($action); ?> User</h1>
            </div>
            <div class="card-body">
                <form action="users.php?action=<?php echo $action; ?>_action" method="post" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                    <?php if (isset($userID)) : ?>
                        <input type="hidden" name="userID" value="<?php echo htmlspecialchars($userID); ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="firstname">First Name</label>
                        <input type="text" name="firstname" id="firstname" class="form-control" value="<?php echo htmlspecialchars($firstname ?? ''); ?>" required>
                        <div class="invalid-feedback">Please enter first name.</div>
                    </div>
                    <div class="form-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" name="lastname" id="lastname" class="form-control" value="<?php echo htmlspecialchars($lastname ?? ''); ?>" required>
                        <div class="invalid-feedback">Please enter last name.</div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                    <div class="form-group">
                        <label for="userName">Username</label>
                        <input type="text" name="userName" id="userName" class="form-control" value="<?php echo htmlspecialchars($userName ?? ''); ?>" required>
                        <div class="invalid-feedback">Please enter a username.</div>
                    </div>
                    <?php if ($action == 'add'): ?>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                            <div class="invalid-feedback">Please enter a password.</div>
                        </div>
                        <div class="form-group">
                            <label for="password2">Confirm Password</label>
                            <input type="password" name="password2" id="password2" class="form-control" required>
                            <div class="invalid-feedback">Passwords must match.</div>
                        </div>
                    <?php endif; ?>
                    <?php if ((!isset($userID) || ($userID !== 1 && $userID !== $loggedInUserID))): ?>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_admin" name="is_admin" <?php echo (isset($is_admin) && $is_admin) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="is_admin">Administrator Access</label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <a href="users.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Manage Users List Card -->
        <div class="card">
            <div class="card-header">
                <h1 class="h4 mb-0">Manage Users</h1>
            </div>
            <div class="card-body">
                <a href="users.php?action=add" class="btn btn-success mb-3">Add User</a>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Admin</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = $mysqli->query("SELECT * FROM " . DB_PREFIX . "users ORDER BY lastname, firstname");
                            while ($row = $query->fetch_assoc()) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['userName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['lastname'] . ', ' . $row['firstname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td class="text-center">
                                        <?php echo $row['status'] ? '<span class="text-success">&#10004;</span>' : '<span class="text-danger">&#10008;</span>'; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $row['is_admin'] ? '<span class="text-success">&#10004;</span>' : '<span class="text-danger">&#10008;</span>'; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="users.php?action=edit&id=<?php echo $row['userID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <?php if ($row['userID'] !== 1 && $row['userID'] !== $loggedInUserID) : ?>
                                            <a href="users.php?action=toggle&id=<?php echo $row['userID']; ?>&csrf_token=<?php echo urlencode($csrf); ?>" class="btn btn-<?php echo $row['status'] ? 'secondary' : 'success'; ?> btn-sm">
                                                <?php echo $row['status'] ? 'Deactivate' : 'Activate'; ?>
                                            </a>
                                            <a href="users.php?action=toggle_admin&id=<?php echo $row['userID']; ?>&csrf_token=<?php echo urlencode($csrf); ?>" class="btn btn-<?php echo $row['is_admin'] ? 'secondary' : 'primary'; ?> btn-sm">
                                                <?php echo $row['is_admin'] ? 'Remove Admin' : 'Make Admin'; ?>
                                            </a>
                                            <a href="users.php?action=delete&id=<?php echo $row['userID']; ?>&csrf_token=<?php echo urlencode($csrf); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Bootstrap custom form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form.needs-validation');
    const password = document.getElementById('password');
    const password2 = document.getElementById('password2');

    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            // Custom password matching validation for add action
            if (password && password2) {
                if (password.value !== password2.value) {
                    password2.setCustomValidity('Passwords must match');
                    event.preventDefault();
                } else {
                    password2.setCustomValidity('');
                }
            }
            form.classList.add('was-validated');
        }, false);

        if (password && password2) {
            password.addEventListener('input', function() {
                password2.setCustomValidity('');
            });
            password2.addEventListener('input', function() {
                password2.setCustomValidity('');
            });
        }
    }
});
</script>

<?php include('includes/footer.php'); ?>
