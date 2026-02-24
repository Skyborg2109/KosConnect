<?php
include 'config/db.php';
header('Content-Type: text/plain');

echo "Updating links...\n";

// Fix 1: /KosConnect/admin/admin_dashboard_summary.php -> /KosConnect/dashboard/dashboardadmin.php?module=admin_dashboard_summary
$sql1 = "UPDATE notifications SET link = REPLACE(link, '/admin/admin_dashboard_summary.php', '/dashboard/dashboardadmin.php?module=admin_dashboard_summary') WHERE link LIKE '%/admin/admin_dashboard_summary.php%'";
if ($conn->query($sql1)) {
    echo "Fixed dashboard summary links: " . $conn->affected_rows . " rows.\n";
} else {
    echo "Error fixing summary links: " . $conn->error . "\n";
}

// Fix 2: transactions (from process_payment) - The logic I added in process_payment ALREADY uses ?module=... but just in case
// logic was: /KosConnect/dashboard/dashboardadmin.php?module=admin_manage_transactions (Correct)

// Just explicitly fix the one known bad link for safety if there are variations
$sql2 = "UPDATE notifications SET link = '/KosConnect/dashboard/dashboardadmin.php?module=admin_dashboard_summary' WHERE link = '/KosConnect/admin/admin_dashboard_summary.php'";
$conn->query($sql2);

echo "Update complete.\n";
$conn->close();
?>
