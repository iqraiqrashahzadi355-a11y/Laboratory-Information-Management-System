<?php
include 'auth_check.php';
checkRole(['admin', 'technician']);
include 'config.php';
include 'audit_log.php';
include 'mailer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token']);
    $test_id    = intval($_POST['test_id']);
    $new_status = $_POST['status'];

    $allowed = ['Registered', 'Testing', 'Completed'];
    if (!in_array($new_status, $allowed)) {
        die("Invalid status.");
    }

    $stmt = $conn->prepare("UPDATE LabTests SET Status=? WHERE TestID=?");
    $stmt->bind_param("si", $new_status, $test_id);
    if ($stmt->execute()) {
        logAction($conn, "Updated Test #$test_id status to '$new_status'");

        // Send email to doctors when test is Completed
        if ($new_status === 'Completed') {
            $info = $conn->query("
                SELECT LabTests.TestName, LabTests.TestDate, LabTests.Result, Patients.Name as PatientName, Patients.PatientID
                FROM LabTests JOIN Patients ON LabTests.PatientID = Patients.PatientID
                WHERE LabTests.TestID = $test_id
            ")->fetch_assoc();

            if ($info) {
                $doctors = $conn->query("SELECT FullName, Email FROM Users WHERE Role='doctor' AND IsActive=1 AND Email IS NOT NULL AND Email != ''");
                $message = "A lab test result is now <strong>Completed</strong> and ready for review.<br><br>
                    <strong>Patient:</strong> " . htmlspecialchars($info['PatientName']) . "<br>
                    <strong>Test:</strong> " . htmlspecialchars($info['TestName']) . "<br>
                    <strong>Date:</strong> " . $info['TestDate'] . "<br>
                    <strong>Result:</strong> " . htmlspecialchars($info['Result']);

                while ($doc = $doctors->fetch_assoc()) {
                    sendLIMSEmail(
                        $doc['Email'],
                        $doc['FullName'],
                        "Test Result Ready — " . $info['PatientName'],
                        limsEmailTemplate("New Test Result Available 🧪", $message, "View in LIMS", "http://localhost/LIMS/view_tests.php")
                    );
                }
            }
        }
    }

    $redirect = $_POST['redirect'] ?? 'track_samples.php';
    header("Location: /LIMS/" . $redirect . "?success=1");
    exit;
}
?>