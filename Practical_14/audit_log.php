<?php
// Practical 14 : Audit Trail - ADMIN ONLY (filterable, latest 100)
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/menu.php";
require_admin();

audit("view_audit", "Audit log viewed");

$fAction = isset($_GET["action"]) ? trim($_GET["action"]) : "";
$fUser = isset($_GET["user"]) ? trim($_GET["user"]) : "";

$sql = "SELECT username, role, action, details, ip, created_at FROM audit_log WHERE 1=1";
$types = "";
$params = [];
if ($fAction !== "") { $sql .= " AND action = ?"; $types .= "s"; $params[] = $fAction; }
if ($fUser !== "") { $sql .= " AND username LIKE ?"; $types .= "s"; $params[] = "%" . $fUser . "%"; }
$sql .= " ORDER BY id DESC LIMIT 100";

$stmt = mysqli_prepare($conn, $sql);
if ($types !== "") { mysqli_stmt_bind_param($stmt, $types, ...$params); }
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$logs = $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
mysqli_stmt_close($stmt);

$actions = [];
$r = mysqli_query($conn, "SELECT DISTINCT action FROM audit_log ORDER BY action ASC");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $actions[] = $row["action"]; } }
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Audit Log - Practical 14</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="card wide">
    <h1>Audit Log</h1>
    <?php render_menu("Audit Log"); ?>
    <p class="muted">Every login, logout, dashboard view, and user change is recorded with user, role, IP and timestamp.</p>

    <form class="filter-bar" method="GET" action="audit_log.php">
        <select name="action">
            <option value="">All Actions</option>
            <?php foreach ($actions as $a): ?>
                <option value="<?php echo htmlspecialchars($a); ?>" <?php echo ($fAction === $a) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($a); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="user" placeholder="Filter by username" value="<?php echo htmlspecialchars($fUser); ?>">
        <button type="submit" class="btn btn-search">Filter</button>
        <a href="audit_log.php" class="btn btn-reset">Reset</a>
    </form>

    <div class="count"><?php echo count($logs); ?> entr(ies) shown (latest 100).</div>

    <table>
        <tr><th>Time</th><th>User</th><th>Role</th><th>Action</th><th>Details</th><th>IP</th></tr>
        <?php if (empty($logs)): ?>
            <tr><td colspan="6" style="text-align:center;">No audit entries match.</td></tr>
        <?php else: ?>
            <?php foreach ($logs as $l): ?>
            <tr>
                <td><?php echo htmlspecialchars($l["created_at"]); ?></td>
                <td><?php echo htmlspecialchars($l["username"]); ?></td>
                <td><?php echo htmlspecialchars($l["role"]); ?></td>
                <td><span class="pill"><?php echo htmlspecialchars($l["action"]); ?></span></td>
                <td><?php echo htmlspecialchars($l["details"] ?? ""); ?></td>
                <td><?php echo htmlspecialchars($l["ip"] ?? ""); ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</div>
</body>
</html>
