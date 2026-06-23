<?php
include '../auth_check.php';
checkRole(['admin', 'manager', 'doctor']);
include '../config.php';
include '../audit_log.php';

logAction($conn, "Viewed patient reports page");

$search = $_GET['search'] ?? '';
$where  = $search ? "WHERE Name LIKE '%" . $conn->real_escape_string($search) . "%'" : "";
$patients = $conn->query("SELECT PatientID, Name, Age, Gender FROM Patients $where ORDER BY PatientID DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Reports — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #f7f6fb; color: #0f0e17; min-height: 100vh; }
        .hero { background: linear-gradient(135deg, #1e293b, #334155); padding: 2rem 2.5rem; color: #fff; }
        .hero h1 { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; margin-bottom: 0.3rem; }
        .hero p { opacity: 0.7; font-size: 0.9rem; }
        .main { max-width: 900px; margin: 0 auto; padding: 2rem 1.5rem; }
        .filter-card { background: #fff; border: 1px solid #e8e7f0; border-radius: 14px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 0.4rem; flex: 1; }
        .filter-group label { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: #72737d; }
        .filter-input { padding: 9px 14px; border: 1.5px solid #e8e7f0; border-radius: 10px; font-size: 0.87rem; font-family: 'DM Sans', sans-serif; color: #0f0e17; background: #fafafa; outline: none; transition: border-color 0.2s; }
        .filter-input:focus { border-color: #6246ea; }
        .btn-filter { padding: 10px 20px; background: #1e293b; color: #fff; border: none; border-radius: 10px; font-family: 'Syne', sans-serif; font-weight: 700; font-size: 0.87rem; cursor: pointer; }
        .btn-reset { padding: 10px 16px; background: #f1f0f7; color: #72737d; border: none; border-radius: 10px; font-size: 0.87rem; cursor: pointer; text-decoration: none; display: inline-block; }
        .table-card { background: #fff; border: 1px solid #e8e7f0; border-radius: 14px; overflow: hidden; }
        .table-header { padding: 1.1rem 1.5rem; border-bottom: 1px solid #e8e7f0; display: flex; justify-content: space-between; align-items: center; }
        .table-header h3 { font-family: 'Syne', sans-serif; font-size: 0.9rem; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        thead th { text-align: left; padding: 9px 1.25rem; font-size: 0.71rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: #72737d; border-bottom: 1px solid #e8e7f0; background: #f7f6fb; }
        tbody td { padding: 11px 1.25rem; border-bottom: 1px solid #f0eff6; font-size: 0.84rem; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #f7f6fb; }
        .badge { display: inline-block; padding: 3px 12px; border-radius: 50px; font-size: 0.72rem; font-weight: 600; }
        .badge-Male   { background: #dbeafe; color: #1e40af; }
        .badge-Female { background: #fce7f3; color: #9d174d; }
        .badge-Other  { background: #ede9fe; color: #6246ea; }
        .btn-pdf { display: inline-block; padding: 6px 16px; background: #dc2626; color: #fff; border-radius: 50px; font-size: 0.78rem; font-weight: 600; text-decoration: none; transition: opacity 0.15s; }
        .btn-pdf:hover { opacity: 0.85; }
        .test-count { background: #f0f9ff; color: #0369a1; padding: 3px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 600; }
    </style>
</head>
<body>
<?php include '../auth_nav.php'; ?>

<div class="hero">
    <h1>📥 Patient Reports</h1>
    <p>Download complete PDF reports for any patient.</p>
</div>

<div class="main">
    <form method="GET" action="patient_reports.php">
        <div class="filter-card">
            <div class="filter-group">
                <label>Search Patient</label>
                <input type="text" name="search" class="filter-input" placeholder="Enter patient name..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <button type="submit" class="btn-filter">Search</button>
            <a href="patient_reports.php" class="btn-reset">Reset</a>
        </div>
    </form>

    <div class="table-card">
        <div class="table-header">
            <h3>All Patients</h3>
            <span style="font-size:0.82rem;color:#72737d;">Click Download to get PDF report</span>
        </div>
        <table>
            <thead>
                <tr><th>ID</th><th>Name</th><th>Age</th><th>Gender</th><th>Tests</th><th>Download</th></tr>
            </thead>
            <tbody>
                <?php while($r = $patients->fetch_assoc()):
                    $tc = $conn->query("SELECT COUNT(*) as t FROM LabTests WHERE PatientID=".$r['PatientID'])->fetch_assoc()['t'];
                ?>
                <tr>
                    <td style="color:#72737d;font-size:0.78rem;">#<?php echo $r['PatientID']; ?></td>
                    <td style="font-weight:500;"><?php echo htmlspecialchars($r['Name']); ?></td>
                    <td><?php echo $r['Age']; ?></td>
                    <td><span class="badge badge-<?php echo $r['Gender']; ?>"><?php echo $r['Gender']; ?></span></td>
                    <td><span class="test-count"><?php echo $tc; ?> test<?php echo $tc!=1?'s':''; ?></span></td>
                    <td>
                        <a class="btn-pdf" href="download_patient_report.php?id=<?php echo $r['PatientID']; ?>">
                            📄 PDF
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>