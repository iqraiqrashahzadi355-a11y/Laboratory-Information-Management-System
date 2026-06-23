<?php
include 'auth_check.php';
checkRole(['manager', 'admin']);
include 'config.php';

$totalPatients = $conn->query("SELECT COUNT(*) as t FROM Patients")->fetch_assoc()['t'];
$totalTests    = $conn->query("SELECT COUNT(*) as t FROM LabTests")->fetch_assoc()['t'];
$todayTests    = $conn->query("SELECT COUNT(*) as t FROM LabTests WHERE DATE(TestDate)=CURDATE()")->fetch_assoc()['t'];

$testData = $conn->query("SELECT TestName, COUNT(*) as total FROM LabTests GROUP BY TestName ORDER BY total DESC LIMIT 5");
$latestTests = $conn->query("
    SELECT LabTests.*, Patients.Name as PatientName
    FROM LabTests JOIN Patients ON LabTests.PatientID=Patients.PatientID
    ORDER BY LabTests.TestID DESC LIMIT 8
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:#f7f6fb;color:#0f0e17;}
        .hero{background:linear-gradient(135deg,#059669,#0d9488);padding:2rem 2.5rem;color:#fff;}
        .hero h1{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:0.3rem;}
        .hero p{opacity:0.8;font-size:0.9rem;}
        .main{max-width:1100px;margin:0 auto;padding:2rem 1.5rem;}
        .stats{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:2rem;}
        .stat-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;padding:1.25rem 1.5rem;}
        .stat-card .num{font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:#059669;}
        .stat-card .lbl{font-size:0.8rem;color:#72737d;margin-top:4px;text-transform:uppercase;letter-spacing:0.06em;}
        .section-title{font-family:'Syne',sans-serif;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#72737d;margin-bottom:1rem;}
        .grid2{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:2rem;}
        .chart-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;padding:1.5rem;}
        .chart-card h3{font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;margin-bottom:1rem;}
        .actions{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:2rem;}
        .action-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;padding:1.25rem;text-decoration:none;color:#0f0e17;transition:transform 0.15s,border-color 0.15s;display:flex;flex-direction:column;gap:0.6rem;}
        .action-card:hover{transform:translateY(-3px);border-color:#059669;}
        .action-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;}
        .action-card h3{font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;}
        .action-card p{font-size:0.78rem;color:#72737d;}
        .table-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;overflow:hidden;}
        .table-header{padding:1.1rem 1.5rem;border-bottom:1px solid #e8e7f0;display:flex;justify-content:space-between;align-items:center;}
        .table-header h3{font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;}
        .table-header a{font-size:0.8rem;color:#059669;text-decoration:none;font-weight:500;}
        table{width:100%;border-collapse:collapse;font-size:0.84rem;}
        thead th{text-align:left;padding:9px 1.5rem;font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:#72737d;border-bottom:1px solid #e8e7f0;background:#f7f6fb;}
        tbody td{padding:10px 1.5rem;border-bottom:1px solid #f0eff6;}
        tbody tr:last-child td{border-bottom:none;}
        tbody tr:hover td{background:#f7f6fb;}
        @media(max-width:700px){.grid2{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>

<div class="hero">
    <h1>Manager Dashboard</h1>
    <p>Monitor lab operations, view analytics, and generate reports.</p>
</div>

<div class="main">
    <div class="stats">
        <div class="stat-card"><div class="num"><?php echo $totalPatients; ?></div><div class="lbl">Total Patients</div></div>
        <div class="stat-card"><div class="num"><?php echo $totalTests; ?></div><div class="lbl">Total Tests</div></div>
        <div class="stat-card"><div class="num"><?php echo $todayTests; ?></div><div class="lbl">Tests Today</div></div>
    </div>

    <p class="section-title">Quick Actions</p>
    <div class="actions">
        <a href="view_patients.php" class="action-card">
            <div class="action-icon" style="background:#d1fae5;">📋</div>
            <h3>View Patients</h3><p>All registered patients.</p>
        </a>
        <a href="view_tests.php" class="action-card">
            <div class="action-icon" style="background:#e0f2fe;">🔬</div>
            <h3>View Tests</h3><p>All lab test records.</p>
        </a>
        <a href="reports/patient_reports.php" class="action-card">
            <div class="action-icon" style="background:#fef3c7;">📥</div>
            <h3>Download Reports</h3><p>Export patient reports.</p>
        </a>
    </div>

    <?php
    $tLabels = []; $tCounts = [];
    while($r = $testData->fetch_assoc()){ $tLabels[]=$r['TestName']; $tCounts[]=$r['total']; }
    ?>
    <p class="section-title">Analytics</p>
    <div class="grid2">
        <div class="chart-card">
            <h3>Top Tests by Volume</h3>
            <canvas id="testsChart" height="200"></canvas>
        </div>
        <div class="table-card" style="border-radius:14px;">
            <div class="table-header"><h3>Recent Lab Tests</h3><a href="view_tests.php">View all →</a></div>
            <table>
                <thead><tr><th>Patient</th><th>Test</th><th>Date</th></tr></thead>
                <tbody>
                    <?php while($r = $latestTests->fetch_assoc()): ?>
                    <tr>
                        <td style="font-weight:500;"><?php echo htmlspecialchars($r['PatientName']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($r['TestName'])); ?></td>
                        <td style="color:#72737d;"><?php echo $r['TestDate']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('testsChart').getContext('2d'), {
    type:'bar',
    data:{
        labels:<?php echo json_encode($tLabels); ?>,
        datasets:[{data:<?php echo json_encode($tCounts); ?>,backgroundColor:'#059669',borderRadius:6,borderSkipped:false}]
    },
    options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'#f0eff6'}},x:{grid:{display:false}}}}
});
</script>
</body>
</html>