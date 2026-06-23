<?php
include 'auth_check.php';
checkRole(['admin', 'technician']);
include 'config.php';
include 'audit_log.php';

// Auto generate barcode for tests that don't have one
$tests = $conn->query("SELECT TestID FROM LabTests WHERE BarcodeID IS NULL OR BarcodeID = ''");
while ($t = $tests->fetch_assoc()) {
    $barcode = 'LMS' . str_pad($t['TestID'], 6, '0', STR_PAD_LEFT) . date('Y');
    $conn->query("UPDATE LabTests SET BarcodeID='$barcode' WHERE TestID=" . $t['TestID']);
}

// Fetch all tests with barcodes
$search = $_GET['search'] ?? '';
$where  = $search ? "WHERE Patients.Name LIKE '%" . $conn->real_escape_string($search) . "%'" : "";

$tests = $conn->query("
    SELECT LabTests.*, Patients.Name as PatientName
    FROM LabTests
    JOIN Patients ON LabTests.PatientID = Patients.PatientID
    $where
    ORDER BY LabTests.TestID DESC
");

logAction($conn, "Viewed barcode generation page");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Generation — LIMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #f7f6fb; color: #0f0e17; min-height: 100vh; }
        .hero { background: linear-gradient(135deg, #1e293b, #334155); padding: 2rem 2.5rem; color: #fff; }
        .hero h1 { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; margin-bottom: 0.3rem; }
        .hero p { opacity: 0.7; font-size: 0.9rem; }
        .main { max-width: 1100px; margin: 0 auto; padding: 2rem 1.5rem; }

        /* Filter */
        .filter-card { background: #fff; border: 1px solid #e8e7f0; border-radius: 14px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 0.4rem; flex: 1; }
        .filter-group label { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: #72737d; }
        .filter-input { padding: 9px 14px; border: 1.5px solid #e8e7f0; border-radius: 10px; font-size: 0.87rem; font-family: 'DM Sans', sans-serif; color: #0f0e17; background: #fafafa; outline: none; transition: border-color 0.2s; }
        .filter-input:focus { border-color: #6246ea; }
        .btn-filter { padding: 10px 20px; background: #1e293b; color: #fff; border: none; border-radius: 10px; font-family: 'Syne', sans-serif; font-weight: 700; font-size: 0.87rem; cursor: pointer; }
        .btn-reset { padding: 10px 16px; background: #f1f0f7; color: #72737d; border: none; border-radius: 10px; font-size: 0.87rem; cursor: pointer; text-decoration: none; display: inline-block; }

        /* Print all button */
        .top-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .top-actions h3 { font-family: 'Syne', sans-serif; font-size: 0.9rem; font-weight: 700; color: #72737d; }
        .btn-print-all { padding: 10px 24px; background: #6246ea; color: #fff; border: none; border-radius: 50px; font-family: 'Syne', sans-serif; font-weight: 700; font-size: 0.87rem; cursor: pointer; }
        .btn-print-all:hover { opacity: 0.88; }

        /* Barcode grid */
        .barcode-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem; }
        .barcode-card { background: #fff; border: 1px solid #e8e7f0; border-radius: 14px; padding: 1.5rem; text-align: center; transition: transform 0.15s, box-shadow 0.15s; }
        .barcode-card:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(0,0,0,0.08); }
        .barcode-card .patient-name { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 0.95rem; color: #0f0e17; margin-bottom: 4px; }
        .barcode-card .test-name { font-size: 0.8rem; color: #72737d; margin-bottom: 4px; }
        .barcode-card .barcode-id { font-size: 0.75rem; color: #6246ea; font-weight: 600; margin-bottom: 1rem; font-family: monospace; letter-spacing: 0.05em; }
        .barcode-card svg { max-width: 100%; height: auto; }
        .barcode-card .date { font-size: 0.72rem; color: #72737d; margin-top: 0.5rem; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 50px; font-size: 0.71rem; font-weight: 600; margin-top: 0.5rem; }
        .badge-Registered { background: #dbeafe; color: #1d4ed8; }
        .badge-Testing    { background: #fef3c7; color: #b45309; }
        .badge-Completed  { background: #dcfce7; color: #15803d; }
        .btn-print-single { margin-top: 1rem; padding: 6px 18px; background: #f7f6fb; color: #6246ea; border: 1px solid #e8e7f0; border-radius: 50px; font-size: 0.78rem; font-weight: 600; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: background 0.15s; }
        .btn-print-single:hover { background: #ede9fe; }

        /* Print styles */
        @media print {
            .no-print { display: none !important; }
            .barcode-card { break-inside: avoid; border: 1px solid #ccc; margin: 10px; }
            .hero, .filter-card, .top-actions { display: none !important; }
            body { background: #fff; }
            .main { padding: 0; max-width: 100%; }
            .barcode-grid { grid-template-columns: repeat(3, 1fr); gap: 10px; }
        }
    </style>
</head>
<body>
<?php include 'auth_nav.php'; ?>

<div class="hero no-print">
    <h1>🔢 Barcode Generation</h1>
    <p>Auto-generated unique barcodes for every lab test sample.</p>
</div>

<div class="main">
    <!-- Filter -->
    <form method="GET" action="/LIMS/generate_barcode.php" class="no-print">
        <div class="filter-card">
            <div class="filter-group">
                <label>Search Patient</label>
                <input type="text" name="search" class="filter-input" placeholder="Enter patient name..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <button type="submit" class="btn-filter">Search</button>
            <a href="/LIMS/generate_barcode.php" class="btn-reset">Reset</a>
        </div>
    </form>

    <div class="top-actions no-print">
        <h3><?php echo $tests->num_rows; ?> barcodes found</h3>
        <button class="btn-print-all" onclick="window.print()">🖨 Print All Barcodes</button>
    </div>

    <!-- Barcode Grid -->
    <div class="barcode-grid">
        <?php while($r = $tests->fetch_assoc()): ?>
        <div class="barcode-card" id="card-<?php echo $r['TestID']; ?>">
            <div class="patient-name"><?php echo htmlspecialchars($r['PatientName']); ?></div>
            <div class="test-name">🔬 <?php echo ucfirst(htmlspecialchars($r['TestName'])); ?></div>
            <div class="barcode-id"><?php echo $r['BarcodeID']; ?></div>
            <svg id="barcode-<?php echo $r['TestID']; ?>"></svg>
            <div class="date">📅 <?php echo $r['TestDate']; ?></div>
            <div><span class="badge badge-<?php echo $r['Status']; ?>"><?php echo $r['Status']; ?></span></div>
            <button class="btn-print-single no-print" onclick="printSingle(<?php echo $r['TestID']; ?>, '<?php echo $r['BarcodeID']; ?>', '<?php echo htmlspecialchars($r['PatientName'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($r['TestName'], ENT_QUOTES); ?>')">
                🖨 Print This
            </button>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
// Generate barcodes using JsBarcode
document.addEventListener('DOMContentLoaded', function() {
    var svgs = document.querySelectorAll('[id^="barcode-"]');
    svgs.forEach(function(svg) {
        var barcodeId = svg.closest('.barcode-card').querySelector('.barcode-id').textContent.trim();
        JsBarcode(svg, barcodeId, {
            format: "CODE128",
            width: 2,
            height: 60,
            displayValue: true,
            fontSize: 12,
            margin: 10,
            background: "#ffffff",
            lineColor: "#0f0e17"
        });
    });
});

// Print single barcode
function printSingle(testId, barcodeId, patientName, testName) {
    var win = window.open('', '_blank', 'width=400,height=300');
    win.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Barcode - ${barcodeId}</title>
            <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 20px; }
                .lbl { font-size: 13px; font-weight: 700; margin-bottom: 4px; }
                .sub { font-size: 11px; color: #72737d; margin-bottom: 8px; }
                .bid { font-size: 10px; color: #6246ea; font-weight: 600; margin-bottom: 8px; font-family: monospace; }
                .footer { font-size: 9px; color: #aaa; margin-top: 8px; }
            </style>
        </head>
        <body>
            <div class="lbl">${patientName}</div>
            <div class="sub">${testName}</div>
            <div class="bid">${barcodeId}</div>
            <svg id="bc"></svg>
            <div class="footer">Women University Multan — LIMS</div>
            <script>
                window.onload = function() {
                    JsBarcode('#bc', '${barcodeId}', {
                        format: 'CODE128', width: 2, height: 50,
                        displayValue: true, fontSize: 11, margin: 8
                    });
                    setTimeout(function(){ window.print(); window.close(); }, 500);
                };
            <\/script>
        </body>
        </html>
    `);
    win.document.close();
}
</script>
</body>
</html>