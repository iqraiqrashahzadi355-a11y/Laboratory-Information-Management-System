<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
    header("Location: /LIMS/login.php");
    exit;
}
include 'config.php';
include 'mailer.php';
require_once __DIR__ . '/auth_check.php';

$patient_id = $_SESSION['patient_id'];
$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token']);
    $test_type = trim($_POST['test_type']);
    $apt_date  = $_POST['apt_date'];
    $apt_time  = $_POST['apt_time'];
    $notes     = trim($_POST['notes']);

    // Validate date is not in past
    if (strtotime($apt_date) < strtotime(date('Y-m-d'))) {
        $error = "Appointment date cannot be in the past.";
    } else {
        $stmt = $conn->prepare("INSERT INTO Appointments (PatientID, TestType, AppointmentDate, AppointmentTime, Notes) VALUES (?,?,?,?,?)");
        $stmt->bind_param("issss", $patient_id, $test_type, $apt_date, $apt_time, $notes);
        if ($stmt->execute()) {
            $success = "Appointment requested successfully! You will be notified once confirmed.";

            // Notify admins
            $patient = $conn->query("SELECT Name FROM Patients WHERE PatientID=$patient_id")->fetch_assoc();
            $admins = $conn->query("SELECT FullName, Email FROM Users WHERE Role IN ('admin','technician') AND IsActive=1 AND Email IS NOT NULL AND Email!=''");
            $msg = "A new appointment has been requested.<br><br>
                <strong>Patient:</strong> " . htmlspecialchars($patient['Name']) . " (ID #$patient_id)<br>
                <strong>Test Type:</strong> " . htmlspecialchars($test_type) . "<br>
                <strong>Date:</strong> " . $apt_date . "<br>
                <strong>Time:</strong> " . $apt_time . "<br>
                <strong>Notes:</strong> " . htmlspecialchars($notes ?: 'None');
            while ($a = $admins->fetch_assoc()) {
                sendLIMSEmail($a['Email'], $a['FullName'], "New Appointment Request — " . $patient['Name'], limsEmailTemplate("New Appointment Request 📅", $msg, "View Appointments", "http://localhost/LIMS/manage_appointments.php"));
            }
        } else {
            $error = "Error booking appointment. Please try again.";
        }
        $stmt->close();
    }
}

// Fetch patient's appointments
$appointments = $conn->query("SELECT * FROM Appointments WHERE PatientID=$patient_id ORDER BY AppointmentDate DESC, AppointmentTime DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        :root{--accent:#6246ea;--accent-soft:#ede9fe;--ink:#0f0e17;--paper:#fffffe;--surface:#f7f6fb;--muted:#72737d;--border:#e8e7f0;}
        body{font-family:'DM Sans',sans-serif;background:var(--surface);color:var(--ink);min-height:100vh;display:flex;flex-direction:column;}
        nav{background:var(--paper);border-bottom:1px solid var(--border);padding:0 2.5rem;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;}
        .nav-logo{font-family:'Syne',sans-serif;font-weight:800;font-size:1.2rem;color:var(--accent);text-decoration:none;}
        .nav-logo em{background:var(--accent);color:#fff;font-style:normal;padding:1px 7px;border-radius:5px;margin-right:3px;}
        .nav-right{display:flex;align-items:center;gap:12px;}
        .patient-badge{background:var(--accent-soft);color:var(--accent);font-size:0.75rem;font-weight:700;padding:4px 12px;border-radius:50px;border:1px solid #d4c8fc;}
        .btn-logout{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:50px;padding:6px 16px;font-size:0.82rem;font-weight:600;text-decoration:none;}
        .main{max-width:700px;margin:0 auto;padding:2rem 1.5rem;flex:1;}
        .page-title{font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800;margin-bottom:0.3rem;}
        .page-sub{color:var(--muted);font-size:0.88rem;margin-bottom:2rem;}
        .card{background:var(--paper);border:1px solid var(--border);border-radius:16px;padding:2rem;margin-bottom:1.5rem;}
        .field-label{font-size:0.73rem;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:var(--ink);display:block;margin-bottom:0.4rem;}
        .input-wrap{border:1.5px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:1.1rem;background:#fafafa;display:flex;align-items:center;}
        .input-wrap:focus-within{border-color:var(--accent);background:#fff;}
        .input-icon{padding:0 14px;height:50px;display:flex;align-items:center;background:#f0f0f0;border-right:1px solid #eee;}
        .input-wrap input,.input-wrap select,.input-wrap textarea{flex:1;border:none;background:transparent;padding:0 14px;height:50px;font-size:0.95rem;font-family:'DM Sans',sans-serif;color:var(--ink);outline:none;}
        .input-wrap textarea{height:80px;padding:10px 14px;resize:vertical;}
        .btn-submit{width:100%;padding:14px;background:var(--accent);color:#fff;border:none;border-radius:12px;font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;cursor:pointer;margin-top:0.5rem;}
        .alert-success{background:#dcfce7;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;font-size:0.84rem;color:#15803d;margin-bottom:1.25rem;}
        .alert-error{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;font-size:0.84rem;color:#dc2626;margin-bottom:1.25rem;}
        .test-types{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:1rem;}
        .test-chip{padding:5px 14px;background:var(--accent-soft);border:1px solid #d4c8fc;border-radius:50px;font-size:0.78rem;color:var(--accent);cursor:pointer;}
        table{width:100%;border-collapse:collapse;}
        thead th{text-align:left;padding:9px 1rem;font-size:0.71rem;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:var(--muted);border-bottom:1px solid var(--border);background:var(--surface);}
        tbody td{padding:10px 1rem;border-bottom:1px solid #f0eff6;font-size:0.84rem;}
        .badge{display:inline-block;padding:3px 10px;border-radius:50px;font-size:0.71rem;font-weight:600;}
        .badge-Pending{background:#fef3c7;color:#b45309;}
        .badge-Confirmed{background:#dbeafe;color:#1d4ed8;}
        .badge-Completed{background:#dcfce7;color:#15803d;}
        .badge-Cancelled{background:#fee2e2;color:#dc2626;}
        .back-link{display:inline-block;margin-top:1rem;font-size:0.83rem;color:var(--muted);text-decoration:none;}
        .empty-state{text-align:center;padding:2rem;color:var(--muted);}
    </style>
</head>
<body>
<nav>
    <a href="/LIMS/patient_dashboard.php" class="nav-logo"><em>L</em>IMS</a>
    <div class="nav-right">
        <span class="patient-badge">👤 Patient</span>
        <a href="/LIMS/patient_logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="main">
    <p class="page-title">📅 Book Appointment</p>
    <p class="page-sub">Schedule a lab test appointment.</p>

    <div class="card">
        <?php if($success): ?><div class="alert-success">✓ <?php echo $success; ?></div><?php endif; ?>
        <?php if($error):   ?><div class="alert-error">⚠ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form method="POST" action="/LIMS/book_appointment.php">
            <?php echo csrfField(); ?>
            <label class="field-label">Test Type</label>
            <div class="test-types">
                <?php foreach(['CBC','Lipid Profile','Blood Sugar','LFT','KFT','Thyroid','Urine Analysis','PCR','Hepatitis Panel','Full Body Checkup'] as $t): ?>
                <span class="test-chip" onclick="document.getElementById('test_type').value='<?php echo $t; ?>'"><?php echo $t; ?></span>
                <?php endforeach; ?>
            </div>
            <div class="input-wrap">
                <div class="input-icon">🔬</div>
                <input type="text" name="test_type" id="test_type" placeholder="Select or type test type" required>
            </div>

            <label class="field-label">Preferred Date</label>
            <div class="input-wrap">
                <div class="input-icon">📅</div>
                <input type="date" name="apt_date" min="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <label class="field-label">Preferred Time</label>
            <div class="input-wrap">
                <div class="input-icon">⏰</div>
                <input type="time" name="apt_time" required>
            </div>

            <label class="field-label">Notes (optional)</label>
            <div class="input-wrap">
                <div class="input-icon">📝</div>
                <textarea name="notes" placeholder="Any special instructions, fasting requirements, etc."></textarea>
            </div>

            <button type="submit" class="btn-submit">Book Appointment →</button>
        </form>
    </div>

    <!-- My Appointments -->
    <div class="card">
        <p class="page-sub" style="margin-bottom:1rem;font-weight:700;color:var(--ink);">My Appointments</p>
        <table>
            <thead><tr><th>Test Type</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
                <?php if($appointments->num_rows===0): ?>
                <tr><td colspan="4"><div class="empty-state">No appointments yet.</div></td></tr>
                <?php else: ?>
                <?php while($a=$appointments->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight:500;"><?php echo htmlspecialchars($a['TestType']); ?></td>
                    <td style="color:var(--muted);"><?php echo $a['AppointmentDate']; ?></td>
                    <td style="color:var(--muted);"><?php echo date('h:i A', strtotime($a['AppointmentTime'])); ?></td>
                    <td><span class="badge badge-<?php echo $a['Status']; ?>"><?php echo $a['Status']; ?></span></td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a href="/LIMS/patient_dashboard.php" class="back-link">← Back to Dashboard</a>
</div>
</body>
</html>