<?php
include 'auth_check.php';
checkRole(['doctor', 'admin']);
include 'config.php';

$totalPatients = $conn->query("SELECT COUNT(*) as t FROM Patients")->fetch_assoc()['t'];
$totalTests    = $conn->query("SELECT COUNT(*) as t FROM LabTests")->fetch_assoc()['t'];
$latestTests   = $conn->query("
    SELECT LabTests.*, Patients.Name as PatientName
    FROM LabTests JOIN Patients ON LabTests.PatientID=Patients.PatientID
    ORDER BY LabTests.TestID DESC LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:#f7f6fb;color:#0f0e17;}
        .hero{background:linear-gradient(135deg,#dc2626,#b91c1c);padding:2rem 2.5rem;color:#fff;}
        .hero h1{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:0.3rem;}
        .hero p{opacity:0.8;font-size:0.9rem;}
        .main{max-width:1000px;margin:0 auto;padding:2rem 1.5rem;}
        .stats{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-bottom:2rem;}
        .stat-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;padding:1.25rem 1.5rem;}
        .stat-card .num{font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:#dc2626;}
        .stat-card .lbl{font-size:0.8rem;color:#72737d;margin-top:4px;text-transform:uppercase;letter-spacing:0.06em;}
        .section-title{font-family:'Syne',sans-serif;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#72737d;margin-bottom:1rem;}
        .table-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;overflow:hidden;}
        .table-header{padding:1.1rem 1.5rem;border-bottom:1px solid #e8e7f0;display:flex;justify-content:space-between;align-items:center;}
        .table-header h3{font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;}
        .table-header a{font-size:0.8rem;color:#dc2626;text-decoration:none;font-weight:500;}
        .search-box{padding:7px 14px;border:1px solid #e8e7f0;border-radius:50px;font-size:0.83rem;font-family:'DM Sans',sans-serif;outline:none;background:#f7f6fb;transition:border-color 0.2s;width:200px;}
        .search-box:focus{border-color:#dc2626;}
        table{width:100%;border-collapse:collapse;font-size:0.84rem;}
        thead th{text-align:left;padding:9px 1.5rem;font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:#72737d;border-bottom:1px solid #e8e7f0;background:#f7f6fb;}
        tbody td{padding:10px 1.5rem;border-bottom:1px solid #f0eff6;}
        tbody tr:last-child td{border-bottom:none;}
        tbody tr:hover td{background:#f7f6fb;}
        .badge{display:inline-block;padding:3px 12px;border-radius:50px;font-size:0.72rem;font-weight:600;}
        .badge-normal{background:#dcfce7;color:#15803d;}
        .badge-high{background:#fef3c7;color:#b45309;}
        .badge-low{background:#dbeafe;color:#1d4ed8;}
        .badge-other{background:#ede9fe;color:#6246ea;}
        .btn-dl{display:inline-block;padding:4px 12px;background:#fee2e2;color:#dc2626;border-radius:50px;font-size:0.75rem;font-weight:600;text-decoration:none;}
        .btn-dl:hover{background:#fecaca;}
        .readonly-note{background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:10px 14px;font-size:0.82rem;color:#92400e;margin-bottom:1.5rem;}
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>

<div class="hero">
    <h1>Doctor Dashboard</h1>
    <p>View patient lab results and download reports. Read-only access.</p>
</div>

<div class="main">
    <div class="readonly-note">
        👁 You have <strong>read-only access</strong>. You can view all lab results and download patient reports.
    </div>

    <div class="stats">
        <div class="stat-card"><div class="num"><?php echo $totalPatients; ?></div><div class="lbl">Total Patients</div></div>
        <div class="stat-card"><div class="num"><?php echo $totalTests; ?></div><div class="lbl">Total Tests</div></div>
    </div>

    <p class="section-title">Lab Test Results</p>
    <div class="table-card">
        <div class="table-header">
            <h3>All Lab Tests</h3>
            <input class="search-box" id="testSearch" type="text" placeholder="Search patient...">
        </div>
        <table>
            <thead>
                <tr><th>Test ID</th><th>Patient</th><th>Test Name</th><th>Date</th><th>Result</th><th>Status</th><th>Report</th></tr>
            </thead>
            <tbody id="testsTable">
                <?php while($r = $latestTests->fetch_assoc()):
                    $res = strtolower($r['Result']);
                    if(strpos($res,'normal')!==false){$cls='badge-normal';$lbl='Normal';}
                    elseif(strpos($res,'high')!==false){$cls='badge-high';$lbl='High';}
                    elseif(strpos($res,'low')!==false){$cls='badge-low';$lbl='Low';}
                    else{$cls='badge-other';$lbl='See Result';}
                ?>
                <tr>
                    <td style="color:#72737d;font-size:0.78rem;">#<?php echo $r['TestID']; ?></td>
                    <td style="font-weight:500;"><?php echo htmlspecialchars($r['PatientName']); ?></td>
                    <td><?php echo ucfirst(htmlspecialchars($r['TestName'])); ?></td>
                    <td style="color:#72737d;"><?php echo $r['TestDate']; ?></td>
                    <td><?php echo ucfirst(htmlspecialchars($r['Result'])); ?></td>
                    <td><span class="badge <?php echo $cls; ?>"><?php echo $lbl; ?></span></td>
                    <td><a class="btn-dl" href="reports/download_patient_report.php?id=<?php echo $r['PatientID']; ?>">Download</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('testSearch').addEventListener('keyup', function() {
    var f = this.value.toUpperCase();
    var rows = document.querySelector('#testsTable').rows;
    for(var i=0;i<rows.length;i++){
        var name = rows[i].cells[1]?rows[i].cells[1].textContent.toUpperCase():'';
        rows[i].style.display = name.includes(f)?'':'none';
    }
});
</script>
</body>
</html>