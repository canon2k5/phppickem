<?php
require('includes/application_top.php');
require_once __DIR__ . '/vendor/autoload.php';
require_once('includes/email_helper.php');

if (!$user->is_admin) {
    header('Location: ./');
    exit;
}

$csrf = $_SESSION['csrf_token'];

// Get current week details
$week = (int)getCurrentWeek();
$prevWeek = $week - 1;
$firstGameTime = getFirstGameTime($week);
$possibleScoreTotal = 0;
$weekStats = [];
$playerTotals = [];
calculateStats();

// Determine winners
$winners = '';
if (!empty($weekStats[$prevWeek]['winners'])) {
    foreach ($weekStats[$prevWeek]['winners'] as $winner => $winnerID) {
        $tmpUser = $login->get_user_by_id($winnerID);
        $winners .= (!empty($winners) ? ', ' : '') . htmlspecialchars($tmpUser->firstname . ' ' . $tmpUser->lastname);
    }
}

// Determine current leaders
$currentLeaders = '';
if (!empty($playerTotals)) {
    arsort($playerTotals);
    $i = 1;
    foreach ($playerTotals as $playerID => $stats) {
        if ($stats['wins'] == 0 || $i > 3) break;
        $currentLeaders .= "<strong>{$i}.</strong> " . htmlspecialchars($stats['name']) . " - {$stats['wins']} " . (($stats['wins'] > 1) ? 'wins' : 'win') . "<br/>";
        $i++;
    }
}

// Determine best pick ratios
$bestPickRatios = '';
if (!empty($playerTotals)) {
    usort($playerTotals, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    $i = 1;
    foreach ($playerTotals as $playerID => $stats) {
        if ($stats['score'] == 0 || $i > 5) break;
        $pickRatio = "{$stats['score']}/{$possibleScoreTotal}";
        $pickPercentage = number_format(($stats['score'] / $possibleScoreTotal) * 100, 2) . '%';
        $bestPickRatios .= "<strong>{$i}.</strong> " . htmlspecialchars($stats['name']) . " - {$pickRatio} ({$pickPercentage})<br/>";
        $i++;
    }
}

// Handle template selection
$subject = $message = '';
if (isset($_POST['action']) && $_POST['action'] == 'Select' && !empty($_POST['cannedMsg'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) {
        $display = '<div class="alert alert-danger">Invalid CSRF token. Please refresh the page and try again.</div>';
    } else {
        $cannedMsg = $_POST['cannedMsg'];

        // FINAL_RESULTS recaps the last week of the season ($week), not $week-1
        $refWeek = ($cannedMsg === 'FINAL_RESULTS') ? $week : $prevWeek;
        $refWinners = $winners;
        if ($refWeek !== $prevWeek && !empty($weekStats[$refWeek]['winners'])) {
            $refWinners = '';
            foreach ($weekStats[$refWeek]['winners'] as $winner => $winnerID) {
                $tmpUser = $login->get_user_by_id($winnerID);
                $refWinners .= (!empty($refWinners) ? ', ' : '') . htmlspecialchars($tmpUser->firstname . ' ' . $tmpUser->lastname);
            }
        }

        // SEASON_KICKOFF always references Week 1's first game, not the current week
        $refFirstGameTime = ($cannedMsg === 'SEASON_KICKOFF') ? getFirstGameTime(1) : $firstGameTime;

        $stmt = $mysqli->prepare("SELECT subject, message FROM " . DB_PREFIX . "email_templates WHERE email_template_key = ?");
        $stmt->bind_param("s", $cannedMsg);
        $stmt->execute();
        $query = $stmt->get_result();
        if ($row = $query->fetch_assoc()) {
            $subjectTemplate = $row['subject'];
            $messageTemplate = $row['message'];

            // Replace template variables
            $templateVars = ['{week}', '{first_game}', '{site_url}', '{rules_url}', '{winners}', '{previousWeek}', '{winningScore}', '{possibleScore}', '{currentLeaders}', '{bestPickRatios}'];
            $replacementValues = [$week, date('l F j, g:i a', strtotime($refFirstGameTime)), SITE_URL, SITE_URL . 'rules.php', $refWinners, $refWeek, $weekStats[$refWeek]['highestScore'], getGameTotal($refWeek), $currentLeaders, $bestPickRatios];

            $subject = str_replace($templateVars, $replacementValues, $subjectTemplate);
            $message = str_replace($templateVars, $replacementValues, $messageTemplate);
        }
        $stmt->close();
    }
}

// Handle sending messages
if (isset($_POST['action']) && $_POST['action'] == 'Send Message') {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) {
        $display = '<div class="alert alert-danger">Invalid CSRF token. Please refresh the page and try again.</div>';
    } else {
        $totalGames = getGameTotal($week);
        $addresses = [];
        $failedAddresses = [];

        // Initialize the EmailHelper class
        $emailHelper = new EmailHelper();

        // Select users to send the message
        if ($_POST['cannedMsg'] == 'WEEKLY_PICKS_REMINDER') {
            $sql = "SELECT u.firstname, u.email
                    FROM " . DB_PREFIX . "users u
                    WHERE u.status = 1 AND u.userName <> 'admin'
                    AND (SELECT COUNT(p.pickID) FROM " . DB_PREFIX . "picks p
                         INNER JOIN " . DB_PREFIX . "schedule s ON p.gameID = s.gameID
                         WHERE p.userID = u.userID AND s.weekNum = " . $week . ") < " . $totalGames;
        } else {
            $sql = "SELECT firstname, email FROM " . DB_PREFIX . "users WHERE status = 1 AND userName <> 'admin'";
        }

        $query = $mysqli->query($sql);
        if ($query->num_rows > 0) {
            while ($row = $query->fetch_assoc()) {
                $email = $row['email'];
                $recipientName = htmlspecialchars($row['firstname']);
                $subject = $_POST['subject'];
                $messageBody = $_POST['message'];
                $messageBody = str_replace('{player}', $recipientName, $messageBody);

                // Use the EmailHelper instance to send email
                $sent = $emailHelper->sendEmail($email, $subject, $messageBody, $recipientName);
                
                if ($sent) {
                    $addresses[] = $email;
                } else {
                    $failedAddresses[] = $email;
                }
            }

            // Display success and/or failure messages
            if (!empty($addresses)) {
                $display = '<div class="alert alert-success">Message successfully sent to: ' . 
                          implode(', ', $addresses) . '.</div>';
            }
            
            if (!empty($failedAddresses)) {
                $display .= '<div class="alert alert-danger">Failed to send email to: ' . 
                           implode(', ', $failedAddresses) . '.</div>';
            }
        }
    }
}

// Rest of your HTML remains unchanged
include('includes/header.php');

if (!empty($display)) {
    echo $display;
}
?>
<!-- Your existing HTML form code remains unchanged -->

<div class="container my-4">
    <div class="card">
        <div class="card-header">
            <h2 class="mb-0">Send Email</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($display)) echo $display; ?>

            <form id="emailForm" action="send_email.php" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                <!-- Template Selection Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">1. Select Template</h5>
                    </div>
                    <div class="card-body">
		    <div class="form-group">
                            <label for="cannedMsg">Email Template:</label>
                            <select name="cannedMsg" id="cannedMsg" class="form-select mb-3">
                                <option value="">Select a template...</option>
                                <?php
                                $sql = "SELECT email_template_key, email_template_title FROM " . DB_PREFIX . "email_templates";
                                $query = $mysqli->query($sql);
                                while ($row = $query->fetch_assoc()) {
                                    $selected = ($_POST['cannedMsg'] == $row['email_template_key']) ? ' selected' : '';
                                    echo '<option value="' . htmlspecialchars($row['email_template_key']) . '"' . $selected . '>' . 
                                         htmlspecialchars($row['email_template_title']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" id="loadTemplateBtn" name="action" value="Select" class="d-none" onclick="disableValidation()"></button>
                    </div>
                </div>

                <!-- Email Composition Section -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">2. Compose Email</h5>
                    </div>
                    <div class="card-body">
                        <!-- Email Subject -->
                        <div class="form-group">
                            <label for="subject">Subject:</label>
                            <input type="text"
                                   id="subject"
                                   name="subject"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($subject); ?>"
                                   required>
                        </div>

                        <!-- Email Message -->
                        <div class="form-group">
                            <label for="message">Message:</label>
                            <textarea id="message"
                                      name="message"
                                      class="form-control"
                                      rows="12"
                                      required><?php echo $message; ?></textarea>
                            <small class="form-text text-muted mt-2">
                                Available variables: {player}, {week}, {first_game}, {site_url}, {rules_url}, {winners},
                                {previousWeek}, {winningScore}, {possibleScore}, {currentLeaders}, {bestPickRatios}
                            </small>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <a href="./" class="btn btn-secondary btn-lg">Cancel</a>
                            <button type="submit"
                                    name="action"
                                    value="Send Message"
                                    class="btn btn-primary btn-lg"
                                    onclick="enableValidation()">
                                Send Email
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.4/tinymce.min.js"></script>
<script>
    // Auto-load template when dropdown changes
    document.getElementById('cannedMsg').addEventListener('change', function() {
        if (this.value) {
            disableValidation();
            document.getElementById('loadTemplateBtn').click();
        }
    });

    function disableValidation() {
        document.getElementById('subject').removeAttribute('required');
        document.getElementById('message').removeAttribute('required');
    }

    function enableValidation() {
        document.getElementById('subject').setAttribute('required', 'true');
        document.getElementById('message').setAttribute('required', 'true');
    }

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

    // Sync TinyMCE to textarea before sending
    document.getElementById('emailForm').addEventListener('submit', function() {
        tinymce.triggerSave();
    });
</script>

<?php include('includes/footer.php'); ?>
