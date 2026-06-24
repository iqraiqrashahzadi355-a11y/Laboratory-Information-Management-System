<?php
include 'auth_check.php';
checkRole(['admin', 'technician', 'manager']);
include 'config.php';
include 'audit_log.php';

$success = '';

// Mark as paid
if (isset($_GET['pay']) && isset($_GET['method'])) {
    $bill_id = intval($_GET['pay']);
    $method  = $_GET['method'];
    $conn->query("UPDATE Billing SET PaymentStatus='Paid', PaymentMethod='" . $conn->real_escape_string($method) . "' WHERE BillID=$bill_id");
    logAction($conn, "Marked Bill #$bill_id as Paid via $method");
    header("Location: /LIMS/manage_billing.php?msg=paid");
    exit;
}

if (isset($_GET['msg'])) {
    $success = "Payment recorded successfully!";
}

// Filters
$filterStatus = $_GET['status'] ?? '';
$search       = $_GET['search'] ?? '';

$where = "WHERE 1=1";
if ($filterStatus) $where .= " AND Billing.PaymentStatus='" . $conn->real_escape_string($filterStatus) . "'";
if ($search)       $where .= " AND Patients.Name LIKE '%" . $conn->real_escape_string($search) . "%'";

$bills = $conn->query("
    SELECT Billing.*, Patients.Name as PatientName, LabTests.TestName
    FROM Billing
    JOIN Patients ON Billing.PatientID = Patients.PatientID
    LEFT JOIN LabTests ON Billing.TestID = LabTests.TestID
    $where
    ORDER BY Billing.BillDate DESC
");

// Summary stats
$totalRevenue = $conn->query("SELECT SUM(Amount) as t FROM Billing WHERE PaymentStatus='Paid'")->fetch_assoc()['t'] ?? 0;
$pendingAmount = $conn->query("SELECT SUM(Amount) as t FROM Billing WHERE PaymentStatus='Unpaid'")->fetch_assoc()['t'] ?? 0;
$totalBills = $conn->query("SELECT COUNT(*) as t FROM Billing")->fetch_assoc()['t'];
$paidCount = $conn->query("SELECT COUNT(*) as t FROM Billing WHERE PaymentStatus='Paid'")->fetch_assoc()['t'];

logAction($conn, "Viewed billing management");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:#f7f6fb;color:#0f0e17;}
        .hero{background:linear-gradient(135deg,#059669,#0d9488);padding:2rem 2.5rem;color:#fff;}
        .hero h1{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:0.3rem;}
        .hero p{opacity:0.8;font-size:0.9rem;}
        .main{max-width:100%;padding:2rem 1.5rem;}
        .alert-success{background:#dcfce7;border:1px solid #bbf7d0;border-radius:10px;padding:10px 16px;font-size:0.84rem;color:#15803d;margin-bottom:1rem;}
        .stats{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem;}
        .stat-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;padding:1.25rem 1.5rem;}
        .stat-card .num{font-family:'Syne',sans-serif;font-size:1.7rem;font-weight:800;}
        .stat-card .lbl{font-size:0.78rem;color:#72737d;margin-top:4px;text-transform:uppercase;letter-spacing:0.05em;}
        .stat-revenue .num{color:#059669;}
        .stat-pending .num{color:#dc2626;}
        .stat-bills .num{color:#6246ea;}
        .stat-paid .num{color:#0891b2;}
        .filter-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;padding:1.25rem 1.5rem;margin-bottom:1.5rem;display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;}
        .filter-group{display:flex;flex-direction:column;gap:0.4rem;flex:1;}
        .filter-group label{font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:#72737d;}
        .filter-input{padding:9px 14px;border:1.5px solid #e8e7f0;border-radius:10px;font-size:0.87rem;font-family:'DM Sans',sans-serif;background:#fafafa;outline:none;}
        .btn-filter{padding:10px 20px;background:#059669;color:#fff;border:none;border-radius:10px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.87rem;cursor:pointer;}
        .btn-reset{padding:10px 16px;background:#f1f0f7;color:#72737d;border:none;border-radius:10px;font-size:0.87rem;cursor:pointer;text-decoration:none;display:inline-block;}
        .table-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;overflow:hidden;}
        .table-header{padding:1.1rem 1.5rem;border-bottom:1px solid #e8e7f0;display:flex;justify-content:space-between;align-items:center;}
        .table-header h3{font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;}
        table{width:100%;border-collapse:collapse;font-size:0.84rem;}
        thead th{text-align:left;padding:9px 1.25rem;font-size:0.71rem;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:#72737d;border-bottom:1px solid #e8e7f0;background:#f7f6fb;}
        tbody td{padding:11px 1.25rem;border-bottom:1px solid #f0eff6;vertical-align:middle;}
        tbody tr:hover td{background:#f7f6fb;}
        .badge{display:inline-block;padding:3px 12px;border-radius:50px;font-size:0.72rem;font-weight:600;}
        .badge-Unpaid{background:#fee2e2;color:#dc2626;}
        .badge-Paid{background:#dcfce7;color:#15803d;}
        .badge-Refunded{background:#fef3c7;color:#b45309;}
        .pay-btns{display:flex;gap:4px;}
        .btn-pay{padding:4px 12px;border-radius:50px;font-size:0.72rem;font-weight:600;text-decoration:none;}
        .btn-cash{background:#dcfce7;color:#15803d;}
        .btn-card{background:#dbeafe;color:#1d4ed8;}
        .empty-state{text-align:center;padding:3rem;color:#72737d;}
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>

<div class="hero">
    <h1>💰 Billing & Invoices</h1>
    <p>Track payments and outstanding fees.</p>
</div>

<div class="main">
    <?php if($success): ?><div class="alert-success">✓ <?php echo $success; ?></div><?php endif; ?>

    <div class="stats">
        <div class="stat-card stat-revenue"><div class="num">Rs. <?php echo number_format($totalRevenue,0); ?></div><div class="lbl">Total Revenue</div></div>
        <div class="stat-card stat-pending"><div class="num">Rs. <?php echo number_format($pendingAmount,0); ?></div><div class="lbl">Pending Amount</div></div>
        <div class="stat-card stat-bills"><div class="num"><?php echo $totalBills; ?></div><div class="lbl">Total Bills</div></div>
        <div class="stat-card stat-paid"><div class="num"><?php echo $paidCount; ?></div><div class="lbl">Paid Bills</div></div>
    </div>

    <form method="GET" action="/LIMS/manage_billing.php">
        <div class="filter-card">
            <div class="filter-group">
                <label>Search Patient</label>
                <input type="text" name="search" class="filter-input" placeholder="Patient name..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select name="status" class="filter-input">
                    <option value="">All</option>
                    <option value="Unpaid" <?php echo $filterStatus==='Unpaid'?'selected':''; ?>>Unpaid</option>
                    <option value="Paid" <?php echo $filterStatus==='Paid'?'selected':''; ?>>Paid</option>
                    <option value="Refunded" <?php echo $filterStatus==='Refunded'?'selected':''; ?>>Refunded</option>
                </select>
            </div>
            <button type="submit" class="btn-filter">Filter</button>
            <a href="/LIMS/manage_billing.php" class="btn-reset">Reset</a>
        </div>
    </form>

    <div class="table-card">
        <div class="table-header">
            <h3>Invoices (<?php echo $bills->num_rows; ?>)</h3>
        </div>
        <table>
            <thead><tr><th>Bill ID</th><th>Patient</th><th>Test</th><th>Amount</th><th>Status</th><th>Method</th><th>Date</th><th>Action</th></tr></thead>
            <tbody>
                <?php if($bills->num_rows===0): ?>
                <tr><td colspan="8"><div class="empty-state">No bills found.</div></td></tr>
                <?php else: ?>
                <?php while($b=$bills->fetch_assoc()): ?>
                <tr>
                    <td style="color:#72737d;font-size:0.78rem;">#<?php echo $b['BillID']; ?></td>
                    <td style="font-weight:500;"><?php echo htmlspecialchars($b['PatientName']); ?></td>
                    <td><?php echo htmlspecialchars($b['TestName'] ?? '—'); ?></td>
                    <td style="font-weight:700;">Rs. <?php echo number_format($b['Amount'],0); ?></td>
                    <td><span class="badge badge-<?php echo $b['PaymentStatus']; ?>"><?php echo $b['PaymentStatus']; ?></span></td>
                    <td style="color:#72737d;"><?php echo htmlspecialchars($b['PaymentMethod'] ?? '—'); ?></td>
                    <td style="color:#72737d;font-size:0.78rem;"><?php echo date('d M Y', strtotime($b['BillDate'])); ?></td>
                    <td>
                        <?php if($b['PaymentStatus']==='Unpaid'): ?>
                        <div class="pay-btns">
                            <a href="/LIMS/manage_billing.php?pay=<?php echo $b['BillID']; ?>&method=Cash" class="btn-pay btn-cash" onclick="return confirm('Mark as paid via Cash?')">💵 Cash</a>
                            <a href="/LIMS/manage_billing.php?pay=<?php echo $b['BillID']; ?>&method=Card" class="btn-pay btn-card" onclick="return confirm('Mark as paid via Card?')">💳 Card</a>
                        </div>
                        <?php else: ?>
                        <span style="color:#72737d;font-size:0.78rem;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
