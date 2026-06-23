<?php
include 'auth_check.php';
checkRole(['admin']);
include 'config.php';
include 'audit_log.php';
include 'mailer.php';

$success = '';
$error   = '';

if (isset($_POST['action']) && $_POST['action'] === 'add') {
    verifyCSRF($_POST['csrf_token']);
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role     = $_POST['role'];
    $email    = trim($_POST['email'] ?? '');

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO Users (FullName, Username, Password, Role, Email) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssss", $fullname, $username, $hashed, $role, $email);
    if ($stmt->execute()) {
        logAction($conn, "Added new user: $username ($role)");
        $success = "User '{$username}' added successfully!";

        // Send welcome email if email provided
        if (!empty($email)) {
            $msg = "Your LIMS account has been created.<br><br>
                <strong>Username:</strong> " . htmlspecialchars($username) . "<br>
                <strong>Temporary Password:</strong> " . htmlspecialchars($password) . "<br>
                <strong>Role:</strong> " . ucfirst($role) . "<br><br>
                Please login and change your password immediately for security.";
            sendLIMSEmail($email, $fullname, "Welcome to LIMS — Account Created", limsEmailTemplate("Welcome to LIMS! 🎉", $msg, "Login Now", "http://localhost/LIMS/auth_login.php"));
        }
    } else {
        $error = "Username already exists or error occurred.";
    }
    $stmt->close();
}

if (isset($_GET['toggle'])) {
    $uid = intval($_GET['toggle']);
    $conn->query("UPDATE Users SET IsActive = 1 - IsActive WHERE UserID = $uid");
    logAction($conn, "Toggled active status for UserID: $uid");
    header("Location: /LIMS/manage_users.php?msg=updated");
    exit;
}

if (isset($_GET['unlock'])) {
    $uid = intval($_GET['unlock']);
    $conn->query("UPDATE Users SET FailedAttempts=0, LockedUntil=NULL WHERE UserID = $uid");
    logAction($conn, "Unlocked account for UserID: $uid");
    header("Location: /LIMS/manage_users.php?msg=unlocked");
    exit;
}

if (isset($_GET['delete'])) {
    $uid = intval($_GET['delete']);
    if ($uid !== $_SESSION['user_id']) {
        $conn->query("DELETE FROM Users WHERE UserID = $uid");
        logAction($conn, "Deleted UserID: $uid");
    }
    header("Location: /LIMS/manage_users.php?msg=deleted");
    exit;
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'updated')  $success = "User status updated.";
    if ($_GET['msg'] === 'deleted')  $success = "User deleted successfully.";
    if ($_GET['msg'] === 'unlocked') $success = "Account unlocked successfully.";
}

$users = $conn->query("SELECT * FROM Users ORDER BY CreatedAt DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:#f7f6fb;color:#0f0e17;}
        .hero{background:linear-gradient(135deg,#7c3aed,#4f46e5);padding:2rem 2.5rem;color:#fff;}
        .hero h1{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;margin-bottom:0.3rem;}
        .hero p{opacity:0.8;font-size:0.9rem;}
        .main{max-width:1100px;margin:0 auto;padding:2rem 1.5rem;}
        .grid{display:grid;grid-template-columns:340px 1fr;gap:1.5rem;align-items:start;}
        .form-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;padding:1.5rem;}
        .form-card h2{font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;margin-bottom:1.25rem;}
        .field-label{font-size:0.73rem;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:#0f0e17;display:block;margin-bottom:0.4rem;}
        .field-input{width:100%;padding:10px 14px;border:1.5px solid #e8e7f0;border-radius:10px;font-size:0.9rem;font-family:'DM Sans',sans-serif;color:#0f0e17;background:#fafafa;outline:none;margin-bottom:1rem;transition:border-color 0.2s;}
        .field-input:focus{border-color:#7c3aed;background:#fff;}
        select.field-input{cursor:pointer;}
        .btn-add{width:100%;padding:12px;background:#7c3aed;color:#fff;border:none;border-radius:10px;font-family:'Syne',sans-serif;font-weight:700;font-size:0.95rem;cursor:pointer;transition:opacity 0.15s;}
        .btn-add:hover{opacity:0.88;}
        .alert-success{background:#dcfce7;border:1px solid #bbf7d0;border-radius:10px;padding:10px 14px;font-size:0.84rem;color:#15803d;margin-bottom:1rem;}
        .alert-error{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;font-size:0.84rem;color:#dc2626;margin-bottom:1rem;}
        .table-card{background:#fff;border:1px solid #e8e7f0;border-radius:14px;overflow:hidden;}
        .table-header{padding:1.1rem 1.5rem;border-bottom:1px solid #e8e7f0;display:flex;justify-content:space-between;align-items:center;}
        .table-header h3{font-family:'Syne',sans-serif;font-size:0.9rem;font-weight:700;}
        .search-box{padding:7px 14px;border:1px solid #e8e7f0;border-radius:50px;font-size:0.83rem;font-family:'DM Sans',sans-serif;outline:none;background:#f7f6fb;width:180px;transition:border-color 0.2s;}
        .search-box:focus{border-color:#7c3aed;}
        table{width:100%;border-collapse:collapse;font-size:0.83rem;}
        thead th{text-align:left;padding:9px 1rem;font-size:0.71rem;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:#72737d;border-bottom:1px solid #e8e7f0;background:#f7f6fb;}
        tbody td{padding:10px 1rem;border-bottom:1px solid #f0eff6;vertical-align:middle;}
        tbody tr:last-child td{border-bottom:none;}
        tbody tr:hover td{background:#f7f6fb;}
        .badge{display:inline-block;padding:3px 10px;border-radius:50px;font-size:0.71rem;font-weight:600;}
        .badge-admin{background:#ede9fe;color:#7c3aed;}
        .badge-technician{background:#e0f2fe;color:#0369a1;}
        .badge-manager{background:#dcfce7;color:#15803d;}
        .badge-doctor{background:#fef3c7;color:#b45309;}
        .badge-active{background:#dcfce7;color:#15803d;}
        .badge-inactive{background:#fee2e2;color:#dc2626;}
        .badge-locked{background:#fef3c7;color:#b45309;}
        .btn-sm{display:inline-block;padding:4px 10px;border-radius:50px;font-size:0.73rem;font-weight:600;text-decoration:none;cursor:pointer;border:none;font-family:'DM Sans',sans-serif;margin-right:3px;}
        .btn-toggle-on{background:#fef3c7;color:#b45309;}
        .btn-toggle-off{background:#dcfce7;color:#15803d;}
        .btn-unlock{background:#e0f2fe;color:#0369a1;}
        .btn-delete{background:#fee2e2;color:#dc2626;}
        .self-tag{font-size:0.72rem;color:#72737d;font-style:italic;}
        @media(max-width:800px){.grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>

<div class="hero">
    <h1>Manage Users</h1>
    <p>Add new staff accounts, change roles, unlock or deactivate users.</p>
</div>

<div class="main">
    <div class="grid">
        <div class="form-card">
            <h2>➕ Add New User</h2>
            <?php if($success): ?><div class="alert-success">✓ <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
            <?php if($error):   ?><div class="alert-error">⚠ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <form method="POST" action="/LIMS/manage_users.php">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add">
                <label class="field-label">Full Name</label>
                <input type="text" name="fullname" class="field-input" placeholder="e.g. Dr. Sara Ahmed" required>
                <label class="field-label">Username</label>
                <input type="text" name="username" class="field-input" placeholder="e.g. sara123" required>
                <label class="field-label">Email (optional)</label>
                <input type="email" name="email" class="field-input" placeholder="e.g. sara@example.com">
                <label class="field-label">Password</label>
                <input type="password" name="password" class="field-input" placeholder="Min. 6 characters" minlength="6" required>
                <label class="field-label">Role</label>
                <select name="role" class="field-input" required>
                    <option value="" disabled selected>Select role</option>
                    <option value="admin">Admin</option>
                    <option value="technician">Technician</option>
                    <option value="manager">Manager</option>
                    <option value="doctor">Doctor</option>
                </select>
                <button type="submit" class="btn-add">Add User</button>
            </form>
        </div>

        <div class="table-card">
            <div class="table-header">
                <h3>All Users (<?php echo $users->num_rows; ?>)</h3>
                <input class="search-box" id="userSearch" type="text" placeholder="Search user...">
            </div>
            <table>
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody id="usersTable">
                    <?php while($u = $users->fetch_assoc()):
                        $isLocked = !empty($u['LockedUntil']) && strtotime($u['LockedUntil']) > time();
                    ?>
                    <tr>
                        <td style="color:#72737d;font-size:0.78rem;">#<?php echo $u['UserID']; ?></td>
                        <td style="font-weight:500;"><?php echo htmlspecialchars($u['FullName']); ?></td>
                        <td style="color:#72737d;"><?php echo htmlspecialchars($u['Username']); ?></td>
                        <td style="color:#72737d;font-size:0.78rem;"><?php echo htmlspecialchars($u['Email'] ?? '—'); ?></td>
                        <td><span class="badge badge-<?php echo $u['Role']; ?>"><?php echo ucfirst($u['Role']); ?></span></td>
                        <td>
                            <?php if ($isLocked): ?>
                                <span class="badge badge-locked">🔒 Locked</span>
                            <?php elseif ($u['IsActive']): ?>
                                <span class="badge badge-active">Active</span>
                            <?php else: ?>
                                <span class="badge badge-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['UserID'] === $_SESSION['user_id']): ?>
                                <span class="self-tag">You</span>
                            <?php else: ?>
                                <?php if ($isLocked): ?>
                                <a href="/LIMS/manage_users.php?unlock=<?php echo $u['UserID']; ?>" class="btn-sm btn-unlock" onclick="return confirm('Unlock this account?')">🔓 Unlock</a>
                                <?php endif; ?>
                                <a href="/LIMS/manage_users.php?toggle=<?php echo $u['UserID']; ?>" class="btn-sm <?php echo $u['IsActive'] ? 'btn-toggle-on' : 'btn-toggle-off'; ?>" onclick="return confirm('Change this user status?')"><?php echo $u['IsActive'] ? 'Deactivate' : 'Activate'; ?></a>
                                <a href="/LIMS/manage_users.php?delete=<?php echo $u['UserID']; ?>" class="btn-sm btn-delete" onclick="return confirm('Delete this user permanently?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('userSearch').addEventListener('keyup', function() {
    var f = this.value.toUpperCase();
    var rows = document.querySelector('#usersTable').rows;
    for(var i=0;i<rows.length;i++){
        var name = rows[i].cells[1].textContent.toUpperCase();
        var uname = rows[i].cells[2].textContent.toUpperCase();
        rows[i].style.display = (name.includes(f)||uname.includes(f))?'':'none';
    }
});
</script>
</body>
</html>