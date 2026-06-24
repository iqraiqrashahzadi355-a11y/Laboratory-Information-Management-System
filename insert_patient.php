<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name    = trim($_POST['name']);
    $age     = intval($_POST['age']);
    $gender  = $_POST['gender'];
    $contact = trim($_POST['contact']);

    // Prepared statement — safe from SQL injection
    $stmt = $conn->prepare("INSERT INTO Patients (Name, Age, Gender, ContactNumber) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $name, $age, $gender, $contact);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: view_patients.php");
        exit;
    } else {
        echo "Error: " . $stmt->error;
        $stmt->close();
    }
}
?>
