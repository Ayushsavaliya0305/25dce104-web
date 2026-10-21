<?php
// Practical 14 : Staff Dashboard - any logged-in role, LIMITED view.
// Staff sees counts + latest lists, but NO user stats, NO audit log,
// and the menu hides Users / Audit Log links (see menu.php).
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/menu.php";
require_once __DIR__ . "/stats.php";
require_login();

$s = dashboard_stats();
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>My Dashboard - Practical 14</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="card wide">
    <h1>Staff Dashboard</h1>
    <?php render_menu("Dashboard"); ?>
    <p>Welcome, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>! Your role
       (<b><?php echo htmlspecialchars($_SESSION["role"]); ?></b>) limits you to this overview.
       User management and audit logs are admin-only.</p>

    <div class="stats">
        <div class="stat"><span class="stat-num"><?php echo dash_num($s["totalStudents"]); ?></span><span class="stat-label">Students</span></div>
        <div class="stat"><span class="stat-num"><?php echo dash_num($s["totalEvents"]); ?></span><span class="stat-label">Events</span></div>
        <div class="stat"><span class="stat-num"><?php echo dash_num($s["upcomingEvents"]); ?></span><span class="stat-label">Upcoming Events</span></div>
    </div>

    <div class="grid2">
        <div>
            <h2>Latest Students</h2>
            <?php if (!$s["studentsOk"]): ?>
                <p class="muted">student_db not found.</p>
            <?php elseif (empty($s["latestStudents"])): ?>
                <p class="muted">No students yet.</p>
            <?php else: ?>
            <table>
                <tr><th>Name</th><th>Course</th></tr>
                <?php foreach ($s["latestStudents"] as $st): ?>
                <tr><td><?php echo htmlspecialchars($st["name"]); ?></td><td><?php echo htmlspecialchars($st["course"]); ?></td></tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
        <div>
            <h2>Latest Events</h2>
            <?php if (!$s["eventsOk"]): ?>
                <p class="muted">event_db not found.</p>
            <?php elseif (empty($s["latestEvents"])): ?>
                <p class="muted">No events yet.</p>
            <?php else: ?>
            <table>
                <tr><th>Title</th><th>Date</th></tr>
                <?php foreach ($s["latestEvents"] as $ev): ?>
                <tr><td><?php echo htmlspecialchars($ev["title"]); ?></td><td><?php echo htmlspecialchars($ev["event_date"]); ?></td></tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
