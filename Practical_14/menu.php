<?php
// Practical 14 : Dynamic menu - links rendered according to $_SESSION['role']
// Admin sees: Dashboard, Users, Audit Log, Students, Events, Logout
// Staff sees: Dashboard, Students, Events, Logout (no Users / Audit Log)
function render_menu($active = "") {
    $role = $_SESSION["role"] ?? "";
    $items = [
        ["label" => "Dashboard", "admin_href" => "admin_dashboard.php", "staff_href" => "user_dashboard.php", "roles" => ["admin", "staff"]],
        ["label" => "Users",     "admin_href" => "users.php",           "staff_href" => "",                   "roles" => ["admin"]],
        ["label" => "Audit Log", "admin_href" => "audit_log.php",       "staff_href" => "",                   "roles" => ["admin"]],
        ["label" => "Students",  "admin_href" => "../Practical_11/index.php", "staff_href" => "../Practical_11/index.php", "roles" => ["admin", "staff"]],
        ["label" => "Events",    "admin_href" => "../Practical_12/index.php", "staff_href" => "../Practical_12/index.php", "roles" => ["admin", "staff"]],
    ];
    echo '<nav class="menu">';
    foreach ($items as $it) {
        if (!in_array($role, $it["roles"], true)) continue; // <-- RBAC: hide disallowed links
        $href = ($role === "admin") ? $it["admin_href"] : $it["staff_href"];
        $cls = ($it["label"] === $active) ? " class='active'" : "";
        echo "<a$cls href='" . htmlspecialchars($href) . "'>" . htmlspecialchars($it["label"]) . "</a>";
    }
    echo "<span class='menu-user'>" . htmlspecialchars($_SESSION["username"] ?? "")
       . " (" . htmlspecialchars($role) . ")</span>";
    echo "<a class='logout' href='logout.php'>Logout</a>";
    echo "</nav>";
}
?>
