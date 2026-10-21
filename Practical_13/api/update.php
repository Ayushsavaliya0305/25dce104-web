<?php
// Practical 13 : API - Update event (PUT or POST api/update.php, JSON body)
// Request:  {"id":1,"title":"...","description":"...","event_date":"YYYY-MM-DD","venue":"...","status":"upcoming"}
// Response: {"success":true, "message":"..."}
header("Content-Type: application/json");
require_once __DIR__ . "/../db.php";

if (!in_array($_SERVER["REQUEST_METHOD"], ["PUT", "POST", "PATCH"], true)) {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed. Use PUT or POST."]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) { $input = $_POST; } // fallback for form-encoded posts

$id          = (int)($input["id"] ?? 0);
$title       = trim($input["title"] ?? "");
$description = trim($input["description"] ?? "");
$event_date  = trim($input["event_date"] ?? "");
$venue       = trim($input["venue"] ?? "");
$status      = trim($input["status"] ?? "upcoming");

if ($id <= 0) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "Valid event id is required."]);
    exit;
}
if ($title === "" || $event_date === "" || $venue === "") {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "Title, date and venue are required."]);
    exit;
}
if (strlen($title) < 3 || strlen($title) > 150) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "Title must be 3-150 characters."]);
    exit;
}
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $event_date)) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "Invalid event date (use YYYY-MM-DD)."]);
    exit;
}
if (!in_array($status, ["upcoming", "completed", "cancelled"], true)) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "Invalid status."]);
    exit;
}

$stmt = mysqli_prepare($conn, "UPDATE events SET title=?, description=?, event_date=?, venue=?, status=? WHERE id=?");
mysqli_stmt_bind_param($stmt, "sssssi", $title, $description, $event_date, $venue, $status, $id);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $out = ["success" => true, "message" => "Event updated successfully."];
    } else {
        // Affected rows = 0 means id not found OR data identical; verify existence
        $chk = mysqli_prepare($conn, "SELECT id FROM events WHERE id = ?");
        mysqli_stmt_bind_param($chk, "i", $id);
        mysqli_stmt_execute($chk);
        $res = mysqli_stmt_get_result($chk);
        $exists = $res && mysqli_fetch_assoc($res);
        mysqli_stmt_close($chk);
        $out = $exists
            ? ["success" => true, "message" => "No changes (data identical)."]
            : ["success" => false, "message" => "Event not found."];
        if (!$exists) { http_response_code(404); }
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    echo json_encode($out);
} else {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Could not update event."]);
}
?>
