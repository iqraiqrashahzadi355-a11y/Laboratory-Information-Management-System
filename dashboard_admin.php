<?php
include 'auth_check.php';
checkRole(['admin']);
include 'config.php';

$totalPatients = $conn->query("SELECT COUNT(*) as t FROM Patients")->fetch_assoc()['t'];
$totalTests    = $conn->query("SELECT COUNT(*) as t FROM LabTests")->fetch_assoc()['t'];
$totalUsers    = $conn->query("SELECT COUNT(*) as t FROM Users WHERE IsActive=1")->fetch_assoc()['t'];
$latestUsers   = $conn->query("SELECT * FROM Users ORDER BY CreatedAt DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:#f7f6fb;color:#0f0e17;}
        .hero{background:linear-gradient(135deg,#7c3aed,#4f46e5);padding:2rem 2.5rem;color:#fff;}
        .hero h1{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:0.3rem;}
        .hero p{opacity:0.8;font-size:0.9rem;}
        .main{max-width:1100px;margin:0 auto;padding:2rem 1.5rem;}
        .stats{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:2rem;}
        .stat-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;padding:1.25rem 1.5rem;}
        .stat-card .num{font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:#7c3aed;}
        .stat-card .lbl{font-size:0.8rem;color:#72737d;margin-top:4px;text-transform:uppercase;letter-spacing:0.06em;}
        .section-title{font-family:'Syne',sans-serif;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#72737d;margin-bottom:1rem;}
        .actions{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:2rem;}
        .action-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;padding:1.25rem;text-decoration:none;color:#0f0e17;transition:transform 0.15s,box-shadow 0.15s,border-color 0.15s;display:flex;flex-direction:column;gap:0.6rem;}
        .action-card:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(124,58,237,0.12);border-color:#7c3aed;}
        .action-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;}
        .action-card h3{font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;}
        .action-card p{font-size:0.78rem;color:#72737d;}
        .table-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;overflow:hidden;}
        .table-header{padding:1.1rem 1.5rem;border-bottom:1px solid #e8e7f0;display:flex;justify-content:space-between;align-items:center;}
        .table-header h3{font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;}
        .table-header a{font-size:0.8rem;color:#7c3aed;text-decoration:none;font-weight:500;}
        table{width:100%;border-collapse:collapse;font-size:0.84rem;}
        thead th{text-align:left;padding:9px 1.5rem;font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:#72737d;border-bottom:1px solid #e8e7f0;background:#f7f6fb;}
        tbody td{padding:10px 1.5rem;border-bottom:1px solid #f0eff6;}
        tbody tr:last-child td{border-bottom:none;}
        tbody tr:hover td{background:#f7f6fb;}
        .badge{display:inline-block;padding:3px 12px;border-radius:50px;font-size:0.72rem;font-weight:600;}
        .badge-admin{background:#ede9fe;color:#7c3aed;}
        .badge-technician{background:#e0f2fe;color:#0369a1;}
        .badge-manager{background:#dcfce7;color:#15803d;}
        .badge-doctor{background:#fef3c7;color:#b45309;}
        .badge-active{background:#dcfce7;color:#15803d;}
        .badge-inactive{background:#fee2e2;color:#dc2626;}
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>

<div class="hero">
    <h1>Admin Dashboard</h1>
    <p>Full system access — manage users, patients, tests, and reports.</p>
</div>

<div class="main">
    <!-- Stats -->
    <div class="stats">
        <div class="stat-card">
            <div class="num"><?php echo $totalPatients; ?></div>
            <div class="lbl">Total Patients</div>
        </div>
        <div class="stat-card">
            <div class="num"><?php echo $totalTests; ?></div>
            <div class="lbl">Total Tests</div>
        </div>
        <div class="stat-card">
            <div class="num"><?php echo $totalUsers; ?></div>
            <div class="lbl">Active Users</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <p class="section-title">Quick Actions</p>
    <div class="actions">
        <a href="manage_users.php" class="action-card">
            <div class="action-icon" style="background:#ede9fe;">👥</div>
            <h3>Manage Users</h3>
            <p>Add, edit, or deactivate user accounts.</p>
        </a>
        <a href="add_patient.php" class="action-card">
            <div class="action-icon" style="background:#e0f2fe;">👤</div>
            <h3>Add Patient</h3>
            <p>Register a new patient.</p>
        </a>
        <a href="add_test.php" class="action-card">
            <div class="action-icon" style="background:#dcfce7;">🧪</div>
            <h3>Add Lab Test</h3>
            <p>Record a new test result.</p>
        </a>
        <a href="view_patients.php" class="action-card">
            <div class="action-icon" style="background:#fef3c7;">📋</div>
            <h3>View Patients</h3>
            <p>Browse all patient records.</p>
        </a>
        <a href="view_tests.php" class="action-card">
            <div class="action-icon" style="background:#ffe4e6;">🔬</div>
            <h3>View Tests</h3>
            <p>Browse all lab test results.</p>
        </a>
        <a href="reports/patient_reports.php" class="action-card">
            <div class="action-icon" style="background:#f0fdf4;">📥</div>
            <h3>Reports</h3>
            <p>Download patient CSV reports.</p>
        </a>
    </div>

    <!-- Recent Users -->
    <p class="section-title">Recent Users</p>
    <div class="table-card">
        <div class="table-header">
            <h3>System Users</h3>
            <a href="manage_users.php">Manage all →</a>
        </div>
        <table>
            <thead>
                <tr><th>ID</th><th>Full Name</th><th>Username</th><th>Role</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php while($u = $latestUsers->fetch_assoc()): ?>
                <tr>
                    <td style="color:#72737d;font-size:0.78rem;">#<?php echo $u['UserID']; ?></td>
                    <td style="font-weight:500;"><?php echo htmlspecialchars($u['FullName']); ?></td>
                    <td style="color:#72737d;"><?php echo htmlspecialchars($u['Username']); ?></td>
                    <td><span class="badge badge-<?php echo $u['Role']; ?>"><?php echo ucfirst($u['Role']); ?></span></td>
                    <td><span class="badge <?php echo $u['IsActive'] ? 'badge-active' : 'badge-inactive'; ?>"><?php echo $u['IsActive'] ? 'Active' : 'Inactive'; ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>