<?php
session_start();

if (isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit;
}

// Hardcoded admin credentials (change these!)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'lims2024');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name'] = 'Administrator';
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIMS — Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --accent: #0f172a;
            --accent2: #6246ea;
            --ink: #0f0e17;
            --paper: #fffffe;
            --muted: #72737d;
            --danger: #dc2626;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            width: 700px; height: 700px;
            border-radius: 50%;
            border: 80px solid rgba(98,70,234,0.08);
            top: -200px; right: -200px;
        }
        body::after {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            border-radius: 50%;
            border: 50px solid rgba(98,70,234,0.06);
            bottom: -100px; left: -100px;
        }

        .login-card {
            background: var(--paper);
            border-radius: 20px;
            padding: 2.75rem 2.5rem;
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 2;
        }

        .admin-badge {
            display: inline-block;
            background: #1e293b;
            color: #94a3b8;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 50px;
            margin-bottom: 1.25rem;
        }

        .login-card h2 {
            font-family: 'Syne', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--ink);
            margin-bottom: 0.3rem;
        }
        .subtitle { color: var(--muted); font-size: 0.87rem; margin-bottom: 2rem; }

        .field-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--ink);
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 0.45rem;
            display: block;
        }
        .input-field {
            width: 100%;
            padding: 13px 16px;
            border: 1.5px solid #ddd;
            border-radius: 12px;
            font-size: 0.95rem;
            font-family: 'DM Sans', sans-serif;
            color: var(--ink);
            background: #fafafa;
            outline: none;
            margin-bottom: 1.1rem;
            transition: border-color 0.2s, background 0.2s;
        }
        .input-field:focus { border-color: var(--accent2); background: #fff; }
        .error-msg {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.84rem;
            color: var(--danger);
            margin-bottom: 1.1rem;
        }
        .btn-login {
            width: 100%;
            padding: 15px;
            background: #0f172a;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.15s, opacity 0.15s;
            margin-bottom: 1.25rem;
        }
        .btn-login:hover { opacity: 0.85; transform: translateY(-1px); }
        .divider {
            text-align: center; color: var(--muted);
            font-size: 0.78rem; margin: 0.75rem 0;
            position: relative;
        }
        .divider::before, .divider::after {
            content: ''; position: absolute;
            top: 50%; width: 38%; height: 1px; background: #eee;
        }
        .divider::before { left: 0; }
        .divider::after { right: 0; }
        .patient-link { text-align: center; font-size: 0.84rem; color: var(--muted); }
        .patient-link a { color: var(--accent2); font-weight: 600; text-decoration: none; }
        .patient-link a:hover { text-decoration: underline; }
        .hint { font-size: 0.75rem; color: var(--muted); margin-top: 1.25rem; text-align: center; background: #f8f8f8; padding: 8px 12px; border-radius: 8px; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="admin-badge">⚙ Staff / Admin</div>
    <h2>Admin Login</h2>
    <p class="subtitle">Restricted access. Staff credentials required.</p>

    <?php if ($error): ?>
    <div class="error-msg">⚠ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="admin_login.php">
        <label class="field-label" for="username">Username</label>
        <input type="text" name="username" id="username" class="input-field"
            placeholder="Enter username" required autofocus>

        <label class="field-label" for="password">Password</label>
        <input type="password" name="password" id="password" class="input-field"
            placeholder="Enter password" required>

        <button type="submit" class="btn-login">Login to Dashboard →</button>
    </form>

    <div class="divider">or</div>
    <p class="patient-link">Are you a patient? <a href="login.php">Login here</a></p>
    <p class="hint">Default: <code>admin</code> / <code>lims2024</code> — change in admin_login.php</p>
</div>
</body>
</html>