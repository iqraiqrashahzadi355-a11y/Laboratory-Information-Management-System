<?php
include 'auth_check.php';
checkRole(['admin', 'technician']);
include 'config.php';
include 'audit_log.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Patient — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #f7f6fb; color: #0f0e17; min-height: 100vh; }
        .main { max-width: 520px; margin: 2.5rem auto; padding: 0 1.5rem; }
        .page-title { font-family: 'Syne', sans-serif; font-size: 1.5rem; font-weight: 800; margin-bottom: 0.3rem; }
        .page-sub { color: #72737d; font-size: 0.88rem; margin-bottom: 2rem; }
        .card { background: #fff; border: 1px solid #e8e7f0; border-radius: 16px; padding: 2rem; }
        .field-label { font-size: 0.73rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: #0f0e17; display: block; margin-bottom: 0.4rem; }
        .input-wrap { border: 1.5px solid #e8e7f0; border-radius: 12px; overflow: hidden; margin-bottom: 1.1rem; background: #fafafa; transition: border-color 0.2s; display: flex; align-items: center; }
        .input-wrap:focus-within { border-color: #0891b2; background: #fff; }
        .input-icon { padding: 0 14px; height: 50px; display: flex; align-items: center; background: #f0f0f0; border-right: 1px solid #eee; font-size: 1rem; }
        .input-wrap input, .input-wrap select { flex: 1; border: none; background: transparent; padding: 0 14px; height: 50px; font-size: 0.95rem; font-family: 'DM Sans', sans-serif; color: #0f0e17; outline: none; }
        .input-wrap input::placeholder { color: #bbb; }
        .btn-submit { width: 100%; padding: 14px; background: #0891b2; color: #fff; border: none; border-radius: 12px; font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1rem; cursor: pointer; transition: opacity 0.15s, transform 0.15s; margin-top: 0.5rem; }
        .btn-submit:hover { opacity: 0.88; transform: translateY(-1px); }
        .alert-success { background: #dcfce7; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px 16px; font-size: 0.84rem; color: #15803d; margin-bottom: 1.25rem; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 12px 16px; font-size: 0.84rem; color: #dc2626; margin-bottom: 1.25rem; }
        .back-link { display: inline-block; margin-top: 1.25rem; font-size: 0.83rem; color: #72737d; text-decoration: none; }
        .back-link:hover { color: #0891b2; }
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>

<div class="main">
    <p class="page-title">👤 Register Patient</p>
    <p class="page-sub">Add a new patient to the LIMS system.</p>

    <?php
    $success = ''; $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCSRF($_POST['csrf_token']);
        $name    = trim($_POST['name']);
        $age     = intval($_POST['age']);
        $gender  = $_POST['gender'];
        $contact = trim($_POST['contact']);
        $branch_id = intval($_POST['branch_id']);
        $patientPassword = hash('sha256', $contact); // default password = contact number

        $stmt = $conn->prepare("INSERT INTO Patients (Name, Age, Gender, ContactNumber, BranchID, Password) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("sissis", $name, $age, $gender, $contact, $branch_id, $patientPassword);
        if ($stmt->execute()) {
            logAction($conn, "Registered new patient: $name");
            $success = "Patient '$name' registered successfully! Default portal password: their contact number.";
        } else {
            $error = "Error registering patient. Please try again.";
        }
        $stmt->close();
    }
    ?>

    <div class="card">
        <?php if ($success): ?><div class="alert-success">✓ <?php echo $success; ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert-error">⚠ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form method="POST" action="/LIMS/add_patient.php">
            <label class="field-label">Full Name</label>
            <div class="input-wrap">
                <div class="input-icon">👤</div>
                <input type="text" name="name" placeholder="Enter full name" required>
            </div>

            <label class="field-label">Age</label>
            <div class="input-wrap">
                <div class="input-icon">🎂</div>
                <input type="number" name="age" placeholder="Enter age" min="1" max="150" required>
            </div>

            <label class="field-label">Gender</label>
            <div class="input-wrap">
                <div class="input-icon">⚧</div>
                <select name="gender" required>
                    <option value="" disabled selected>Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <label class="field-label">Contact Number</label>
            <div class="input-wrap">
                <div class="input-icon">📞</div>
                <input type="text" name="contact" placeholder="e.g. 03001234567" required>
            </div>

            <label class="field-label">Branch</label>
            <div class="input-wrap">
                <div class="input-icon">🏥</div>
                <select name="branch_id" required>
                    <option value="" disabled selected>Select Branch</option>
                    <?php
                    $branches = $conn->query("SELECT BranchID, BranchName, City FROM Branches WHERE IsActive=1");
                    while($br=$branches->fetch_assoc()):
                    ?>
                    <option value="<?php echo $br['BranchID']; ?>"><?php echo htmlspecialchars($br['BranchName']); ?> — <?php echo $br['City']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <?php echo csrfField(); ?>
            <button type="submit" class="btn-submit">Register Patient →</button>
        </form>

        <a href="<?php echo dashboardLink(); ?>" class="back-link">← Back to Dashboard</a>
    </div>
</div>
</body>
</html>
