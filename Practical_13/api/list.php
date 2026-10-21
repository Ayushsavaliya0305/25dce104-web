<?php
// Practical 13 : API - List events (GET api/list.php?q=...&status=...)
// Response: {"success":true, "count":N, "data":[...]}
header("Content-Type: application/json");
require_once __DIR__ . "/../db.php";

$search = isset($_GET["q"]) ? trim($_GET["q"]) : "";
$status = isset($_GET["status"]) ? trim($_GET["status"]) : "";

$sql = "SELECT id, title, description, event_date, venue, status, created_at FROM events WHERE 1=1";
$types = "";
$params = [];

if ($search !== "") {
    $sql .= " AND (title LIKE ? OR venue LIKE ? OR description LIKE ?)";
    $like = "%" . $search . "%";
    $types .= "sss";
    array_push($params, $like, $like, $like);
}
if ($status !== "" && in_array($status, ["upcoming", "completed", "cancelled"], true)) {
    $sql .= " AND status = ?";
    $types .= "s";
    $params[] = $status;
}
$sql .= " ORDER BY event_date ASC, id DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Query prepare failed."]);
    exit;
}
if ($types !== "") {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$events = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
mysqli_stmt_close($stmt);
mysqli_close($conn);

echo json_encode(["success" => true, "count" => count($events), "data" => $events]);
?>
