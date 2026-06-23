<?php
session_start();

if (isset($_SESSION['user_id'])) {
    $map = ['admin'=>'dashboard_admin.php','technician'=>'dashboard_technician.php','manager'=>'dashboard_manager.php','doctor'=>'dashboard_doctor.php'];
    header("Location: /LIMS/" . ($map[$_SESSION['role']] ?? 'auth_login.php'));
    exit;
}

include 'config.php';
include 'mailer.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT UserID, FullName, Username, Password, Role, IsActive, FailedAttempts, LockedUntil FROM Users WHERE Username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (!$user['IsActive']) {
            $error = "Your account has been deactivated. Please contact admin.";

        } elseif (!empty($user['LockedUntil']) && strtotime($user['LockedUntil']) > time()) {
            $remaining = ceil((strtotime($user['LockedUntil']) - time()) / 60);
            $error = "Account locked due to multiple failed attempts. Try again in {$remaining} minute(s) or contact admin.";

        } elseif (password_verify($password, $user['Password'])) {
            $conn->query("UPDATE Users SET FailedAttempts=0, LockedUntil=NULL WHERE UserID=" . $user['UserID']);

            $_SESSION['user_id']       = $user['UserID'];
            $_SESSION['username']      = $user['Username'];
            $_SESSION['fullname']      = $user['FullName'];
            $_SESSION['role']          = $user['Role'];
            $_SESSION['last_activity'] = time();
            $stmt->close();

            $uid=$user['UserID']; $uname=$user['Username']; $role=$user['Role'];
            $ip=$_SERVER['REMOTE_ADDR']??''; $page=$_SERVER['REQUEST_URI']??'';
            $action="Logged in successfully";
            $l=$conn->prepare("INSERT INTO AuditLog (UserID,Username,Role,Action,PageURL,IPAddress) VALUES (?,?,?,?,?,?)");
            $l->bind_param("isssss",$uid,$uname,$role,$action,$page,$ip);
            $l->execute(); $l->close();

            $map = ['admin'=>'dashboard_admin.php','technician'=>'dashboard_technician.php','manager'=>'dashboard_manager.php','doctor'=>'dashboard_doctor.php'];
            header("Location: /LIMS/" . ($map[$user['Role']] ?? 'auth_login.php'));
            exit;

        } else {
            $attempts = $user['FailedAttempts'] + 1;

            if ($attempts >= 3) {
                $lockTime = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                $conn->query("UPDATE Users SET FailedAttempts=$attempts, LockedUntil='$lockTime' WHERE UserID=" . $user['UserID']);
                $error = "Too many failed attempts. Account locked for 30 minutes. Contact admin to unlock.";

                $uid=$user['UserID']; $uname=$user['Username']; $role=$user['Role'];
                $ip=$_SERVER['REMOTE_ADDR']??''; $page=$_SERVER['REQUEST_URI']??'';
                $action="Account locked after 3 failed login attempts";
                $l=$conn->prepare("INSERT INTO AuditLog (UserID,Username,Role,Action,PageURL,IPAddress) VALUES (?,?,?,?,?,?)");
                $l->bind_param("isssss",$uid,$uname,$role,$action,$page,$ip);
                $l->execute(); $l->close();

                // Email admins about the lockout
                $admins = $conn->query("SELECT FullName, Email FROM Users WHERE Role='admin' AND IsActive=1 AND Email IS NOT NULL AND Email != ''");
                $msg = "An account has been <strong>locked</strong> due to 3 failed login attempts.<br><br>
                    <strong>Username:</strong> " . htmlspecialchars($uname) . "<br>
                    <strong>Role:</strong> " . ucfirst($role) . "<br>
                    <strong>IP Address:</strong> " . htmlspecialchars($ip) . "<br>
                    <strong>Time:</strong> " . date('d M Y, h:i A') . "<br><br>
                    You can unlock this account from the Manage Users page.";
                while ($adm = $admins->fetch_assoc()) {
                    sendLIMSEmail(
                        $adm['Email'],
                        $adm['FullName'],
                        "⚠ Account Locked — " . $uname,
                        limsEmailTemplate("Account Locked 🔒", $msg, "Manage Users", "http://localhost/LIMS/manage_users.php")
                    );
                }
            } else {
                $conn->query("UPDATE Users SET FailedAttempts=$attempts WHERE UserID=" . $user['UserID']);
                $remaining = 3 - $attempts;
                $error = "Incorrect password. {$remaining} attempt(s) remaining before account lockout.";
            }
        }
    } else {
        $error = "No account found with that username.";
    }
    if(isset($stmt)) $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIMS — Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --accent: #6246ea; --ink: #0f0e17; --paper: #fffffe; --muted: #72737d; --danger: #dc2626; }
        body { font-family: 'DM Sans', sans-serif; background: var(--ink); min-height: 100vh; display: flex; overflow: hidden; }
        .brand-panel { width: 52%; background: var(--accent); display: flex; flex-direction: column; justify-content: space-between; padding: 3rem; position: relative; overflow: hidden; }
        .brand-panel::before { content: ''; position: absolute; width: 500px; height: 500px; border-radius: 50%; border: 60px solid rgba(255,255,255,0.08); top: -120px; right: -120px; }
        .brand-panel::after { content: ''; position: absolute; width: 300px; height: 300px; border-radius: 50%; border: 40px solid rgba(255,255,255,0.06); bottom: -60px; left: -60px; }
        .brand-logo { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.4rem; color: #fff; }
        .brand-logo em { background: rgba(255,255,255,0.2); font-style: normal; padding: 1px 8px; border-radius: 6px; margin-right: 3px; }
        .brand-headline { position: relative; z-index: 2; }
        .brand-headline h1 { font-family: 'Syne', sans-serif; font-size: 2.8rem; font-weight: 800; color: #fff; line-height: 1.1; margin-bottom: 1rem; }
        .brand-headline p { color: rgba(255,255,255,0.75); font-size: 0.95rem; line-height: 1.7; max-width: 340px; font-weight: 300; }
        .roles-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; position: relative; z-index: 2; }
        .role-chip { background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); border-radius: 10px; padding: 0.65rem 0.9rem; color: rgba(255,255,255,0.9); font-size: 0.82rem; font-weight: 500; }
        .login-panel { flex: 1; background: var(--paper); display: flex; align-items: center; justify-content: center; padding: 3rem 2.5rem; }
        .login-box { width: 100%; max-width: 380px; }
        .login-box h2 { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1.85rem; color: var(--ink); margin-bottom: 0.35rem; }
        .subtitle { color: var(--muted); font-size: 0.87rem; margin-bottom: 2rem; }
        .field-label { font-size: 0.74rem; font-weight: 600; color: var(--ink); text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 0.45rem; display: block; }
        .input-wrap { border: 1.5px solid #ddd; border-radius: 12px; overflow: hidden; margin-bottom: 1rem; background: #fafafa; transition: border-color 0.2s; display: flex; align-items: center; }
        .input-wrap:focus-within { border-color: var(--accent); background: #fff; }
        .input-icon { padding: 0 14px; height: 50px; display: flex; align-items: center; background: #f0f0f0; border-right: 1px solid #eee; font-size: 1.1rem; }
        .input-wrap input { flex: 1; border: none; background: transparent; padding: 0 14px; height: 50px; font-size: 0.95rem; font-family: 'DM Sans', sans-serif; color: var(--ink); outline: none; }
        .input-wrap input::placeholder { color: #bbb; }
        .error-msg { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 10px 14px; font-size: 0.84rem; color: var(--danger); margin-bottom: 1rem; }
        .lock-msg { background: #fef3c7; border: 1px solid #fde68a; border-radius: 10px; padding: 10px 14px; font-size: 0.84rem; color: #92400e; margin-bottom: 1rem; }
        .timeout-msg { background: #fef3c7; border: 1px solid #fde68a; border-radius: 10px; padding: 10px 14px; font-size: 0.84rem; color: #92400e; margin-bottom: 1rem; }
        .btn-login { width: 100%; padding: 14px; background: var(--accent); color: #fff; border: none; border-radius: 12px; font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1rem; cursor: pointer; transition: transform 0.15s, opacity 0.15s; margin-bottom: 1.5rem; }
        .btn-login:hover { opacity: 0.88; transform: translateY(-1px); }
        .hint-box { background: #f7f6fb; border-radius: 10px; padding: 12px 14px; font-size: 0.8rem; color: var(--muted); }
        .hint-box strong { color: var(--ink); font-weight: 600; display: block; margin-bottom: 6px; }
        .hint-row { display: flex; justify-content: space-between; padding: 2px 0; }
        .patient-link { text-align: center; margin-top: 1rem; font-size: 0.83rem; color: var(--muted); }
        .patient-link a { color: var(--accent); font-weight: 600; text-decoration: none; }
        @media (max-width: 768px) { .brand-panel { display: none; } .login-panel { padding: 2rem 1.5rem; } }
    </style>
</head>
<body>
<div class="brand-panel">
    <div class="brand-logo"><em>L</em>IMS</div>
    <div class="brand-headline">
        <h1>Lab Information<br>Management<br>System</h1>
        <p>Centralized platform for managing laboratory operations, samples, tests, and reports.</p>
    </div>
    <div class="roles-grid">
        <div class="role-chip">⚙️ Admin</div>
        <div class="role-chip">🧪 Technician</div>
        <div class="role-chip">📊 Manager</div>
        <div class="role-chip">👨‍⚕️ Doctor</div>
    </div>
</div>

<div class="login-panel">
    <div class="login-box">
        <h2>Staff Login</h2>
        <p class="subtitle">Sign in with your assigned credentials.</p>

        <?php if (isset($_GET['timeout'])): ?>
        <div class="timeout-msg">⏱ Session expired. Please login again.</div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="<?php echo strpos($error,'locked')!==false ? 'lock-msg' : 'error-msg'; ?>">
            <?php echo strpos($error,'locked')!==false ? '🔒' : '⚠'; ?> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="/LIMS/auth_login.php">
            <label class="field-label" for="username">Username</label>
            <div class="input-wrap">
                <div class="input-icon">👤</div>
                <input type="text" name="username" id="username" placeholder="Enter your username" required autofocus>
            </div>
            <label class="field-label" for="password">Password</label>
            <div class="input-wrap">
                <div class="input-icon">🔒</div>
                <input type="password" name="password" id="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn-login">Sign In →</button>
        </form>

        <div class="hint-box">
            <strong>Default credentials (password: lims1234)</strong>
            <div class="hint-row"><span>Admin:</span><span>admin</span></div>
            <div class="hint-row"><span>Technician:</span><span>tech1</span></div>
            <div class="hint-row"><span>Manager:</span><span>manager1</span></div>
            <div class="hint-row"><span>Doctor:</span><span>doctor1</span></div>
        </div>

        <p class="patient-link">Patient? <a href="/LIMS/login.php">Access your records here</a></p>
    </div>
</div>
</body>
</html>