<?php
// Practical 11 : Delete Student (MySQLi Prepared Statement)
require_once "db.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: index.php?msg=" . urlencode("Failure: Invalid student ID.") . "&type=error");
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM students WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $m = "Success: Student deleted successfully.";
        $t = "success";
    } else {
        $m = "Failure: Student not found.";
        $t = "error";
    }
} else {
    $m = "Failure: Could not delete student. " . mysqli_error($conn);
    $t = "error";
}
mysqli_stmt_close($stmt);
mysqli_close($conn);
header("Location: index.php?msg=" . urlencode($m) . "&type=" . $t);
exit;
?>
