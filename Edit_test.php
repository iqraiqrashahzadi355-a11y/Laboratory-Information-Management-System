<?php
include 'auth_check.php';
checkRole(['admin', 'technician']);
include 'config.php';
include 'audit_log.php';

if (!isset($_GET['id'])) {
    header("Location: /LIMS/view_tests.php");
    exit;
}

$test_id = intval($_GET['id']);
$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token']);
    $test_name = trim($_POST['test_name']);
    $test_date = $_POST['test_date'];
    $result    = trim($_POST['result']);
    $status    = $_POST['status'];

    $stmt = $conn->prepare("UPDATE LabTests SET TestName=?, TestDate=?, Result=?, Status=? WHERE TestID=?");
    $stmt->bind_param("ssssi", $test_name, $test_date, $result, $status, $test_id);
    if ($stmt->execute()) {
        logAction($conn, "Updated test #$test_id: $test_name");
        $success = "Test updated successfully!";
    } else {
        $error = "Error updating test.";
    }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT LabTests.*, Patients.Name as PatientName FROM LabTests JOIN Patients ON LabTests.PatientID = Patients.PatientID WHERE TestID=?");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$test = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$test) die("Test not found.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Test — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:#f7f6fb;color:#0f0e17;min-height:100vh;}
        .main{max-width:520px;margin:2.5rem auto;padding:0 1.5rem;}
        .page-title{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;margin-bottom:0.3rem;}
        .page-sub{color:#72737d;font-size:0.88rem;margin-bottom:2rem;}
        .card{background:#fff;border:1px solid #e8e7f0;border-radius:16px;padding:2rem;}
        .field-label{font-size:0.73rem;font-weight:600;text-transform:uppercase;letter-spacing:0.07em;color:#0f0e17;display:block;margin-bottom:0.4rem;}
        .input-wrap{border:1.5px solid #e8e7f0;border-radius:12px;overflow:hidden;margin-bottom:1.1rem;background:#fafafa;transition:border-color 0.2s;display:flex;align-items:center;}
        .input-wrap:focus-within{border-color:#059669;background:#fff;}
        .input-icon{padding:0 14px;height:50px;display:flex;align-items:center;background:#f0f0f0;border-right:1px solid #eee;font-size:1rem;}
        .input-wrap input,.input-wrap select{flex:1;border:none;background:transparent;padding:0 14px;height:50px;font-size:0.95rem;font-family:'DM Sans',sans-serif;color:#0f0e17;outline:none;}
        .btn-submit{width:100%;padding:14px;background:#059669;color:#fff;border:none;border-radius:12px;font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;cursor:pointer;transition:opacity 0.15s;margin-top:0.5rem;}
        .btn-submit:hover{opacity:0.88;}
        .alert-success{background:#dcfce7;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;font-size:0.84rem;color:#15803d;margin-bottom:1.25rem;}
        .alert-error{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;font-size:0.84rem;color:#dc2626;margin-bottom:1.25rem;}
        .back-link{display:inline-block;margin-top:1rem;font-size:0.83rem;color:#72737d;text-decoration:none;}
        .info-badge{display:inline-block;background:#dcfce7;color:#15803d;padding:4px 14px;border-radius:50px;font-size:0.8rem;font-weight:700;margin-bottom:1.25rem;}
        .test-types{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:1rem;}
        .test-chip{padding:4px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:50px;font-size:0.75rem;color:#15803d;cursor:pointer;}
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>
<div class="main">
    <p class="page-title">✏️ Edit Lab Test</p>
    <p class="page-sub">Update test information.</p>
    <div class="card">
        <div class="info-badge">Test #<?php echo $test_id; ?> — <?php echo htmlspecialchars($test['PatientName']); ?></div>
        <?php if($success): ?><div class="alert-success">✓ <?php echo $success; ?></div><?php endif; ?>
        <?php if($error):   ?><div class="alert-error">⚠ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST" action="/LIMS/edit_test.php?id=<?php echo $test_id; ?>">
            <?php echo csrfField(); ?>
            <label class="field-label">Test Name</label>
            <div class="test-types">
                <?php foreach(['CBC','Lipid Profile','Blood Sugar','LFT','KFT','Thyroid','Urine Analysis','PCR','Hepatitis Panel','Blood Culture'] as $t): ?>
                <span class="test-chip" onclick="document.getElementById('test_name').value='<?php echo $t; ?>'"><?php echo $t; ?></span>
                <?php endforeach; ?>
            </div>
            <div class="input-wrap">
                <div class="input-icon">🔬</div>
                <input type="text" name="test_name" id="test_name" value="<?php echo htmlspecialchars($test['TestName']); ?>" required>
            </div>
            <label class="field-label">Test Date</label>
            <div class="input-wrap">
                <div class="input-icon">📅</div>
                <input type="date" name="test_date" value="<?php echo $test['TestDate']; ?>" required>
            </div>
            <label class="field-label">Result</label>
            <div class="input-wrap">
                <div class="input-icon">📋</div>
                <input type="text" name="result" value="<?php echo htmlspecialchars($test['Result']); ?>" required>
            </div>
            <label class="field-label">Status</label>
            <div class="input-wrap">
                <div class="input-icon">📊</div>
                <select name="status" required>
                    <option value="Registered" <?php echo $test['Status']==='Registered'?'selected':''; ?>>Registered</option>
                    <option value="Testing"    <?php echo $test['Status']==='Testing'?'selected':''; ?>>Testing</option>
                    <option value="Completed"  <?php echo $test['Status']==='Completed'?'selected':''; ?>>Completed</option>
                </select>
            </div>
            <button type="submit" class="btn-submit">Update Test →</button>
        </form>
        <a href="/LIMS/view_tests.php" class="back-link">← Back to Tests</a>
    </div>
</div>
</body>
</html>
