<?php
include 'auth_check.php';
checkRole(['admin']);
include 'config.php';
include 'audit_log.php';

logAction($conn, "Viewed audit log");

// Filters
$filterRole = $_GET['role'] ?? '';
$filterUser = $_GET['username'] ?? '';
$filterDate = $_GET['date'] ?? '';

$where = "WHERE 1=1";
if ($filterRole)  $where .= " AND Role = '" . $conn->real_escape_string($filterRole) . "'";
if ($filterUser)  $where .= " AND Username LIKE '%" . $conn->real_escape_string($filterUser) . "%'";
if ($filterDate)  $where .= " AND DATE(LoggedAt) = '" . $conn->real_escape_string($filterDate) . "'";

$logs  = $conn->query("SELECT * FROM AuditLog $where ORDER BY LoggedAt DESC LIMIT 200");
$total = $conn->query("SELECT COUNT(*) as t FROM AuditLog $where")->fetch_assoc()['t'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:#f7f6fb;color:#0f0e17;}
        .hero{background:linear-gradient(135deg,#1e293b,#334155);padding:2rem 2.5rem;color:#fff;}
        .hero h1{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:0.3rem;}
        .hero p{opacity:0.7;font-size:0.9rem;}
        .main{max-width:1200px;margin:0 auto;padding:2rem 1.5rem;}

        /* Filters */
        .filter-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;padding:1.25rem 1.5rem;margin-bottom:1.5rem;display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;}
        .filter-group{display:flex;flex-direction:column;gap:0.4rem;}
        .filter-group label{font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:#72737d;}
        .filter-input{padding:8px 14px;border:1.5px solid #e8e7f0;border-radius:10px;font-size:0.87rem;font-family:'DM Sans',sans-serif;color:#0f0e17;background:#fafafa;outline:none;transition:border-color 0.2s;}
        .filter-input:focus{border-color:#7c3aed;}
        .btn-filter{padding:9px 20px;background:#7c3aed;color:#fff;border:none;border-radius:10px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.87rem;cursor:pointer;transition:opacity 0.15s;}
        .btn-filter:hover{opacity:0.88;}
        .btn-reset{padding:9px 16px;background:#f1f0f7;color:#72737d;border:none;border-radius:10px;font-family:'DM Sans',sans-serif;font-weight:500;font-size:0.87rem;cursor:pointer;text-decoration:none;display:inline-block;}

        /* Stats row */
        .stats-row{display:flex;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;}
        .stat-pill{background:#fff;border:1px solid #e8e7f0;border-radius:10px;padding:0.75rem 1.25rem;display:flex;align-items:center;gap:0.75rem;}
        .stat-pill .num{font-family:'Syne',sans-serif;font-size:1.4rem;font-weight:800;color:#7c3aed;}
        .stat-pill .lbl{font-size:0.78rem;color:#72737d;}

        /* Table */
        .table-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;overflow:hidden;}
        .table-header{padding:1.1rem 1.5rem;border-bottom:1px solid #e8e7f0;display:flex;justify-content:space-between;align-items:center;}
        .table-header h3{font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;}
        .table-header span{font-size:0.82rem;color:#72737d;}
        table{width:100%;border-collapse:collapse;font-size:0.83rem;}
        thead th{text-align:left;padding:9px 1.25rem;font-size:0.71rem;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:#72737d;border-bottom:1px solid #e8e7f0;background:#f7f6fb;}
        tbody td{padding:10px 1.25rem;border-bottom:1px solid #f0eff6;vertical-align:middle;}
        tbody tr:last-child td{border-bottom:none;}
        tbody tr:hover td{background:#f7f6fb;}
        .badge{display:inline-block;padding:3px 10px;border-radius:50px;font-size:0.71rem;font-weight:600;}
        .badge-admin{background:#ede9fe;color:#7c3aed;}
        .badge-technician{background:#e0f2fe;color:#0369a1;}
        .badge-manager{background:#dcfce7;color:#15803d;}
        .badge-doctor{background:#fef3c7;color:#b45309;}
        .action-text{color:#0f0e17;font-weight:500;}
        .url-text{color:#72737d;font-size:0.78rem;font-family:monospace;}
        .ip-text{color:#72737d;font-size:0.78rem;}
        .time-text{color:#72737d;font-size:0.78rem;}
        .empty-state{text-align:center;padding:3rem;color:#72737d;}
        .empty-state .icon{font-size:2.5rem;margin-bottom:0.75rem;}
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>

<div class="hero">
    <h1>📋 Audit Log</h1>
    <p>Track all user activity — who did what, when, and from where.</p>
</div>

<div class="main">

    <!-- Filters -->
    <form method="GET" action="view_audit_log.php">
        <div class="filter-card">
            <div class="filter-group">
                <label>Username</label>
                <input type="text" name="username" class="filter-input" placeholder="Search user..." value="<?php echo htmlspecialchars($filterUser); ?>">
            </div>
            <div class="filter-group">
                <label>Role</label>
                <select name="role" class="filter-input">
                    <option value="">All Roles</option>
                    <option value="admin"      <?php echo $filterRole==='admin'?'selected':''; ?>>Admin</option>
                    <option value="technician" <?php echo $filterRole==='technician'?'selected':''; ?>>Technician</option>
                    <option value="manager"    <?php echo $filterRole==='manager'?'selected':''; ?>>Manager</option>
                    <option value="doctor"     <?php echo $filterRole==='doctor'?'selected':''; ?>>Doctor</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Date</label>
                <input type="date" name="date" class="filter-input" value="<?php echo htmlspecialchars($filterDate); ?>">
            </div>
            <button type="submit" class="btn-filter">Filter</button>
            <a href="view_audit_log.php" class="btn-reset">Reset</a>
        </div>
    </form>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-pill">
            <div class="num"><?php echo $total; ?></div>
            <div class="lbl">Total Logs<?php echo ($filterRole||$filterUser||$filterDate)?' (filtered)':''; ?></div>
        </div>
        <?php
        $todayCount = $conn->query("SELECT COUNT(*) as t FROM AuditLog WHERE DATE(LoggedAt)=CURDATE()")->fetch_assoc()['t'];
        $uniqueUsers = $conn->query("SELECT COUNT(DISTINCT UserID) as t FROM AuditLog WHERE DATE(LoggedAt)=CURDATE()")->fetch_assoc()['t'];
        ?>
        <div class="stat-pill">
            <div class="num"><?php echo $todayCount; ?></div>
            <div class="lbl">Actions Today</div>
        </div>
        <div class="stat-pill">
            <div class="num"><?php echo $uniqueUsers; ?></div>
            <div class="lbl">Active Users Today</div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-card">
        <div class="table-header">
            <h3>Activity Log</h3>
            <span>Showing latest <?php echo min($total, 200); ?> of <?php echo $total; ?> records</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Page</th>
                    <th>IP Address</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logs->num_rows === 0): ?>
                <tr><td colspan="7">
                    <div class="empty-state">
                        <div class="icon">📭</div>
                        <p>No logs found for selected filters.</p>
                    </div>
                </td></tr>
                <?php else: ?>
                <?php while($log = $logs->fetch_assoc()): ?>
                <tr>
                    <td style="color:#72737d;font-size:0.78rem;"><?php echo $log['LogID']; ?></td>
                    <td style="font-weight:500;"><?php echo htmlspecialchars($log['Username']); ?></td>
                    <td><span class="badge badge-<?php echo $log['Role']; ?>"><?php echo ucfirst($log['Role']); ?></span></td>
                    <td class="action-text"><?php echo htmlspecialchars($log['Action']); ?></td>
                    <td class="url-text"><?php echo htmlspecialchars($log['PageURL']); ?></td>
                    <td class="ip-text"><?php echo htmlspecialchars($log['IPAddress']); ?></td>
                    <td class="time-text"><?php echo date('d M Y, h:i A', strtotime($log['LoggedAt'])); ?></td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>