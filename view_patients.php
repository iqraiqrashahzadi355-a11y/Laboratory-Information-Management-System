<?php
include 'auth_check.php';
checkAuth();
include 'config.php';
include 'audit_log.php';

$role = currentRole();
logAction($conn, "Viewed all patients");

// Search & filter
$search     = $_GET['search'] ?? '';
$filterGender = $_GET['gender'] ?? '';

$where = "WHERE 1=1";
if ($search)       $where .= " AND (Name LIKE '%" . $conn->real_escape_string($search) . "%' OR ContactNumber LIKE '%" . $conn->real_escape_string($search) . "%')";
if ($filterGender) $where .= " AND Gender = '" . $conn->real_escape_string($filterGender) . "'";

$patients = $conn->query("SELECT * FROM Patients $where ORDER BY PatientID DESC");
$total    = $conn->query("SELECT COUNT(*) as t FROM Patients $where")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Patients — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #f7f6fb; color: #0f0e17; min-height: 100vh; }
        .hero { background: linear-gradient(135deg, #1e293b, #334155); padding: 2rem 2.5rem; color: #fff; }
        .hero h1 { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; margin-bottom: 0.3rem; }
        .hero p { opacity: 0.7; font-size: 0.9rem; }
        .main { max-width:100%; padding: 2rem 1.5rem; }
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
        .table-header span { font-size: 0.82rem; color: #72737d; }
        <?php if (in_array($role, ['admin','technician'])): ?>
        .btn-add { padding: 7px 18px; background: #0891b2; color: #fff; border-radius: 50px; font-size: 0.82rem; font-weight: 600; text-decoration: none; }
        <?php endif; ?>
        table { width: 100%; border-collapse: collapse; }
        thead th { text-align: left; padding: 9px 1.25rem; font-size: 0.71rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: #72737d; border-bottom: 1px solid #e8e7f0; background: #f7f6fb; }
        tbody td { padding: 11px 1.25rem; border-bottom: 1px solid #f0eff6; font-size: 0.84rem; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #f7f6fb; }
        .badge { display: inline-block; padding: 3px 12px; border-radius: 50px; font-size: 0.72rem; font-weight: 600; }
        .badge-Male   { background: #dbeafe; color: #1e40af; }
        .badge-Female { background: #fce7f3; color: #9d174d; }
        .badge-Other  { background: #ede9fe; color: #6246ea; }
        .btn-view { display: inline-block; padding: 4px 12px; background: #ede9fe; color: #6246ea; border-radius: 50px; font-size: 0.75rem; font-weight: 600; text-decoration: none; }
        .empty-state { text-align: center; padding: 3rem; color: #72737d; }
        .empty-state .icon { font-size: 2.5rem; margin-bottom: 0.75rem; }
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>

<div class="hero">
    <h1>👥 All Patients</h1>
    <p>Browse, search, and filter all registered patients.</p>
</div>

<div class="main">

    <!-- Filters -->
    <?php if(isset($_GET['msg']) && $_GET['msg']==='deleted'): ?>
    <div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:10px;padding:10px 16px;font-size:0.84rem;color:#15803d;margin-bottom:1rem;">✓ Patient deleted successfully.</div>
    <?php endif; ?>
    <form method="GET" action="view_patients.php">
        <div class="filter-card">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" class="filter-input" placeholder="Name or contact..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <label>Gender</label>
                <select name="gender" class="filter-input">
                    <option value="">All Genders</option>
                    <option value="Male"   <?php echo $filterGender==='Male'?'selected':''; ?>>Male</option>
                    <option value="Female" <?php echo $filterGender==='Female'?'selected':''; ?>>Female</option>
                    <option value="Other"  <?php echo $filterGender==='Other'?'selected':''; ?>>Other</option>
                </select>
            </div>
            <button type="submit" class="btn-filter">Search</button>
            <a href="view_patients.php" class="btn-reset">Reset</a>
        </div>
    </form>

    <!-- Table -->
    <div class="table-card">
        <div class="table-header">
            <h3>Patients <span style="font-weight:400;">(<?php echo $total; ?> found)</span></h3>
            <div style="display:flex;gap:10px;align-items:center;">
                <?php if (in_array($role, ['admin','technician'])): ?>
                <a href="add_patient.php" class="btn-add">+ Add Patient</a>
                <?php endif; ?>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Contact</th>
                    <th>Tests</th>
                    <?php if (in_array($role, ['admin','manager','doctor'])): ?>
                    <th>Report</th>
                    <?php endif; ?>
                    <?php if (in_array($role, ['admin','technician'])): ?>
                    <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($total == 0): ?>
                <tr><td colspan="7">
                    <div class="empty-state">
                        <div class="icon">🔍</div>
                        <p>No patients found matching your search.</p>
                    </div>
                </td></tr>
                <?php else: ?>
                <?php while($r = $patients->fetch_assoc()):
                    $testCount = $conn->query("SELECT COUNT(*) as t FROM LabTests WHERE PatientID=".$r['PatientID'])->fetch_assoc()['t'];
                ?>
                <tr>
                    <td style="color:#72737d;font-size:0.78rem;">#<?php echo $r['PatientID']; ?></td>
                    <td style="font-weight:500;"><?php echo htmlspecialchars($r['Name']); ?></td>
                    <td><?php echo $r['Age']; ?></td>
                    <td><span class="badge badge-<?php echo $r['Gender']; ?>"><?php echo $r['Gender']; ?></span></td>
                    <td style="color:#72737d;"><?php echo htmlspecialchars($r['ContactNumber']); ?></td>
                    <td>
                        <span style="background:#f0f9ff;color:#0369a1;padding:3px 10px;border-radius:50px;font-size:0.75rem;font-weight:600;">
                            <?php echo $testCount; ?> test<?php echo $testCount!=1?'s':''; ?>
                        </span>
                    </td>
                    <?php if (in_array($role, ['admin','manager','doctor'])): ?>
                    <td><a class="btn-view" href="reports/download_patient_report.php?id=<?php echo $r['PatientID']; ?>">Download</a></td>
                    <?php endif; ?>
                    <?php if (in_array($role, ['admin','technician'])): ?>
                    <td style="display:flex;gap:6px;">
                        <a href="/LIMS/edit_patient.php?id=<?php echo $r['PatientID']; ?>" style="display:inline-block;padding:4px 12px;background:#e0f2fe;color:#0369a1;border-radius:50px;font-size:0.75rem;font-weight:600;text-decoration:none;">✏️ Edit</a>
                        <?php if($role==='admin'): ?>
                        <a href="/LIMS/delete_patient.php?id=<?php echo $r['PatientID']; ?>" style="display:inline-block;padding:4px 12px;background:#fee2e2;color:#dc2626;border-radius:50px;font-size:0.75rem;font-weight:600;text-decoration:none;" onclick="return confirm('Delete this patient and ALL their tests? This cannot be undone!')">🗑 Delete</a>
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
