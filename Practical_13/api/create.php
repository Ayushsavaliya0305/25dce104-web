<?php
// Practical 13 : API - Add event (POST api/create.php, JSON body)
// Request:  {"title":"...","description":"...","event_date":"YYYY-MM-DD","venue":"...","status":"upcoming"}
// Response: {"success":true, "message":"...", "id":NEW_ID}
header("Content-Type: application/json");
require_once __DIR__ . "/../db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed. Use POST."]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) { $input = $_POST; } // fallback for form-encoded posts

$title       = trim($input["title"] ?? "");
$description = trim($input["description"] ?? "");
$event_date  = trim($input["event_date"] ?? "");
$venue       = trim($input["venue"] ?? "");
$status      = trim($input["status"] ?? "upcoming");

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

$stmt = mysqli_prepare($conn, "INSERT INTO events (title, description, event_date, venue, status) VALUES (?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "sssss", $title, $description, $event_date, $venue, $status);

if (mysqli_stmt_execute($stmt)) {
    $newId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    http_response_code(201);
    echo json_encode(["success" => true, "message" => "Event added successfully.", "id" => $newId]);
} else {
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Could not add event."]);
}
?>
