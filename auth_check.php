<?php
function checkAuth() {
    if (session_status() === PHP_SESSION_NONE) session_start();

    // Session timeout 15 min
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 7200) {
        session_unset();
        session_destroy();
        header("Location: auth_login.php?timeout=1");
        exit;
    }
    $_SESSION['last_activity'] = time();

    if (!isset($_SESSION['user_id'])) {
        header("Location: auth_login.php");
        exit;
    }
}

function checkRole(array $allowedRoles) {
    checkAuth();
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: unauthorized.php");
        exit;
    }
}

function currentRole()  { return $_SESSION['role'] ?? ''; }
function currentUser()  { return $_SESSION['fullname'] ?? 'User'; }
function dashboardLink() {
    $map = ['admin'=>'dashboard_admin.php','technician'=>'dashboard_technician.php','manager'=>'dashboard_manager.php','doctor'=>'dashboard_doctor.php'];
    return $map[$_SESSION['role'] ?? ''] ?? 'auth_login.php';
}

// Auto log any page visit
function autoLog($conn, $action = '') {
    if (!isset($_SESSION['user_id'])) return;
    if (empty($action)) {
        $page   = basename($_SERVER['PHP_SELF']);
        $action = "Visited: " . $page;
    }
    $userID   = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $role     = $_SESSION['role'];
    $pageURL  = $_SERVER['REQUEST_URI'] ?? '';
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $conn->prepare("INSERT INTO AuditLog (UserID, Username, Role, Action, PageURL, IPAddress) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("isssss", $userID, $username, $role, $action, $pageURL, $ip);
    $stmt->execute();
    $stmt->close();
}
// CSRF Token functions
function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRF($token) {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die("CSRF verification failed. Please go back and try again.");
    }
    return true;
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRF() . '">';
}
?>
