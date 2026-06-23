<?php
include 'auth_check.php';
checkRole(['admin']);
include 'config.php';
include 'audit_log.php';

if (!isset($_GET['id'])) {
    header("Location: /LIMS/view_patients.php");
    exit;
}

$patient_id = intval($_GET['id']);

// Get patient name for log
$p = $conn->query("SELECT Name FROM Patients WHERE PatientID=$patient_id")->fetch_assoc();
if ($p) {
    // Delete tests first
    $conn->query("DELETE FROM LabTests WHERE PatientID=$patient_id");
    // Delete patient
    $conn->query("DELETE FROM Patients WHERE PatientID=$patient_id");
    logAction($conn, "Deleted patient #$patient_id: " . $p['Name'] . " and all their tests");
}

header("Location: /LIMS/view_patients.php?msg=deleted");
exit;
?>