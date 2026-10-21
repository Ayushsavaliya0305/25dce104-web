<?php
// Practical 11 : Add Student (MySQLi Prepared Statement)
require_once "db.php";

$error = "";
$name = $email = $phone = $course = $status = "";
$status = "active";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name   = trim($_POST["name"] ?? "");
    $email  = trim($_POST["email"] ?? "");
    $phone  = trim($_POST["phone"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $status = trim($_POST["status"] ?? "active");

    if ($name === "" || $email === "" || $phone === "" || $course === "") {
        $error = "Failure: All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Failure: Invalid email address.";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $error = "Failure: Phone must be 10 digits.";
    } elseif ($status !== "active" && $status !== "inactive") {
        $error = "Failure: Invalid status.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO students (name, email, phone, course, status) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $phone, $course, $status);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            header("Location: index.php?msg=" . urlencode("Success: Student added successfully.") . "&type=success");
            exit;
        } else {
            if (mysqli_errno($conn) == 1062) {
                $error = "Failure: Email already exists.";
            } else {
                $error = "Failure: Could not add student. " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Add Student</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="card form-box">
    <h1>Add Student</h1>
    <div class="top-links"><a href="index.php">All Students</a> <a href="add.php">+ Add Student</a></div>

    <?php if ($error !== ""): ?><div class="msg-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <form method="POST" action="add.php">
        <label>Name</label>
        <input type="text" name="name" required value="<?php echo htmlspecialchars($name); ?>">

        <label>Email</label>
        <input type="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">

        <label>Phone (10 digits)</label>
        <input type="text" name="phone" required maxlength="10" value="<?php echo htmlspecialchars($phone); ?>">

        <label>Course</label>
        <select name="course" required>
            <option value="">-- Select Course --</option>
            <?php foreach (["BBA","BCA","B.Sc","B.Tech","M.Tech","MCA"] as $c): ?>
                <option value="<?php echo $c; ?>" <?php echo ($course === $c) ? "selected" : ""; ?>><?php echo $c; ?></option>
            <?php endforeach; ?>
        </select>

        <label>Status</label>
        <select name="status">
            <option value="active" <?php echo ($status === "active") ? "selected" : ""; ?>>active</option>
            <option value="inactive" <?php echo ($status === "inactive") ? "selected" : ""; ?>>inactive</option>
        </select>

        <div class="form-row">
            <button type="submit" class="btn btn-save">Save</button>
            <a href="index.php" class="btn btn-back">Back</a>
        </div>
    </form>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
