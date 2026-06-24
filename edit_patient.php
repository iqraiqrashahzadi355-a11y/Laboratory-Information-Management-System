<?php
include 'auth_check.php';
checkRole(['admin', 'technician']);
include 'config.php';
include 'audit_log.php';

if (!isset($_GET['id'])) {
    header("Location: /LIMS/view_patients.php");
    exit;
}

$patient_id = intval($_GET['id']);
$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token']);
    $name    = trim($_POST['name']);
    $age     = intval($_POST['age']);
    $gender  = $_POST['gender'];
    $contact = trim($_POST['contact']);

    $stmt = $conn->prepare("UPDATE Patients SET Name=?, Age=?, Gender=?, ContactNumber=? WHERE PatientID=?");
    $stmt->bind_param("sissi", $name, $age, $gender, $contact, $patient_id);
    if ($stmt->execute()) {
        logAction($conn, "Updated patient #$patient_id: $name");
        $success = "Patient updated successfully!";
    } else {
        $error = "Error updating patient.";
    }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT * FROM Patients WHERE PatientID=?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) die("Patient not found.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:#f7f6fb;color:#0f0e17;min-height:100vh;}
        .main{max-width:520px;margin:2.5rem auto;padding:0 1.5rem;}
        .page-title{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;margin-bottom:0.3rem;}
        .page-sub{color:#72737d;font-size:0.88rem;margin-bottom:2rem;}
        .card{background:#fff;border:1px solid #e8e7f0;border-radius:16px;padding:2rem;}
        .field-label{font-size:0.73rem;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:#0f0e17;display:block;margin-bottom:0.4rem;}
        .input-wrap{border:1.5px solid #e8e7f0;border-radius:12px;overflow:hidden;margin-bottom:1.1rem;background:#fafafa;transition:border-color 0.2s;display:flex;align-items:center;}
        .input-wrap:focus-within{border-color:#0891b2;background:#fff;}
        .input-icon{padding:0 14px;height:50px;display:flex;align-items:center;background:#f0f0f0;border-right:1px solid #eee;font-size:1rem;}
        .input-wrap input,.input-wrap select{flex:1;border:none;background:transparent;padding:0 14px;height:50px;font-size:0.95rem;font-family:'DM Sans',sans-serif;color:#0f0e17;outline:none;}
        .btn-submit{width:100%;padding:14px;background:#0891b2;color:#fff;border:none;border-radius:12px;font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;cursor:pointer;transition:opacity 0.15s,transform 0.15s;margin-top:0.5rem;}
        .btn-submit:hover{opacity:0.88;transform:translateY(-1px);}
        .alert-success{background:#dcfce7;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;font-size:0.84rem;color:#15803d;margin-bottom:1.25rem;}
        .alert-error{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;font-size:0.84rem;color:#dc2626;margin-bottom:1.25rem;}
        .back-link{display:inline-block;margin-top:1rem;font-size:0.83rem;color:#72737d;text-decoration:none;}
        .patient-id-badge{display:inline-block;background:#ede9fe;color:#6246ea;padding:4px 14px;border-radius:50px;font-size:0.8rem;font-weight:700;margin-bottom:1.25rem;}
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>
<div class="main">
    <p class="page-title">✏️ Edit Patient</p>
    <p class="page-sub">Update patient information.</p>
    <div class="card">
        <div class="patient-id-badge">Patient ID: #<?php echo $patient_id; ?></div>
        <?php if($success): ?><div class="alert-success">✓ <?php echo $success; ?></div><?php endif; ?>
        <?php if($error):   ?><div class="alert-error">⚠ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST" action="/LIMS/edit_patient.php?id=<?php echo $patient_id; ?>">
            <?php echo csrfField(); ?>
            <label class="field-label">Full Name</label>
            <div class="input-wrap">
                <div class="input-icon">👤</div>
                <input type="text" name="name" value="<?php echo htmlspecialchars($patient['Name']); ?>" required>
            </div>
            <label class="field-label">Age</label>
            <div class="input-wrap">
                <div class="input-icon">🎂</div>
                <input type="number" name="age" value="<?php echo $patient['Age']; ?>" min="1" max="150" required>
            </div>
            <label class="field-label">Gender</label>
            <div class="input-wrap">
                <div class="input-icon">⚧</div>
                <select name="gender" required>
                    <option value="Male"   <?php echo $patient['Gender']==='Male'?'selected':''; ?>>Male</option>
                    <option value="Female" <?php echo $patient['Gender']==='Female'?'selected':''; ?>>Female</option>
                    <option value="Other"  <?php echo $patient['Gender']==='Other'?'selected':''; ?>>Other</option>
                </select>
            </div>
            <label class="field-label">Contact Number</label>
            <div class="input-wrap">
                <div class="input-icon">📞</div>
                <input type="text" name="contact" value="<?php echo htmlspecialchars($patient['ContactNumber']); ?>" required>
            </div>
            <button type="submit" class="btn-submit">Update Patient →</button>
        </form>
        <a href="/LIMS/view_patients.php" class="back-link">← Back to Patients</a>
    </div>
</div>
</body>
</html>
