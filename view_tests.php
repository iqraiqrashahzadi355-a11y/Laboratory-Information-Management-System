<?php
include 'auth_check.php';
checkAuth();
include 'config.php';
include 'audit_log.php';

$role = currentRole();
logAction($conn, "Viewed all lab tests");

// Filters
$search      = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterDate   = $_GET['date'] ?? '';

$where = "WHERE 1=1";
if ($search)       $where .= " AND (Patients.Name LIKE '%" . $conn->real_escape_string($search) . "%' OR LabTests.TestName LIKE '%" . $conn->real_escape_string($search) . "%')";
if ($filterStatus) $where .= " AND LabTests.Status = '" . $conn->real_escape_string($filterStatus) . "'";
if ($filterDate)   $where .= " AND LabTests.TestDate = '" . $conn->real_escape_string($filterDate) . "'";

$tests = $conn->query("
    SELECT LabTests.*, Patients.Name as PatientName
    FROM LabTests
    JOIN Patients ON LabTests.PatientID = Patients.PatientID
    $where
    ORDER BY LabTests.TestID DESC
");
$total = $tests->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Lab Tests — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #f7f6fb; color: #0f0e17; min-height: 100vh; }
        .hero { background: linear-gradient(135deg, #1e293b, #334155); padding: 2rem 2.5rem; color: #fff; }
        .hero h1 { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; margin-bottom: 0.3rem; }
        .hero p { opacity: 0.7; font-size: 0.9rem; }
        .main { max-width: 1100px; margin: 0 auto; padding: 2rem 1.5rem; }
        .filter-card { background: #fff; border: 1px solid #e8e7f0; border-radius: 14px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 0.4rem; }
        .filter-group label { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: #72737d; }
        .filter-input { padding: 8px 14px; border: 1.5px solid #e8e7f0; border-radius: 10px; font-size: 0.87rem; font-family: 'DM Sans', sans-serif; color: #0f0e17; background: #fafafa; outline: none; transition: border-color 0.2s; min-width: 180px; }
        .filter-input:focus { border-color: #6246ea; }
        .btn-filter { padding: 9px 20px; background: #1e293b; color: #fff; border: none; border-radius: 10px; font-family: 'Syne', sans-serif; font-weight: 700; font-size: 0.87rem; cursor: pointer; }
        .btn-reset { padding: 9px 16px; background: #f1f0f7; color: #72737d; border: none; border-radius: 10px; font-size: 0.87rem; cursor: pointer; text-decoration: none; display: inline-block; }
        .table-card { background: #fff; border: 1px solid #e8e7f0; border-radius: 14px; overflow: hidden; }
        .table-header { padding: 1.1rem 1.5rem; border-bottom: 1px solid #e8e7f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
        .table-header h3 { font-family: 'Syne', sans-serif; font-size: 0.9rem; font-weight: 700; }
        .btn-add { padding: 7px 18px; background: #059669; color: #fff; border-radius: 50px; font-size: 0.82rem; font-weight: 600; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; }
        thead th { text-align: left; padding: 9px 1.25rem; font-size: 0.71rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: #72737d; border-bottom: 1px solid #e8e7f0; background: #f7f6fb; }
        tbody td { padding: 11px 1.25rem; border-bottom: 1px solid #f0eff6; font-size: 0.84rem; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #f7f6fb; }
        .badge { display: inline-block; padding: 3px 12px; border-radius: 50px; font-size: 0.72rem; font-weight: 600; }
        .badge-Registered { background: #dbeafe; color: #1d4ed8; }
        .badge-Testing    { background: #fef3c7; color: #b45309; }
        .badge-Completed  { background: #dcfce7; color: #15803d; }
        .badge-normal { background: #dcfce7; color: #15803d; }
        .badge-high   { background: #fef3c7; color: #b45309; }
        .badge-low    { background: #dbeafe; color: #1d4ed8; }
        .badge-other  { background: #ede9fe; color: #6246ea; }
        .progress-bar { width: 60px; height: 6px; background: #e8e7f0; border-radius: 50px; overflow: hidden; display: inline-block; vertical-align: middle; }
        .progress-fill { height: 100%; border-radius: 50px; }
        .fill-Registered { background: #3b82f6; width: 33%; }
        .fill-Testing    { background: #f59e0b; width: 66%; }
        .fill-Completed  { background: #10b981; width: 100%; }
        .btn-dl { display: inline-block; padding: 4px 12px; background: #ede9fe; color: #6246ea; border-radius: 50px; font-size: 0.75rem; font-weight: 600; text-decoration: none; }
        .status-select { padding: 4px 8px; border: 1.5px solid #e8e7f0; border-radius: 8px; font-size: 0.78rem; font-family: 'DM Sans', sans-serif; background: #fafafa; cursor: pointer; outline: none; }
        .btn-update { padding: 4px 12px; background: #1e293b; color: #fff; border: none; border-radius: 8px; font-size: 0.75rem; font-weight: 600; cursor: pointer; font-family: 'DM Sans', sans-serif; }
        .empty-state { text-align: center; padding: 3rem; color: #72737d; }
        .empty-state .icon { font-size: 2.5rem; margin-bottom: 0.75rem; }
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>

<div class="hero">
    <h1>🔬 All Lab Tests</h1>
    <p>Browse, search, and filter all laboratory test records.</p>
</div>

<div class="main">

    <?php if(isset($_GET['msg']) && $_GET['msg']==='deleted'): ?>
    <div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:10px;padding:10px 16px;font-size:0.84rem;color:#15803d;margin-bottom:1rem;">✓ Test deleted successfully.</div>
    <?php endif; ?>
    <!-- Filters -->
    <form method="GET" action="view_tests.php">
        <div class="filter-card">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" class="filter-input" placeholder="Patient name or test..." value="<?php echo htmlspecialchars($search); ?>">
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
            <div class="filter-group">
                <label>Date</label>
                <input type="date" name="date" class="filter-input" value="<?php echo htmlspecialchars($filterDate); ?>">
            </div>
            <button type="submit" class="btn-filter">Search</button>
            <a href="view_tests.php" class="btn-reset">Reset</a>
        </div>
    </form>

    <!-- Table -->
    <div class="table-card">
        <div class="table-header">
            <h3>Lab Tests <span style="font-weight:400;">(<?php echo $total; ?> found)</span></h3>
            <?php if (in_array($role, ['admin','technician'])): ?>
            <a href="add_test.php" class="btn-add">+ Add Test</a>
            <?php endif; ?>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Patient</th>
                    <th>Test Name</th>
                    <th>Date</th>
                    <th>Result</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <?php if (in_array($role, ['admin','technician'])): ?><th>Update</th><?php endif; ?>
                    <?php if (in_array($role, ['admin','manager','doctor'])): ?><th>Report</th><?php endif; ?>
                    <?php if (in_array($role, ['admin','technician'])): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($total == 0): ?>
                <tr><td colspan="9">
                    <div class="empty-state">
                        <div class="icon">🔍</div>
                        <p>No lab tests found matching your search.</p>
                    </div>
                </td></tr>
                <?php else: ?>
                <?php while($r = $tests->fetch_assoc()):
                    $res = strtolower($r['Result']);
                    if(strpos($res,'normal')!==false)     { $rcls='badge-normal'; }
                    elseif(strpos($res,'high')!==false)   { $rcls='badge-high'; }
                    elseif(strpos($res,'low')!==false)    { $rcls='badge-low'; }
                    else                                   { $rcls='badge-other'; }
                ?>
                <tr>
                    <td style="color:#72737d;font-size:0.78rem;">#<?php echo $r['TestID']; ?></td>
                    <td style="font-weight:500;"><?php echo htmlspecialchars($r['PatientName']); ?></td>
                    <td><?php echo ucfirst(htmlspecialchars($r['TestName'])); ?></td>
                    <td style="color:#72737d;"><?php echo $r['TestDate']; ?></td>
                    <td><span class="badge <?php echo $rcls; ?>"><?php echo ucfirst(htmlspecialchars($r['Result'])); ?></span></td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill fill-<?php echo $r['Status']; ?>"></div>
                        </div>
                    </td>
                    <td><span class="badge badge-<?php echo $r['Status']; ?>"><?php echo $r['Status']; ?></span></td>
                    <?php if (in_array($role, ['admin','technician'])): ?>
                    <td>
                        <form method="POST" action="/LIMS/update_status.php" style="display:flex;gap:5px;align-items:center;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="test_id" value="<?php echo $r['TestID']; ?>">
                            <input type="hidden" name="redirect" value="view_tests.php">
                            <select name="status" class="status-select">
                                <option value="Registered" <?php echo $r['Status']==='Registered'?'selected':''; ?>>Registered</option>
                                <option value="Testing"    <?php echo $r['Status']==='Testing'?'selected':''; ?>>Testing</option>
                                <option value="Completed"  <?php echo $r['Status']==='Completed'?'selected':''; ?>>Completed</option>
                            </select>
                            <button type="submit" class="btn-update">Save</button>
                        </form>
                    </td>
                    <?php endif; ?>
                    <?php if (in_array($role, ['admin','manager','doctor'])): ?>
                    <td><a class="btn-dl" href="reports/download_patient_report.php?id=<?php echo $r['PatientID']; ?>">Download</a></td>
                    <?php endif; ?>
                    <?php if (in_array($role, ['admin','technician'])): ?>
                    <td style="display:flex;gap:5px;">
                        <a href="/LIMS/edit_test.php?id=<?php echo $r['TestID']; ?>" style="display:inline-block;padding:4px 10px;background:#e0f2fe;color:#0369a1;border-radius:50px;font-size:0.73rem;font-weight:600;text-decoration:none;">✏️ Edit</a>
                        <?php if($role==='admin'): ?>
                        <a href="/LIMS/delete_test.php?id=<?php echo $r['TestID']; ?>" style="display:inline-block;padding:4px 10px;background:#fee2e2;color:#dc2626;border-radius:50px;font-size:0.73rem;font-weight:600;text-decoration:none;" onclick="return confirm('Delete this test permanently?')">🗑 Delete</a>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>