<?php
// Auth check — role based
include 'auth_check.php';
checkAuth();
include 'config.php';

$role     = currentRole();
$fullname = currentUser();

// Role colors & labels
$roleColors = [
    'admin'      => '#7c3aed',
    'technician' => '#0891b2',
    'manager'    => '#059669',
    'doctor'     => '#dc2626',
];
$roleColor = $roleColors[$role] ?? '#6246ea';

// Common stats
$totalPatients = $conn->query("SELECT COUNT(*) as t FROM Patients")->fetch_assoc()['t'];
$totalTests    = $conn->query("SELECT COUNT(*) as t FROM LabTests")->fetch_assoc()['t'];
$todayTests    = $conn->query("SELECT COUNT(*) as t FROM LabTests WHERE DATE(TestDate)=CURDATE()")->fetch_assoc()['t'];

// Gender chart data
$genderData   = $conn->query("SELECT Gender, COUNT(*) as total FROM Patients GROUP BY Gender");
$genderLabels = []; $genderCounts = [];
while($r = $genderData->fetch_assoc()){ $genderLabels[]=$r['Gender']; $genderCounts[]=$r['total']; }

// Test chart data
$testData   = $conn->query("SELECT TestName, COUNT(*) as total FROM LabTests GROUP BY TestName");
$testLabels = []; $testCounts = [];
while($r = $testData->fetch_assoc()){ $testLabels[]=$r['TestName']; $testCounts[]=$r['total']; }

// Latest patients & tests
$latestPatients = $conn->query("SELECT * FROM Patients ORDER BY PatientID DESC LIMIT 5");
$latestTests    = $conn->query("
    SELECT LabTests.*, Patients.Name as PatientName
    FROM LabTests JOIN Patients ON LabTests.PatientID=Patients.PatientID
    ORDER BY LabTests.TestID DESC LIMIT 5
");

// Admin only: total users
$totalUsers = 0;
if ($role === 'admin') {
    $totalUsers = $conn->query("SELECT COUNT(*) as t FROM Users WHERE IsActive=1")->fetch_assoc()['t'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIMS Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --accent: <?php echo $roleColor; ?>;
            --accent-soft: <?php echo $roleColor; ?>22;
            --ink: #0f0e17;
            --paper: #fffffe;
            --surface: #f7f6fb;
            --muted: #72737d;
            --border: #e8e7f0;
        }
        body { font-family: 'DM Sans', sans-serif; background: var(--surface); color: var(--ink); min-height: 100vh; display: flex; flex-direction: column; }

        /* Nav */
        nav {
            background: var(--paper); border-bottom: 1px solid var(--border);
            padding: 0 2.5rem; height: 64px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
        }
        .nav-logo { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.2rem; color: var(--accent); text-decoration: none; }
        .nav-logo em { background: var(--accent); color: #fff; font-style: normal; padding: 1px 7px; border-radius: 5px; margin-right: 3px; }
        .nav-links { display: flex; align-items: center; gap: 4px; }
        .nav-links a { color: var(--muted); text-decoration: none; font-size: 0.87rem; font-weight: 500; padding: 6px 12px; border-radius: 8px; transition: background 0.15s, color 0.15s; }
        .nav-links a:hover, .nav-links a.active { background: var(--accent-soft); color: var(--accent); }
        .nav-right { display: flex; align-items: center; gap: 10px; }
        .role-badge {
            font-size: 0.72rem; font-weight: 700; padding: 4px 12px;
            border-radius: 50px; border: 1px solid <?php echo $roleColor; ?>44;
            background: <?php echo $roleColor; ?>18; color: var(--accent);
        }
        .nav-user { font-size: 0.84rem; color: var(--muted); }
        .nav-user strong { color: var(--ink); }
        .btn-logout { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 50px; padding: 6px 16px; font-size: 0.82rem; font-weight: 600; text-decoration: none; transition: background 0.15s; }
        .btn-logout:hover { background: #fee2e2; }

        /* Hero */
        .hero {
            background: linear-gradient(135deg, var(--accent) 0%, #1e1b4b 100%);
            padding: 2.5rem; color: #fff; position: relative; overflow: hidden;
        }
        .hero::before { content: ''; position: absolute; width: 500px; height: 500px; border-radius: 50%; border: 60px solid rgba(255,255,255,0.06); top: -150px; right: -100px; }
        .hero-inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 2rem; position: relative; z-index: 2; }
        .hero h1 { font-family: 'Syne', sans-serif; font-size: 1.9rem; font-weight: 800; line-height: 1.15; margin-bottom: 0.3rem; }
        .hero p { opacity: 0.75; font-size: 0.9rem; }
        .hero-stats { display: flex; gap: 1rem; flex-shrink: 0; }
        .hero-stat { background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); border-radius: 14px; padding: 1rem 1.5rem; text-align: center; }
        .hero-stat .num { font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800; line-height: 1; }
        .hero-stat .lbl { font-size: 0.7rem; opacity: 0.7; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.06em; }

        /* Main */
        .main { max-width: 1100px; margin: 0 auto; padding: 2rem 1.5rem; flex: 1; }
        .section-label { font-family: 'Syne', sans-serif; font-size: 0.74rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); margin-bottom: 1rem; }

        /* Action cards */
        .actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .action-card { background: var(--paper); border: 1px solid var(--border); border-radius: 14px; padding: 1.25rem; text-decoration: none; color: var(--ink); transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s; display: flex; flex-direction: column; gap: 0.6rem; }
        .action-card:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(0,0,0,0.1); border-color: var(--accent); color: var(--ink); text-decoration: none; }
        .action-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        .action-card h3 { font-family: 'Syne', sans-serif; font-size: 0.88rem; font-weight: 700; }
        .action-card p { font-size: 0.77rem; color: var(--muted); line-height: 1.4; }

        /* Charts */
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 2rem; }
        .chart-card { background: var(--paper); border: 1px solid var(--border); border-radius: 14px; padding: 1.5rem; }
        .chart-title { font-family: 'Syne', sans-serif; font-size: 0.88rem; font-weight: 700; margin-bottom: 1.25rem; }

        /* Tables */
        .tables-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 2rem; }
        .table-card { background: var(--paper); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
        .table-header { display: flex; align-items: center; justify-content: space-between; padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--border); }
        .table-header h3 { font-family: 'Syne', sans-serif; font-size: 0.88rem; font-weight: 700; }
        .table-header a { font-size: 0.8rem; color: var(--accent); text-decoration: none; font-weight: 500; }
        .search-box { padding: 7px 14px; border: 1px solid var(--border); border-radius: 50px; font-size: 0.83rem; font-family: 'DM Sans', sans-serif; outline: none; background: var(--surface); width: 180px; transition: border-color 0.2s; }
        .search-box:focus { border-color: var(--accent); }
        table { width: 100%; border-collapse: collapse; }
        thead th { text-align: left; padding: 9px 1.25rem; font-size: 0.71rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: var(--muted); border-bottom: 1px solid var(--border); background: var(--surface); }
        tbody td { padding: 10px 1.25rem; border-bottom: 1px solid #f0eff6; font-size: 0.84rem; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: var(--surface); }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 50px; font-size: 0.71rem; font-weight: 600; }
        .badge-m { background: #dbeafe; color: #1e40af; }
        .badge-f { background: #fce7f3; color: #9d174d; }
        .badge-admin { background: #ede9fe; color: #7c3aed; }
        .badge-technician { background: #e0f2fe; color: #0369a1; }
        .badge-manager { background: #dcfce7; color: #15803d; }
        .badge-doctor { background: #fef3c7; color: #b45309; }
        .badge-normal { background: #dcfce7; color: #15803d; }
        .badge-high { background: #fef3c7; color: #b45309; }
        .badge-low { background: #dbeafe; color: #1d4ed8; }
        .badge-other { background: #ede9fe; color: #6246ea; }
        .btn-dl { display: inline-block; padding: 4px 12px; background: var(--accent-soft); color: var(--accent); border-radius: 50px; font-size: 0.75rem; font-weight: 600; text-decoration: none; }

        /* Doctor readonly notice */
        .readonly-note { background: #fef3c7; border: 1px solid #fde68a; border-radius: 10px; padding: 10px 16px; font-size: 0.82rem; color: #92400e; margin-bottom: 1.5rem; }

        /* Footer */
        footer {
            background: var(--ink); color: rgba(255,255,255,0.5);
            padding: 2rem 2.5rem; margin-top: auto;
        }
        .footer-inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
        .footer-logo { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.1rem; color: #fff; }
        .footer-logo em { background: var(--accent); color: #fff; font-style: normal; padding: 1px 7px; border-radius: 5px; margin-right: 3px; }
        .footer-links { display: flex; gap: 1.5rem; }
        .footer-links a { color: rgba(255,255,255,0.45); text-decoration: none; font-size: 0.83rem; transition: color 0.15s; }
        .footer-links a:hover { color: #fff; }
        .footer-copy { font-size: 0.78rem; }

        @media (max-width: 900px) { .charts-grid, .tables-grid { grid-template-columns: 1fr; } .hero-stats { display: none; } }
        @media (max-width: 600px) { nav { padding: 0 1rem; } .hero { padding: 2rem 1rem; } .main { padding: 1.5rem 1rem; } .nav-links { display: none; } }
    </style>
</head>
<body>

<!-- NAV -->
<nav>
    <a href="index.php" class="nav-logo"><em>L</em>IMS</a>
    <div class="nav-links">
        <a href="index.php" class="active">Dashboard</a>
        <?php if (in_array($role, ['admin','technician'])): ?>
            <a href="add_patient.php">Add Patient</a>
            <a href="add_test.php">Add Test</a>
        <?php endif; ?>
        <?php if (in_array($role, ['admin','technician','manager'])): ?>
            <a href="view_patients.php">Patients</a>
        <?php endif; ?>
        <a href="view_tests.php">Tests</a>
        <?php if (in_array($role, ['admin','manager'])): ?>
            <a href="reports/patient_reports.php">Reports</a>
        <?php endif; ?>
        <?php if ($role === 'admin'): ?>
            <a href="manage_users.php">Users</a>
        <?php endif; ?>
    </div>
    <div class="nav-right">
        <span class="role-badge">
            <?php
            $icons = ['admin'=>'⚙️','technician'=>'🧪','manager'=>'📊','doctor'=>'👨‍⚕️'];
            echo ($icons[$role]??'').' '.ucfirst($role);
            ?>
        </span>
        <span class="nav-user">Hi, <strong><?php echo htmlspecialchars($fullname); ?></strong></span>
        <a href="auth_logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <div class="hero-inner">
        <div>
            <h1>
                <?php
                $greet = ['admin'=>'Admin Dashboard','technician'=>'Technician Dashboard','manager'=>'Manager Dashboard','doctor'=>'Doctor Dashboard'];
                echo $greet[$role] ?? 'LIMS Dashboard';
                ?>
            </h1>
            <p>Welcome back, <?php echo htmlspecialchars($fullname); ?> &nbsp;|&nbsp; <?php echo date('l, d M Y'); ?></p>
        </div>
        <div class="hero-stats">
            <div class="hero-stat">
                <div class="num"><?php echo $totalPatients; ?></div>
                <div class="lbl">Patients</div>
            </div>
            <div class="hero-stat">
                <div class="num"><?php echo $totalTests; ?></div>
                <div class="lbl">Tests</div>
            </div>
            <div class="hero-stat">
                <div class="num"><?php echo $todayTests; ?></div>
                <div class="lbl">Today</div>
            </div>
            <?php if ($role === 'admin'): ?>
            <div class="hero-stat">
                <div class="num"><?php echo $totalUsers; ?></div>
                <div class="lbl">Users</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MAIN -->
<div class="main">

    <?php if ($role === 'doctor'): ?>
    <div class="readonly-note">👁 You have <strong>read-only access</strong>. You can view test results and download patient reports.</div>
    <?php endif; ?>

    <!-- QUICK ACTIONS — role based -->
    <p class="section-label">Quick Actions</p>
    <div class="actions-grid">
        <?php if ($role === 'admin'): ?>
            <a href="manage_users.php" class="action-card"><div class="action-icon" style="background:#ede9fe;">👥</div><h3>Manage Users</h3><p>Add, edit or deactivate accounts.</p></a>
            <a href="add_patient.php" class="action-card"><div class="action-icon" style="background:#e0f2fe;">👤</div><h3>Add Patient</h3><p>Register a new patient.</p></a>
            <a href="add_test.php" class="action-card"><div class="action-icon" style="background:#dcfce7;">🧪</div><h3>Add Lab Test</h3><p>Record a new test result.</p></a>
            <a href="view_patients.php" class="action-card"><div class="action-icon" style="background:#fef3c7;">📋</div><h3>View Patients</h3><p>Browse all patients.</p></a>
            <a href="view_tests.php" class="action-card"><div class="action-icon" style="background:#ffe4e6;">🔬</div><h3>View Tests</h3><p>Browse all lab tests.</p></a>
            <a href="reports/patient_reports.php" class="action-card"><div class="action-icon" style="background:#f0fdf4;">📥</div><h3>Reports</h3><p>Download CSV reports.</p></a>

        <?php elseif ($role === 'technician'): ?>
            <a href="add_patient.php" class="action-card"><div class="action-icon" style="background:#e0f2fe;">👤</div><h3>Register Patient</h3><p>Add a new patient.</p></a>
            <a href="add_test.php" class="action-card"><div class="action-icon" style="background:#dcfce7;">🧪</div><h3>Add Lab Test</h3><p>Record test result.</p></a>
            <a href="view_patients.php" class="action-card"><div class="action-icon" style="background:#fef3c7;">📋</div><h3>View Patients</h3><p>Browse all patients.</p></a>
            <a href="view_tests.php" class="action-card"><div class="action-icon" style="background:#ffe4e6;">🔬</div><h3>View Tests</h3><p>Browse all tests.</p></a>

        <?php elseif ($role === 'manager'): ?>
            <a href="view_patients.php" class="action-card"><div class="action-icon" style="background:#d1fae5;">📋</div><h3>View Patients</h3><p>All registered patients.</p></a>
            <a href="view_tests.php" class="action-card"><div class="action-icon" style="background:#e0f2fe;">🔬</div><h3>View Tests</h3><p>All lab test records.</p></a>
            <a href="reports/patient_reports.php" class="action-card"><div class="action-icon" style="background:#fef3c7;">📥</div><h3>Download Reports</h3><p>Export patient reports.</p></a>

        <?php elseif ($role === 'doctor'): ?>
            <a href="view_patients.php" class="action-card"><div class="action-icon" style="background:#fee2e2;">👤</div><h3>View Patients</h3><p>Browse patient records.</p></a>
            <a href="view_tests.php" class="action-card"><div class="action-icon" style="background:#fef3c7;">🔬</div><h3>Test Results</h3><p>View all lab results.</p></a>
            <a href="reports/patient_reports.php" class="action-card"><div class="action-icon" style="background:#ede9fe;">📥</div><h3>Download Reports</h3><p>Get patient CSV reports.</p></a>
        <?php endif; ?>
    </div>

    <!-- CHARTS -->
    <p class="section-label">Analytics</p>
    <div class="charts-grid">
        <div class="chart-card">
            <p class="chart-title">Patient Gender Distribution</p>
            <canvas id="patientsChart" height="200"></canvas>
        </div>
        <div class="chart-card">
            <p class="chart-title">Tests by Type</p>
            <canvas id="testsChart" height="200"></canvas>
        </div>
    </div>

    <!-- TABLES -->
    <div class="tables-grid">
        <!-- Patients -->
        <?php if ($role !== 'doctor'): ?>
        <div class="table-card">
            <div class="table-header">
                <h3>Recent Patients</h3>
                <a href="view_patients.php">View all →</a>
            </div>
            <table>
                <thead><tr><th>ID</th><th>Name</th><th>Age</th><th>Gender</th><th>Contact</th></tr></thead>
                <tbody id="patientsTable">
                    <?php while($r = $latestPatients->fetch_assoc()): ?>
                    <tr>
                        <td style="color:var(--muted);font-size:0.78rem;">#<?php echo $r['PatientID']; ?></td>
                        <td style="font-weight:500;"><?php echo htmlspecialchars($r['Name']); ?></td>
                        <td><?php echo $r['Age']; ?></td>
                        <td><span class="badge badge-<?php echo strtolower($r['Gender'][0]); ?>"><?php echo $r['Gender']; ?></span></td>
                        <td style="color:var(--muted);"><?php echo htmlspecialchars($r['ContactNumber']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Tests -->
        <div class="table-card">
            <div class="table-header">
                <h3>Recent Lab Tests</h3>
                <a href="view_tests.php">View all →</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Patient</th><th>Test</th><th>Date</th><th>Result</th>
                        <?php if ($role === 'doctor'): ?><th>Report</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="testsTable">
                    <?php while($r = $latestTests->fetch_assoc()):
                        $res = strtolower($r['Result']);
                        if(strpos($res,'normal')!==false){$cls='badge-normal';}
                        elseif(strpos($res,'high')!==false){$cls='badge-high';}
                        elseif(strpos($res,'low')!==false){$cls='badge-low';}
                        else{$cls='badge-other';}
                    ?>
                    <tr>
                        <td style="font-weight:500;"><?php echo htmlspecialchars($r['PatientName']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($r['TestName'])); ?></td>
                        <td style="color:var(--muted);"><?php echo $r['TestDate']; ?></td>
                        <td><span class="badge <?php echo $cls; ?>"><?php echo ucfirst(htmlspecialchars($r['Result'])); ?></span></td>
                        <?php if ($role === 'doctor'): ?>
                        <td><a class="btn-dl" href="reports/download_patient_report.php?id=<?php echo $r['PatientID']; ?>">Download</a></td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Admin: Users table -->
        <?php if ($role === 'admin'):
            $recentUsers = $conn->query("SELECT * FROM Users ORDER BY CreatedAt DESC LIMIT 5");
        ?>
        <div class="table-card">
            <div class="table-header">
                <h3>Recent Users</h3>
                <a href="manage_users.php">Manage →</a>
            </div>
            <table>
                <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th></tr></thead>
                <tbody>
                    <?php while($u = $recentUsers->fetch_assoc()): ?>
                    <tr>
                        <td style="font-weight:500;"><?php echo htmlspecialchars($u['FullName']); ?></td>
                        <td style="color:var(--muted);"><?php echo htmlspecialchars($u['Username']); ?></td>
                        <td><span class="badge badge-<?php echo $u['Role']; ?>"><?php echo ucfirst($u['Role']); ?></span></td>
                        <td><span class="badge" style="background:<?php echo $u['IsActive']?'#dcfce7':'#fee2e2'; ?>;color:<?php echo $u['IsActive']?'#15803d':'#dc2626'; ?>"><?php echo $u['IsActive']?'Active':'Inactive'; ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /main -->

<!-- FOOTER -->
<footer>
    <div class="footer-inner">
        <div class="footer-logo"><em>L</em>IMS</div>
        <div class="footer-links">
            <a href="index.php">Dashboard</a>
            <a href="view_patients.php">Patients</a>
            <a href="view_tests.php">Tests</a>
            <?php if(in_array($role,['admin','manager'])): ?>
            <a href="reports/patient_reports.php">Reports</a>
            <?php endif; ?>
            <?php if($role==='admin'): ?>
            <a href="manage_users.php">Users</a>
            <?php endif; ?>
        </div>
        <div class="footer-copy">© <?php echo date('Y'); ?> LIMS — Women University Multan</div>
    </div>
</footer>

<script>
new Chart(document.getElementById('patientsChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($genderLabels); ?>,
        datasets: [{ data: <?php echo json_encode($genderCounts); ?>, backgroundColor: ['<?php echo $roleColor; ?>','#2563eb','#16a34a'], borderWidth: 0, hoverOffset: 6 }]
    },
    options: { cutout: '65%', responsive: true, plugins: { legend: { position: 'bottom', labels: { padding: 16, font: { family: 'DM Sans', size: 12 } } } } }
});
new Chart(document.getElementById('testsChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($testLabels); ?>,
        datasets: [{ data: <?php echo json_encode($testCounts); ?>, backgroundColor: '<?php echo $roleColor; ?>', borderRadius: 6, borderSkipped: false }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#f0eff6' } }, x: { grid: { display: false } } } }
});
</script>
</body>
</html>