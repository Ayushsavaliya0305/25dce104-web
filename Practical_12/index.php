<?php
// Practical 12 : View Events (Admin) - MySQLi Prepared Statements
require_once "auth.php";
require_once "db.php";
require_once "config.php";
require_admin();

$search = isset($_GET["q"]) ? trim($_GET["q"]) : "";
$status = isset($_GET["status"]) ? trim($_GET["status"]) : "";

$msg  = isset($_GET["msg"]) ? $_GET["msg"] : "";
$type = isset($_GET["type"]) ? $_GET["type"] : "";

$sql = "SELECT id, title, description, event_date, venue, status, poster, created_at FROM events WHERE 1=1";
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
if ($types !== "") {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$events = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
$count = count($events);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Event Management (Admin)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="card wide">
    <h1>Event Management</h1>
    <div class="top-links">
        <a href="index.php">All Events</a>
        <a href="add.php">+ Add Event</a>
        <span class="admin-tag">Logged in as: <b><?php echo htmlspecialchars($_SESSION["admin_user"]); ?></b></span>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <?php if ($msg !== ""): ?>
        <div class="<?php echo ($type === "error") ? "msg-error" : "msg-success"; ?>">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <form class="filter-bar" method="GET" action="index.php">
        <input type="text" name="q" placeholder="Search title / venue"
               value="<?php echo htmlspecialchars($search); ?>">
        <select name="status">
            <option value="">All Status</option>
            <option value="upcoming" <?php echo ($status === "upcoming") ? "selected" : ""; ?>>upcoming</option>
            <option value="completed" <?php echo ($status === "completed") ? "selected" : ""; ?>>completed</option>
            <option value="cancelled" <?php echo ($status === "cancelled") ? "selected" : ""; ?>>cancelled</option>
        </select>
        <button type="submit" class="btn btn-search">Search</button>
        <a href="index.php" class="btn btn-reset">Reset</a>
    </form>

    <div class="count"><?php echo $count; ?> record(s) found.</div>

    <table>
        <tr>
            <th>ID</th><th>Poster</th><th>Title</th><th>Date</th>
            <th>Venue</th><th>Status</th><th>Actions</th>
        </tr>
        <?php if ($count > 0): ?>
            <?php foreach ($events as $e): ?>
            <tr>
                <td><?php echo (int)$e["id"]; ?></td>
                <td>
                    <?php if (!empty($e["poster"]) && is_file(UPLOAD_DIR . basename($e["poster"]))): ?>
                        <a href="<?php echo htmlspecialchars(UPLOAD_URL . basename($e["poster"])); ?>" target="_blank">
                            <img class="thumb" src="<?php echo htmlspecialchars(UPLOAD_URL . basename($e["poster"])); ?>"
                                 alt="Event poster">
                        </a>
                    <?php else: ?>
                        <span class="muted">No poster</span>
                    <?php endif; ?>
                </td>
                <td>
                    <b><?php echo htmlspecialchars($e["title"]); ?></b><br>
                    <span class="muted"><?php echo htmlspecialchars(mb_strimwidth($e["description"] ?? "", 0, 80, "...")); ?></span>
                </td>
                <td><?php echo htmlspecialchars($e["event_date"]); ?></td>
                <td><?php echo htmlspecialchars($e["venue"]); ?></td>
                <td><?php echo htmlspecialchars($e["status"]); ?></td>
                <td class="actions">
                    <a class="btn btn-edit" href="edit.php?id=<?php echo (int)$e["id"]; ?>">Edit</a>
                    <a class="btn btn-delete" href="delete.php?id=<?php echo (int)$e["id"]; ?>"
                       onclick="return confirm('Delete this event and its poster?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center;">No events found.</td></tr>
        <?php endif; ?>
    </table>
    <p class="muted">Poster rules: JPG / JPEG / PNG / GIF / WEBP only, max 2 MB.</p>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
