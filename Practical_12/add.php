<?php
// Practical 12 : Add Event + Poster Upload with type & size validation
require_once "auth.php";
require_once "db.php";
require_once "config.php";
require_admin();

$error = "";
$title = $description = $event_date = $venue = "";
$status = "upcoming";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title       = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $event_date  = trim($_POST["event_date"] ?? "");
    $venue       = trim($_POST["venue"] ?? "");
    $status      = trim($_POST["status"] ?? "upcoming");

    if ($title === "" || $event_date === "" || $venue === "" || $status === "") {
        $error = "Failure: Title, date, venue and status are required.";
    } elseif (strlen($title) < 3 || strlen($title) > 150) {
        $error = "Failure: Title must be 3-150 characters.";
    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $event_date)) {
        $error = "Failure: Invalid event date (use YYYY-MM-DD).";
    } elseif (!in_array($status, ["upcoming", "completed", "cancelled"], true)) {
        $error = "Failure: Invalid status.";
    } elseif (!isset($_FILES["poster"]) || $_FILES["poster"]["error"] === UPLOAD_ERR_NO_FILE) {
        $error = "Failure: Event poster is required (JPG/PNG/GIF/WEBP, max 2 MB).";
    } else {
        // --- File validation happens inside the helper ---
        $uploadError = "";
        $storedName = handlePosterUpload($_FILES["poster"], $uploadError);

        if ($storedName === false || $storedName === null) {
            $error = $uploadError !== "" ? $uploadError : "Failure: Poster upload failed.";
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO events (title, description, event_date, venue, status, poster) VALUES (?, ?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, "ssssss", $title, $description, $event_date, $venue, $status, $storedName);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                mysqli_close($conn);
                header("Location: index.php?msg=" . urlencode("Success: Event added successfully.") . "&type=success");
                exit;
            } else {
                deletePosterFile($storedName); // rollback uploaded file on DB failure
                $error = "Failure: Could not add event. " . mysqli_error($conn);
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Add Event</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="card form-box">
    <h1>Add Event</h1>
    <div class="top-links"><a href="index.php">All Events</a> <a href="logout.php" class="logout">Logout</a></div>

    <?php if ($error !== ""): ?><div class="msg-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <form method="POST" action="add.php" enctype="multipart/form-data" id="eventForm">
        <label>Title *</label>
        <input type="text" name="title" required minlength="3" maxlength="150"
               value="<?php echo htmlspecialchars($title); ?>">

        <label>Description</label>
        <textarea name="description" rows="4"><?php echo htmlspecialchars($description); ?></textarea>

        <label>Event Date *</label>
        <input type="date" name="event_date" required value="<?php echo htmlspecialchars($event_date); ?>">

        <label>Venue *</label>
        <input type="text" name="venue" required maxlength="150"
               value="<?php echo htmlspecialchars($venue); ?>">

        <label>Status</label>
        <select name="status">
            <option value="upcoming" <?php echo ($status === "upcoming") ? "selected" : ""; ?>>upcoming</option>
            <option value="completed" <?php echo ($status === "completed") ? "selected" : ""; ?>>completed</option>
            <option value="cancelled" <?php echo ($status === "cancelled") ? "selected" : ""; ?>>cancelled</option>
        </select>

        <label>Event Poster * (JPG / JPEG / PNG / GIF / WEBP, max 2 MB)</label>
        <input type="file" name="poster" id="poster" required accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
        <div class="muted" id="fileHint">No file chosen.</div>
        <div class="msg-error hidden" id="clientError"></div>

        <div class="form-row">
            <button type="submit" class="btn btn-save">Save Event</button>
            <a href="index.php" class="btn btn-back">Back</a>
        </div>
    </form>
</div>
<script>
// Client-side file validation (server-side in config.php is authoritative)
document.getElementById("poster").addEventListener("change", function () {
    var hint = document.getElementById("fileHint");
    var err  = document.getElementById("clientError");
    err.classList.add("hidden"); err.textContent = "";
    if (!this.files || !this.files[0]) { hint.textContent = "No file chosen."; return; }
    var f = this.files[0];
    var okExt = /\.(jpe?g|png|gif|webp)$/i.test(f.name);
    hint.textContent = f.name + " (" + Math.round(f.size / 1024) + " KB)";
    if (!okExt) {
        err.textContent = "Invalid type: only JPG, PNG, GIF, WEBP allowed.";
        err.classList.remove("hidden"); this.value = ""; hint.textContent = "No file chosen.";
    } else if (f.size > 2 * 1024 * 1024) {
        err.textContent = "File too large: max 2 MB.";
        err.classList.remove("hidden"); this.value = ""; hint.textContent = "No file chosen.";
    }
});
document.getElementById("eventForm").addEventListener("submit", function (e) {
    var input = document.getElementById("poster");
    if (input.files && input.files[0] && input.files[0].size > 2 * 1024 * 1024) {
        e.preventDefault();
        var err = document.getElementById("clientError");
        err.textContent = "File too large: max 2 MB.";
        err.classList.remove("hidden");
    }
});
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
