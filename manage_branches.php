<?php
include 'auth_check.php';
checkRole(['admin']);
include 'config.php';
include 'audit_log.php';

$success = ''; $error = '';

// Add branch
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    verifyCSRF($_POST['csrf_token']);
    $name    = trim($_POST['branch_name']);
    $city    = trim($_POST['city']);
    $address = trim($_POST['address']);
    $phone   = trim($_POST['phone']);

    $stmt = $conn->prepare("INSERT INTO Branches (BranchName, City, Address, Phone) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $name, $city, $address, $phone);
    if ($stmt->execute()) {
        logAction($conn, "Added new branch: $name ($city)");
        $success = "Branch '$name' added successfully!";
    } else {
        $error = "Error adding branch.";
    }
    $stmt->close();
}

// Toggle active
if (isset($_GET['toggle'])) {
    $bid = intval($_GET['toggle']);
    $conn->query("UPDATE Branches SET IsActive = 1 - IsActive WHERE BranchID = $bid");
    header("Location: /LIMS/manage_branches.php?msg=updated");
    exit;
}

if (isset($_GET['msg'])) $success = "Branch status updated.";

$branches = $conn->query("SELECT Branches.*, 
    (SELECT COUNT(*) FROM Users WHERE BranchID=Branches.BranchID) as StaffCount,
    (SELECT COUNT(*) FROM Patients WHERE BranchID=Branches.BranchID) as PatientCount
    FROM Branches ORDER BY BranchID ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Branches — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:#f7f6fb;color:#0f0e17;}
        .hero{background:linear-gradient(135deg,#7c3aed,#4f46e5);padding:2rem 2.5rem;color:#fff;}
        .hero h1{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:0.3rem;}
        .hero p{opacity:0.8;font-size:0.9rem;}
        .main{max-width:100%;padding:2rem 1.5rem;}
        .grid{display:grid;grid-template-columns:320px 1fr;gap:1.5rem;align-items:start;}
        .form-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;padding:1.5rem;}
        .form-card h2{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;margin-bottom:1.25rem;}
        .field-label{font-size:0.73rem;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:#0f0e17;display:block;margin-bottom:0.4rem;}
        .field-input{width:100%;padding:10px 14px;border:1.5px solid #e8e7f0;border-radius:10px;font-size:0.9rem;font-family:'DM Sans',sans-serif;color:#0f0e17;background:#fafafa;outline:none;margin-bottom:1rem;transition:border-color 0.2s;}
        .field-input:focus{border-color:#7c3aed;background:#fff;}
        .btn-add{width:100%;padding:12px;background:#7c3aed;color:#fff;border:none;border-radius:10px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.95rem;cursor:pointer;}
        .alert-success{background:#dcfce7;border:1px solid #bbf7d0;border-radius:10px;padding:10px 14px;font-size:0.84rem;color:#15803d;margin-bottom:1rem;}
        .alert-error{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;font-size:0.84rem;color:#dc2626;margin-bottom:1rem;}
        .branches-grid{display:flex;flex-direction:column;gap:1rem;}
        .branch-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;padding:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;}
        .branch-icon{width:52px;height:52px;border-radius:14px;background:#ede9fe;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;}
        .branch-info{flex:1;}
        .branch-name{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;margin-bottom:3px;}
        .branch-detail{font-size:0.82rem;color:#72737d;}
        .branch-stats{display:flex;gap:1rem;margin-top:8px;}
        .branch-stat{font-size:0.78rem;color:#72737d;}
        .branch-stat strong{color:#0f0e17;font-weight:600;}
        .badge-active{background:#dcfce7;color:#15803d;display:inline-block;padding:3px 10px;border-radius:50px;font-size:0.71rem;font-weight:600;}
        .badge-inactive{background:#fee2e2;color:#dc2626;display:inline-block;padding:3px 10px;border-radius:50px;font-size:0.71rem;font-weight:600;}
        .btn-toggle{padding:6px 16px;border-radius:50px;font-size:0.78rem;font-weight:600;text-decoration:none;background:#f1f0f7;color:#72737d;}
        @media(max-width:800px){.grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>

<div class="hero">
    <h1>🏢 Branch Management</h1>
    <p>Manage all LIMS laboratory branches.</p>
</div>

<div class="main">
    <div class="grid">
        <!-- Add Branch Form -->
        <div class="form-card">
            <h2>➕ Add New Branch</h2>
            <?php if($success): ?><div class="alert-success">✓ <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <?php if($error):   ?><div class="alert-error">⚠ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <form method="POST" action="/LIMS/manage_branches.php">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add">
                <label class="field-label">Branch Name</label>
                <input type="text" name="branch_name" class="field-input" placeholder="e.g. Karachi Branch" required>
                <label class="field-label">City</label>
                <input type="text" name="city" class="field-input" placeholder="e.g. Karachi" required>
                <label class="field-label">Address</label>
                <input type="text" name="address" class="field-input" placeholder="Full address">
                <label class="field-label">Phone</label>
                <input type="text" name="phone" class="field-input" placeholder="e.g. 021-1234567">
                <button type="submit" class="btn-add">Add Branch</button>
            </form>
        </div>

        <!-- Branches List -->
        <div class="branches-grid">
            <?php while($b=$branches->fetch_assoc()): ?>
            <div class="branch-card">
                <div class="branch-icon">🏥</div>
                <div class="branch-info">
                    <div class="branch-name"><?php echo htmlspecialchars($b['BranchName']); ?></div>
                    <div class="branch-detail">📍 <?php echo htmlspecialchars($b['City']); ?> &nbsp;|&nbsp; <?php echo htmlspecialchars($b['Address']); ?></div>
                    <div class="branch-detail">📞 <?php echo htmlspecialchars($b['Phone']); ?></div>
                    <div class="branch-stats">
                        <div class="branch-stat">👥 Staff: <strong><?php echo $b['StaffCount']; ?></strong></div>
                        <div class="branch-stat">👤 Patients: <strong><?php echo $b['PatientCount']; ?></strong></div>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                    <span class="<?php echo $b['IsActive'] ? 'badge-active' : 'badge-inactive'; ?>"><?php echo $b['IsActive'] ? 'Active' : 'Inactive'; ?></span>
                    <a href="/LIMS/manage_branches.php?toggle=<?php echo $b['BranchID']; ?>" class="btn-toggle" onclick="return confirm('Change branch status?')"><?php echo $b['IsActive'] ? 'Deactivate' : 'Activate'; ?></a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
</body>
</html>
