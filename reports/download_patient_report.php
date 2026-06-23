<?php
session_start();

// Allow both staff and patient access
$isStaff   = isset($_SESSION['user_id']);
$isPatient = isset($_SESSION['patient_id']);

if (!$isStaff && !$isPatient) {
    header("Location: /LIMS/login.php");
    exit;
}

// Staff role check
if ($isStaff) {
    include dirname(__FILE__) . '/../auth_check.php';
    checkRole(['admin', 'manager', 'doctor']);
}

include dirname(__FILE__) . '/../config.php';

if ($isStaff) {
    include dirname(__FILE__) . '/../audit_log.php';
}

if (!isset($_GET['id'])) die("Patient ID missing.");

$patient_id = intval($_GET['id']);

// Patient can only download their own report
if ($isPatient && !$isStaff && $_SESSION['patient_id'] != $patient_id) {
    die("Access denied.");
}

$stmt = $conn->prepare("SELECT * FROM Patients WHERE PatientID = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) die("Patient not found.");

$stmt = $conn->prepare("SELECT * FROM LabTests WHERE PatientID = ? ORDER BY TestDate DESC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$tests = $stmt->get_result();
$stmt->close();

if ($isStaff) {
    logAction($conn, "Downloaded PDF report for patient: " . $patient['Name']);
}

$testRows = '';
$i = 1;
while ($t = $tests->fetch_assoc()) {
    $status = $t['Status'];
    $statusColor = $status==='Completed' ? '#15803d' : ($status==='Testing' ? '#b45309' : '#1d4ed8');
    $statusBg    = $status==='Completed' ? '#dcfce7' : ($status==='Testing' ? '#fef3c7' : '#dbeafe');
    $testRows .= "<tr>
        <td>{$i}</td>
        <td>".htmlspecialchars($t['TestName'])."</td>
        <td>".$t['TestDate']."</td>
        <td>".htmlspecialchars($t['Result'])."</td>
        <td><span style='background:{$statusBg};color:{$statusColor};padding:3px 10px;border-radius:50px;font-size:11px;font-weight:600;'>{$status}</span></td>
    </tr>";
    $i++;
}

if (empty($testRows)) {
    $testRows = "<tr><td colspan='5' style='text-align:center;color:#72737d;padding:20px;'>No lab tests found.</td></tr>";
}

$totalTests     = $i - 1;
$completedTests = $conn->query("SELECT COUNT(*) as t FROM LabTests WHERE PatientID=$patient_id AND Status='Completed'")->fetch_assoc()['t'];
$testingTests   = $conn->query("SELECT COUNT(*) as t FROM LabTests WHERE PatientID=$patient_id AND Status='Testing'")->fetch_assoc()['t'];
$reportDate     = date('d M Y, h:i A');
$patientName    = htmlspecialchars($patient['Name']);
$backLink       = $isStaff ? '/LIMS/reports/patient_reports.php' : '/LIMS/patient_dashboard.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Patient Report — {$patientName}</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Segoe UI',Arial,sans-serif;background:#fff;color:#1a1a1a;padding:30px;font-size:13px;}
        .header{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:20px;border-bottom:2px solid #6246ea;margin-bottom:25px;}
        .logo{font-size:22px;font-weight:900;color:#6246ea;}
        .logo em{background:#6246ea;color:#fff;font-style:normal;padding:2px 8px;border-radius:5px;margin-right:3px;}
        .logo-sub{font-size:10px;color:#72737d;margin-top:3px;letter-spacing:0.05em;}
        .report-info{text-align:right;}
        .report-info .title{font-size:16px;font-weight:700;}
        .report-info .date{font-size:11px;color:#72737d;margin-top:3px;}
        .report-info .rid{font-size:11px;color:#6246ea;font-weight:600;}
        .patient-card{background:#f7f6fb;border:1px solid #e8e7f0;border-radius:10px;padding:18px 20px;margin-bottom:25px;}
        .patient-card h2{font-size:13px;font-weight:700;color:#6246ea;margin-bottom:14px;text-transform:uppercase;letter-spacing:0.06em;}
        .patient-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;}
        .patient-field label{font-size:10px;color:#72737d;text-transform:uppercase;letter-spacing:0.07em;font-weight:600;display:block;margin-bottom:3px;}
        .patient-field span{font-size:13px;font-weight:600;}
        .summary-row{display:flex;gap:15px;margin-bottom:25px;}
        .summary-box{flex:1;background:#6246ea;color:#fff;border-radius:10px;padding:14px 16px;text-align:center;}
        .summary-box .num{font-size:26px;font-weight:900;line-height:1;}
        .summary-box .lbl{font-size:10px;opacity:0.8;margin-top:4px;text-transform:uppercase;letter-spacing:0.06em;}
        .summary-box.green{background:#059669;}
        .summary-box.blue{background:#0891b2;}
        .section-title{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#72737d;margin-bottom:12px;}
        table{width:100%;border-collapse:collapse;}
        thead th{background:#6246ea;color:#fff;padding:10px 14px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;}
        tbody td{padding:10px 14px;border-bottom:1px solid #f0eff6;font-size:12px;}
        tbody tr:nth-child(even) td{background:#f7f6fb;}
        .footer{margin-top:30px;padding-top:15px;border-top:1px solid #e8e7f0;display:flex;justify-content:space-between;align-items:flex-end;}
        .footer-left{font-size:10px;color:#72737d;}
        .signature-box{border-top:1px solid #1a1a1a;padding-top:5px;margin-top:30px;font-size:10px;color:#72737d;width:180px;text-align:center;}
        .confidential{background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:8px 14px;font-size:11px;color:#92400e;margin-bottom:20px;text-align:center;}
        @media print{.no-print{display:none!important;}}
    </style>
</head>
<body>
<div class='no-print' style='margin-bottom:20px;display:flex;gap:10px;'>
    <button onclick='window.print()' style='padding:10px 24px;background:#6246ea;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;'>🖨 Print / Save as PDF</button>
    <a href='{$backLink}' style='padding:10px 20px;background:#f1f0f7;color:#72737d;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none;'>← Back</a>
</div>
<div class='confidential'>🔒 CONFIDENTIAL — This report contains sensitive patient information. Authorized personnel only.</div>
<div class='header'>
    <div>
        <div class='logo'><em>L</em>IMS</div>
        <div class='logo-sub'>LABORATORY INFORMATION MANAGEMENT SYSTEM<br>Women University Multan</div>
    </div>
    <div class='report-info'>
        <div class='title'>Patient Lab Report</div>
        <div class='date'>Generated: {$reportDate}</div>
        <div class='rid'>Report ID: RPT-{$patient_id}-".date('Ymd')."</div>
    </div>
</div>
<div class='patient-card'>
    <h2>Patient Information</h2>
    <div class='patient-grid'>
        <div class='patient-field'><label>Patient ID</label><span>#".str_pad($patient_id,4,'0',STR_PAD_LEFT)."</span></div>
        <div class='patient-field'><label>Full Name</label><span>{$patientName}</span></div>
        <div class='patient-field'><label>Age</label><span>".htmlspecialchars($patient['Age'])." years</span></div>
        <div class='patient-field'><label>Gender</label><span>".htmlspecialchars($patient['Gender'])."</span></div>
        <div class='patient-field'><label>Contact</label><span>".htmlspecialchars($patient['ContactNumber'])."</span></div>
        <div class='patient-field'><label>Report Date</label><span>".date('d M Y')."</span></div>
    </div>
</div>
<div class='summary-row'>
    <div class='summary-box'><div class='num'>{$totalTests}</div><div class='lbl'>Total Tests</div></div>
    <div class='summary-box green'><div class='num'>{$completedTests}</div><div class='lbl'>Completed</div></div>
    <div class='summary-box blue'><div class='num'>{$testingTests}</div><div class='lbl'>In Testing</div></div>
</div>
<p class='section-title'>Laboratory Test Results</p>
<table>
    <thead><tr><th>#</th><th>Test Name</th><th>Test Date</th><th>Result</th><th>Status</th></tr></thead>
    <tbody>{$testRows}</tbody>
</table>
<div class='footer'>
    <div class='footer-left'>Women University Multan — Laboratory Information Management System<br>This report is computer generated and valid without signature.</div>
    <div><div class='signature-box'><br><br>Authorized Signature</div></div>
</div>
</body>
</html>";
?>