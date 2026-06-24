<?php
include 'auth_check.php';
checkAuth();
include 'config.php';
include 'audit_log.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token']);
    $current  = $_POST['current_password'];
    $new      = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];
    $user_id  = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT Password FROM Users WHERE UserID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!password_verify($current, $result['Password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } elseif ($current === $new) {
        $error = "New password must be different from current password.";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE Users SET Password = ? WHERE UserID = ?");
        $stmt->bind_param("si", $hashed, $user_id);
        if ($stmt->execute()) {
            logAction($conn, "Changed own password");
            $success = "Password changed successfully!";
        } else {
            $error = "Something went wrong. Please try again.";
        }
        $stmt->close();
    }
}

$role  = currentRole();
$name  = currentUser();
$dash  = dashboardLink();

$roleColors = ['admin'=>'#7c3aed','technician'=>'#0891b2','manager'=>'#059669','doctor'=>'#dc2626'];
$roleColor  = $roleColors[$role] ?? '#6246ea';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #f7f6fb; color: #0f0e17; min-height: 100vh; }
        .main { max-width: 480px; margin: 3rem auto; padding: 0 1.5rem; }
        .page-title { font-family: 'Syne', sans-serif; font-size: 1.5rem; font-weight: 800; margin-bottom: 0.3rem; }
        .page-sub { color: #72737d; font-size: 0.88rem; margin-bottom: 2rem; }
        .card { background: #fff; border: 1px solid #e8e7f0; border-radius: 16px; padding: 2rem; }
        .field-label { font-size: 0.73rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: #0f0e17; display: block; margin-bottom: 0.4rem; }
        .input-wrap { border: 1.5px solid #e8e7f0; border-radius: 12px; overflow: hidden; margin-bottom: 1.1rem; background: #fafafa; transition: border-color 0.2s; display: flex; align-items: center; }
        .input-wrap:focus-within { border-color: <?php echo $roleColor; ?>; background: #fff; }
        .input-icon { padding: 0 14px; height: 50px; display: flex; align-items: center; background: #f0f0f0; border-right: 1px solid #eee; font-size: 1rem; }
        .input-wrap input { flex: 1; border: none; background: transparent; padding: 0 14px; height: 50px; font-size: 0.95rem; font-family: 'DM Sans', sans-serif; color: #0f0e17; outline: none; }
        .input-wrap input::placeholder { color: #bbb; }
        .toggle-pw { padding: 0 14px; cursor: pointer; font-size: 0.8rem; color: #72737d; background: none; border: none; }
        .btn-submit { width: 100%; padding: 14px; background: <?php echo $roleColor; ?>; color: #fff; border: none; border-radius: 12px; font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1rem; cursor: pointer; transition: opacity 0.15s, transform 0.15s; margin-top: 0.5rem; }
        .btn-submit:hover { opacity: 0.88; transform: translateY(-1px); }
        .alert-success { background: #dcfce7; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px 16px; font-size: 0.84rem; color: #15803d; margin-bottom: 1.25rem; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 12px 16px; font-size: 0.84rem; color: #dc2626; margin-bottom: 1.25rem; }
        .divider { border: none; border-top: 1px solid #e8e7f0; margin: 1.5rem 0; }
        .rules { background: #f7f6fb; border-radius: 10px; padding: 12px 14px; font-size: 0.8rem; color: #72737d; margin-bottom: 1.25rem; }
        .rules ul { padding-left: 1.2rem; margin-top: 4px; }
        .rules li { margin-bottom: 3px; }
        .back-link { display: inline-block; margin-top: 1.25rem; font-size: 0.83rem; color: #72737d; text-decoration: none; }
        .user-info { display: flex; align-items: center; gap: 12px; background: <?php echo $roleColor; ?>11; border: 1px solid <?php echo $roleColor; ?>33; border-radius: 12px; padding: 12px 16px; margin-bottom: 1.5rem; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: <?php echo $roleColor; ?>; display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-weight: 800; color: #fff; font-size: 1rem; flex-shrink: 0; }
        .user-name { font-weight: 600; font-size: 0.9rem; }
        .user-role { font-size: 0.78rem; color: #72737d; }
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>
<div class="main">
    <p class="page-title">🔑 Change Password</p>
    <p class="page-sub">Update your account password securely.</p>
    <div class="card">
        <div class="user-info">
            <div class="user-avatar"><?php echo strtoupper(substr($name, 0, 1)); ?></div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($name); ?></div>
                <div class="user-role"><?php echo ucfirst($role); ?> &nbsp;·&nbsp; <?php echo htmlspecialchars($_SESSION['username']); ?></div>
            </div>
        </div>

        <?php if ($success): ?><div class="alert-success">✓ <?php echo $success; ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert-error">⚠ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="rules">
            <strong>Password requirements:</strong>
            <ul>
                <li>Minimum 6 characters</li>
                <li>Must be different from current password</li>
                <li>New password and confirm must match</li>
            </ul>
        </div>

        <form method="POST" action="/LIMS/change_password.php">
            <?php echo csrfField(); ?>
            <label class="field-label">Current Password</label>
            <div class="input-wrap">
                <div class="input-icon">🔒</div>
                <input type="password" name="current_password" id="current_password" placeholder="Enter current password" required>
                <button type="button" class="toggle-pw" onclick="togglePw('current_password', this)">Show</button>
            </div>
            <hr class="divider">
            <label class="field-label">New Password</label>
            <div class="input-wrap">
                <div class="input-icon">🔑</div>
                <input type="password" name="new_password" id="new_password" placeholder="Enter new password" minlength="6" required>
                <button type="button" class="toggle-pw" onclick="togglePw('new_password', this)">Show</button>
            </div>
            <label class="field-label">Confirm New Password</label>
            <div class="input-wrap">
                <div class="input-icon">✅</div>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter new password" minlength="6" required>
                <button type="button" class="toggle-pw" onclick="togglePw('confirm_password', this)">Show</button>
            </div>
            <button type="submit" class="btn-submit">Update Password →</button>
        </form>
        <a href="<?php echo $dash; ?>" class="back-link">← Back to Dashboard</a>
    </div>
</div>
<script>
function togglePw(id, btn) {
    var input = document.getElementById(id);
    if (input.type === 'password') { input.type = 'text'; btn.textContent = 'Hide'; }
    else { input.type = 'password'; btn.textContent = 'Show'; }
}
</script>
</body>
</html>
