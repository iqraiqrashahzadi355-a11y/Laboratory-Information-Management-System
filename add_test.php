<?php
include 'auth_check.php';
checkRole(['admin', 'technician']);
include 'config.php';
include 'audit_log.php';

$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token']);
    $patient_id = intval($_POST['patient_id']);
    $test_name  = trim($_POST['test_name']);
    $test_date  = $_POST['test_date'];
    $result     = trim($_POST['result']);

    $check = $conn->prepare("SELECT Name FROM Patients WHERE PatientID = ?");
    $check->bind_param("i", $patient_id);
    $check->execute();
    $patient = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$patient) {
        $error = "No patient found with ID #$patient_id. Please check and try again.";
    } else {
        $stmt = $conn->prepare("INSERT INTO LabTests (PatientID, TestName, TestDate, Result, Status) VALUES (?,?,?,?,'Registered')");
        $stmt->bind_param("isss", $patient_id, $test_name, $test_date, $result);
        if ($stmt->execute()) {
            $test_id = $conn->insert_id;
            logAction($conn, "Added lab test '$test_name' for patient: " . $patient['Name']);

            // Create billing entry
            $priceRow = $conn->query("SELECT Price FROM TestPrices WHERE TestName='" . $conn->real_escape_string($test_name) . "'")->fetch_assoc();
            $price = $priceRow ? $priceRow['Price'] : 0;
            $bstmt = $conn->prepare("INSERT INTO Billing (PatientID, TestID, Amount) VALUES (?,?,?)");
            $bstmt->bind_param("iid", $patient_id, $test_id, $price);
            $bstmt->execute();
            $bstmt->close();

            $success = "Lab test '$test_name' added successfully for " . $patient['Name'] . "! (Fee: Rs. " . number_format($price,0) . ")";
        } else {
            $error = "Error adding lab test. Please try again.";
        }
        $stmt->close();
    }
}

$patients = $conn->query("SELECT PatientID, Name FROM Patients ORDER BY Name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Lab Test — LIMS</title>
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
        .input-wrap:focus-within { border-color: #059669; background: #fff; }
        .input-icon { padding: 0 14px; height: 50px; display: flex; align-items: center; background: #f0f0f0; border-right: 1px solid #eee; font-size: 1rem; flex-shrink: 0; }
        .input-wrap input, .input-wrap select { flex: 1; border: none; background: transparent; padding: 0 14px; height: 50px; font-size: 0.95rem; font-family: 'DM Sans', sans-serif; color: #0f0e17; outline: none; width: 100%; }
        .input-wrap input::placeholder { color: #bbb; }
        .btn-submit { width: 100%; padding: 14px; background: #059669; color: #fff; border: none; border-radius: 12px; font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1rem; cursor: pointer; transition: opacity 0.15s, transform 0.15s; margin-top: 0.5rem; }
        .btn-submit:hover { opacity: 0.88; transform: translateY(-1px); }
        .alert-success { background: #dcfce7; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px 16px; font-size: 0.84rem; color: #15803d; margin-bottom: 1.25rem; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 12px 16px; font-size: 0.84rem; color: #dc2626; margin-bottom: 1.25rem; }
        .back-link { display: inline-block; margin-top: 1.25rem; font-size: 0.83rem; color: #72737d; text-decoration: none; }
        .back-link:hover { color: #059669; }
        .test-types { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 1.1rem; }
        .test-chip { padding: 5px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 50px; font-size: 0.78rem; color: #15803d; cursor: pointer; transition: background 0.15s; }
        .test-chip:hover { background: #dcfce7; }
        .hint { font-size: 0.75rem; color: #72737d; margin-bottom: 0.5rem; }
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>
<div class="main">
    <p class="page-title">🧪 Add Lab Test</p>
    <p class="page-sub">Record a new lab test result for a patient.</p>
    <div class="card">
        <?php if ($success): ?><div class="alert-success">✓ <?php echo $success; ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert-error">⚠ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST" action="/LIMS/add_test.php">
            <?php echo csrfField(); ?>
            <label class="field-label">Select Patient</label>
            <div class="input-wrap">
                <div class="input-icon">👤</div>
                <select name="patient_id" required>
                    <option value="" disabled selected>Select a patient</option>
                    <?php while($p = $patients->fetch_assoc()): ?>
                    <option value="<?php echo $p['PatientID']; ?>"><?php echo '#'.$p['PatientID'].' — '.htmlspecialchars($p['Name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <label class="field-label">Test Name</label>
            <p class="hint">Quick select or type below:</p>
            <div class="test-types">
                <?php foreach(['CBC','Lipid Profile','Blood Sugar','LFT','KFT','Thyroid','Urine Analysis','PCR','Hepatitis Panel','Blood Culture'] as $t): ?>
                <span class="test-chip" onclick="document.getElementById('test_name').value='<?php echo $t; ?>'"><?php echo $t; ?></span>
                <?php endforeach; ?>
            </div>
            <div class="input-wrap">
                <div class="input-icon">🔬</div>
                <input type="text" name="test_name" id="test_name" placeholder="Enter or select test name" required>
            </div>
            <label class="field-label">Test Date</label>
            <div class="input-wrap">
                <div class="input-icon">📅</div>
                <input type="date" name="test_date" required value="<?php echo date('Y-m-d'); ?>">
            </div>
            <label class="field-label">Result</label>
            <div class="input-wrap">
                <div class="input-icon">📋</div>
                <input type="text" name="result" placeholder="e.g. Normal, High, 120mg/dL" required>
            </div>
            <button type="submit" class="btn-submit">Add Lab Test →</button>
        </form>
        <a href="<?php echo dashboardLink(); ?>" class="back-link">← Back to Dashboard</a>
    </div>
</div>
</body>
</html>
