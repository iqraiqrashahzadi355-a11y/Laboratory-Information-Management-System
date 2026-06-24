<?php
include 'auth_check.php';
checkRole(['admin']);
include 'config.php';

$totalPatients  = $conn->query("SELECT COUNT(*) as t FROM Patients")->fetch_assoc()['t'];
$totalTests     = $conn->query("SELECT COUNT(*) as t FROM LabTests")->fetch_assoc()['t'];
$totalUsers     = $conn->query("SELECT COUNT(*) as t FROM Users WHERE IsActive=1")->fetch_assoc()['t'];
$completedTests = $conn->query("SELECT COUNT(*) as t FROM LabTests WHERE Status='Completed'")->fetch_assoc()['t'];
$pendingTests   = $conn->query("SELECT COUNT(*) as t FROM LabTests WHERE Status='Registered'")->fetch_assoc()['t'];
$testingTests   = $conn->query("SELECT COUNT(*) as t FROM LabTests WHERE Status='Testing'")->fetch_assoc()['t'];
$totalRevenue   = $conn->query("SELECT COALESCE(SUM(Amount),0) as t FROM Billing WHERE PaymentStatus='Paid'")->fetch_assoc()['t'];
$pendingBills   = $conn->query("SELECT COUNT(*) as t FROM Billing WHERE PaymentStatus='Unpaid'")->fetch_assoc()['t'];

// Gender distribution
$maleCount   = $conn->query("SELECT COUNT(*) as t FROM Patients WHERE Gender='Male'")->fetch_assoc()['t'];
$femaleCount = $conn->query("SELECT COUNT(*) as t FROM Patients WHERE Gender='Female'")->fetch_assoc()['t'];
$otherCount  = $conn->query("SELECT COUNT(*) as t FROM Patients WHERE Gender='Other'")->fetch_assoc()['t'];

// Top tests
$topTests = $conn->query("SELECT TestName, COUNT(*) as total FROM LabTests GROUP BY TestName ORDER BY total DESC LIMIT 6");
$tLabels = []; $tCounts = [];
while($r = $topTests->fetch_assoc()){ $tLabels[] = $r['TestName']; $tCounts[] = $r['total']; }

// Last 7 days tests
$last7 = $conn->query("SELECT DATE(TestDate) as d, COUNT(*) as c FROM LabTests WHERE TestDate >= DATE_SUB(CURDATE(),INTERVAL 6 DAY) GROUP BY DATE(TestDate) ORDER BY d");
$days = []; $dayCounts = [];
while($r = $last7->fetch_assoc()){ $days[] = date('D', strtotime($r['d'])); $dayCounts[] = $r['c']; }

// Role distribution
$roleData = $conn->query("SELECT Role, COUNT(*) as c FROM Users WHERE IsActive=1 GROUP BY Role");
$roleLabels = []; $roleCounts = [];
while($r = $roleData->fetch_assoc()){ $roleLabels[] = ucfirst($r['Role']); $roleCounts[] = $r['c']; }

// Recent users
$latestUsers = $conn->query("SELECT * FROM Users ORDER BY CreatedAt DESC LIMIT 5");

// Recent audit
$recentAudit = $conn->query("SELECT * FROM AuditLog ORDER BY LoggedAt DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:#f7f6fb;color:#0f0e17;}
        .hero{background:linear-gradient(135deg,#7c3aed,#4f46e5);padding:2rem 2.5rem;color:#fff;}
        .hero h1{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:0.3rem;}
        .hero p{opacity:0.8;font-size:0.9rem;}
        .main{max-width:1200px;margin:0 auto;padding:2rem 1.5rem;}
        .stats{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem;}
        .stat-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;padding:1.25rem 1.5rem;display:flex;align-items:center;gap:1rem;}
        .stat-icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;}
        .stat-card .num{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;color:#7c3aed;line-height:1;}
        .stat-card .lbl{font-size:0.75rem;color:#72737d;margin-top:3px;text-transform:uppercase;letter-spacing:0.05em;}
        .stat-card .trend{font-size:0.72rem;color:#059669;font-weight:600;margin-top:2px;}
        .section-title{font-family:'Syne',sans-serif;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#72737d;margin-bottom:1rem;}
        .actions{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem;}
        .action-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;padding:1.25rem;text-decoration:none;color:#0f0e17;transition:transform 0.15s,box-shadow 0.15s,border-color 0.15s;display:flex;flex-direction:column;gap:0.6rem;}
        .action-card:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(124,58,237,0.12);border-color:#7c3aed;}
        .action-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;}
        .action-card h3{font-family:'Syne',sans-serif;font-size:0.88rem;font-weight:700;}
        .action-card p{font-size:0.76rem;color:#72737d;}
        .charts-grid{display:grid;grid-template-columns:2fr 1fr;gap:1.25rem;margin-bottom:2rem;}
        .charts-row{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;margin-bottom:2rem;}
        .chart-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;padding:1.5rem;}
        .chart-card h3{font-family:'Syne',sans-serif;font-size:0.88rem;font-weight:700;margin-bottom:1.25rem;color:#0f0e17;}
        .table-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;overflow:hidden;}
        .table-header{padding:1.1rem 1.5rem;border-bottom:1px solid #e8e7f0;display:flex;justify-content:space-between;align-items:center;}
        .table-header h3{font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;}
        .table-header a{font-size:0.8rem;color:#7c3aed;text-decoration:none;font-weight:500;}
        .tables-row{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:2rem;}
        table{width:100%;border-collapse:collapse;font-size:0.84rem;}
        thead th{text-align:left;padding:9px 1.5rem;font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:#72737d;border-bottom:1px solid #e8e7f0;background:#f7f6fb;}
        tbody td{padding:10px 1.5rem;border-bottom:1px solid #f0eff6;}
        tbody tr:last-child td{border-bottom:none;}
        tbody tr:hover td{background:#f7f6fb;}
        .badge{display:inline-block;padding:3px 10px;border-radius:50px;font-size:0.7rem;font-weight:600;}
        .badge-admin{background:#ede9fe;color:#7c3aed;}
        .badge-technician{background:#e0f2fe;color:#0369a1;}
        .badge-manager{background:#dcfce7;color:#15803d;}
        .badge-doctor{background:#fef3c7;color:#b45309;}
        .badge-active{background:#dcfce7;color:#15803d;}
        .badge-inactive{background:#fee2e2;color:#dc2626;}
        @media(max-width:900px){.stats{grid-template-columns:repeat(2,1fr);}.charts-grid,.charts-row,.tables-row{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>

<div class="hero">
    <h1>Admin Dashboard</h1>
    <p>Full system overview — patients, tests, users, revenue, and activity.</p>
</div>

<div class="main">
    <!-- Stats -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon" style="background:#ede9fe;">👥</div>
            <div><div class="num"><?php echo $totalPatients; ?></div><div class="lbl">Total Patients</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#dcfce7;">🧪</div>
            <div><div class="num"><?php echo $totalTests; ?></div><div class="lbl">Total Tests</div><div class="trend">✓ <?php echo $completedTests; ?> completed</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#dbeafe;">👤</div>
            <div><div class="num"><?php echo $totalUsers; ?></div><div class="lbl">Active Staff</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef3c7;">💰</div>
            <div><div class="num">Rs.<?php echo number_format($totalRevenue); ?></div><div class="lbl">Revenue</div><div class="trend" style="color:#f59e0b;"><?php echo $pendingBills; ?> unpaid</div></div>
        </div>
    </div>

    <!-- Quick Actions -->
    <p class="section-title">Quick Actions</p>
    <div class="actions">
        <a href="manage_users.php" class="action-card"><div class="action-icon" style="background:#ede9fe;">👥</div><h3>Manage Users</h3><p>Add, edit, deactivate accounts.</p></a>
        <a href="add_patient.php" class="action-card"><div class="action-icon" style="background:#e0f2fe;">👤</div><h3>Add Patient</h3><p>Register a new patient.</p></a>
        <a href="add_test.php" class="action-card"><div class="action-icon" style="background:#dcfce7;">🧪</div><h3>Add Test</h3><p>Record a new test result.</p></a>
        <a href="track_samples.php" class="action-card"><div class="action-icon" style="background:#fef3c7;">📍</div><h3>Tracking</h3><p>Update sample status.</p></a>
        <a href="manage_billing.php" class="action-card"><div class="action-icon" style="background:#ffe4e6;">💰</div><h3>Billing</h3><p>Manage invoices & payments.</p></a>
        <a href="view_audit_log.php" class="action-card"><div class="action-icon" style="background:#f0fdf4;">📋</div><h3>Audit Log</h3><p>View all system activity.</p></a>
    </div>

    <!-- Charts Row 1 -->
    <p class="section-title">Analytics</p>
    <div class="charts-grid">
        <div class="chart-card">
            <h3>Tests Last 7 Days</h3>
            <canvas id="lineChart" height="120"></canvas>
        </div>
        <div class="chart-card" style="display:flex;flex-direction:column;align-items:center;">
            <h3 style="align-self:flex-start;">Sample Status</h3>
            <canvas id="donutChart" height="180"></canvas>
        </div>
    </div>

    <div class="charts-row">
        <div class="chart-card">
            <h3>Top Tests by Volume</h3>
            <canvas id="barChart" height="180"></canvas>
        </div>
        <div class="chart-card" style="display:flex;flex-direction:column;align-items:center;">
            <h3 style="align-self:flex-start;">Patient Gender</h3>
            <canvas id="genderChart" height="180"></canvas>
        </div>
        <div class="chart-card" style="display:flex;flex-direction:column;align-items:center;">
            <h3 style="align-self:flex-start;">Staff Roles</h3>
            <canvas id="roleChart" height="180"></canvas>
        </div>
    </div>

    <!-- Tables -->
    <div class="tables-row">
        <div class="table-card">
            <div class="table-header"><h3>Recent Users</h3><a href="manage_users.php">Manage all →</a></div>
            <table>
                <thead><tr><th>Name</th><th>Role</th><th>Status</th></tr></thead>
                <tbody>
                    <?php while($u = $latestUsers->fetch_assoc()): ?>
                    <tr>
                        <td style="font-weight:500;"><?php echo htmlspecialchars($u['FullName']); ?><br><span style="font-size:0.75rem;color:#72737d;"><?php echo $u['Username']; ?></span></td>
                        <td><span class="badge badge-<?php echo $u['Role']; ?>"><?php echo ucfirst($u['Role']); ?></span></td>
                        <td><span class="badge <?php echo $u['IsActive'] ? 'badge-active':'badge-inactive'; ?>"><?php echo $u['IsActive'] ? 'Active':'Inactive'; ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="table-card">
            <div class="table-header"><h3>Recent Activity</h3><a href="view_audit_log.php">View all →</a></div>
            <table>
                <thead><tr><th>User</th><th>Action</th><th>Time</th></tr></thead>
                <tbody>
                    <?php while($a = $recentAudit->fetch_assoc()): ?>
                    <tr>
                        <td style="font-weight:500;"><?php echo htmlspecialchars($a['Username']); ?></td>
                        <td style="font-size:0.78rem;color:#72737d;"><?php echo htmlspecialchars(substr($a['Action'],0,30)); ?>...</td>
                        <td style="font-size:0.75rem;color:#72737d;"><?php echo date('d M H:i', strtotime($a['LoggedAt'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Line chart - last 7 days
new Chart(document.getElementById('lineChart').getContext('2d'),{
    type:'line',
    data:{
        labels:<?php echo json_encode($days ?: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']); ?>,
        datasets:[{label:'Tests',data:<?php echo json_encode($dayCounts ?: [0,0,0,0,0,0,0]); ?>,borderColor:'#7c3aed',backgroundColor:'rgba(124,58,237,0.08)',borderWidth:2.5,pointBackgroundColor:'#7c3aed',pointRadius:4,tension:0.4,fill:true}]
    },
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#f0eff6'}},x:{grid:{display:false}}}}
});

// Donut - sample status
new Chart(document.getElementById('donutChart').getContext('2d'),{
    type:'doughnut',
    data:{
        labels:['Completed','Testing','Registered'],
        datasets:[{data:[<?php echo $completedTests; ?>,<?php echo $testingTests; ?>,<?php echo $pendingTests; ?>],backgroundColor:['#059669','#f59e0b','#7c3aed'],borderWidth:0,hoverOffset:6}]
    },
    options:{responsive:true,cutout:'68%',plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:12}}}}
});

// Bar - top tests
new Chart(document.getElementById('barChart').getContext('2d'),{
    type:'bar',
    data:{
        labels:<?php echo json_encode($tLabels); ?>,
        datasets:[{data:<?php echo json_encode($tCounts); ?>,backgroundColor:['#7c3aed','#4f46e5','#0891b2','#059669','#f59e0b','#dc2626'],borderRadius:6,borderSkipped:false}]
    },
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#f0eff6'}},x:{grid:{display:false},ticks:{font:{size:10}}}}}
});

// Pie - gender
new Chart(document.getElementById('genderChart').getContext('2d'),{
    type:'doughnut',
    data:{
        labels:['Male','Female','Other'],
        datasets:[{data:[<?php echo $maleCount; ?>,<?php echo $femaleCount; ?>,<?php echo $otherCount; ?>],backgroundColor:['#3b82f6','#ec4899','#8b5cf6'],borderWidth:0,hoverOffset:6}]
    },
    options:{responsive:true,cutout:'60%',plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:10}}}}
});

// Pie - roles
new Chart(document.getElementById('roleChart').getContext('2d'),{
    type:'doughnut',
    data:{
        labels:<?php echo json_encode($roleLabels); ?>,
        datasets:[{data:<?php echo json_encode($roleCounts); ?>,backgroundColor:['#7c3aed','#0891b2','#059669','#f59e0b'],borderWidth:0,hoverOffset:6}]
    },
    options:{responsive:true,cutout:'60%',plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:10}}}}
});
</script>
</body>
</html>