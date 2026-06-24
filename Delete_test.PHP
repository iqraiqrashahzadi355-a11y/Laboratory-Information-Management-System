<?php
include 'auth_check.php';
checkRole(['admin']);
include 'config.php';
include 'audit_log.php';

if (!isset($_GET['id'])) {
    header("Location: /LIMS/view_tests.php");
    exit;
}

$test_id = intval($_GET['id']);
$t = $conn->query("SELECT TestName FROM LabTests WHERE TestID=$test_id")->fetch_assoc();
if ($t) {
    $conn->query("DELETE FROM LabTests WHERE TestID=$test_id");
    logAction($conn, "Deleted test #$test_id: " . $t['TestName']);
}

header("Location: /LIMS/view_tests.php?msg=deleted");
exit;
?>
