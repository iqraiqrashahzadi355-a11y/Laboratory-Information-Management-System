<?php
session_start();

if (isset($_SESSION['patient_id'])) {
    header("Location: /LIMS/patient_dashboard.php");
    exit;
}

include 'config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $password   = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT PatientID, Name, Password FROM Patients WHERE PatientID = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $patient = $result->fetch_assoc();
        $hashedInput = hash('sha256', $password);

        if (!empty($patient['Password']) && hash_equals($patient['Password'], $hashedInput)) {
            $_SESSION['patient_id']   = $patient['PatientID'];
            $_SESSION['patient_name'] = $patient['Name'];
            $stmt->close();
            header("Location: /LIMS/patient_dashboard.php");
            exit;
        } else {
            $error = "Incorrect password. Please try again.";
        }
    } else {
        $error = "No patient found with this ID. Please check and try again.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LIMS — Patient Login</title>
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
        .features-list { display: flex; flex-direction: column; gap: 1rem; position: relative; z-index: 2; }
        .feature-item { display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,0.85); font-size: 0.88rem; }
        .feature-dot { width: 7px; height: 7px; border-radius: 50%; background: rgba(255,255,255,0.5); flex-shrink: 0; }
        .login-panel { flex: 1; background: var(--paper); display: flex; align-items: center; justify-content: center; padding: 3rem 2.5rem; }
        .login-box { width: 100%; max-width: 380px; }
        .login-box h2 { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1.85rem; color: var(--ink); margin-bottom: 0.35rem; }
        .subtitle { color: var(--muted); font-size: 0.87rem; margin-bottom: 2rem; }
        .field-label { font-size: 0.74rem; font-weight: 600; color: var(--ink); text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 0.45rem; display: block; }
        .id-input-wrap { display: flex; align-items: center; border: 1.5px solid #ddd; border-radius: 12px; overflow: hidden; margin-bottom: 1.1rem; background: #fafafa; transition: border-color 0.2s; }
        .id-input-wrap:focus-within { border-color: var(--accent); background: #fff; }
        .id-prefix { padding: 0 16px; font-size: 0.85rem; color: var(--muted); border-right: 1px solid #eee; height: 52px; display: flex; align-items: center; background: #f0f0f0; font-weight: 600; flex-shrink: 0; }
        .id-input-wrap input { flex: 1; border: none; background: transparent; padding: 0 16px; height: 52px; font-size: 1.1rem; font-family: 'Syne', sans-serif; font-weight: 700; color: var(--ink); outline: none; }
        .id-input-wrap input::placeholder { color: #ccc; font-weight: 400; letter-spacing: 0; font-size: 0.95rem; }
        .toggle-pw { padding: 0 14px; cursor: pointer; font-size: 0.78rem; color: var(--muted); background: none; border: none; font-family: 'DM Sans', sans-serif; flex-shrink: 0; }
        .error-msg { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 10px 14px; font-size: 0.84rem; color: var(--danger); margin-bottom: 1.1rem; }
        .btn-login { width: 100%; padding: 15px; background: var(--accent); color: #fff; border: none; border-radius: 12px; font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1rem; cursor: pointer; letter-spacing: 0.02em; transition: transform 0.15s, opacity 0.15s; margin-bottom: 1.25rem; }
        .btn-login:hover { opacity: 0.88; transform: translateY(-1px); }
        .divider { text-align: center; color: var(--muted); font-size: 0.78rem; margin: 1rem 0; position: relative; }
        .divider::before, .divider::after { content: ''; position: absolute; top: 50%; width: 38%; height: 1px; background: #eee; }
        .divider::before { left: 0; }
        .divider::after { right: 0; }
        .staff-link { text-align: center; color: var(--muted); font-size: 0.84rem; }
        .staff-link a { color: var(--accent); font-weight: 600; text-decoration: none; }
        .staff-link a:hover { text-decoration: underline; }
        .security-note { background: #f7f6fb; border-radius: 10px; padding: 10px 14px; font-size: 0.78rem; color: var(--muted); margin-bottom: 1.1rem; }
        @media (max-width: 768px) { .brand-panel { display: none; } .login-panel { padding: 2rem 1.5rem; } }
    </style>
</head>
<body>
<div class="brand-panel">
    <div class="brand-logo"><em>L</em>IMS</div>
    <div class="brand-headline">
        <h1>Your Health.<br>Your Records.</h1>
        <p>Access your lab test results, medical reports, and patient history — all in one secure place.</p>
    </div>
    <div class="features-list">
        <div class="feature-item"><div class="feature-dot"></div> View your lab test results instantly</div>
        <div class="feature-item"><div class="feature-dot"></div> Download personalized patient reports</div>
        <div class="feature-item"><div class="feature-dot"></div> Password-protected secure access</div>
        <div class="feature-item"><div class="feature-dot"></div> Your data stays private and secure</div>
    </div>
</div>

<div class="login-panel">
    <div class="login-box">
        <h2>Patient Login</h2>
        <p class="subtitle">Enter your Patient ID and password to continue.</p>

        <?php if ($error): ?>
        <div class="error-msg">⚠ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="security-note">🔒 First time logging in? Your default password is your registered contact number.</div>

        <form method="POST" action="/LIMS/login.php">
            <label class="field-label" for="patient_id">Patient ID</label>
            <div class="id-input-wrap">
                <div class="id-prefix">ID #</div>
                <input type="number" name="patient_id" id="patient_id" placeholder="e.g. 1042" min="1" required autofocus>
            </div>

            <label class="field-label" for="password">Password</label>
            <div class="id-input-wrap">
                <div class="id-prefix">🔒</div>
                <input type="password" name="password" id="password" placeholder="Enter password" required>
                <button type="button" class="toggle-pw" onclick="togglePw()">Show</button>
            </div>

            <button type="submit" class="btn-login">Access My Records →</button>
        </form>

        <div class="divider">or</div>
        <p class="staff-link">Staff / Admin? <a href="/LIMS/auth_login.php">Login here</a></p>
    </div>
</div>

<script>
function togglePw() {
    var input = document.getElementById('password');
    var btn = event.target;
    if (input.type === 'password') { input.type = 'text'; btn.textContent = 'Hide'; }
    else { input.type = 'password'; btn.textContent = 'Show'; }
}
</script>
</body>
</html>
