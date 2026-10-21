<?php
// Practical 11 : Student Management - View + Search + Filter (MySQLi Prepared Statements)
require_once "db.php";

$search = isset($_GET['q']) ? trim($_GET['q']) : "";
$course = isset($_GET['course']) ? trim($_GET['course']) : "";
$status = isset($_GET['status']) ? trim($_GET['status']) : "";

$msg  = isset($_GET['msg']) ? $_GET['msg'] : "";
$type = isset($_GET['type']) ? $_GET['type'] : "";

// Course list for filter dropdown (distinct from DB using MySQLi)
$courses = [];
$cRes = mysqli_query($conn, "SELECT DISTINCT course FROM students ORDER BY course ASC");
if ($cRes) {
    while ($r = mysqli_fetch_assoc($cRes)) { $courses[] = $r['course']; }
    mysqli_free_result($cRes);
}
if (empty($courses)) {
    $courses = ["BBA", "BCA", "B.Sc", "B.Tech", "M.Tech", "MCA"];
}

// Build dynamic query with prepared statement
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
$students = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
$count = count($students);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="card">
    <h1>Student Management</h1>
    <div class="top-links">
        <a href="index.php">All Students</a>
        <a href="add.php">+ Add Student</a>
    </div>

    <?php if ($msg !== ""): ?>
        <div class="<?php echo ($type === 'error') ? 'msg-error' : 'msg-success'; ?>">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <form class="filter-bar" method="GET" action="index.php">
        <input type="text" name="q" placeholder="Search name / email / course"
               value="<?php echo htmlspecialchars($search); ?>">
        <select name="course">
            <option value="">All Courses</option>
            <?php foreach ($courses as $c): ?>
                <option value="<?php echo htmlspecialchars($c); ?>"
                    <?php echo ($course === $c) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($c); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">All Status</option>
            <option value="active" <?php echo ($status === "active") ? "selected" : ""; ?>>Active</option>
            <option value="inactive" <?php echo ($status === "inactive") ? "selected" : ""; ?>>Inactive</option>
        </select>
        <button type="submit" class="btn btn-search">Search</button>
        <a href="index.php" class="btn btn-reset">Reset</a>
        <a class="btn btn-export" href="export.php?q=<?php echo urlencode($search); ?>&course=<?php echo urlencode($course); ?>&status=<?php echo urlencode($status); ?>">Export CSV</a>
    </form>

    <div class="count"><?php echo $count; ?> record(s) found.</div>

    <table>
        <tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Phone</th>
            <th>Course</th><th>Status</th><th>Actions</th>
        </tr>
        <?php if ($count > 0): ?>
            <?php foreach ($students as $s): ?>
            <tr>
                <td><?php echo $s['id']; ?></td>
                <td><?php echo htmlspecialchars($s['name']); ?></td>
                <td><?php echo htmlspecialchars($s['email']); ?></td>
                <td><?php echo htmlspecialchars($s['phone']); ?></td>
                <td><?php echo htmlspecialchars($s['course']); ?></td>
                <td><?php echo htmlspecialchars($s['status']); ?></td>
                <td class="actions">
                    <a class="btn btn-edit" href="edit.php?id=<?php echo $s['id']; ?>">Edit</a>
                    <a class="btn btn-delete" href="delete.php?id=<?php echo $s['id']; ?>"
                       onclick="return confirm('Delete this student record?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center;">No records found.</td></tr>
        <?php endif; ?>
    </table>
</div>
</body>
</html>
<?php mysqli_close($conn); ?>
