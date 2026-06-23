<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
    header("Location: /LIMS/login.php");
    exit;
}
include 'config.php';

$patient_id = $_SESSION['patient_id'];
$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current  = $_POST['current_password'];
    $new      = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT Password FROM Patients WHERE PatientID = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $hashedCurrent = hash('sha256', $current);

    if (!hash_equals($row['Password'], $hashedCurrent)) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new) < 4) {
        $error = "New password must be at least 4 characters.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } else {
        $hashedNew = hash('sha256', $new);
        $stmt = $conn->prepare("UPDATE Patients SET Password = ? WHERE PatientID = ?");
        $stmt->bind_param("si", $hashedNew, $patient_id);
        $stmt->execute();
        $stmt->close();
        $success = "Password updated successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:#f7f6fb;color:#0f0e17;min-height:100vh;}
        nav{background:#fffffe;border-bottom:1px solid #e8e7f0;padding:0 2.5rem;height:64px;display:flex;align-items:center;justify-content:space-between;}
        .nav-logo{font-family:'Syne',sans-serif;font-weight:800;font-size:1.2rem;color:#6246ea;text-decoration:none;}
        .nav-logo em{background:#6246ea;color:#fff;font-style:normal;padding:1px 7px;border-radius:5px;margin-right:3px;}
        .main{max-width:460px;margin:3rem auto;padding:0 1.5rem;}
        .page-title{font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;margin-bottom:0.3rem;}
        .page-sub{color:#72737d;font-size:0.87rem;margin-bottom:2rem;}
        .card{background:#fff;border:1px solid #e8e7f0;border-radius:16px;padding:2rem;}
        .field-label{font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:#0f0e17;display:block;margin-bottom:0.4rem;}
        .input-wrap{border:1.5px solid #e8e7f0;border-radius:12px;overflow:hidden;margin-bottom:1.1rem;background:#fafafa;display:flex;align-items:center;}
        .input-wrap:focus-within{border-color:#6246ea;background:#fff;}
        .input-icon{padding:0 14px;height:48px;display:flex;align-items:center;background:#f0f0f0;border-right:1px solid #eee;}
        .input-wrap input{flex:1;border:none;background:transparent;padding:0 14px;height:48px;font-size:0.92rem;font-family:'DM Sans',sans-serif;outline:none;}
        .btn-submit{width:100%;padding:13px;background:#6246ea;color:#fff;border:none;border-radius:12px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.95rem;cursor:pointer;margin-top:0.5rem;}
        .alert-success{background:#dcfce7;border:1px solid #bbf7d0;border-radius:10px;padding:11px 15px;font-size:0.83rem;color:#15803d;margin-bottom:1.1rem;}
        .alert-error{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:11px 15px;font-size:0.83rem;color:#dc2626;margin-bottom:1.1rem;}
        .back-link{display:inline-block;margin-top:1rem;font-size:0.82rem;color:#72737d;text-decoration:none;}
    </style>
</head>
<body>
<nav><a href="/LIMS/patient_dashboard.php" class="nav-logo"><em>L</em>IMS</a></nav>
<div class="main">
    <p class="page-title">🔑 Change Password</p>
    <p class="page-sub">Update your patient portal password.</p>
    <div class="card">
        <?php if($success): ?><div class="alert-success">✓ <?php echo $success; ?></div><?php endif; ?>
        <?php if($error):   ?><div class="alert-error">⚠ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST" action="/LIMS/patient_change_password.php">
            <label class="field-label">Current Password</label>
            <div class="input-wrap"><div class="input-icon">🔒</div><input type="password" name="current_password" required></div>
            <label class="field-label">New Password</label>
            <div class="input-wrap"><div class="input-icon">🔑</div><input type="password" name="new_password" minlength="4" required></div>
            <label class="field-label">Confirm New Password</label>
            <div class="input-wrap"><div class="input-icon">✅</div><input type="password" name="confirm_password" minlength="4" required></div>
            <button type="submit" class="btn-submit">Update Password →</button>
        </form>
        <a href="/LIMS/patient_dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
</div>
</body>
</html>