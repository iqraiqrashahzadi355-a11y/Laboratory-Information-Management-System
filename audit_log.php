<?php
// Include this file to log any action
// Usage: logAction($conn, "Viewed patients list");

function logAction($conn, $action) {
    if (!isset($_SESSION['user_id'])) return;

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
?>
