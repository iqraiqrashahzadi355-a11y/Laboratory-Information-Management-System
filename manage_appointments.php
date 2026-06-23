<?php
include 'auth_check.php';
checkRole(['admin', 'technician']);
include 'config.php';
include 'audit_log.php';
include 'mailer.php';

$success = ''; $error = '';

// Confirm / Complete / Cancel appointment
if (isset($_GET['action']) && isset($_GET['id'])) {
    $apt_id = intval($_GET['id']);
    $action = $_GET['action'];
    $map = ['confirm'=>'Confirmed','complete'=>'Completed','cancel'=>'Cancelled'];

    if (isset($map[$action])) {
        $new_status = $map[$action];
        $conn->query("UPDATE Appointments SET Status='$new_status' WHERE AppointmentID=$apt_id");
        logAction($conn, "Appointment #$apt_id marked as $new_status");

        // Notify patient if confirmed/cancelled
        if (in_array($new_status, ['Confirmed','Cancelled'])) {
            $apt = $conn->query("
                SELECT Appointments.*, Patients.Name, Patients.PatientID
                FROM Appointments JOIN Patients ON Appointments.PatientID=Patients.PatientID
                WHERE AppointmentID=$apt_id
            ")->fetch_assoc();
            // No patient email field exists, so just log — patient sees status in their portal
        }

        header("Location: /LIMS/manage_appointments.php?msg=" . strtolower($new_status));
        exit;
    }
}

if (isset($_GET['msg'])) {
    $success = "Appointment marked as " . htmlspecialchars($_GET['msg']) . ".";
}

$filterStatus = $_GET['status'] ?? '';
$where = $filterStatus ? "WHERE Appointments.Status='" . $conn->real_escape_string($filterStatus) . "'" : "";

$appointments = $conn->query("
    SELECT Appointments.*, Patients.Name as PatientName, Patients.ContactNumber
    FROM Appointments
    JOIN Patients ON Appointments.PatientID = Patients.PatientID
    $where
    ORDER BY Appointments.AppointmentDate ASC, Appointments.AppointmentTime ASC
");

$counts = [];
$cr = $conn->query("SELECT Status, COUNT(*) as t FROM Appointments GROUP BY Status");
while($r=$cr->fetch_assoc()) $counts[$r['Status']]=$r['t'];
foreach(['Pending','Confirmed','Completed','Cancelled'] as $s) $counts[$s] = $counts[$s] ?? 0;

logAction($conn, "Viewed appointments management");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:#f7f6fb;color:#0f0e17;}
        .hero{background:linear-gradient(135deg,#6246ea,#2563eb);padding:2rem 2.5rem;color:#fff;}
        .hero h1{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:0.3rem;}
        .hero p{opacity:0.8;font-size:0.9rem;}
        .main{max-width:1100px;margin:0 auto;padding:2rem 1.5rem;}
        .alert-success{background:#dcfce7;border:1px solid #bbf7d0;border-radius:10px;padding:10px 16px;font-size:0.84rem;color:#15803d;margin-bottom:1rem;}
        .status-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem;}
        .status-card{background:#fff;border:2px solid #e8e7f0;border-radius:14px;padding:1.1rem 1.25rem;text-decoration:none;display:block;transition:transform 0.15s;}
        .status-card:hover{transform:translateY(-2px);}
        .status-card .num{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;}
        .status-card .lbl{font-size:0.78rem;color:#72737d;margin-top:2px;}
        .card-pending .num{color:#b45309;}
        .card-confirmed .num{color:#1d4ed8;}
        .card-completed .num{color:#15803d;}
        .card-cancelled .num{color:#dc2626;}
        .table-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;overflow:hidden;}
        .table-header{padding:1.1rem 1.5rem;border-bottom:1px solid #e8e7f0;display:flex;justify-content:space-between;align-items:center;}
        .table-header h3{font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;}
        table{width:100%;border-collapse:collapse;font-size:0.84rem;}
        thead th{text-align:left;padding:9px 1.25rem;font-size:0.71rem;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:#72737d;border-bottom:1px solid #e8e7f0;background:#f7f6fb;}
        tbody td{padding:11px 1.25rem;border-bottom:1px solid #f0eff6;vertical-align:middle;}
        tbody tr:hover td{background:#f7f6fb;}
        .badge{display:inline-block;padding:3px 12px;border-radius:50px;font-size:0.72rem;font-weight:600;}
        .badge-Pending{background:#fef3c7;color:#b45309;}
        .badge-Confirmed{background:#dbeafe;color:#1d4ed8;}
        .badge-Completed{background:#dcfce7;color:#15803d;}
        .badge-Cancelled{background:#fee2e2;color:#dc2626;}
        .btn-action{display:inline-block;padding:4px 12px;border-radius:50px;font-size:0.73rem;font-weight:600;text-decoration:none;margin-right:4px;}
        .btn-confirm{background:#dbeafe;color:#1d4ed8;}
        .btn-complete{background:#dcfce7;color:#15803d;}
        .btn-cancel{background:#fee2e2;color:#dc2626;}
        .empty-state{text-align:center;padding:3rem;color:#72737d;}
        .reset-link{font-size:0.82rem;color:#6246ea;text-decoration:none;}
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>

<div class="hero">
    <h1>📅 Appointments</h1>
    <p>Manage patient appointment requests.</p>
</div>

<div class="main">
    <?php if($success): ?><div class="alert-success">✓ <?php echo $success; ?></div><?php endif; ?>

    <div class="status-cards">
        <a href="/LIMS/manage_appointments.php?status=Pending" class="status-card card-pending"><div class="num"><?php echo $counts['Pending']; ?></div><div class="lbl">⏳ Pending</div></a>
        <a href="/LIMS/manage_appointments.php?status=Confirmed" class="status-card card-confirmed"><div class="num"><?php echo $counts['Confirmed']; ?></div><div class="lbl">✅ Confirmed</div></a>
        <a href="/LIMS/manage_appointments.php?status=Completed" class="status-card card-completed"><div class="num"><?php echo $counts['Completed']; ?></div><div class="lbl">🏁 Completed</div></a>
        <a href="/LIMS/manage_appointments.php?status=Cancelled" class="status-card card-cancelled"><div class="num"><?php echo $counts['Cancelled']; ?></div><div class="lbl">❌ Cancelled</div></a>
    </div>

    <div class="table-card">
        <div class="table-header">
            <h3>All Appointments <?php if($filterStatus) echo "— ".$filterStatus; ?></h3>
            <a href="/LIMS/manage_appointments.php" class="reset-link">Show all</a>
        </div>
        <table>
            <thead><tr><th>Patient</th><th>Contact</th><th>Test Type</th><th>Date</th><th>Time</th><th>Notes</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if($appointments->num_rows===0): ?>
                <tr><td colspan="8"><div class="empty-state">No appointments found.</div></td></tr>
                <?php else: ?>
                <?php while($a=$appointments->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight:500;"><?php echo htmlspecialchars($a['PatientName']); ?></td>
                    <td style="color:#72737d;"><?php echo htmlspecialchars($a['ContactNumber']); ?></td>
                    <td><?php echo htmlspecialchars($a['TestType']); ?></td>
                    <td style="color:#72737d;"><?php echo $a['AppointmentDate']; ?></td>
                    <td style="color:#72737d;"><?php echo date('h:i A', strtotime($a['AppointmentTime'])); ?></td>
                    <td style="color:#72737d;font-size:0.78rem;"><?php echo htmlspecialchars($a['Notes'] ?: '—'); ?></td>
                    <td><span class="badge badge-<?php echo $a['Status']; ?>"><?php echo $a['Status']; ?></span></td>
                    <td>
                        <?php if($a['Status']==='Pending'): ?>
                            <a href="/LIMS/manage_appointments.php?action=confirm&id=<?php echo $a['AppointmentID']; ?>" class="btn-action btn-confirm">Confirm</a>
                            <a href="/LIMS/manage_appointments.php?action=cancel&id=<?php echo $a['AppointmentID']; ?>" class="btn-action btn-cancel">Cancel</a>
                        <?php elseif($a['Status']==='Confirmed'): ?>
                            <a href="/LIMS/manage_appointments.php?action=complete&id=<?php echo $a['AppointmentID']; ?>" class="btn-action btn-complete">Complete</a>
                            <a href="/LIMS/manage_appointments.php?action=cancel&id=<?php echo $a['AppointmentID']; ?>" class="btn-action btn-cancel">Cancel</a>
                        <?php else: ?>
                            <span style="color:#72737d;font-size:0.78rem;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>