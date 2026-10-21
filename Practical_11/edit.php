<?php
// Practical 11 : Edit Student (MySQLi Prepared Statements)
require_once "db.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0 && isset($_POST['id'])) { $id = (int)$_POST['id']; }
if ($id <= 0) {
    header("Location: index.php?msg=" . urlencode("Failure: Invalid student ID.") . "&type=error");
    exit;
}

// Fetch existing record using prepared statement
$stmt = mysqli_prepare($conn, "SELECT id, name, email, phone, course, status FROM students WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$student) {
    mysqli_close($conn);
    header("Location: index.php?msg=" . urlencode("Failure: Student not found.") . "&type=error");
    exit;
}

$error = "";
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
        $u = mysqli_prepare($conn, "UPDATE students SET name=?, email=?, phone=?, course=?, status=? WHERE id=?");
        mysqli_stmt_bind_param($u, "sssssi", $name, $email, $phone, $course, $status, $id);
        if (mysqli_stmt_execute($u)) {
            mysqli_stmt_close($u);
            mysqli_close($conn);
            header("Location: index.php?msg=" . urlencode("Success: Student updated successfully.") . "&type=success");
            exit;
        } else {
            if (mysqli_errno($conn) == 1062) {
                $error = "Failure: Email already exists.";
            } else {
                $error = "Failure: Could not update student. " . mysqli_error($conn);
            }
            mysqli_stmt_close($u);
            $student = ["id"=>$id,"name"=>$name,"email"=>$email,"phone"=>$phone,"course"=>$course,"status"=>$status];
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Edit Student</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="card form-box">
    <h1>Edit Student (ID: <?php echo $student['id']; ?>)</h1>
    <div class="top-links"><a href="index.php">All Students</a> <a href="add.php">+ Add Student</a></div>

    <?php if ($error !== ""): ?><div class="msg-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <form method="POST" action="edit.php?id=<?php echo $student['id']; ?>">
        <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
        <label>Name</label>
        <input type="text" name="name" required value="<?php echo htmlspecialchars($student['name']); ?>">
        <label>Email</label>
        <input type="email" name="email" required value="<?php echo htmlspecialchars($student['email']); ?>">
        <label>Phone (10 digits)</label>
        <input type="text" name="phone" required maxlength="10" value="<?php echo htmlspecialchars($student['phone']); ?>">
        <label>Course</label>
        <select name="course" required>
            <?php foreach (["BBA","BCA","B.Sc","B.Tech","M.Tech","MCA"] as $c): ?>
                <option value="<?php echo $c; ?>" <?php echo ($student['course'] === $c) ? "selected" : ""; ?>><?php echo $c; ?></option>
            <?php endforeach; ?>
        </select>
        <label>Status</label>
        <select name="status">
            <option value="active" <?php echo ($student['status'] === "active") ? "selected" : ""; ?>>active</option>
            <option value="inactive" <?php echo ($student['status'] === "inactive") ? "selected" : ""; ?>>inactive</option>
        </select>
        <div class="form-row">
            <button type="submit" class="btn btn-save">Update</button>
            <a href="index.php" class="btn btn-back">Back</a>
        </div>
    </form>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
