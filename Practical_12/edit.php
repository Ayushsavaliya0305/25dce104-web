<?php
// Practical 12 : Edit Event + optional Poster replacement (type & size validation)
require_once "auth.php";
require_once "db.php";
require_once "config.php";
require_admin();

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
if ($id <= 0 && isset($_POST["id"])) { $id = (int)$_POST["id"]; }
if ($id <= 0) {
    header("Location: index.php?msg=" . urlencode("Failure: Invalid event ID.") . "&type=error");
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id, title, description, event_date, venue, status, poster FROM events WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$event = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$event) {
    mysqli_close($conn);
    header("Location: index.php?msg=" . urlencode("Failure: Event not found.") . "&type=error");
    exit;
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title       = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $event_date  = trim($_POST["event_date"] ?? "");
    $venue       = trim($_POST["venue"] ?? "");
    $status      = trim($_POST["status"] ?? "upcoming");
    $oldPoster   = $event["poster"];

    if ($title === "" || $event_date === "" || $venue === "" || $status === "") {
        $error = "Failure: Title, date, venue and status are required.";
    } elseif (strlen($title) < 3 || strlen($title) > 150) {
        $error = "Failure: Title must be 3-150 characters.";
    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $event_date)) {
        $error = "Failure: Invalid event date (use YYYY-MM-DD).";
    } elseif (!in_array($status, ["upcoming", "completed", "cancelled"], true)) {
        $error = "Failure: Invalid status.";
    } else {
        // Optional new poster: validate only if a file was chosen
        $newPoster = $oldPoster;
        $hasNewFile = isset($_FILES["poster"]) && $_FILES["poster"]["error"] !== UPLOAD_ERR_NO_FILE;

        if ($hasNewFile) {
            $uploadError = "";
            $uploaded = handlePosterUpload($_FILES["poster"], $uploadError);
            if ($uploaded === false) {
                $error = $uploadError;
            } else {
                $newPoster = $uploaded; // non-null here since a file was sent
            }
        }

        if ($error === "") {
            $u = mysqli_prepare(
                $conn,
                "UPDATE events SET title=?, description=?, event_date=?, venue=?, status=?, poster=? WHERE id=?"
            );
            mysqli_stmt_bind_param($u, "ssssssi", $title, $description, $event_date, $venue, $status, $newPoster, $id);
            if (mysqli_stmt_execute($u)) {
                mysqli_stmt_close($u);
                // Delete replaced poster only after DB success
                if ($hasNewFile && $newPoster !== $oldPoster) {
                    deletePosterFile($oldPoster);
                }
                mysqli_close($conn);
                header("Location: index.php?msg=" . urlencode("Success: Event updated successfully.") . "&type=success");
                exit;
            } else {
                // Rollback newly uploaded file on DB failure
                if ($hasNewFile && isset($uploaded) && $uploaded && $uploaded !== $oldPoster) {
                    deletePosterFile($uploaded);
                }
                $error = "Failure: Could not update event. " . mysqli_error($conn);
                mysqli_stmt_close($u);
            }
            $event = ["id"=>$id,"title"=>$title,"description"=>$description,"event_date"=>$event_date,
                      "venue"=>$venue,"status"=>$status,"poster"=>$newPoster];
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Edit Event</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="card form-box">
    <h1>Edit Event (ID: <?php echo (int)$event["id"]; ?>)</h1>
    <div class="top-links"><a href="index.php">All Events</a> <a href="logout.php" class="logout">Logout</a></div>

    <?php if ($error !== ""): ?><div class="msg-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <?php if (!empty($event["poster"])): ?>
        <p>Current poster:</p>
        <?php if (is_file(UPLOAD_DIR . basename($event["poster"]))): ?>
            <img class="preview" src="<?php echo htmlspecialchars(UPLOAD_URL . basename($event["poster"])); ?>" alt="Current poster">
        <?php else: ?>
            <p class="muted"><?php echo htmlspecialchars($event["poster"]); ?> (file missing on disk)</p>
        <?php endif; ?>
    <?php endif; ?>

    <form method="POST" action="edit.php?id=<?php echo (int)$event["id"]; ?>" enctype="multipart/form-data" id="eventForm">
        <input type="hidden" name="id" value="<?php echo (int)$event["id"]; ?>">
        <label>Title *</label>
        <input type="text" name="title" required minlength="3" maxlength="150"
               value="<?php echo htmlspecialchars($event["title"]); ?>">
        <label>Description</label>
        <textarea name="description" rows="4"><?php echo htmlspecialchars($event["description"] ?? ""); ?></textarea>
        <label>Event Date *</label>
        <input type="date" name="event_date" required value="<?php echo htmlspecialchars($event["event_date"]); ?>">
        <label>Venue *</label>
        <input type="text" name="venue" required maxlength="150" value="<?php echo htmlspecialchars($event["venue"]); ?>">
        <label>Status</label>
        <select name="status">
            <option value="upcoming" <?php echo ($event["status"] === "upcoming") ? "selected" : ""; ?>>upcoming</option>
            <option value="completed" <?php echo ($event["status"] === "completed") ? "selected" : ""; ?>>completed</option>
            <option value="cancelled" <?php echo ($event["status"] === "cancelled") ? "selected" : ""; ?>>cancelled</option>
        </select>
        <label>Replace Poster (optional &mdash; JPG / PNG / GIF / WEBP, max 2 MB)</label>
        <input type="file" name="poster" id="poster" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
        <div class="muted">Leave empty to keep the current poster.</div>
        <div class="msg-error hidden" id="clientError"></div>
        <div class="form-row">
            <button type="submit" class="btn btn-save">Update</button>
            <a href="index.php" class="btn btn-back">Back</a>
        </div>
    </form>
</div>
<script>
// Client-side file validation (server-side in config.php is authoritative)
document.getElementById("poster").addEventListener("change", function () {
    var err = document.getElementById("clientError");
    err.classList.add("hidden"); err.textContent = "";
    if (!this.files || !this.files[0]) return;
    var f = this.files[0];
    if (!/\.(jpe?g|png|gif|webp)$/i.test(f.name)) {
        err.textContent = "Invalid type: only JPG, PNG, GIF, WEBP allowed.";
        err.classList.remove("hidden"); this.value = "";
    } else if (f.size > 2 * 1024 * 1024) {
        err.textContent = "File too large: max 2 MB.";
        err.classList.remove("hidden"); this.value = "";
    }
});
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
