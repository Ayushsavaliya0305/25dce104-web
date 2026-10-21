<?php
// Practical 11 : Export filtered students to CSV (MySQLi Prepared Statement)
require_once "db.php";

$search = isset($_GET['q']) ? trim($_GET['q']) : "";
$course = isset($_GET['course']) ? trim($_GET['course']) : "";
$status = isset($_GET['status']) ? trim($_GET['status']) : "";

$sql = "SELECT id, name, email, phone, course, status FROM students WHERE 1=1";
$types = "";
$params = [];

if ($search !== "") {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR course LIKE ? OR phone LIKE ?)";
    $like = "%" . $search . "%";
    $types .= "ssss";
    array_push($params, $like, $like, $like, $like);
}
if ($course !== "") {
    $sql .= " AND course = ?";
    $types .= "s";
    $params[] = $course;
}
if ($status !== "" && ($status === "active" || $status === "inactive")) {
    $sql .= " AND status = ?";
    $types .= "s";
    $params[] = $status;
}
$sql .= " ORDER BY id DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($types !== "") {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

header("Content-Type: text/csv");
header('Content-Disposition: attachment; filename="students.csv"');

$out = fopen("php://output", "w");
fputcsv($out, ["ID", "Name", "Email", "Phone", "Course", "Status"]);
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($out, $row);
}
fclose($out);
mysqli_stmt_close($stmt);
mysqli_close($conn);
exit;
?>
