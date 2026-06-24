<?php
include 'auth_check.php';
checkRole(['manager','admin']);
include 'config.php';

$totalPatients  = $conn->query("SELECT COUNT(*) as t FROM Patients")->fetch_assoc()['t'];
$totalTests     = $conn->query("SELECT COUNT(*) as t FROM LabTests")->fetch_assoc()['t'];
$todayTests     = $conn->query("SELECT COUNT(*) as t FROM LabTests WHERE DATE(TestDate)=CURDATE()")->fetch_assoc()['t'];
$completedTests = $conn->query("SELECT COUNT(*) as t FROM LabTests WHERE Status='Completed'")->fetch_assoc()['t'];
$testingTests   = $conn->query("SELECT COUNT(*) as t FROM LabTests WHERE Status='Testing'")->fetch_assoc()['t'];
$pendingTests   = $conn->query("SELECT COUNT(*) as t FROM LabTests WHERE Status='Registered'")->fetch_assoc()['t'];
$totalRevenue   = $conn->query("SELECT COALESCE(SUM(Amount),0) as t FROM Billing WHERE PaymentStatus='Paid'")->fetch_assoc()['t'];
$totalUnpaid    = $conn->query("SELECT COALESCE(SUM(Amount),0) as t FROM Billing WHERE PaymentStatus='Unpaid'")->fetch_assoc()['t'];

// Top tests
$topTests = $conn->query("SELECT TestName, COUNT(*) as total FROM LabTests GROUP BY TestName ORDER BY total DESC LIMIT 6");
$tLabels = []; $tCounts = [];
while($r = $topTests->fetch_assoc()){ $tLabels[] = $r['TestName']; $tCounts[] = $r['total']; }

// Last 7 days
$last7 = $conn->query("SELECT DATE(TestDate) as d, COUNT(*) as c FROM LabTests WHERE TestDate >= DATE_SUB(CURDATE(),INTERVAL 6 DAY) GROUP BY DATE(TestDate) ORDER BY d");
$days = []; $dayCounts = [];
while($r = $last7->fetch_assoc()){ $days[] = date('D', strtotime($r['d'])); $dayCounts[] = $r['c']; }

// Revenue by payment method
$cashRev  = $conn->query("SELECT COALESCE(SUM(Amount),0) as t FROM Billing WHERE PaymentStatus='Paid' AND PaymentMethod='Cash'")->fetch_assoc()['t'];
$cardRev  = $conn->query("SELECT COALESCE(SUM(Amount),0) as t FROM Billing WHERE PaymentStatus='Paid' AND PaymentMethod='Card'")->fetch_assoc()['t'];

// Recent tests
$latestTests = $conn->query("SELECT LabTests.*, Patients.Name as PatientName FROM LabTests JOIN Patients ON LabTests.PatientID=Patients.PatientID ORDER BY LabTests.TestID DESC LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:#f7f6fb;color:#0f0e17;}
        .hero{background:linear-gradient(135deg,#059669,#0d9488);padding:2rem 2.5rem;color:#fff;}
        .hero h1{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:0.3rem;}
        .hero p{opacity:0.8;font-size:0.9rem;}
        .main{max-width:1200px;margin:0 auto;padding:2rem 1.5rem;}
        .stats{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem;}
        .stat-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;padding:1.25rem 1.5rem;display:flex;align-items:center;gap:1rem;}
        .stat-icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;}
        .stat-card .num{font-family:'Syne',sans-serif;font-size:1.7rem;font-weight:800;color:#059669;line-height:1;}
        .stat-card .lbl{font-size:0.75rem;color:#72737d;margin-top:3px;text-transform:uppercase;letter-spacing:0.05em;}
        .stat-card .sub{font-size:0.72rem;color:#059669;font-weight:600;margin-top:2px;}
        .section-title{font-family:'Syne',sans-serif;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#72737d;margin-bottom:1rem;}
        .actions{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem;}
        .action-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;padding:1.25rem;text-decoration:none;color:#0f0e17;transition:transform 0.15s,border-color 0.15s;display:flex;flex-direction:column;gap:0.6rem;}
        .action-card:hover{transform:translateY(-3px);border-color:#059669;}
        .action-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;}
        .action-card h3{font-family:'Syne',sans-serif;font-size:0.88rem;font-weight:700;}
        .action-card p{font-size:0.76rem;color:#72737d;}
        .charts-grid{display:grid;grid-template-columns:2fr 1fr;gap:1.25rem;margin-bottom:1.25rem;}
        .charts-row{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:2rem;}
        .chart-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;padding:1.5rem;}
        .chart-card h3{font-family:'Syne',sans-serif;font-size:0.88rem;font-weight:700;margin-bottom:1.25rem;}
        .table-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;overflow:hidden;}
        .table-header{padding:1.1rem 1.5rem;border-bottom:1px solid #e8e7f0;display:flex;justify-content:space-between;align-items:center;}
        .table-header h3{font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;}
        .table-header a{font-size:0.8rem;color:#059669;text-decoration:none;font-weight:500;}
        table{width:100%;border-collapse:collapse;font-size:0.84rem;}
        thead th{text-align:left;padding:9px 1.5rem;font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:#72737d;border-bottom:1px solid #e8e7f0;background:#f7f6fb;}
        tbody td{padding:10px 1.5rem;border-bottom:1px solid #f0eff6;}
        tbody tr:last-child td{border-bottom:none;}
        tbody tr:hover td{background:#f7f6fb;}
        .badge{display:inline-block;padding:3px 10px;border-radius:50px;font-size:0.7rem;font-weight:600;}
        .badge-Completed{background:#dcfce7;color:#15803d;}
        .badge-Testing{background:#fef3c7;color:#b45309;}
        .badge-Registered{background:#ede9fe;color:#7c3aed;}
        @media(max-width:900px){.stats{grid-template-columns:repeat(2,1fr);}.charts-grid,.charts-row{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>
<div class="hero">
    <h1>Manager Dashboard</h1>
    <p>Monitor lab operations, analytics, billing, and reports.</p>
</div>
<div class="main">
    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon" style="background:#d1fae5;">👥</div>
            <div><div class="num"><?php echo $totalPatients; ?></div><div class="lbl">Total Patients</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#e0f2fe;">🧪</div>
            <div><div class="num"><?php echo $totalTests; ?></div><div class="lbl">Total Tests</div><div class="sub"><?php echo $todayTests; ?> today</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef3c7;">💰</div>
            <div><div class="num">Rs.<?php echo number_format($totalRevenue); ?></div><div class="lbl">Revenue Collected</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2;">⏳</div>
            <div><div class="num" style="color:#dc2626;">Rs.<?php echo number_format($totalUnpaid); ?></div><div class="lbl">Pending Amount</div></div>
        </div>
    </div>

    <p class="section-title">Quick Actions</p>
    <div class="actions">
        <a href="view_patients.php" class="action-card"><div class="action-icon" style="background:#d1fae5;">📋</div><h3>View Patients</h3><p>All registered patients.</p></a>
        <a href="view_tests.php" class="action-card"><div class="action-icon" style="background:#e0f2fe;">🔬</div><h3>View Tests</h3><p>All lab test records.</p></a>
        <a href="manage_billing.php" class="action-card"><div class="action-icon" style="background:#fef3c7;">💰</div><h3>Billing</h3><p>Revenue & payment tracking.</p></a>
        <a href="reports/patient_reports.php" class="action-card"><div class="action-icon" style="background:#ede9fe;">📥</div><h3>Reports</h3><p>Download patient reports.</p></a>
    </div>

    <p class="section-title">Analytics</p>
    <div class="charts-grid">
        <div class="chart-card">
            <h3>Tests — Last 7 Days</h3>
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
            <h3 style="align-self:flex-start;">Revenue — Cash vs Card</h3>
            <canvas id="revenueChart" height="180"></canvas>
        </div>
    </div>

    <div class="table-card">
        <div class="table-header"><h3>Recent Lab Tests</h3><a href="view_tests.php">View all →</a></div>
        <table>
            <thead><tr><th>Patient</th><th>Test</th><th>Date</th><th>Status</th></tr></thead>
            <tbody>
                <?php while($r = $latestTests->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight:500;"><?php echo htmlspecialchars($r['PatientName']); ?></td>
                    <td><?php echo htmlspecialchars($r['TestName']); ?></td>
                    <td style="color:#72737d;"><?php echo $r['TestDate']; ?></td>
                    <td><span class="badge badge-<?php echo $r['Status']; ?>"><?php echo $r['Status']; ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
new Chart(document.getElementById('lineChart').getContext('2d'),{
    type:'line',
    data:{labels:<?php echo json_encode($days ?: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']); ?>,datasets:[{label:'Tests',data:<?php echo json_encode($dayCounts ?: [0,0,0,0,0,0,0]); ?>,borderColor:'#059669',backgroundColor:'rgba(5,150,105,0.08)',borderWidth:2.5,pointBackgroundColor:'#059669',pointRadius:4,tension:0.4,fill:true}]},
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#f0eff6'}},x:{grid:{display:false}}}}
});

new Chart(document.getElementById('donutChart').getContext('2d'),{
    type:'doughnut',
    data:{labels:['Completed','Testing','Registered'],datasets:[{data:[<?php echo $completedTests; ?>,<?php echo $testingTests; ?>,<?php echo $pendingTests; ?>],backgroundColor:['#059669','#f59e0b','#7c3aed'],borderWidth:0,hoverOffset:6}]},
    options:{responsive:true,cutout:'68%',plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:12}}}}
});

new Chart(document.getElementById('barChart').getContext('2d'),{
    type:'bar',
    data:{labels:<?php echo json_encode($tLabels); ?>,datasets:[{data:<?php echo json_encode($tCounts); ?>,backgroundColor:'#059669',borderRadius:6,borderSkipped:false}]},
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#f0eff6'}},x:{grid:{display:false},ticks:{font:{size:10}}}}}
});

new Chart(document.getElementById('revenueChart').getContext('2d'),{
    type:'doughnut',
    data:{labels:['Cash','Card'],datasets:[{data:[<?php echo $cashRev; ?>,<?php echo $cardRev; ?>],backgroundColor:['#059669','#0891b2'],borderWidth:0,hoverOffset:6}]},
    options:{responsive:true,cutout:'60%',plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:12}}}}
});
</script>
</body>
</html>