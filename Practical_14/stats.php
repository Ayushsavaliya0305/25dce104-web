<?php
// Practical 14 : Shared statistics helpers (cross-DB reads, fail-safe).
// Students live in student_db (P11), events in event_db (P12).
// Every helper returns a safe default if that DB/table is missing.

/** Single scalar value or $default on any failure. */
function stat_value($sql, $default = 0) {
    global $conn;
    try {
        $res = mysqli_query($conn, $sql);
        if (!$res) return $default;
        $row = mysqli_fetch_row($res);
        return $row ? $row[0] : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

/** Row list or [] on any failure. $ok is set false when the source is missing. */
function stat_rows($sql, &$ok = null) {
    global $conn;
    $ok = true;
    try {
        $res = mysqli_query($conn, $sql);
        if (!$res) { $ok = false; return []; }
        return mysqli_fetch_all($res, MYSQLI_ASSOC);
    } catch (Throwable $e) {
        $ok = false;
        return [];
    }
}

function dashboard_stats() {
    // Users (local dashboard_db)
    $totalUsers = (int)stat_value("SELECT COUNT(*) FROM users");
    $adminCount = (int)stat_value("SELECT COUNT(*) FROM users WHERE role='admin'");
    $staffCount = (int)stat_value("SELECT COUNT(*) FROM users WHERE role='staff'");
    $loginsToday = (int)stat_value("SELECT COUNT(*) FROM audit_log WHERE action='login_success' AND DATE(created_at)=CURDATE()");

    // Students (student_db from Practical 11)
    $totalStudents  = stat_value("SELECT COUNT(*) FROM student_db.students", null);
    $activeStudents = stat_value("SELECT COUNT(*) FROM student_db.students WHERE status='active'", null);
    $latestStudents = stat_rows("SELECT id, name, email, course, status, created_at FROM student_db.students ORDER BY created_at DESC, id DESC LIMIT 5", $studentsOk);

    // Events (event_db from Practical 12)
    $totalEvents     = stat_value("SELECT COUNT(*) FROM event_db.events", null);
    $upcomingEvents  = stat_value("SELECT COUNT(*) FROM event_db.events WHERE status='upcoming'", null);
    $latestEvents    = stat_rows("SELECT id, title, event_date, venue, status FROM event_db.events ORDER BY created_at DESC, id DESC LIMIT 5", $eventsOk);

    // Audit (local)
    $recentAudit = stat_rows("SELECT username, role, action, details, ip, created_at FROM audit_log ORDER BY id DESC LIMIT 10");

    return compact(
        "totalUsers", "adminCount", "staffCount", "loginsToday",
        "totalStudents", "activeStudents", "latestStudents", "studentsOk",
        "totalEvents", "upcomingEvents", "latestEvents", "eventsOk",
        "recentAudit"
    );
}

/** Pretty "—" for missing cross-DB sources. */
function dash_num($v) {
    return ($v === null) ? "—" : (int)$v;
}
?>
