<?php
$role = currentRole();
$name = currentUser();
$dash = dashboardLink();
$roleLabels = ['admin'=>'⚙️ Admin','technician'=>'🧪 Technician','manager'=>'📊 Manager','doctor'=>'👨‍⚕️ Doctor'];
$roleColors = ['admin'=>'#7c3aed','technician'=>'#0891b2','manager'=>'#059669','doctor'=>'#dc2626'];
$badge = $roleLabels[$role] ?? $role;
$color = $roleColors[$role] ?? '#6246ea';
?>
<nav style="background:#fffffe;border-bottom:1px solid #e8e7f0;padding:0 2rem;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:1000;font-family:'DM Sans',sans-serif;">

    <!-- Logo -->
    <a href="/LIMS/index.php" style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.2rem;color:#6246ea;text-decoration:none;flex-shrink:0;">
        <span style="background:#6246ea;color:#fff;padding:1px 7px;border-radius:5px;margin-right:3px;">L</span>IMS
    </a>

    <!-- Nav Links -->
    <div style="display:flex;align-items:center;gap:2px;">

        <?php if ($role === 'admin'): ?>
            <a href="/LIMS/dashboard_admin.php" style="color:#72737d;text-decoration:none;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;white-space:nowrap;">Dashboard</a>
            <a href="/LIMS/manage_users.php" style="color:#72737d;text-decoration:none;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;white-space:nowrap;">Users</a>
            <a href="/LIMS/view_patients.php" style="color:#72737d;text-decoration:none;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;white-space:nowrap;">Patients</a>
            <a href="/LIMS/view_tests.php" style="color:#72737d;text-decoration:none;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;white-space:nowrap;">Tests</a>

            <!-- More dropdown -->
            <div style="position:relative;" id="moreDropdown">
                <button onclick="toggleDropdown()" style="color:#72737d;background:none;border:1px solid #e8e7f0;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;cursor:pointer;white-space:nowrap;font-family:'DM Sans',sans-serif;">More ▾</button>
                <div id="dropdownMenu" style="display:none;position:absolute;top:calc(100% + 8px);left:0;background:#fff;border:1px solid #e8e7f0;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.1);min-width:180px;z-index:9999;overflow:hidden;">
                    <a href="/LIMS/reports/patient_reports.php" style="display:block;padding:10px 16px;color:#0f0e17;text-decoration:none;font-size:0.84rem;font-weight:500;border-bottom:1px solid #f0eff6;">📥 Reports</a>
                    <a href="/LIMS/view_audit_log.php" style="display:block;padding:10px 16px;color:#0f0e17;text-decoration:none;font-size:0.84rem;font-weight:500;border-bottom:1px solid #f0eff6;">📋 Audit Log</a>
                    <a href="/LIMS/track_samples.php" style="display:block;padding:10px 16px;color:#0f0e17;text-decoration:none;font-size:0.84rem;font-weight:500;border-bottom:1px solid #f0eff6;">🔬 Tracking</a>
                    <a href="/LIMS/generate_barcode.php" style="display:block;padding:10px 16px;color:#0f0e17;text-decoration:none;font-size:0.84rem;font-weight:500;border-bottom:1px solid #f0eff6;">🔢 Barcodes</a>
                    <a href="/LIMS/manage_appointments.php" style="display:block;padding:10px 16px;color:#0f0e17;text-decoration:none;font-size:0.84rem;font-weight:500;border-bottom:1px solid #f0eff6;">📅 Appointments</a>
                    <a href="/LIMS/manage_billing.php" style="display:block;padding:10px 16px;color:#0f0e17;text-decoration:none;font-size:0.84rem;font-weight:500;border-bottom:1px solid #f0eff6;">💰 Billing</a>
                    <a href="/LIMS/manage_branches.php" style="display:block;padding:10px 16px;color:#0f0e17;text-decoration:none;font-size:0.84rem;font-weight:500;">🏢 Branches</a>
                </div>
            </div>

        <?php elseif ($role === 'technician'): ?>
            <a href="/LIMS/dashboard_technician.php" style="color:#72737d;text-decoration:none;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;white-space:nowrap;">Dashboard</a>
            <a href="/LIMS/add_patient.php" style="color:#72737d;text-decoration:none;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;white-space:nowrap;">Add Patient</a>
            <a href="/LIMS/add_test.php" style="color:#72737d;text-decoration:none;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;white-space:nowrap;">Add Test</a>
            <a href="/LIMS/view_patients.php" style="color:#72737d;text-decoration:none;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;white-space:nowrap;">Patients</a>
            <div style="position:relative;" id="moreDropdown">
                <button onclick="toggleDropdown()" style="color:#72737d;background:none;border:1px solid #e8e7f0;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;cursor:pointer;white-space:nowrap;font-family:'DM Sans',sans-serif;">More ▾</button>
                <div id="dropdownMenu" style="display:none;position:absolute;top:calc(100% + 8px);left:0;background:#fff;border:1px solid #e8e7f0;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.1);min-width:180px;z-index:9999;overflow:hidden;">
                    <a href="/LIMS/track_samples.php" style="display:block;padding:10px 16px;color:#0f0e17;text-decoration:none;font-size:0.84rem;font-weight:500;border-bottom:1px solid #f0eff6;">🔬 Tracking</a>
                    <a href="/LIMS/generate_barcode.php" style="display:block;padding:10px 16px;color:#0f0e17;text-decoration:none;font-size:0.84rem;font-weight:500;border-bottom:1px solid #f0eff6;">🔢 Barcodes</a>
                    <a href="/LIMS/manage_appointments.php" style="display:block;padding:10px 16px;color:#0f0e17;text-decoration:none;font-size:0.84rem;font-weight:500;border-bottom:1px solid #f0eff6;">📅 Appointments</a>
                    <a href="/LIMS/manage_billing.php" style="display:block;padding:10px 16px;color:#0f0e17;text-decoration:none;font-size:0.84rem;font-weight:500;">💰 Billing</a>
                </div>
            </div>

        <?php elseif ($role === 'manager'): ?>
            <a href="/LIMS/dashboard_manager.php" style="color:#72737d;text-decoration:none;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;white-space:nowrap;">Dashboard</a>
            <a href="/LIMS/view_patients.php" style="color:#72737d;text-decoration:none;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;white-space:nowrap;">Patients</a>
            <a href="/LIMS/view_tests.php" style="color:#72737d;text-decoration:none;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;white-space:nowrap;">Tests</a>
            <a href="/LIMS/track_samples.php" style="color:#72737d;text-decoration:none;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;white-space:nowrap;">Tracking</a>
            <a href="/LIMS/reports/patient_reports.php" style="color:#72737d;text-decoration:none;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;white-space:nowrap;">Reports</a>
            <a href="/LIMS/manage_billing.php" style="color:#72737d;text-decoration:none;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;white-space:nowrap;">Billing</a>

        <?php elseif ($role === 'doctor'): ?>
            <a href="/LIMS/dashboard_doctor.php" style="color:#72737d;text-decoration:none;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;white-space:nowrap;">Dashboard</a>
            <a href="/LIMS/view_patients.php" style="color:#72737d;text-decoration:none;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;white-space:nowrap;">Patients</a>
            <a href="/LIMS/view_tests.php" style="color:#72737d;text-decoration:none;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;white-space:nowrap;">Test Results</a>
            <a href="/LIMS/track_samples.php" style="color:#72737d;text-decoration:none;font-size:0.85rem;font-weight:500;padding:6px 12px;border-radius:8px;white-space:nowrap;">Tracking</a>
        <?php endif; ?>
    </div>

    <!-- Right side -->
    <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
        <span style="background:<?php echo $color; ?>22;color:<?php echo $color; ?>;font-size:0.75rem;font-weight:700;padding:4px 12px;border-radius:50px;border:1px solid <?php echo $color; ?>44;white-space:nowrap;"><?php echo $badge; ?></span>
        <span style="font-size:0.84rem;color:#0f0e17;font-weight:500;white-space:nowrap;"><?php echo htmlspecialchars($name); ?></span>
        <a href="/LIMS/change_password.php" style="background:#f0f4f8;color:#72737d;border:1px solid #e8e7f0;border-radius:50px;padding:6px 14px;font-size:0.82rem;font-weight:600;text-decoration:none;white-space:nowrap;">🔑 Password</a>
        <a href="/LIMS/auth_logout.php" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:50px;padding:6px 16px;font-size:0.82rem;font-weight:600;text-decoration:none;white-space:nowrap;">Logout</a>
    </div>
</nav>

<script>
function toggleDropdown() {
    var menu = document.getElementById('dropdownMenu');
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}
// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    var dropdown = document.getElementById('moreDropdown');
    if (dropdown && !dropdown.contains(e.target)) {
        document.getElementById('dropdownMenu').style.display = 'none';
    }
});
</script>