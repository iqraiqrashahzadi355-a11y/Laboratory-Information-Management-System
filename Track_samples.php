<?php
include 'auth_check.php';
checkRole(['admin', 'technician', 'manager', 'doctor']);
include 'config.php';
include 'audit_log.php';

logAction($conn, "Viewed sample tracking");

$role = currentRole();

// Filters
$filterStatus  = $_GET['status'] ?? '';
$filterPatient = $_GET['patient'] ?? '';

$where = "WHERE 1=1";
if ($filterStatus)  $where .= " AND LabTests.Status = '" . $conn->real_escape_string($filterStatus) . "'";
if ($filterPatient) $where .= " AND Patients.Name LIKE '%" . $conn->real_escape_string($filterPatient) . "%'";

$tests = $conn->query("
    SELECT LabTests.*, Patients.Name as PatientName
    FROM LabTests
    JOIN Patients ON LabTests.PatientID = Patients.PatientID
    $where
    ORDER BY LabTests.TestID DESC
");

// Count by status
$counts = [];
$cr = $conn->query("SELECT Status, COUNT(*) as t FROM LabTests GROUP BY Status");
while($r = $cr->fetch_assoc()) $counts[$r['Status']] = $r['t'];
$counts['Registered'] = $counts['Registered'] ?? 0;
$counts['Testing']    = $counts['Testing']    ?? 0;
$counts['Completed']  = $counts['Completed']  ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Tracking — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #f7f6fb; color: #0f0e17; }
        .hero { background: linear-gradient(135deg, #0f172a, #1e3a5f); padding: 2rem 2.5rem; color: #fff; }
        .hero h1 { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; margin-bottom: 0.3rem; }
        .hero p { opacity: 0.7; font-size: 0.9rem; }
        .main { max-width:100%; padding: 2rem 1.5rem; }

        /* Status cards */
        .status-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem; }
        .status-card { background: #fff; border: 2px solid #e8e7f0; border-radius: 14px; padding: 1.25rem 1.5rem; cursor: pointer; text-decoration: none; display: block; transition: transform 0.15s, border-color 0.15s; }
        .status-card:hover { transform: translateY(-3px); }
        .status-card.registered { border-color: #93c5fd; }
        .status-card.registered:hover, .status-card.registered.active { border-color: #3b82f6; background: #eff6ff; }
        .status-card.testing { border-color: #fcd34d; }
        .status-card.testing:hover, .status-card.testing.active { border-color: #f59e0b; background: #fffbeb; }
        .status-card.completed { border-color: #6ee7b7; }
        .status-card.completed:hover, .status-card.completed.active { border-color: #10b981; background: #ecfdf5; }
        .status-card .num { font-family: 'Syne', sans-serif; font-size: 2.2rem; font-weight: 800; line-height: 1; margin-bottom: 4px; }
        .status-card.registered .num { color: #3b82f6; }
        .status-card.testing .num    { color: #f59e0b; }
        .status-card.completed .num  { color: #10b981; }
        .status-card .lbl { font-size: 0.82rem; color: #72737d; font-weight: 500; }
        .status-card .icon { font-size: 1.5rem; margin-bottom: 0.5rem; }

        /* Pipeline visual */
        .pipeline { display: flex; align-items: center; gap: 0; margin-bottom: 2rem; background: #fff; border: 1px solid #e8e7f0; border-radius: 14px; overflow: hidden; }
        .pipeline-step { flex: 1; padding: 1rem 1.25rem; text-align: center; position: relative; }
        .pipeline-step::after { content: '▶'; position: absolute; right: -8px; top: 50%; transform: translateY(-50%); color: #e8e7f0; font-size: 1.2rem; z-index: 2; }
        .pipeline-step:last-child::after { display: none; }
        .pipeline-step .step-num { font-family: 'Syne', sans-serif; font-size: 1.3rem; font-weight: 800; }
        .pipeline-step .step-lbl { font-size: 0.75rem; color: #72737d; margin-top: 2px; text-transform: uppercase; letter-spacing: 0.06em; }
        .step-registered { background: #eff6ff; }
        .step-registered .step-num { color: #3b82f6; }
        .step-testing { background: #fffbeb; }
        .step-testing .step-num { color: #f59e0b; }
        .step-completed { background: #ecfdf5; }
        .step-completed .step-num { color: #10b981; }

        /* Filters */
        .filter-card { background: #fff; border: 1px solid #e8e7f0; border-radius: 14px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 0.4rem; }
        .filter-group label { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: #72737d; }
        .filter-input { padding: 8px 14px; border: 1.5px solid #e8e7f0; border-radius: 10px; font-size: 0.87rem; font-family: 'DM Sans', sans-serif; color: #0f0e17; background: #fafafa; outline: none; transition: border-color 0.2s; }
        .filter-input:focus { border-color: #6246ea; }
        .btn-filter { padding: 9px 20px; background: #0f172a; color: #fff; border: none; border-radius: 10px; font-family: 'Syne', sans-serif; font-weight: 700; font-size: 0.87rem; cursor: pointer; }
        .btn-reset { padding: 9px 16px; background: #f1f0f7; color: #72737d; border: none; border-radius: 10px; font-size: 0.87rem; cursor: pointer; text-decoration: none; display: inline-block; }

        /* Alerts */
        .alert-success { background: #dcfce7; border: 1px solid #bbf7d0; border-radius: 10px; padding: 10px 14px; font-size: 0.84rem; color: #15803d; margin-bottom: 1rem; }
        .alert-error   { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 10px 14px; font-size: 0.84rem; color: #dc2626; margin-bottom: 1rem; }

        /* Table */
        .table-card { background: #fff; border: 1px solid #e8e7f0; border-radius: 14px; overflow: hidden; }
        .table-header { padding: 1.1rem 1.5rem; border-bottom: 1px solid #e8e7f0; display: flex; justify-content: space-between; align-items: center; }
        .table-header h3 { font-family: 'Syne', sans-serif; font-size: 0.9rem; font-weight: 700; }
        .table-header span { font-size: 0.82rem; color: #72737d; }
        table { width: 100%; border-collapse: collapse; }
        thead th { text-align: left; padding: 9px 1.25rem; font-size: 0.71rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: #72737d; border-bottom: 1px solid #e8e7f0; background: #f7f6fb; }
        tbody td { padding: 11px 1.25rem; border-bottom: 1px solid #f0eff6; font-size: 0.84rem; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #f7f6fb; }

        /* Status badges */
        .badge { display: inline-block; padding: 4px 12px; border-radius: 50px; font-size: 0.72rem; font-weight: 700; }
        .badge-Registered { background: #dbeafe; color: #1d4ed8; }
        .badge-Testing    { background: #fef3c7; color: #b45309; }
        .badge-Completed  { background: #dcfce7; color: #15803d; }

        /* Progress bar */
        .progress-wrap { display: flex; align-items: center; gap: 8px; }
        .progress-bar { flex: 1; height: 6px; background: #e8e7f0; border-radius: 50px; overflow: hidden; min-width: 60px; }
        .progress-fill { height: 100%; border-radius: 50px; }
        .fill-Registered { background: #3b82f6; width: 33%; }
        .fill-Testing    { background: #f59e0b; width: 66%; }
        .fill-Completed  { background: #10b981; width: 100%; }

        /* Status update dropdown — only for admin/technician */
        .status-select { padding: 5px 10px; border: 1.5px solid #e8e7f0; border-radius: 8px; font-size: 0.8rem; font-family: 'DM Sans', sans-serif; background: #fafafa; cursor: pointer; outline: none; }
        .btn-update { padding: 5px 14px; background: #0f172a; color: #fff; border: none; border-radius: 8px; font-size: 0.78rem; font-weight: 600; cursor: pointer; font-family: 'DM Sans', sans-serif; }
        .btn-update:hover { opacity: 0.85; }

        .section-label { font-family: 'Syne', sans-serif; font-size: 0.74rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #72737d; margin-bottom: 1rem; }
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>

<div class="hero">
    <h1>🔬 Sample Tracking</h1>
    <p>Track every lab test from registration to completion in real-time.</p>
</div>

<div class="main">

    <?php if (isset($_GET['success'])): ?>
    <div class="alert-success">✓ Sample status updated successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
    <div class="alert-error">⚠ Error updating status. Please try again.</div>
    <?php endif; ?>

    <!-- Status overview cards -->
    <p class="section-label">Overview</p>
    <div class="status-cards">
        <a href="track_samples.php?status=Registered" class="status-card registered <?php echo $filterStatus==='Registered'?'active':''; ?>">
            <div class="icon">📋</div>
            <div class="num"><?php echo $counts['Registered']; ?></div>
            <div class="lbl">Registered</div>
        </a>
        <a href="track_samples.php?status=Testing" class="status-card testing <?php echo $filterStatus==='Testing'?'active':''; ?>">
            <div class="icon">🧪</div>
            <div class="num"><?php echo $counts['Testing']; ?></div>
            <div class="lbl">In Testing</div>
        </a>
        <a href="track_samples.php?status=Completed" class="status-card completed <?php echo $filterStatus==='Completed'?'active':''; ?>">
            <div class="icon">✅</div>
            <div class="num"><?php echo $counts['Completed']; ?></div>
            <div class="lbl">Completed</div>
        </a>
    </div>

    <!-- Pipeline -->
    <p class="section-label">Sample Pipeline</p>
    <div class="pipeline" style="margin-bottom:2rem;">
        <div class="pipeline-step step-registered">
            <div class="step-num"><?php echo $counts['Registered']; ?></div>
            <div class="step-lbl">📋 Registered</div>
        </div>
        <div class="pipeline-step step-testing">
            <div class="step-num"><?php echo $counts['Testing']; ?></div>
            <div class="step-lbl">🧪 Testing</div>
        </div>
        <div class="pipeline-step step-completed">
            <div class="step-num"><?php echo $counts['Completed']; ?></div>
            <div class="step-lbl">✅ Completed</div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="track_samples.php">
        <div class="filter-card">
            <div class="filter-group">
                <label>Patient Name</label>
                <input type="text" name="patient" class="filter-input" placeholder="Search patient..." value="<?php echo htmlspecialchars($filterPatient); ?>">
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select name="status" class="filter-input">
                    <option value="">All Statuses</option>
                    <option value="Registered" <?php echo $filterStatus==='Registered'?'selected':''; ?>>Registered</option>
                    <option value="Testing"    <?php echo $filterStatus==='Testing'?'selected':''; ?>>Testing</option>
                    <option value="Completed"  <?php echo $filterStatus==='Completed'?'selected':''; ?>>Completed</option>
                </select>
            </div>
            <button type="submit" class="btn-filter">Filter</button>
            <a href="track_samples.php" class="btn-reset">Reset</a>
        </div>
    </form>

    <!-- Table -->
    <div class="table-card">
        <div class="table-header">
            <h3>All Samples</h3>
            <span><?php echo $tests->num_rows; ?> records</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Test ID</th>
                    <th>Patient</th>
                    <th>Test Name</th>
                    <th>Date</th>
                    <th>Result</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <?php if (in_array($role, ['admin','technician'])): ?>
                    <th>Update</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php while($r = $tests->fetch_assoc()): ?>
                <tr>
                    <td style="color:#72737d;font-size:0.78rem;">#<?php echo $r['TestID']; ?></td>
                    <td style="font-weight:500;"><?php echo htmlspecialchars($r['PatientName']); ?></td>
                    <td><?php echo ucfirst(htmlspecialchars($r['TestName'])); ?></td>
                    <td style="color:#72737d;"><?php echo $r['TestDate']; ?></td>
                    <td><?php echo ucfirst(htmlspecialchars($r['Result'])); ?></td>
                    <td>
                        <div class="progress-wrap">
                            <div class="progress-bar">
                                <div class="progress-fill fill-<?php echo $r['Status']; ?>"></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-<?php echo $r['Status']; ?>"><?php echo $r['Status']; ?></span></td>
                    <?php if (in_array($role, ['admin','technician'])): ?>
                    <td>
                        <form method="POST" action="/LIMS/update_status.php" style="display:flex;gap:6px;align-items:center;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="test_id" value="<?php echo $r['TestID']; ?>">
                            <select name="status" class="status-select">
                                <option value="Registered" <?php echo $r['Status']==='Registered'?'selected':''; ?>>Registered</option>
                                <option value="Testing"    <?php echo $r['Status']==='Testing'?'selected':''; ?>>Testing</option>
                                <option value="Completed"  <?php echo $r['Status']==='Completed'?'selected':''; ?>>Completed</option>
                            </select>
                            <button type="submit" class="btn-update">Save</button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
