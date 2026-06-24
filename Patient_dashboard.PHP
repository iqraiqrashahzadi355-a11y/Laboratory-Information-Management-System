<?php
session_start();

// Guard
if (!isset($_SESSION['patient_id'])) {
    header("Location: /LIMS/login.php");
    exit;
}

include 'config.php';

$patient_id      = $_SESSION['patient_id'];
$patient_name    = $_SESSION["patient_name"];

// Fetch from DB
$stmt = $conn->prepare("SELECT * FROM Patients WHERE PatientID = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$pinfo = $stmt->get_result()->fetch_assoc();
$stmt->close();
$patient_age     = $pinfo["Age"] ?? "";
$patient_gender  = $pinfo["Gender"] ?? "";
$patient_contact = $pinfo["ContactNumber"] ?? "";




// Fetch tests
$stmt = $conn->prepare("SELECT * FROM LabTests WHERE PatientID = ? ORDER BY TestDate DESC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$tests = $stmt->get_result();
$stmt->close();

$total_tests     = $conn->query("SELECT COUNT(*) as t FROM LabTests WHERE PatientID=$patient_id")->fetch_assoc()['t'];
$completed_tests = $conn->query("SELECT COUNT(*) as t FROM LabTests WHERE PatientID=$patient_id AND Status='Completed'")->fetch_assoc()['t'];
$pending_tests   = $total_tests - $completed_tests;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --accent: #6246ea; --accent-soft: #ede9fe; --ink: #0f0e17; --paper: #fffffe; --surface: #f7f6fb; --muted: #72737d; --border: #e8e7f0; }
        body { font-family: 'DM Sans', sans-serif; background: var(--surface); color: var(--ink); min-height: 100vh; display: flex; flex-direction: column; }

        /* Nav */
        nav { background: var(--paper); border-bottom: 1px solid var(--border); padding: 0 2.5rem; height: 64px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
        .nav-logo { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.2rem; color: var(--accent); text-decoration: none; }
        .nav-logo em { background: var(--accent); color: #fff; font-style: normal; padding: 1px 7px; border-radius: 5px; margin-right: 3px; }
        .nav-right { display: flex; align-items: center; gap: 12px; }
        .patient-badge { background: var(--accent-soft); color: var(--accent); font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 50px; border: 1px solid #d4c8fc; }
        .nav-name { font-size: 0.84rem; color: var(--muted); font-weight: 500; }
        .nav-name strong { color: var(--ink); }
        .btn-logout { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 50px; padding: 6px 16px; font-size: 0.82rem; font-weight: 600; text-decoration: none; }

        /* Hero */
        .hero { background: linear-gradient(135deg, #6246ea 0%, #2563eb 100%); padding: 2.5rem; color: #fff; position: relative; overflow: hidden; }
        .hero::before { content: ''; position: absolute; width: 500px; height: 500px; border-radius: 50%; border: 60px solid rgba(255,255,255,0.07); top: -150px; right: -100px; }
        .hero-inner { max-width:100%; display: flex; align-items: center; justify-content: space-between; gap: 2rem; position: relative; z-index: 2; }
        .avatar { width: 60px; height: 60px; border-radius: 50%; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-size: 1.5rem; font-weight: 800; color: #fff; flex-shrink: 0; }
        .hero-info h1 { font-family: 'Syne', sans-serif; font-size: 1.7rem; font-weight: 800; margin-bottom: 0.3rem; }
        .hero-info p { opacity: 0.75; font-size: 0.88rem; }
        .hero-stats { display: flex; gap: 1rem; flex-shrink: 0; }
        .hero-stat { background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; padding: 1rem 1.4rem; text-align: center; }
        .hero-stat .num { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; line-height: 1; }
        .hero-stat .lbl { font-size: 0.68rem; opacity: 0.7; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.06em; }

        /* Main */
        .main { max-width:100%; padding: 2rem 1.5rem; flex: 1; }

        /* Info card */
        .info-card { background: var(--paper); border: 1px solid var(--border); border-radius: 14px; padding: 1.5rem 1.75rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem; }
        .info-field label { font-size: 0.71rem; text-transform: uppercase; letter-spacing: 0.07em; color: var(--muted); font-weight: 600; display: block; margin-bottom: 4px; }
        .info-field span { font-size: 0.95rem; font-weight: 500; color: var(--ink); }

        /* Download btn */
        .btn-dl { display: inline-block; padding: 8px 20px; background: var(--accent); color: #fff; border-radius: 50px; font-size: 0.82rem; font-weight: 600; text-decoration: none; transition: opacity 0.15s; }
        .btn-dl:hover { opacity: 0.88; }

        /* Tests card */
        .tests-card { background: var(--paper); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
        .card-header { padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .card-header h2 { font-family: 'Syne', sans-serif; font-size: 0.95rem; font-weight: 700; }
        .search-box { padding: 7px 14px; border: 1px solid var(--border); border-radius: 50px; font-size: 0.83rem; font-family: 'DM Sans', sans-serif; outline: none; background: var(--surface); width: 180px; transition: border-color 0.2s; }
        .search-box:focus { border-color: var(--accent); }
        table { width: 100%; border-collapse: collapse; }
        thead th { text-align: left; padding: 9px 1.25rem; font-size: 0.71rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: var(--muted); border-bottom: 1px solid var(--border); background: var(--surface); }
        tbody td { padding: 11px 1.25rem; border-bottom: 1px solid #f0eff6; font-size: 0.84rem; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: var(--surface); }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 50px; font-size: 0.71rem; font-weight: 600; }
        .badge-Registered { background: #dbeafe; color: #1d4ed8; }
        .badge-Testing    { background: #fef3c7; color: #b45309; }
        .badge-Completed  { background: #dcfce7; color: #15803d; }
        .badge-normal { background: #dcfce7; color: #15803d; }
        .badge-high   { background: #fef3c7; color: #b45309; }
        .badge-low    { background: #dbeafe; color: #1d4ed8; }
        .badge-other  { background: var(--accent-soft); color: var(--accent); }
        .empty-state { text-align: center; padding: 3rem; color: var(--muted); }
        .empty-state .icon { font-size: 2.5rem; margin-bottom: 0.75rem; }

        /* Footer */
        footer { background: var(--ink); color: rgba(255,255,255,0.45); padding: 1.5rem 2.5rem; margin-top: auto; }
        .footer-inner { max-width:100%; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
        .footer-logo { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1rem; color: #fff; }
        .footer-logo em { background: var(--accent); color: #fff; font-style: normal; padding: 1px 7px; border-radius: 5px; margin-right: 3px; }

        @media (max-width: 700px) { .hero-stats { display: none; } .hero-inner { flex-direction: column; } nav { padding: 0 1rem; } .main { padding: 1.5rem 1rem; } }
    </style>
</head>
<body>

<!-- Nav -->
<nav>
    <a href="/LIMS/patient_dashboard.php" class="nav-logo"><em>L</em>IMS</a>
    <div class="nav-right">
        <span class="patient-badge">👤 Patient</span>
        <a href="/LIMS/book_appointment.php" style="font-size:0.84rem;color:var(--accent);text-decoration:none;font-weight:600;">📅 Appointments</a>
        <a href="/LIMS/patient_change_password.php" style="font-size:0.84rem;color:var(--muted);text-decoration:none;">🔑</a>
        <span class="nav-name">Hi, <strong><?php echo htmlspecialchars($patient_name); ?></strong></span>
        <a href="/LIMS/patient_logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<!-- Hero -->
<div class="hero">
    <div class="hero-inner">
        <div style="display:flex;align-items:center;gap:1.25rem;">
            <div class="avatar"><?php echo strtoupper(substr($patient_name,0,1)); ?></div>
            <div class="hero-info">
                <h1><?php echo htmlspecialchars($patient_name); ?></h1>
                <p>Patient ID: #<?php echo $patient_id; ?> &nbsp;|&nbsp; <?php echo htmlspecialchars($patient_gender); ?>, <?php echo $patient_age; ?> years</p>
            </div>
        </div>
        <div class="hero-stats">
            <div class="hero-stat"><div class="num"><?php echo $total_tests; ?></div><div class="lbl">Total Tests</div></div>
            <div class="hero-stat"><div class="num"><?php echo $completed_tests; ?></div><div class="lbl">Completed</div></div>
            <div class="hero-stat"><div class="num"><?php echo $pending_tests; ?></div><div class="lbl">Pending</div></div>
        </div>
    </div>
</div>

<!-- Main -->
<div class="main">

    <!-- Patient Info -->
    <div class="info-card">
        <div class="info-field"><label>Patient ID</label><span>#<?php echo $patient_id; ?></span></div>
        <div class="info-field"><label>Full Name</label><span><?php echo htmlspecialchars($patient_name); ?></span></div>
        <div class="info-field"><label>Age</label><span><?php echo $patient_age; ?> years</span></div>
        <div class="info-field"><label>Gender</label><span><?php echo htmlspecialchars($patient_gender); ?></span></div>
        <div class="info-field"><label>Contact</label><span><?php echo htmlspecialchars($patient_contact); ?></span></div>
        <div class="info-field">
            <label>Report</label>
            <a class="btn-dl" href="/LIMS/reports/download_patient_report.php?id=<?php echo $patient_id; ?>">📄 Download PDF</a>
        </div>
    </div>

    <!-- Tests Table -->
    <div class="tests-card">
        <div class="card-header">
            <h2>🧪 My Lab Tests</h2>
            <input class="search-box" id="testSearch" type="text" placeholder="Search tests...">
        </div>
        <table>
            <thead>
                <tr><th>#</th><th>Test Name</th><th>Date</th><th>Result</th><th>Status</th></tr>
            </thead>
            <tbody id="testsTable">
                <?php if ($total_tests == 0): ?>
                <tr><td colspan="5">
                    <div class="empty-state">
                        <div class="icon">🧪</div>
                        <p>No lab tests found for your account yet.</p>
                    </div>
                </td></tr>
                <?php else: ?>
                <?php while($row = $tests->fetch_assoc()):
                    $res = strtolower($row['Result']);
                    if(strpos($res,'normal')!==false)    { $rcls='badge-normal'; }
                    elseif(strpos($res,'high')!==false)  { $rcls='badge-high'; }
                    elseif(strpos($res,'low')!==false)   { $rcls='badge-low'; }
                    else                                  { $rcls='badge-other'; }
                ?>
                <tr>
                    <td style="color:var(--muted);font-size:0.78rem;">#<?php echo $row['TestID']; ?></td>
                    <td style="font-weight:500;"><?php echo ucfirst(htmlspecialchars($row['TestName'])); ?></td>
                    <td style="color:var(--muted);"><?php echo $row['TestDate']; ?></td>
                    <td><span class="badge <?php echo $rcls; ?>"><?php echo ucfirst(htmlspecialchars($row['Result'])); ?></span></td>
                    <td><span class="badge badge-<?php echo $row['Status']; ?>"><?php echo $row['Status']; ?></span></td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Footer -->
<footer>
    <div class="footer-inner">
        <div class="footer-logo"><em>L</em>IMS</div>
        <div style="font-size:0.78rem;">© <?php echo date('Y'); ?> LIMS — Women University Multan</div>
    </div>
</footer>

<script>
document.getElementById('testSearch').addEventListener('keyup', function() {
    var f = this.value.toUpperCase();
    var rows = document.querySelector('#testsTable').rows;
    for(var i=0;i<rows.length;i++){
        var name = rows[i].cells[1] ? rows[i].cells[1].textContent.toUpperCase() : '';
        rows[i].style.display = name.includes(f) ? '' : 'none';
    }
});
</script>
</body>
</html>
