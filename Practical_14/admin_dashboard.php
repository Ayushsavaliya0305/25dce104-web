<?php
// Practical 14 : Admin Dashboard - ADMIN ROLE ONLY
// Shows user stats, student count, event count, latest registrations + audit trail.
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/menu.php";
require_once __DIR__ . "/stats.php";
require_admin(); // <-- RBAC: staff gets 403 here

audit("view_dashboard", "Admin dashboard viewed");
$s = dashboard_stats();
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Admin Dashboard - Practical 14</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="card wide">
    <h1>Admin Dashboard</h1>
    <?php render_menu("Dashboard"); ?>

    <div class="stats">
        <div class="stat"><span class="stat-num"><?php echo (int)$s["totalUsers"]; ?></span><span class="stat-label">Total Users</span></div>
        <div class="stat"><span class="stat-num"><?php echo (int)$s["adminCount"]; ?> / <?php echo (int)$s["staffCount"]; ?></span><span class="stat-label">Admins / Staff</span></div>
        <div class="stat"><span class="stat-num"><?php echo dash_num($s["totalStudents"]); ?></span><span class="stat-label">Students<?php echo ($s["activeStudents"] !== null) ? " (" . (int)$s["activeStudents"] . " active)" : ""; ?></span></div>
        <div class="stat"><span class="stat-num"><?php echo dash_num($s["totalEvents"]); ?></span><span class="stat-label">Events<?php echo ($s["upcomingEvents"] !== null) ? " (" . (int)$s["upcomingEvents"] . " upcoming)" : ""; ?></span></div>
        <div class="stat"><span class="stat-num"><?php echo (int)$s["loginsToday"]; ?></span><span class="stat-label">Logins Today</span></div>
    </div>

    <div class="grid2">
        <div>
            <h2>Latest Student Registrations</h2>
            <?php if (!$s["studentsOk"]): ?>
                <p class="muted">student_db not found. Import Practical_11/database.sql to enable.</p>
            <?php elseif (empty($s["latestStudents"])): ?>
                <p class="muted">No students yet.</p>
            <?php else: ?>
            <table>
                <tr><th>Name</th><th>Course</th><th>Status</th></tr>
                <?php foreach ($s["latestStudents"] as $st): ?>
                <tr>
                    <td><?php echo htmlspecialchars($st["name"]); ?><br><span class="muted"><?php echo htmlspecialchars($st["email"]); ?></span></td>
                    <td><?php echo htmlspecialchars($st["course"]); ?></td>
                    <td><?php echo htmlspecialchars($st["status"]); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
        <div>
            <h2>Latest Events</h2>
            <?php if (!$s["eventsOk"]): ?>
                <p class="muted">event_db not found. Import Practical_12/database.sql to enable.</p>
            <?php elseif (empty($s["latestEvents"])): ?>
                <p class="muted">No events yet.</p>
            <?php else: ?>
            <table>
                <tr><th>Title</th><th>Date</th><th>Status</th></tr>
                <?php foreach ($s["latestEvents"] as $ev): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ev["title"]); ?><br><span class="muted"><?php echo htmlspecialchars($ev["venue"]); ?></span></td>
                    <td><?php echo htmlspecialchars($ev["event_date"]); ?></td>
                    <td><?php echo htmlspecialchars($ev["status"]); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <h2>Recent Audit Log <a class="btn btn-small" href="audit_log.php">View all</a></h2>
    <?php if (empty($s["recentAudit"])): ?>
        <p class="muted">No audit entries yet.</p>
    <?php else: ?>
    <table>
        <tr><th>Time</th><th>User</th><th>Action</th><th>Details</th></tr>
        <?php foreach ($s["recentAudit"] as $a): ?>
        <tr>
            <td><?php echo htmlspecialchars($a["created_at"]); ?></td>
            <td><?php echo htmlspecialchars($a["username"]); ?> (<?php echo htmlspecialchars($a["role"]); ?>)</td>
            <td><span class="pill"><?php echo htmlspecialchars($a["action"]); ?></span></td>
            <td><?php echo htmlspecialchars($a["details"] ?? ""); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>
</body>
</html>
