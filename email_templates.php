<?php
require('includes/application_top.php');

// Security: Verify admin status
if (!$user->is_admin) {
    header('Location: ./');
    exit();
}

// Initialize variables
$email_template_key = '';
$subject = '';
$message = '';
$update_success = false;
$error_message = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_template_key = isset($_POST['email_template_key']) ? 
        filter_var($_POST['email_template_key'], FILTER_SANITIZE_STRING) : '';

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = 'Invalid CSRF token. Please refresh the page and try again.';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'Update' && !empty($email_template_key)) {
        try {
            $stmt = $mysqli->prepare("UPDATE " . DB_PREFIX . "email_templates
                                    SET subject = ?, message = ?
                                    WHERE email_template_key = ?");

            if ($stmt === false) {
                throw new Exception("Failed to prepare statement: " . $mysqli->error);
            }

            $stmt->bind_param("sss", $_POST['subject'], $_POST['message'], $email_template_key);

            if (!$stmt->execute()) {
                throw new Exception("Error updating email template: " . $stmt->error);
            }

            $update_success = true;
        } catch (Exception $e) {
            $error_message = htmlspecialchars($e->getMessage());
        } finally {
            if (isset($stmt)) {
                $stmt->close();
            }
        }
    }
}

// Handle template selection (both GET and POST)
if (!empty($_REQUEST['email_template_key'])) {
    try {
        $stmt = $mysqli->prepare("SELECT * FROM " . DB_PREFIX . "email_templates
                                WHERE email_template_key = ?");
        if ($stmt === false) {
            throw new Exception("Failed to prepare statement: " . $mysqli->error);
        }

        $stmt->bind_param("s", $_REQUEST['email_template_key']);
        if (!$stmt->execute()) {
            throw new Exception("Error fetching email template: " . $stmt->error);
        }

        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $email_template_key = $row['email_template_key'];
            $subject = htmlspecialchars($row['subject']);
            $message = $row['message']; // raw HTML — TinyMCE needs unescaped content
        }
    } catch (Exception $e) {
        $error_message = htmlspecialchars($e->getMessage());
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}

include('includes/header.php');
?>

<div class="container my-4">
    <div class="card">
        <div class="card-header">
            <h2 class="mb-0">Manage Email Templates</h2>
        </div>
        <div class="card-body">
            <!-- Display Success Message -->
            <?php if ($update_success): ?>
                <div id="successMessage" class="alert alert-success text-center">
                    ✅ Template Updated Successfully!
                </div>
            <?php endif; ?>

            <!-- Display Error Messages -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-9">
                    <!-- Template Selection Form -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Select Template</h5>
                        </div>
                        <div class="card-body">
                            <form action="email_templates.php" method="get">
                                <div class="form-group mb-0">
                                    <label for="email_template_key">Email Template:</label>
                                    <select name="email_template_key" id="email_template_key" class="form-select" onchange="this.form.submit()">
                                        <option value="">Select a template...</option>
                                        <?php
                                        try {
                                            $query = $mysqli->query("SELECT email_template_key, email_template_title FROM " . DB_PREFIX . "email_templates");
                                            while ($row = $query->fetch_assoc()) {
                                                $selected = ($email_template_key === $row['email_template_key']) ? ' selected' : '';
                                                echo "<option value=\"" . htmlspecialchars($row['email_template_key']) . "\"" . $selected . ">" .
                                                     htmlspecialchars($row['email_template_title']) . "</option>\n";
                                            }
                                            $query->free();
                                        } catch (Exception $e) {
                                            echo '<option value="">Error loading templates</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Template Update Form -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Edit Template</h5>
                        </div>
                        <div class="card-body">
                            <form action="email_templates.php" method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="email_template_key" value="<?php echo htmlspecialchars($email_template_key); ?>">

                                <div class="form-group">
                                    <label for="subject">Subject:</label>
                                    <input type="text" id="subject" name="subject" class="form-control"
                                           value="<?php echo $subject; ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="message">Message:</label>
                                    <textarea id="message" name="message" class="form-control" rows="12" required><?php echo $message; ?></textarea>
                                </div>

                                <div class="d-flex gap-2 justify-content-end mb-0 mt-4">
                                    <a href="./" class="btn btn-secondary btn-lg">Cancel</a>
                                    <button type="submit" name="action" value="Update"
                                            class="btn btn-primary btn-lg"
                                            <?php echo (empty($email_template_key)) ? ' disabled' : ''; ?>>
                                        Update Template
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Available Variables -->
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Available Variables</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">{week}</li>
                                <li class="list-group-item">{player}</li>
                                <li class="list-group-item">{first_game}</li>
                                <li class="list-group-item">{site_url}</li>
                                <li class="list-group-item">{rules_url}</li>
                                <li class="list-group-item">{winners}</li>
                                <li class="list-group-item">{previousWeek}</li>
                                <li class="list-group-item">{winningScore}</li>
                                <li class="list-group-item">{possibleScore}</li>
                                <li class="list-group-item">{currentLeaders}</li>
                                <li class="list-group-item">{bestPickRatios}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.4/tinymce.min.js"></script>
<script>
    // Hide success message after 3 seconds
    setTimeout(function() {
        var successBox = document.getElementById('successMessage');
        if (successBox) successBox.style.display = 'none';
    }, 3000);

    // WYSIWYG editor
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    tinymce.init({
        selector: '#message',
        base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.4',
        suffix: '.min',
        skin: isDark ? 'oxide-dark' : 'oxide',
        content_css: isDark ? 'dark' : 'default',
        plugins: 'lists link',
        toolbar: 'bold italic underline | bullist numlist | link | removeformat',
        menubar: false,
        statusbar: false,
        height: 420,
        content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
    });

    // Sync TinyMCE content to textarea before form submit
    document.querySelector('form[action="email_templates.php"][method="post"]')
        .addEventListener('submit', function() { tinymce.triggerSave(); });
</script>

<?php include('includes/footer.php'); ?>
