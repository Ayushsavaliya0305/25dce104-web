<?php
// Practical 13 : API - Delete event (DELETE or POST api/delete.php, JSON body or ?id=)
// Request:  {"id":1}  OR  delete.php?id=1
// Response: {"success":true, "message":"..."}
header("Content-Type: application/json");
require_once __DIR__ . "/../db.php";

if (!in_array($_SERVER["REQUEST_METHOD"], ["DELETE", "POST", "GET"], true)) {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed."]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) { $input = []; }
$id = (int)($input["id"] ?? $_REQUEST["id"] ?? 0);

if ($id <= 0) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "Valid event id is required."]);
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM events WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $out = ["success" => true, "message" => "Event deleted successfully."];
    } else {
        http_response_code(404);
        $out = ["success" => false, "message" => "Event not found."];
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    echo json_encode($out);
} else {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Could not delete event."]);
}
?>
