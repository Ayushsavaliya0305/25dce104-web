<?php
// Practical 12 : Delete Event + its poster file (MySQLi Prepared Statement)
require_once "auth.php";
require_once "db.php";
require_once "config.php";
require_admin();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
if ($id <= 0) {
    header("Location: index.php?msg=" . urlencode("Failure: Invalid event ID.") . "&type=error");
    exit;
}

// Fetch poster name first so the file can be removed from disk
$stmt = mysqli_prepare($conn, "SELECT poster FROM events WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$row) {
    mysqli_close($conn);
    header("Location: index.php?msg=" . urlencode("Failure: Event not found.") . "&type=error");
    exit;
}

$del = mysqli_prepare($conn, "DELETE FROM events WHERE id = ?");
mysqli_stmt_bind_param($del, "i", $id);

if (mysqli_stmt_execute($del)) {
    if (mysqli_stmt_affected_rows($del) > 0) {
        deletePosterFile($row["poster"]); // remove poster from uploads/
        $m = "Success: Event deleted successfully.";
        $t = "success";
    } else {
        $m = "Failure: Event not found.";
        $t = "error";
    }
} else {
    $m = "Failure: Could not delete event. " . mysqli_error($conn);
    $t = "error";
}
mysqli_stmt_close($del);
mysqli_close($conn);
header("Location: index.php?msg=" . urlencode($m) . "&type=" . $t);
exit;
?>
