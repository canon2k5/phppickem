<?php
class Login {
    public function __construct() {
        // Constructor (if needed)
    }

    public function validate_password() {
        $user_name = $this->no_injections($_POST['username']);
        $password  = $_POST['password'];  // never modify before password_verify()
        $user = $this->get_user($user_name);
        // Use password_verify to check the password against the stored hash
        if (!empty($user) && !empty($password) && password_verify($password, $user->password)) {
            session_regenerate_id(true);
            $_SESSION['logged'] = 'yes';
            $_SESSION['loggedInUser'] = $user->userName;
            $_SESSION['is_admin'] = $user->is_admin;
            header('Location: '.SITE_URL);
            exit;
        } else {
            $_SESSION = array();
            header('Location: '.SITE_URL.'login.php?login=failed');
            exit;
        }
    }

    public function get_user($user_name) {
        global $mysqli;
        $stmt = $mysqli->prepare("SELECT * FROM " . DB_PREFIX . "users WHERE userName = ? AND status = 1");
        if (!$stmt) {
            die("Prepare failed: " . $mysqli->error);
        }
        $stmt->bind_param("s", $user_name);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_info = $result->num_rows > 0 ? $result->fetch_object() : false;
        $stmt->close();
        return $user_info;
    }

public function get_user_by_id($user_id) {
    global $mysqli;
    $stmt = $mysqli->prepare("SELECT * FROM " . DB_PREFIX . "users WHERE userID = ? AND status = 1");
    if (!$stmt) {
        die("Prepare failed: " . $mysqli->error);
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_object();
    $stmt->close();
    return $user_info;
}


    public function no_injections($input) {
        $injections = array('/(\n+)/i','/(\r+)/i','/(\t+)/i','/(%0A+)/i','/(%0D+)/i','/(%08+)/i','/(%09+)/i');
        $input = preg_replace($injections, '', $input);
        return trim($input);
    }

    public function logout() {
        $_SESSION = array();
    }
}
?>
